<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        

        // Consumir datos de la API externa
        $response = Http::withoutVerifying()->get('https://moriahmkt.com/iotapp/test/');

        if ($response->failed()) {
            Log::error('Error al consumir la API externa');
            return response()->json(['error' => 'Error al consumir la API externa'], 500);
        }

        $data = $response->json();

        // 1. Almacenar mediciones globales de sensores
        if (isset($data['sensores']) && is_array($data['sensores'])) {
            foreach ($data['sensores'] as $nombreSensor => $valor) {
                $sensor = Sensor::where('name', ucfirst($nombreSensor))->first();
                if ($sensor) {
                    MedicionGeneral::create([
                        'sensor_id' => $sensor->id,
                        'value'     => $valor,
                        'date'      => Carbon::now(),
                    ]);
                } else {
                    Log::warning("Sensor '{$nombreSensor}' no encontrado para mediciones generales.");
                }
            }
        } else {
            Log::warning("No se encontraron datos de sensores en la respuesta.");
        }

        // 2. Almacenar mediciones de parcelas y actualizar coordenadas
        if (isset($data['parcelas']) && is_array($data['parcelas'])) {
            foreach ($data['parcelas'] as $parcelaData) {
                $parcela = Parcela::find($parcelaData['id']);
                if (!$parcela) {
                    Log::warning("Parcela con ID {$parcelaData['id']} no encontrada.");
                    continue;
                }

                // ✅ Actualizar latitud y longitud si vienen datos nuevos
                if (isset($parcelaData['latitud'], $parcelaData['longitud'])) {
                    $nuevaLatitud = (float) $parcelaData['latitud'];
                    $nuevaLongitud = (float) $parcelaData['longitud'];

                    // Solo actualiza si la latitud o longitud cambiaron
                    if ($parcela->latitude != $nuevaLatitud || $parcela->longitude != $nuevaLongitud) {
                        Log::info("Actualizando ubicación de Parcela ID {$parcela->id}: Nueva Latitud {$nuevaLatitud}, Nueva Longitud {$nuevaLongitud}");
                        $parcela->update([
                            'latitude'  => $nuevaLatitud,
                            'longitude' => $nuevaLongitud,
                        ]);
                    }
                }

                // ✅ Almacenar mediciones de sensores de la parcela
                if (isset($parcelaData['sensor']) && is_array($parcelaData['sensor'])) {
                    foreach ($parcelaData['sensor'] as $nombreSensor => $valor) {
                        $sensor = Sensor::where('name', ucfirst($nombreSensor))->first();
                        if ($sensor) {
                            MedicionParcela::create([
                                'parcela_id' => $parcela->id,
                                'sensor_id'  => $sensor->id,
                                'value'      => $valor,
                                'date'       => Carbon::now(),
                            ]);
                        } else {
                            Log::warning("Sensor '{$nombreSensor}' no encontrado para la parcela ID {$parcela->id}.");
                        }
                    }
                } else {
                    Log::warning("No se encontraron datos de sensores para la parcela ID {$parcelaData['id']}.");
                }
            }
        } else {
            Log::warning("No se encontraron datos de parcelas en la respuesta.");
        }

        return response()->json(['message' => 'Datos consumidos y almacenados correctamente'], 200);
    }
}
