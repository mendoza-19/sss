<?php

namespace App\Http\Controllers;

use App\Models\Ingrediente;
use App\Models\Preparacion;
use App\Models\Producto;
use App\Models\Proporcionesreceta;
use App\Models\Receta;
use App\Services\CostoProductoService;
use App\Services\UnidadIngredienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PreparacionController extends Controller
{
    public function indexHorneado()
    {
        $preparaciones = Preparacion::with('receta.productos')
            ->orderByDesc('id')
            ->get();
        $ordenesPendientes = $preparaciones
            ->whereNull('unidades_horneadas')
            ->values();
        $ordenesHorneadas = $preparaciones
            ->whereNotNull('unidades_horneadas')
            ->groupBy('receta_id')
            ->map(function ($lotes) {
                return [
                    'receta' => $lotes->first()->receta,
                    'lotes' => $lotes->values(),
                    'costo_total' => $lotes->every(fn ($lote) => $lote->costo_total === null)
                        ? null
                        : $lotes->sum(fn ($lote) => (float) ($lote->costo_total ?? 0)),
                ];
            })
            ->values();
        $pestanaInicial = $ordenesPendientes->isNotEmpty() ? 'pendientes' : 'horneadas';

        return view('receta.horneados', compact(
            'ordenesPendientes',
            'ordenesHorneadas',
            'pestanaInicial'
        ));
    }

    public function horneado(Preparacion $preparacion)
    {
        $preparacion->load(['receta', 'proporciones.ingrediente']);
        $unidadesParaCosto = $preparacion->unidades_horneadas ?? $preparacion->unidades_producidas;
        $costoUnitario = $unidadesParaCosto > 0
            ? $preparacion->costo_total / $unidadesParaCosto
            : null;

        return view('receta.horneado', compact('preparacion', 'costoUnitario'));
    }

    public function procesar(Request $request, UnidadIngredienteService $unidades)
    {
        $validated = $request->validate([
            'receta_id' => ['required', 'integer', Rule::exists('recetas', 'idreceta')],
            'fecha' => ['required', 'date'],
            'libras_harina' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $preparacion = DB::transaction(function () use ($validated, $unidades) {
            $receta = Receta::with('ingredientes')->findOrFail($validated['receta_id']);
            $ids = $receta->ingredientes->pluck('idingrediente');

            if ($ids->isEmpty()) {
                throw ValidationException::withMessages([
                    'receta_id' => 'La receta no tiene ingredientes.',
                ]);
            }

            $harinas = $receta->ingredientes->filter(
                fn ($ingrediente) => str_contains(strtolower($ingrediente->nombre), 'harina')
            );

            if ($harinas->count() !== 1) {
                throw ValidationException::withMessages([
                    'receta_id' => 'La receta debe tener exactamente un ingrediente de harina para calcular las cantidades.',
                ]);
            }

            $harinaReceta = $harinas->first();
            $harinaPorPan = (float) $harinaReceta->pivot->peso;
            $medidaHarinaReceta = $harinaReceta->pivot->medida ?: $harinaReceta->medida;
            $librasHarina = (float) $validated['libras_harina'];
            $totalHarinaBase = $unidades->convertirABase($librasHarina, 'lb');
            $harinaPorPanBase = $unidades->convertirABase($harinaPorPan, $medidaHarinaReceta);

            if ($harinaPorPan <= 0 || $totalHarinaBase['tipo'] !== 'peso' || $harinaPorPanBase['tipo'] !== 'peso') {
                throw ValidationException::withMessages([
                    'libras_harina' => 'La harina de la receta debe tener una cantidad válida en una unidad de peso.',
                ]);
            }

            $factorProduccion = $totalHarinaBase['cantidad'] / $harinaPorPanBase['cantidad'];
            $unidadesPlaneadas = (int) floor($factorProduccion);

            if ($unidadesPlaneadas < 1) {
                throw ValidationException::withMessages([
                    'libras_harina' => 'La cantidad debe alcanzar para preparar al menos una unidad de la receta.',
                ]);
            }

            $inventario = Ingrediente::whereIn('idingrediente', $ids)
                ->orderBy('idingrediente')
                ->lockForUpdate()
                ->get()
                ->keyBy('idingrediente');

            $consumos = [];
            foreach ($receta->ingredientes as $ingredienteReceta) {
                $ingrediente = $inventario->get($ingredienteReceta->idingrediente);
                $esHarina = (int) $ingredienteReceta->idingrediente === (int) $harinaReceta->idingrediente;
                $medida = $esHarina
                    ? 'lb'
                    : ($ingredienteReceta->pivot->medida ?: $ingrediente->medida);
                $cantidad = $esHarina
                    ? $librasHarina
                    : round((float) $ingredienteReceta->pivot->peso * $factorProduccion, 3);
                $cantidadStock = $unidades->convertirAStock(
                    $cantidad,
                    $medida,
                    $ingrediente->medida
                );

                if ($ingrediente->stock < $cantidadStock) {
                    $mensajeStock = (int) $ingrediente->idingrediente === (int) $harinaReceta->idingrediente
                        ? 'No hay suficiente stock'
                        : "Stock insuficiente de {$ingrediente->nombre}. Disponible: {$ingrediente->stock} {$ingrediente->medida}; necesario: {$cantidadStock}.";

                    throw ValidationException::withMessages([
                        'libras_harina' => $mensajeStock,
                    ]);
                }

                $consumos[] = [
                    'ingrediente' => $ingrediente,
                    'cantidad' => $cantidad,
                    'medida' => $medida,
                    'cantidad_stock' => $cantidadStock,
                ];
            }

            $preparacion = Preparacion::create([
                'receta_id' => $receta->idreceta,
                'fecha' => $validated['fecha'],
                'libras_planeadas' => $librasHarina,
                'unidades_planeadas' => $unidadesPlaneadas,
                'unidades_quemadas' => 0,
            ]);

            foreach ($consumos as $consumo) {
                $ingrediente = $consumo['ingrediente'];
                $ingrediente->stock = round($ingrediente->stock - $consumo['cantidad_stock'], 3);
                $ingrediente->save();

                Proporcionesreceta::create([
                    'preparacion_id' => $preparacion->id,
                    'ingrediente_id' => $ingrediente->idingrediente,
                    'unidades' => $consumo['cantidad'],
                    'medida' => $consumo['medida'],
                ]);
            }

            return $preparacion;
        });

        return redirect()
            ->route('recetas.index')
            ->with('success', 'Orden procesada');
    }

    public function storeHorneado(
        Request $request,
        Preparacion $preparacion,
        CostoProductoService $costos
    ) {
        $validated = $request->validate([
            'latas' => ['required', 'array', 'min:1'],
            'latas.*.cantidad' => ['required', 'integer', 'min:0'],
            'latas.*.panes_por_lata' => ['required', 'integer', 'min:1'],
            'unidades_quemadas' => ['required', 'integer', 'min:0'],
        ]);

        $detalleLatas = collect($validated['latas'])
            ->map(fn ($lata) => [
                'cantidad' => (int) $lata['cantidad'],
                'panes_por_lata' => (int) $lata['panes_por_lata'],
            ])
            ->filter(fn ($lata) => $lata['cantidad'] > 0)
            ->values()
            ->all();
        $latasHorneadas = array_sum(array_column($detalleLatas, 'cantidad'));
        $unidadesHorneadas = array_sum(array_map(
            fn ($lata) => $lata['cantidad'] * $lata['panes_por_lata'],
            $detalleLatas
        ));

        if ($validated['unidades_quemadas'] > $unidadesHorneadas) {
            throw ValidationException::withMessages([
                'unidades_quemadas' => 'Las unidades dañadas no pueden superar las horneadas.',
            ]);
        }

        DB::transaction(function () use (
            $validated,
            $detalleLatas,
            $latasHorneadas,
            $unidadesHorneadas,
            $preparacion,
            $costos
        ) {
            $preparacion = Preparacion::whereKey($preparacion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($preparacion->unidades_horneadas !== null) {
                throw ValidationException::withMessages([
                    'horneado' => 'Este proceso ya tiene un resultado de horneado.',
                ]);
            }

            $buenas = $unidadesHorneadas - (int) $validated['unidades_quemadas'];
            $preparacion->latas_horneadas = $latasHorneadas;
            $preparacion->detalle_latas = $detalleLatas;
            $preparacion->unidades_horneadas = $unidadesHorneadas;
            $preparacion->unidades_quemadas = (int) $validated['unidades_quemadas'];
            $preparacion->unidades_producidas = $buenas;
            $preparacion->costo_total = $costos->calcularCostoPreparacion($preparacion);
            $preparacion->save();

            if ($buenas < 1) {
                return;
            }

            $productos = Producto::where('receta_id', $preparacion->receta_id)
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get();

            if ($productos->count() > 1) {
                throw ValidationException::withMessages([
                    'horneado' => 'La receta está vinculada a más de un producto.',
                ]);
            }

            $producto = $productos->first();
            $costoUnitario = round($preparacion->costo_total / $unidadesHorneadas, 2);

            if (!$producto) {
                $receta = Receta::findOrFail($preparacion->receta_id);
                $producto = Producto::create([
                    'nombre' => $receta->nombre,
                    'descripcion' => $receta->descripcion,
                    'precio' => 0,
                    'costo' => $costoUnitario,
                    'stock' => $buenas,
                    'foto' => '',
                    'receta_id' => $receta->idreceta,
                    'estado' => 'available',
                ]);
            } else {
                $producto->stock += $buenas;
                $producto->costo = $costoUnitario;
                $producto->estado = 'available';
                $producto->save();
            }
        });

        return redirect()
            ->route('horneados.index')
            ->with('success', 'Horneado registrado y costo de producción calculado.');
    }

    /**
     * Registrar una preparación, guardar las cantidades
     * utilizadas y descontar el stock de los ingredientes.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receta_id' => [
                'required',
                'integer',
                Rule::exists('recetas', 'idreceta'),
            ],
            'fecha' => ['required', 'date'],
            'ingredientes' => ['required', 'array', 'min:1'],
            'ingredientes.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('ingredientes', 'idingrediente'),
            ],
            'ingredientes.*.unidades' => [
                'required',
                'integer',
                'min:1',
            ],
            'ingredientes.*.medida' => [
                'required',
                Rule::in(['g', 'kg', 'oz', 'lb']),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            $receta = Receta::with('ingredientes')
                ->findOrFail($validated['receta_id']);

            $cantidades = collect($validated['ingredientes'])
                ->keyBy(fn ($item) => (int) $item['id']);

            // Verificar que se hayan enviado todos los ingredientes
            // asociados a la receta y ninguno que no pertenezca a ella.
            $idsReceta = $receta->ingredientes
                ->pluck('idingrediente')
                ->merge(
                    Proporcionesreceta::whereHas(
                        'preparacion',
                        fn ($query) => $query->where('receta_id', $receta->idreceta)
                    )
                        ->pluck('ingrediente_id')
                )
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $idsEnviados = $cantidades->keys()
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            if ($idsReceta->all() !== $idsEnviados->all()) {
                throw ValidationException::withMessages([
                    'ingredientes' =>
                        'Debes ingresar las cantidades de todos los ingredientes de la receta.',
                ]);
            }

            $pesoPorPanOnzas = (float) $receta->peso;
            if ($pesoPorPanOnzas <= 0) {
                throw ValidationException::withMessages([
                    'receta_id' => 'Configura un peso válido por pan en la receta antes de procesarla.',
                ]);
            }

            $productos = Producto::where('receta_id', $receta->idreceta)
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get();

            if ($productos->count() > 1) {
                throw ValidationException::withMessages([
                    'receta_id' => 'Esta receta está enlazada a varios productos. Enlázala a uno solo antes de procesarla.',
                ]);
            }

            // Bloquear los ingredientes durante la verificación
            // y actualización para evitar descuentos simultáneos.
            $ingredientes = Ingrediente::whereIn(
                'idingrediente',
                $idsReceta
            )
                ->orderBy('idingrediente')
                ->lockForUpdate()
                ->get()
                ->keyBy('idingrediente');

            // El selector del modal solo define cómo convertir al descontar
            // el inventario; el cálculo de producción conserva la medida
            // registrada del ingrediente.
            $consumosEnOnzas = [];
            $pesoTotalOnzas = 0.0;
            foreach ($cantidades as $id => $item) {
                $ingrediente = $ingredientes->get((int) $id);
                $unidades = (int) $item['unidades'];
                $factorStock = match ($item['medida']) {
                    'g' => 1 / 28.349523125,
                    'kg' => 1000 / 28.349523125,
                    'oz' => 1,
                    'lb' => 16,
                    default => null,
                };
                $unidadesEnOnzas = (int) ceil($unidades * ($factorStock ?? 1));
                $consumosEnOnzas[(int) $id] = $unidadesEnOnzas;
                $pesoTotalOnzas += $unidades * ($factorStock ?? 1);

                if (! $ingrediente || $ingrediente->stock < $unidadesEnOnzas) {
                    $nombre = $ingrediente
                        ? $ingrediente->nombre
                        : 'Ingrediente desconocido';

                    $stockDisponible = $ingrediente
                        ? $ingrediente->stock
                        : 0;

                    throw ValidationException::withMessages([
                        'ingredientes' =>
                            "Stock insuficiente para {$nombre}. " .
                            "Disponible: {$stockDisponible} onzas; " .
                            "necesario: {$unidadesEnOnzas} onzas.",
                    ]);
                }
            }

            $unidadesProducidas = (int) floor(
                $pesoTotalOnzas / $pesoPorPanOnzas
            );

            if ($unidadesProducidas < 1) {
                throw ValidationException::withMessages([
                    'receta_id' => 'El peso total de los ingredientes no alcanza para producir un pan de ese tamaño.',
                ]);
            }

            // Registrar una preparación nueva.
            $preparacion = Preparacion::create([
                'receta_id' => $receta->idreceta,
                'fecha' => $validated['fecha'],
                'unidades_producidas' => $unidadesProducidas,
            ]);

            // Guardar las cantidades y descontar el inventario.
            foreach ($cantidades as $id => $item) {
                $unidades = (int) $item['unidades'];
                $ingrediente = $ingredientes->get((int) $id);

                Proporcionesreceta::create([
                    'preparacion_id' => $preparacion->id,
                    'ingrediente_id' => (int) $id,
                    'unidades' => $unidades,
                    'medida' => $item['medida'],
                ]);

                $ingrediente->stock -= $consumosEnOnzas[(int) $id];
                $ingrediente->save();
            }

            $producto = $productos->first();
            $costoUnitario = app(CostoProductoService::class)
                ->calcularUnitario($receta->idreceta);

            if (!$producto) {
                $producto = Producto::create([
                    'nombre' => $receta->nombre,
                    'descripcion' => $receta->descripcion,
                    'precio' => 0,
                    'costo' => $costoUnitario,
                    'stock' => $unidadesProducidas,
                    'foto' => '',
                    'receta_id' => $receta->idreceta,
                    'estado' => 'available',
                ]);
            } else {
                $producto->stock += $unidadesProducidas;
                $producto->costo = $costoUnitario;
                $producto->estado = 'available';
                $producto->save();
            }
        });

        return redirect()
            ->route('recetas.index')
            ->with('success', 'Producción registrada y stock del producto actualizado correctamente.');
    }
}
