<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecetaController extends Controller
{
    /**
     * Mostrar todas las recetas con sus ingredientes.
     */
    public function index(Request $request)
    {
        $recetas = Receta::with('ingredientes')
            ->orderBy('nombre')
            ->get();

        return view('receta.index', compact('recetas'));
    }

    /**
     * Registrar una receta y sus ingredientes asociados.
     */
    public function create(Request $request)
    {
        $this->normalizeIngredientQuantities($request);

        // Compatibilidad con el nombre anterior del campo.
        if (
            ! $request->has('instructivo') &&
            $request->has('instrucciones')
        ) {
            $request->merge([
                'instructivo' => $request->input('instrucciones'),
            ]);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string'],
            'instructivo' => ['required', 'string'],

            'ingredientes' => ['nullable', 'array'],
            'ingredientes.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('ingredientes', 'idingrediente'),
            ],
            'ingredientes.*.peso' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'ingredientes.*.medida' => ['required', Rule::in(['g', 'kg', 'lb', 'oz', 'u', 'L', 'ml'])],
        ]);

        DB::transaction(function () use ($validated) {
            // Guardar la receta una sola vez.
            $receta = Receta::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'instructivo' => $validated['instructivo'],
            ]);

            // Guardar sus ingredientes en receta_ingrediente.
            $receta->ingredientes()->sync(
                collect($validated['ingredientes'] ?? [])
                    ->mapWithKeys(fn ($ingrediente) => [
                        $ingrediente['id'] => [
                            'peso' => $ingrediente['peso'],
                            'medida' => $ingrediente['medida'],
                        ],
                    ])
                    ->all()
            );
        });

        return redirect()
            ->route('recetas.index')
            ->with('success', 'Receta guardada correctamente.');
    }

    /**
     * Método store conservado por si alguna ruta lo utiliza.
     */
    public function store(Request $request)
    {
        return $this->create($request);
    }

    /**
     * Mostrar una receta y sus ingredientes.
     */
    public function show(Receta $receta)
    {
        $receta->load('ingredientes');

        return view('receta.show', compact('receta'));
    }

    /**
     * Mostrar el formulario de edición.
     */
    public function edit(Receta $receta)
    {
        $receta->load('ingredientes');

        return view('receta.edit', compact('receta'));
    }

    /**
     * Actualizar la receta y sus ingredientes.
     */
    public function update(Request $request, Receta $receta)
    {
        $this->normalizeIngredientQuantities($request);

        if (
            ! $request->has('instructivo') &&
            $request->has('instrucciones')
        ) {
            $request->merge([
                'instructivo' => $request->input('instrucciones'),
            ]);
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'descripcion' => ['required', 'string'],
            'instructivo' => ['required', 'string'],

            'ingredientes' => ['sometimes', 'array'],
            'ingredientes.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('ingredientes', 'idingrediente'),
            ],
            'ingredientes.*.peso' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'ingredientes.*.medida' => ['required', Rule::in(['g', 'kg', 'lb', 'oz', 'u', 'L', 'ml'])],
        ]);

        DB::transaction(function () use ($validated, $receta) {
            $receta->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
                'instructivo' => $validated['instructivo'],
            ]);

            $receta->ingredientes()->sync(
                collect($validated['ingredientes'] ?? [])
                    ->mapWithKeys(fn ($ingrediente) => [
                        $ingrediente['id'] => [
                            'peso' => $ingrediente['peso'],
                            'medida' => $ingrediente['medida'],
                        ],
                    ])
                    ->all()
            );
        });

        return redirect()
            ->route('recetas.index')
            ->with('success', 'Receta actualizada correctamente.');
    }

    /**
     * Eliminar una receta sin dejar asociaciones de ingredientes.
     */
    public function destroy(Receta $receta)
    {
        // No eliminar recetas que ya tienen preparaciones registradas.
        if ($receta->preparaciones()->exists()) {
            return redirect()
                ->route('recetas.index')
                ->with(
                    'error',
                    'No se puede eliminar esta receta porque tiene preparaciones registradas.'
                );
        }

        DB::transaction(function () use ($receta) {
            $receta->ingredientes()->detach();

            $receta->delete();
        });

        return redirect()
            ->route('recetas.index')
            ->with('success', 'Receta eliminada correctamente.');
    }

    private function normalizeIngredientQuantities(Request $request): void
    {
        $ingredients = $request->input('ingredientes');
        if (!is_array($ingredients)) {
            return;
        }

        foreach ($ingredients as $index => $ingredient) {
            if (!is_array($ingredient) || !isset($ingredient['peso']) || !is_string($ingredient['peso'])) {
                continue;
            }

            $ingredient['peso'] = str_replace(',', '.', trim($ingredient['peso']));
            $ingredients[$index] = $ingredient;
        }

        $request->merge(['ingredientes' => $ingredients]);
    }
}
