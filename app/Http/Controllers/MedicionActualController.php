<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Sensor;

class MedicionActualController extends Controller
{
    // Método para obtener los últimos datos de cada sensor (Humedad, Temperatura, Lluvia, Sol)
    public function datosActuales()
    {
        // Obtener las últimas mediciones de cada tipo de sensor para cada parcela
        $parcelas = Parcela::with(['mediciones' => function($query) {
            // Obtener las últimas mediciones de cada tipo de sensor (por separado)
            $query->whereIn('sensor_id', Sensor::pluck('id'))->latest('date');
        }])->get();

        // Estructura para los sensores
        $sensores = [
            'humedad' => null,
            'temperatura' => null,
            'lluvia' => null,
            'sol' => null,
        ];

        $parcelasData = [];

        // Recorrer las parcelas para armar la respuesta
        foreach ($parcelas as $parcela) {
            $sensorData = [
                'humedad' => null,
                'temperatura' => null,
                'lluvia' => null,
                'sol' => null,
            ];

            // Procesar las mediciones de cada sensor
            foreach ($parcela->mediciones as $medicion) {
                // Asignar los valores según el tipo de sensor
                switch ($medicion->sensor->name) {
                    case 'Humedad':
                        $sensorData['humedad'] = $medicion->value;
                        break;
                    case 'Temperatura':
                        $sensorData['temperatura'] = $medicion->value;
                        break;
                    case 'Lluvia':
                        $sensorData['lluvia'] = $medicion->value;
                        break;
                    case 'Sol':
                        $sensorData['sol'] = $medicion->value;
                        break;
                }
            }

            // Añadir la parcela y sus mediciones de sensores
            $parcelasData[] = [
                'id' => $parcela->id,
                'nombre' => $parcela->name,
                'ubicacion' => $parcela->location,
                'responsable' => $parcela->responsible,
                'tipo_cultivo' => $parcela->crop_type,
                'ultimo_riego' => $parcela->last_watering,
                'latitud' => $parcela->latitude,
                'longitud' => $parcela->longitude,
                'sensor' => $sensorData
            ];

            // Actualizar los valores globales de los sensores (solo los últimos valores)
            foreach ($sensorData as $key => $value) {
                if ($value !== null && $sensores[$key] === null) {
                    $sensores[$key] = $value;
                }
            }
        }

        // Respuesta final
        return response()->json([
            'sensores' => $sensores,
            'parcelas' => $parcelasData
        ]);
    }
}
