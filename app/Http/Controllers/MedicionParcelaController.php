<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicionParcela;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicionParcelaController extends Controller
{
    // Listar las últimas mediciones por cada sensor en cada parcela (agrupadas)
    public function ultimasMedicionesParcelas()
    {
        try {
            // Obtener la última medición por cada sensor en cada parcela
            $ultimasMediciones = MedicionParcela::select('parcela_id', 'sensor_id', 'value', 'date', 'registered_in')
                ->whereIn('id', function ($query) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('mediciones_parcelas')
                          ->groupBy('parcela_id', 'sensor_id');
                })
                ->with(['parcela', 'sensor'])
                ->get();
            
            if ($ultimasMediciones->isEmpty()) {
                Log::warning("No se encontraron mediciones en la consulta de últimas mediciones.");
                return response()->json(['error' => 'No se encontraron mediciones'], 404);
            }
            
            // Reestructurar el resultado para agrupar por parcela
            $resultado = [];
            foreach ($ultimasMediciones as $medicion) {
                $parcelaId = $medicion->parcela_id;
                
                // Verifica que la relación con la parcela exista
                if (!$medicion->parcela) {
                    Log::error("No se encontró la parcela asociada a la medición con ID {$medicion->id}.");
                    continue;
                }
                
                // Verifica que la relación con el sensor exista
                if (!$medicion->sensor) {
                    Log::error("No se encontró el sensor asociado a la medición con ID {$medicion->id}.");
                    continue;
                }
                
                // Si la parcela no está aún en el arreglo, se inicializa
                if (!isset($resultado[$parcelaId])) {
                    $resultado[$parcelaId] = [
                        'id'           => $medicion->parcela->id,
                        'nombre'       => $medicion->parcela->name,         // Asegúrate de que en la tabla 'parcelas' el campo sea 'name'
                        'ubicacion'    => $medicion->parcela->location,
                        'responsable'  => $medicion->parcela->responsible,
                        'tipo_cultivo' => $medicion->parcela->crop_type,
                        'ultimo_riego' => $medicion->parcela->last_watering,
                        'sensor'       => []  // Aquí se agruparán los datos de cada sensor
                    ];
                }
                
                // Asumimos que en el modelo Sensor tienes un campo 'name' que identifica el sensor
                $sensorTipo = $medicion->sensor->name;
                if (!$sensorTipo) {
                    Log::error("El sensor con ID {$medicion->sensor_id} no tiene definido el campo 'name'.");
                    continue;
                }
                
                // Guarda el valor de la medición en el array de sensores
                $resultado[$parcelaId]['sensor'][$sensorTipo] = $medicion->value;
            }
            
            // Convierte el array asociativo a un array indexado para la respuesta JSON
            $resultadoFinal = array_values($resultado);
            
            // Loguea el resultado final para depuración
            Log::info("Ultimas mediciones agrupadas:", $resultadoFinal);
            
            return response()->json($resultadoFinal);
        } catch (\Exception $e) {
            Log::error("Error en ultimasMedicionesParcelas: " . $e->getMessage());
            return response()->json(['error' => 'Error interno al obtener las últimas mediciones'], 500);
        }
    }

    // Listar todas las mediciones de parcelas con sus relaciones
    public function index()
    {
        $mediciones = MedicionParcela::with(['parcela', 'sensor'])->get();
        return response()->json($mediciones);
    }

    // Crear una nueva medición de parcela
    public function store(Request $request)
    {
        $request->validate([
            'parcela_id' => 'required|exists:parcelas,id',
            'sensor_id'  => 'required|exists:sensores,id',
            'value'      => 'required|numeric',
            'date'       => 'required|date'
        ]);

        $medicion = MedicionParcela::create($request->only('parcela_id', 'sensor_id', 'value', 'date'));
        return response()->json(['message' => 'Medición de parcela creada', 'medicion' => $medicion], 201);
    }

    // Mostrar una medición de parcela específica
    public function show($id)
    {
        $medicion = MedicionParcela::with(['parcela', 'sensor'])->find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        return response()->json($medicion);
    }

    // Actualizar una medición de parcela
    public function update(Request $request, $id)
    {
        $medicion = MedicionParcela::find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        $medicion->update($request->only('parcela_id', 'sensor_id', 'value', 'date'));
        return response()->json(['message' => 'Medición actualizada', 'medicion' => $medicion]);
    }

    // Eliminar una medición de parcela
    public function destroy($id)
    {
        $medicion = MedicionParcela::find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        $medicion->delete();
        return response()->json(['message' => 'Medición eliminada']);
    }
}
