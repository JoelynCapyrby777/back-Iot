<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicionGeneral;

class MedicionGeneralController extends Controller
{
    // Listar todas las mediciones generales con información del sensor relacionado
    public function index()
    {
        $mediciones = MedicionGeneral::with('sensor')->get();
        return response()->json($mediciones);
    }

    // Crear una nueva medición general
    public function store(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|exists:sensores,id',
            'value'     => 'required|numeric',
            'date'      => 'required|date'
        ]);

        $medicion = MedicionGeneral::create($request->only('sensor_id', 'value', 'date'));
        return response()->json(['message' => 'Medición general creada', 'medicion' => $medicion], 201);
    }

    // Mostrar una medición general específica
    public function show($id)
    {
        $medicion = MedicionGeneral::with('sensor')->find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        return response()->json($medicion);
    }

    // Actualizar una medición general
    public function update(Request $request, $id)
    {
        $medicion = MedicionGeneral::find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        $medicion->update($request->only('sensor_id', 'value', 'date'));
        return response()->json(['message' => 'Medición actualizada', 'medicion' => $medicion]);
    }

    // Eliminar una medición general
    public function destroy($id)
    {
        $medicion = MedicionGeneral::find($id);
        if (!$medicion) {
            return response()->json(['error' => 'Medición no encontrada'], 404);
        }
        $medicion->delete();
        return response()->json(['message' => 'Medición eliminada']);
    }
}
