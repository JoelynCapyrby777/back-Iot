<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;

class SensorController extends Controller
{
    // Listar todos los sensores
    public function index()
    {
        $sensores = Sensor::all();
        return response()->json($sensores);
    }

    // Crear un nuevo sensor
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255'
        ]);

        $sensor = Sensor::create([
            'name' => $request->name,
            'unit' => $request->unit
        ]);

        return response()->json(['message' => 'Sensor creado', 'sensor' => $sensor], 201);
    }

    // Mostrar un sensor específico
    public function show($id)
    {
        $sensor = Sensor::find($id);
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }
        return response()->json($sensor);
    }

    // Actualizar un sensor
    public function update(Request $request, $id)
    {
        $sensor = Sensor::find($id);
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }
        $sensor->update($request->only('name', 'unit'));
        return response()->json(['message' => 'Sensor actualizado', 'sensor' => $sensor]);
    }

    // Eliminar un sensor
    public function destroy($id)
    {
        $sensor = Sensor::find($id);
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }
        $sensor->delete();
        return response()->json(['message' => 'Sensor eliminado']);
    }
}
