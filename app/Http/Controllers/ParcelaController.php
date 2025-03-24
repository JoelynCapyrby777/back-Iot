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
}
