<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicionParcela;

class MedicionParcelaController extends Controller
{
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
