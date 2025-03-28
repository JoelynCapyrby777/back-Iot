<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicionParcela;
use App\Models\Parcela;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedicionParcelaController extends Controller
{
    // Listar las últimas mediciones por cada sensor en cada parcela (agrupadas)
    public function ultimasMedicionesParcelas()
    {
        try {
            // Obtener solo las parcelas activas
            $parcelasActivas = Parcela::where('status', 'active')->pluck('id');
            
            // Obtener la última medición por cada sensor en cada parcela activa
            $ultimasMediciones = MedicionParcela::select('parcela_id', 'sensor_id', 'value', 'date')
                ->whereIn('parcela_id', $parcelasActivas)
                ->whereIn('id', function ($query) use ($parcelasActivas) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('mediciones_parcelas')
                          ->whereIn('parcela_id', $parcelasActivas)
                          ->groupBy('parcela_id', 'sensor_id');
                })
                ->with(['parcela' => function($query) {
                    $query->where('status', 'active');
                }, 'sensor'])
                ->get();
            
            if ($ultimasMediciones->isEmpty()) {
                Log::warning("No se encontraron mediciones para parcelas activas.");
                return response()->json(['error' => 'No se encontraron mediciones'], 404);
            }
            
            // Reestructurar el resultado para agrupar por parcela
            $resultado = [];
            foreach ($ultimasMediciones as $medicion) {
                $parcelaId = $medicion->parcela_id;
                
                // Verifica que la parcela exista y esté activa
                if (!$medicion->parcela || $medicion->parcela->status !== 'active') {
                    Log::info("Saltando parcela {$parcelaId} por estar inactiva o no existir.");
                    continue;
                }
                
                // Verifica que la relación con el sensor exista
                if (!$medicion->sensor) {
                    Log::error("No se encontró el sensor asociado a la medición en parcela {$parcelaId}.");
                    continue;
                }
                
                // Inicializar la estructura de la parcela si no existe
                if (!isset($resultado[$parcelaId])) {
                    $resultado[$parcelaId] = [
                        'id' => $medicion->parcela->id,
                        'nombre' => $medicion->parcela->name,
                        'ubicacion' => $medicion->parcela->location,
                        'responsable' => $medicion->parcela->responsible,
                        'tipo_cultivo' => $medicion->parcela->crop_type,
                        'ultimo_riego' => $medicion->parcela->last_watering,
                        'latitud' => $medicion->parcela->latitude,
                        'longitud' => $medicion->parcela->longitude,
                        'sensor' => []
                    ];
                }
                
                // Agregar la medición del sensor
                $sensorTipo = strtolower($medicion->sensor->name);
                $resultado[$parcelaId]['sensor'][$sensorTipo] = floatval($medicion->value);
            }
            
            // Convertir a array indexado y ordenar por ID de parcela
            $resultadoFinal = array_values($resultado);
            usort($resultadoFinal, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
            
            Log::info("Mediciones recuperadas exitosamente para " . count($resultadoFinal) . " parcelas activas");
            
            return response()->json($resultadoFinal);
            
        } catch (\Exception $e) {
            Log::error("Error en ultimasMedicionesParcelas: " . $e->getMessage());
            return response()->json(['error' => 'Error interno al obtener las últimas mediciones'], 500);
        }
    }

    // Listar todas las mediciones de parcelas con sus relaciones
    public function index()
    {
        try {
            $mediciones = MedicionParcela::with(['parcela' => function($query) {
                $query->where('status', 'active');
            }, 'sensor'])
            ->whereHas('parcela', function($query) {
                $query->where('status', 'active');
            })
            ->get();
            
            return response()->json($mediciones);
        } catch (\Exception $e) {
            Log::error("Error en index: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener las mediciones'], 500);
        }
    }

    // Crear una nueva medición de parcela
    public function store(Request $request)
    {
        try {
            $request->validate([
                'parcela_id' => 'required|exists:parcelas,id',
                'sensor_id' => 'required|exists:sensores,id',
                'value' => 'required|numeric',
                'date' => 'required|date'
            ]);

            // Verificar que la parcela esté activa
            $parcela = Parcela::find($request->parcela_id);
            if (!$parcela || $parcela->status !== 'active') {
                return response()->json(['error' => 'La parcela no está activa o no existe'], 422);
            }

            $medicion = MedicionParcela::create($request->only('parcela_id', 'sensor_id', 'value', 'date'));
            return response()->json(['message' => 'Medición de parcela creada', 'medicion' => $medicion], 201);
        } catch (\Exception $e) {
            Log::error("Error en store: " . $e->getMessage());
            return response()->json(['error' => 'Error al crear la medición'], 500);
        }
    }

    // Mostrar una medición de parcela específica
    public function show($id)
    {
        try {
            $medicion = MedicionParcela::with(['parcela' => function($query) {
                $query->where('status', 'active');
            }, 'sensor'])
            ->whereHas('parcela', function($query) {
                $query->where('status', 'active');
            })
            ->find($id);

            if (!$medicion) {
                return response()->json(['error' => 'Medición no encontrada o parcela inactiva'], 404);
            }

            return response()->json($medicion);
        } catch (\Exception $e) {
            Log::error("Error en show: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener la medición'], 500);
        }
    }

    // Actualizar una medición de parcela
    public function update(Request $request, $id)
    {
        try {
            $medicion = MedicionParcela::whereHas('parcela', function($query) {
                $query->where('status', 'active');
            })->find($id);

            if (!$medicion) {
                return response()->json(['error' => 'Medición no encontrada o parcela inactiva'], 404);
            }

            $medicion->update($request->only('parcela_id', 'sensor_id', 'value', 'date'));
            return response()->json(['message' => 'Medición actualizada', 'medicion' => $medicion]);
        } catch (\Exception $e) {
            Log::error("Error en update: " . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar la medición'], 500);
        }
    }

    // Eliminar una medición de parcela
    public function destroy($id)
    {
        try {
            $medicion = MedicionParcela::whereHas('parcela', function($query) {
                $query->where('status', 'active');
            })->find($id);

            if (!$medicion) {
                return response()->json(['error' => 'Medición no encontrada o parcela inactiva'], 404);
            }

            $medicion->delete();
            return response()->json(['message' => 'Medición eliminada']);
        } catch (\Exception $e) {
            Log::error("Error en destroy: " . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar la medición'], 500);
        }
    }
}
