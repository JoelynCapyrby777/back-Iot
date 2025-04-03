<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;

class ParcelaController extends Controller
{
    // Listar todas las parcelas con su estado
        public function index()
{
    $parcelas = Parcela::all()->map(function ($parcela) {
        return [
            'id'            => $parcela->id,
            'nombre'        => $parcela->name,
            'ubicacion'     => $parcela->location,
            'responsable'   => $parcela->responsible,
            'tipo_cultivo'  => $parcela->crop_type,
            'ultimo_riego'  => $parcela->last_watering,
            'latitud'       => $parcela->latitude,
            'longitud'      => $parcela->longitude,
            'status'        => $parcela->status, 
            'ultima_actualizacion' => $parcela->updated_at, // Última actualización
        ];
    });

    return response()->json([
        'ultima_actualizacion_global' => Parcela::max('updated_at'), // Última actualización de cualquier parcela
        'parcelas' => $parcelas
    ]);
}


    // NUEVO: Mostrar solo el status de todas las parcelas
    public function statusParcelas()
    {
        $parcelas = Parcela::all()->map(function ($parcela) {
            return [
                'id'     => $parcela->id,
                'status' => $parcela->status === 'active' ? 'activo' : 'inactivo',
            ];
        });

        return response()->json($parcelas);
    }

    // Crear una nueva parcela
    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'location'      => 'required|string|max:255',
            'responsible'   => 'required|string|max:255',
            'crop_type'     => 'required|string|max:255',
            'last_watering' => 'required|date',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'user_id'       => 'required|exists:users,id',
            'status'        => 'required|in:active,inactive', // Validar estado
        ]);

        $parcela = Parcela::create($request->all());
        return response()->json(['message' => 'Parcela creada', 'parcela' => $parcela], 201);
    }

    // Mostrar una parcela específica con su estado
    public function show($id)
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return response()->json(['error' => 'Parcela no encontrada'], 404);
        }

        return response()->json([
            'id'            => $parcela->id,
            'nombre'        => $parcela->name,
            'ubicacion'     => $parcela->location,
            'responsable'   => $parcela->responsible,
            'tipo_cultivo'  => $parcela->crop_type,
            'ultimo_riego'  => $parcela->last_watering,
            'latitud'       => $parcela->latitude,
            'longitud'      => $parcela->longitude,
            'status'        => $parcela->status === 'active' ? 'activo' : 'inactivo',
        ]);
    }

    // Actualizar una parcela (incluyendo su estado)
    public function update(Request $request, $id)
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return response()->json(['error' => 'Parcela no encontrada'], 404);
        }

        $parcela->update($request->all());

        return response()->json([
            'message' => 'Parcela actualizada',
            'parcela' => $parcela,
        ]);
    }

    // Eliminar una parcela
    public function destroy($id)
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return response()->json(['error' => 'Parcela no encontrada'], 404);
        }

        $parcela->delete();
        return response()->json(['message' => 'Parcela eliminada']);
    }

    // Mostrar solo parcelas inactivas con su estado
    public function inactivas()
    {
        $parcelasInactivas = Parcela::where('status', 'inactive')->with('medicionesParcela.sensor')->get();
    
        $resultado = [];
    
        foreach ($parcelasInactivas as $parcela) {
            // Inicializamos todos los sensores en null
            $sensoresParcela = [
                'humedad'     => null,
                'temperatura' => null,
                'lluvia'      => null,
                'sol'         => null,
            ];
    
            // Agrupar mediciones por sensor para tomar la última de cada tipo
            $mediciones = $parcela->medicionesParcela->groupBy('sensor_id');
    
            foreach ($mediciones as $sensorId => $medicionesSensor) {
                $ultimaMedicion = $medicionesSensor->sortByDesc('created_at')->first(); // Última medición por sensor
    
                if ($ultimaMedicion && $ultimaMedicion->sensor) {
                    $tipoSensor = strtolower($ultimaMedicion->sensor->name);
                    if (array_key_exists($tipoSensor, $sensoresParcela)) {
                        $sensoresParcela[$tipoSensor] = floatval($ultimaMedicion->value);
                    }
                }
            }
    
            $resultado[] = [
                'id'            => $parcela->id,
                'nombre'        => $parcela->name,
                'ubicacion'     => $parcela->location,
                'responsable'   => $parcela->responsible,
                'tipo_cultivo'  => $parcela->crop_type,
                'ultimo_riego'  => $parcela->last_watering,
                'latitud'       => $parcela->latitude,
                'longitud'      => $parcela->longitude,
                'status'        => 'inactivo',
                'sensor'        => $sensoresParcela, // Últimos datos de cada sensor
            ];
        }
    
        return response()->json([
            'status'   => 'success',
            'parcelas' => $resultado
        ]);
    }
    
      


    // Mostrar datos actuales de las parcelas
    public function datosActuales()
    {
        // Obtener todas las parcelas activas con sus mediciones más recientes
        $parcelas = Parcela::with(['mediciones' => function ($query) {
            $query->orderBy('created_at', 'desc')->take(1);
        }, 'mediciones.sensor'])->get();

        $resultado = [];

        foreach ($parcelas as $parcela) {
            $sensoresParcela = [];

            // Recolectar las últimas mediciones de sensores para cada parcela
            foreach ($parcela->mediciones as $medicion) {
                if ($medicion->sensor) {
                    $tipoSensor = strtolower($medicion->sensor->name);  // Usar el nombre del sensor como clave
                    $sensoresParcela[$tipoSensor] = floatval($medicion->value);
                }
            }

            $resultado[] = [
                'id' => $parcela->id,
                'nombre' => $parcela->name,
                'ubicacion' => $parcela->location,
                'responsable' => $parcela->responsible,
                'tipo_cultivo' => $parcela->crop_type,
                'ultimo_riego' => $parcela->last_watering,
                'latitud' => $parcela->latitude,
                'longitud' => $parcela->longitude,
                'status' => $parcela->status === 'active' ? 'activo' : 'inactivo',
                'sensor' => $sensoresParcela
            ];
        }

        return response()->json([
            'status' => 'success',
            'parcelas' => $resultado
        ]);
    }
}
