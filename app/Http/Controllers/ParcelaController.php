<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;

class ParcelaController extends Controller
{
    // Listar todas las parcelas
    public function index()
    {
        $parcelas = Parcela::all();
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
            'user_id'       => 'required|exists:users,id'
        ]);

        $parcela = Parcela::create($request->all());
        return response()->json(['message' => 'Parcela creada', 'parcela' => $parcela], 201);
    }

    // Mostrar una parcela específica
    public function show($id)
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return response()->json(['error' => 'Parcela no encontrada'], 404);
        }
        return response()->json($parcela);
    }

    // Actualizar una parcela
    public function update(Request $request, $id)
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return response()->json(['error' => 'Parcela no encontrada'], 404);
        }
        $parcela->update($request->all());
        return response()->json(['message' => 'Parcela actualizada', 'parcela' => $parcela]);
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

    // Mostrar parcelas inactivas
    public function inactivas()
    {
        $parcelasInactivas = Parcela::where('status', 'inactive')->get();

        if ($parcelasInactivas->isEmpty()) {
            return response()->json(['message' => 'No hay parcelas inactivas'], 404);
        }

        $resultado = [];
        foreach ($parcelasInactivas as $parcela) {
            $resultado[] = [
                'id' => $parcela->id,
                'nombre' => $parcela->name,
                'ubicacion' => $parcela->location,
                'responsable' => $parcela->responsible,
                'tipo_cultivo' => $parcela->crop_type,
                'ultimo_riego' => $parcela->last_watering,
                'latitud' => $parcela->latitude,
                'longitud' => $parcela->longitude,
            ];
        }

        return response()->json($resultado);
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
                'sensor' => $sensoresParcela
            ];
        }

        return response()->json([
            'status' => 'success',
            'parcelas' => $resultado
        ]);
    }
}
