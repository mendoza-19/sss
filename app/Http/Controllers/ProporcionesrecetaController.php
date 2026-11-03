<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proporcionesreceta;
use App\Models\Ingrediente;

class ProporcionesrecetaController extends Controller
{
    /**
     * Buscar ingredientes existentes por nombre.
     */
    public function buscarIngredientes(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $ingredientes = Ingrediente::where(
            'nombre',
            'LIKE',
            '%' . $request->nombre . '%'
        )
        ->limit(10)
        ->get();

        return response()->json($ingredientes);
    }

    /**
     * Guardar la proporción de un ingrediente para una preparación.
     */
    public function store(Request $request)
    {
        $request->validate([
            'preparacion_id' => 'required|integer|exists:preparaciones,id',
            'ingrediente_id' => 'required|integer|exists:ingredientes,idingrediente',
            'unidades' => 'required|integer|min:1',
            'medida' => 'nullable|string|max:30',
        ]);

        Proporcionesreceta::create([
            'preparacion_id' => $request->preparacion_id,
            'ingrediente_id' => $request->ingrediente_id,
            'unidades' => $request->unidades,
            'medida' => $request->medida,
        ]);

        return response()->json([
            'mensaje' => 'Ingrediente agregado correctamente'
        ]);
    }
}
