<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Sensor;
use App\Models\MedicionParcela;
use App\Models\MedicionGeneral;
use Illuminate\Support\Facades\DB;

class MedicionActualController extends Controller
{
    // Método para obtener los últimos datos de cada sensor para cada parcela activa
    public function datosActuales()
    {
        try {
            // Obtener todas las parcelas activas y cargar todas sus mediciones ordenadas por fecha (descendente)
            $parcelas = Parcela::where('status', 'active')
                ->with(['medicionesParcela' => function($query) {
                    $query->orderBy('date', 'desc');
                }, 'medicionesParcela.sensor'])
                ->get();

            // Inicializamos los valores globales de los sensores
            $sensoresGlobal = [
                'humedad' => null,
                'temperatura' => null,
                'lluvia' => null,
                'sol' => null,
            ];

            $parcelasData = [];

            foreach ($parcelas as $parcela) {
                // Agrupar las mediciones de la parcela por sensor (usando el nombre del sensor en minúsculas)
                $medicionesAgrupadas = $parcela->medicionesParcela->groupBy(function ($item) {
                    return strtolower($item->sensor->name);
                });
                
                // Inicializamos la estructura de sensores para la parcela
                $sensorData = [
                    'humedad' => null,
                    'temperatura' => null,
                    'lluvia' => null,
                    'sol' => null,
                ];

                // Para cada tipo de sensor, tomar la primera medición (la más reciente, gracias al orderBy)
                foreach ($medicionesAgrupadas as $sensorType => $mediciones) {
                    $sensorData[$sensorType] = $mediciones->first()->value;
                }

                // Agregar los datos de la parcela a la respuesta
                $parcelasData[] = [
                    'id'            => $parcela->id,
                    'nombre'        => $parcela->name,
                    'ubicacion'     => $parcela->location,
                    'responsable'   => $parcela->responsible,
                    'tipo_cultivo'  => $parcela->crop_type,
                    'ultimo_riego'  => $parcela->last_watering,
                    'latitud'       => $parcela->latitude,
                    'longitud'      => $parcela->longitude,
                    'sensor'        => $sensorData,
                ];

                // Actualizar los valores globales (se asigna el valor de la primera parcela que tenga dato)
                foreach ($sensorData as $key => $value) {
                    if ($value !== null && $sensoresGlobal[$key] === null) {
                        $sensoresGlobal[$key] = $value;
                    }
                }
            }

            // Obtener las últimas mediciones generales de cada sensor
            $ultimasMedicionesGenerales = MedicionGeneral::select('sensor_id', 'value', 'date')
                ->whereIn('id', function ($query) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('mediciones_generales')
                          ->groupBy('sensor_id');
                })
                ->get();

            // Reestructurar las mediciones generales en un formato similar al de sensores
            $sensoresGenerales = [];
            foreach ($ultimasMedicionesGenerales as $medicion) {
                $sensorType = strtolower($medicion->sensor->name);
                $sensoresGenerales[$sensorType] = $medicion->value;
            }

            // Devolver la respuesta combinada
            return response()->json([
                'sensores' => array_merge($sensoresGlobal, $sensoresGenerales),
                'parcelas' => $parcelasData,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las mediciones'], 500);
        }
    }
}
