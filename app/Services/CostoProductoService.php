<?php

namespace App\Services;

use App\Models\Preparacion;
use App\Services\UnidadIngredienteService;
use Illuminate\Validation\ValidationException;

class CostoProductoService
{
    public function calcularUnitario(int $recetaId): float
    {
        $preparaciones = Preparacion::with('proporciones.ingrediente.detallefacturas')
            ->where('receta_id', $recetaId)
            ->whereNotNull('unidades_producidas')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();
        $unidadesHorneadas = (int) $preparaciones->sum(
            fn ($preparacion) => $preparacion->unidades_horneadas ?? $preparacion->unidades_producidas
        );

        if ($preparaciones->isEmpty() || $unidadesHorneadas < 1) {
            throw ValidationException::withMessages([
                'recipe_id' => 'Procesa la receta y registra el horneado para calcular el costo unitario.',
            ]);
        }

        $costoTotal = $preparaciones->sum(
            fn ($preparacion) => $this->calcularCostoPreparacion($preparacion)
        );

        return round($costoTotal / $unidadesHorneadas, 2);
    }

    public function calcularCostoPreparacion(Preparacion $preparacion): float
    {
        $preparacion->loadMissing('proporciones.ingrediente.detallefacturas');
        $unidades = app(UnidadIngredienteService::class);
        $costoTotal = 0.0;

        foreach ($preparacion->proporciones as $proporcion) {
            $ingrediente = $proporcion->ingrediente;
            $compras = $ingrediente?->detallefacturas ?? collect();

            if (!$ingrediente || $compras->isEmpty()) {
                throw ValidationException::withMessages([
                    'horneado' => 'Registra una compra para cada ingrediente antes de calcular el costo.',
                ]);
            }

            $unidadCompra = $ingrediente->medida;
            $cantidadCompradaBase = $compras->sum(
                fn ($detalle) => $unidades->convertirABase((float) $detalle->cantidad, $unidadCompra)['cantidad']
            );
            $costoCompras = $compras->sum(
                fn ($detalle) => (float) $detalle->cantidad * (float) $detalle->valorunitario
            );

            if ($cantidadCompradaBase <= 0) {
                throw ValidationException::withMessages([
                    'horneado' => "No hay cantidad comprada registrada para {$ingrediente->nombre}.",
                ]);
            }

            $unidadConsumo = $proporcion->medida ?: $unidadCompra;
            $consumoBase = $unidades->convertirABase((float) $proporcion->unidades, $unidadConsumo);
            $unidadInventario = $unidades->convertirABase(1, $unidadCompra);

            if ($consumoBase['tipo'] !== $unidadInventario['tipo']) {
                throw ValidationException::withMessages([
                    'horneado' => "La unidad de {$ingrediente->nombre} no coincide con su inventario.",
                ]);
            }

            $costoTotal += ($costoCompras / $cantidadCompradaBase) * $consumoBase['cantidad'];
        }

        return round($costoTotal, 2);
    }

    public function calcularCostoPreparacion(Preparacion $preparacion): float
    {
        $preparacion->loadMissing('proporciones.ingrediente.detallefacturas');
        $unidades = app(UnidadIngredienteService::class);
        $costoTotal = 0.0;

        foreach ($preparacion->proporciones as $proporcion) {
            $ingrediente = $proporcion->ingrediente;
            $compras = $ingrediente?->detallefacturas ?? collect();

            if (!$ingrediente || $compras->isEmpty()) {
                throw ValidationException::withMessages([
                    'horneado' => 'Registra una compra para cada ingrediente antes de calcular el costo.',
                ]);
            }

            $unidadCompra = $ingrediente->medida;
            $cantidadCompradaBase = $compras->sum(
                fn ($detalle) => $unidades->convertirABase((float) $detalle->cantidad, $unidadCompra)['cantidad']
            );
            $costoCompras = $compras->sum(
                fn ($detalle) => (float) $detalle->cantidad * (float) $detalle->valorunitario
            );

            if ($cantidadCompradaBase <= 0) {
                throw ValidationException::withMessages([
                    'horneado' => "No hay cantidad comprada registrada para {$ingrediente->nombre}.",
                ]);
            }

            $unidadConsumo = $proporcion->medida ?: $unidadCompra;
            $consumoBase = $unidades->convertirABase((float) $proporcion->unidades, $unidadConsumo);
            $unidadInventario = $unidades->convertirABase(1, $unidadCompra);

            if ($consumoBase['tipo'] !== $unidadInventario['tipo']) {
                throw ValidationException::withMessages([
                    'horneado' => "La unidad de {$ingrediente->nombre} no coincide con su inventario.",
                ]);
            }

            $costoTotal += ($costoCompras / $cantidadCompradaBase) * $consumoBase['cantidad'];
        }

        return round($costoTotal, 2);
    }
}