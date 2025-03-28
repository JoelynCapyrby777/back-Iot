<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\Parcela;
use App\Models\MedicionGeneral;
use App\Models\MedicionParcela;

class DataConsumerController extends Controller
{
    /**
     * Consume la API externa y almacena las mediciones globales y por parcela.
     */
    public function consumirYAlmacenarDatos()
    {
        $lock = Cache::lock('data_consumer_lock', 120);
        if (!$lock->get()) {
            Log::warning('Intento de ejecución concurrente de consumirYAlmacenarDatos');
            return response()->json(['error' => 'El proceso ya está en ejecución'], 409);
        }
    
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $lock->release();
            Log::error("Error de conexión a BD: " . $e->getMessage());
            return response()->json(['error' => 'Error de conexión a la base de datos'], 500);
        }
    
        $transactionStarted = false;
        try {
            DB::beginTransaction();
            $transactionStarted = true;
    
            $now = now();
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->retry(3, 1000)
                ->get('https://moriahmkt.com/iotapp/test/');
    
            if ($response->failed()) {
                throw new \Exception('Error al consumir la API: ' . $response->status());
            }
    
            $data = $response->json();
            if (!is_array($data)) {
                throw new \Exception('Respuesta de API no válida');
            }

            // Obtener IDs de parcelas en la API
            $parcelasApiIds = collect($data['parcelas'] ?? [])->pluck('id')->toArray();
            
            // Activar parcelas que aparecen en la API pero están inactivas
            Parcela::whereIn('id', $parcelasApiIds)
                   ->where('status', 'inactive')
                   ->update(['status' => 'active', 'updated_at' => $now]);

            // Inactivar parcelas que no aparecen en la API
            $this->procesarParcelasDesaparecidas($parcelasApiIds, $now);
            
            $sensors = $this->cargarSensores($data);
            $this->procesarSensoresGlobales($data['sensores'] ?? [], $sensors, $now);
            $this->procesarParcelas($data['parcelas'] ?? [], $sensors, $now);
    
            DB::commit();
            Log::info('Datos consumidos y almacenados correctamente');
            return response()->json(['message' => 'Datos procesados correctamente'], 200);
    
        } catch (\Exception $e) {
            if ($transactionStarted) {
                DB::rollBack();
            }
            Log::error("Error en consumirYAlmacenarDatos: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * Procesa las parcelas que ya no aparecen en la API
     */

     protected function procesarParcelasDesaparecidas(array $parcelasApiIds, Carbon $now)
     {
         $parcelasParaInactivar = Parcela::whereNotIn('id', $parcelasApiIds)
                                       ->where('status', 'active')
                                       ->get();
 
         if (!Schema::hasTable('parcelas_backup')) {
             Schema::create('parcelas_backup', function (Blueprint $table) {
                 $table->id();
                 $table->integer('original_id');
                 $table->string('name');
                 $table->string('location');
                 $table->string('responsible');
                 $table->string('crop_type');
                 $table->dateTime('last_watering');
                 $table->decimal('latitude', 10, 8)->nullable();
                 $table->decimal('longitude', 10, 8)->nullable();
                 $table->foreignId('user_id')->nullable();
                 $table->enum('status', ['active', 'inactive']);
                 $table->dateTime('backup_date');
                 $table->text('reason');
                 $table->timestamps();
             });
         }
 
         foreach ($parcelasParaInactivar as $parcela) {
             DB::table('parcelas_backup')->insert([
                 'original_id' => $parcela->id,
                 'name' => $parcela->name,
                 'location' => $parcela->location,
                 'responsible' => $parcela->responsible,
                 'crop_type' => $parcela->crop_type,
                 'last_watering' => $parcela->last_watering,
                 'latitude' => $parcela->latitude,
                 'longitude' => $parcela->longitude,
                 'user_id' => $parcela->user_id,
                 'status' => $parcela->status,
                 'backup_date' => $now,
                 'reason' => 'Parcela no presente en la API',
                 'created_at' => $now,
                 'updated_at' => $now
             ]);
 
             // Actualizar el estado a inactivo
             $parcela->update([
                 'status' => 'inactive',
                 'updated_at' => $now
             ]);
         }
 
         Log::info('Parcelas inactivadas: ' . $parcelasParaInactivar->count());
     }


    /**
     * Carga y devuelve todos los sensores necesarios
     */
    protected function cargarSensores(array $data): \Illuminate\Support\Collection
    {
        $sensorNames = collect($data['sensores'] ?? [])
            ->merge(collect($data['parcelas'] ?? [])->pluck('sensor')->collapse())
            ->keys()
            ->map(fn($name) => strtolower($name))
            ->unique();

        $sensors = Sensor::whereIn(DB::raw('LOWER(name)'), $sensorNames)
                       ->get()
                       ->keyBy(fn($s) => strtolower($s->name));

        if ($sensors->isEmpty()) {
            Log::warning('No se encontraron sensores en la base de datos');
        }

        return $sensors;
    }

    /**
     * Procesa los sensores globales
     */
    protected function procesarSensoresGlobales(array $sensoresData, \Illuminate\Support\Collection $sensors, Carbon $now)
    {
        foreach ($sensoresData as $nombreSensor => $valor) {
            $sensorKey = strtolower($nombreSensor);
            if (!$sensors->has($sensorKey)) {
                Log::warning("Sensor global no encontrado: {$nombreSensor}");
                continue;
            }

            $valor = $this->normalizarValorSensor($valor);
            if ($valor === null) {
                Log::warning("Valor inválido para sensor global {$nombreSensor}: {$valor}");
                continue;
            }

            MedicionGeneral::create([
                'sensor_id' => $sensors[$sensorKey]->id,
                'value' => $valor,
                'date' => $now,
            ]);
        }
    }

    /**
     * Procesa las parcelas y sus sensores
     */
    protected function procesarParcelas(array $parcelasData, \Illuminate\Support\Collection $sensors, Carbon $now)
    {
        foreach ($parcelasData as $parcelaData) {
            $parcela = Parcela::find($parcelaData['id']);
            if (!$parcela) {
                Log::warning("Parcela no encontrada: " . ($parcelaData['id'] ?? 'null'));
                continue;
            }

            $this->actualizarDatosParcela($parcela, $parcelaData, $now);
            $this->procesarSensoresParcela($parcela, $parcelaData['sensor'] ?? [], $sensors, $now);
        }
    }

    /**
     * Actualiza los datos básicos de una parcela
     */
    protected function actualizarDatosParcela(Parcela $parcela, array $parcelaData, Carbon $now)
    {
        $updates = [
            'status' => 'active', // Aseguramos que la parcela esté activa
            'updated_at' => $now
        ];

        if (isset($parcelaData['latitud'], $parcelaData['longitud'])) {
            $lat = floatval($parcelaData['latitud']);
            $lng = floatval($parcelaData['longitud']);
            
            if ($this->validarCoordenadas($lat, $lng)) {
                $updates['latitude'] = $lat;
                $updates['longitude'] = $lng;
            }
        }

        $campos = [
            'nombre' => 'name',
            'ubicacion' => 'location',
            'responsable' => 'responsible',
            'tipo_cultivo' => 'crop_type',
            'ultimo_riego' => 'last_watering'
        ];

        foreach ($campos as $apiKey => $dbField) {
            if (isset($parcelaData[$apiKey])) {
                $updates[$dbField] = $parcelaData[$apiKey];
            }
        }

        $parcela->update($updates);
    }

    /**
     * Procesa los sensores de una parcela específica
     */
    protected function procesarSensoresParcela(Parcela $parcela, array $sensoresData, \Illuminate\Support\Collection $sensors, Carbon $now)
    {
        foreach ($sensoresData as $nombreSensor => $valor) {
            $sensorKey = strtolower($nombreSensor);
            if (!$sensors->has($sensorKey)) {
                Log::warning("Sensor no encontrado para parcela {$parcela->id}: {$nombreSensor}");
                continue;
            }

            $valor = $this->normalizarValorSensor($valor);
            if ($valor === null) {
                Log::warning("Valor inválido para sensor {$nombreSensor} en parcela {$parcela->id}: {$valor}");
                continue;
            }

            MedicionParcela::create([
                'parcela_id' => $parcela->id,
                'sensor_id' => $sensors[$sensorKey]->id,
                'value' => $valor,
                'date' => $now,
            ]);
        }
    }
    /**
     * Normaliza y valida el valor de un sensor
     */
    protected function normalizarValorSensor($valor): ?float
    {
        $valor = is_numeric($valor) ? floatval($valor) : null;
        return ($valor !== null && is_finite($valor)) ? $valor : null;
    }
     /* Valida que las coordenadas sean correctas
     */
    protected function validarCoordenadas(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }  
}