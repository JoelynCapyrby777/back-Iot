<?php
namespace App\Http\Controllers;

use App\Models\MedicionGeneral;
use App\Models\MedicionParcela;
use App\Models\Parcela;

class MedicionActualController extends Controller
{
    // Obtener datos actuales de mediciones generales y mediciones de parcelas
    public function datosActuales()
    {
        // Obtener las últimas mediciones generales de todos los sensores (suponiendo que hay 4 sensores)
        $medicionesGenerales = MedicionGeneral::latest()->take(4)->get(); 

        // Estructura para los sensores
        $sensores = [
            'humedad' => null,
            'temperatura' => null,
            'lluvia' => null,
            'sol' => null
        ];

        // Mapear las mediciones generales a sus sensores correspondientes
        foreach ($medicionesGenerales as $medicion) {
            switch ($medicion->sensor_id) {
                case 1:
                    $sensores['humedad'] = $medicion->value;
                    break;
                case 2:
                    $sensores['temperatura'] = $medicion->value;
                    break;
                case 3:
                    $sensores['lluvia'] = $medicion->value;
                    break;
                case 4:
                    $sensores['sol'] = $medicion->value;
                    break;
            }
        }

        // Obtener la última medición de cada parcela
        $parcelas = Parcela::with(['mediciones' => function ($query) {
            $query->latest()->take(1); // Solo la última medición de cada parcela
        }])->get();

        // Estructura para las parcelas
        $parcelasData = $parcelas->map(function ($parcela) {
            $ultimaMedicion = $parcela->mediciones->first();

            return [
                'id' => $parcela->id,
                'nombre' => $parcela->nombre,
                'ubicacion' => $parcela->ubicacion,
                'responsable' => $parcela->responsable,
                'tipo_cultivo' => $parcela->tipo_cultivo,
                'ultimo_riego' => $ultimaMedicion ? $ultimaMedicion->created_at : null,
                'sensor' => [
                    'humedad' => $ultimaMedicion ? $ultimaMedicion->humedad : null,
                    'temperatura' => $ultimaMedicion ? $ultimaMedicion->temperatura : null,
                    'lluvia' => $ultimaMedicion ? $ultimaMedicion->lluvia : null,
                    'sol' => $ultimaMedicion ? $ultimaMedicion->sol : null
                ],
                'latitud' => $parcela->latitud,
                'longitud' => $parcela->longitud
            ];
        });

        // Retorno de los datos
        return response()->json([
            'status' => 'success',
            'sensores' => $sensores,
            'parcelas' => $parcelasData
        ]);
    }
}
