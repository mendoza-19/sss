<?php

namespace App\Http\Controllers;

use App\Models\Detalleproducto;
use App\Models\Preparacion;
use App\Models\Ventaambulante;
use Illuminate\Support\Carbon;

class MermaController extends Controller
{
    public function index()
    {
        $movimientos = collect();

        Preparacion::with('receta')
            ->where('unidades_quemadas', '>', 0)
            ->orderByDesc('fecha')
            ->get()
            ->each(function ($preparacion) use ($movimientos) {
                $costoUnitario = $preparacion->unidades_horneadas > 0 && $preparacion->costo_total !== null
                    ? (float) $preparacion->costo_total / (int) $preparacion->unidades_horneadas
                    : null;

                $movimientos->push([
                    'fecha' => $preparacion->fecha,
                    'tipo' => 'Quemado',
                    'articulo' => $preparacion->receta?->nombre ?? 'Receta eliminada',
                    'cantidad' => (int) $preparacion->unidades_quemadas,
                    'costo_perdida' => $costoUnitario !== null
                        ? round($costoUnitario * (int) $preparacion->unidades_quemadas, 2)
                        : null,
                    'origen' => 'Horneado',
                    'grupo' => 'receta-' . $preparacion->receta_id,
                    'costo_unitario' => $costoUnitario,
                ]);
            });

        Detalleproducto::with(['producto', 'venta.ventaambulante.empleado'])
            ->whereHas('venta')
            ->where(function ($query) {
                $query->where('unidadesdevueltas', '>', 0)
                    ->orWhere('unidadesperdidas', '>', 0);
            })
            ->get()
            ->each(function ($detalle) use ($movimientos) {
                $venta = $detalle->venta;
                $vendedor = $venta?->ventaambulante?->empleado?->nombreCompleto;
                $origen = $vendedor ? "Venta ambulante · {$vendedor}" : 'Venta';
                $fecha = $venta?->emision ?? $detalle->updated_at;
                $costoUnitario = $detalle->producto?->costo !== null
                    ? (float) $detalle->producto->costo
                    : null;

                foreach ([
                    'Devuelto' => (int) $detalle->unidadesdevueltas,
                    'Pérdida de venta' => (int) $detalle->unidadesperdidas,
                ] as $tipo => $cantidad) {
                    if ($cantidad < 1) {
                        continue;
                    }

                    $movimientos->push([
                        'fecha' => $fecha,
                        'tipo' => $tipo,
                        'articulo' => $detalle->producto?->nombre ?? 'Producto eliminado',
                        'cantidad' => $cantidad,
                        'costo_perdida' => $tipo === 'Pérdida de venta' && $costoUnitario !== null
                            ? round($costoUnitario * $cantidad, 2)
                            : null,
                        'origen' => $origen,
                    ]);
                }
            });

        Ventaambulante::with(['venta', 'empleado'])
            ->where(function ($query) {
                $query->where('unidadesdevueltas', '>', 0)
                    ->orWhere('unidadesperdidas', '>', 0);
            })
            ->whereDoesntHave('venta.detalleproductos', function ($query) {
                $query->where(function ($detailQuery) {
                    $detailQuery->where('unidadesdevueltas', '>', 0)
                        ->orWhere('unidadesperdidas', '>', 0);
                });
            })
            ->get()
            ->each(function ($asignacion) use ($movimientos) {
                $fecha = $asignacion->venta?->emision ?? $asignacion->created_at;
                $vendedor = $asignacion->empleado?->nombreCompleto ?? 'Vendedor';

                foreach ([
                    'Devuelto' => (int) $asignacion->unidadesdevueltas,
                    'Pérdida de venta' => (int) $asignacion->unidadesperdidas,
                ] as $tipo => $cantidad) {
                    if ($cantidad < 1) {
                        continue;
                    }

                    $movimientos->push([
                        'fecha' => $fecha,
                        'tipo' => $tipo,
                        'articulo' => 'Asignación ambulante',
                        'cantidad' => $cantidad,
                        'costo_perdida' => null,
                        'origen' => "Venta ambulante · {$vendedor}",
                    ]);
                }
            });

        $movimientos = $movimientos
            ->sortByDesc(fn ($movimiento) => Carbon::parse($movimiento['fecha'])->timestamp)
            ->groupBy(fn ($movimiento, $key) => $movimiento['tipo'] === 'Quemado'
                ? $movimiento['grupo']
                : 'movimiento-' . $key)
            ->map(function ($detalles) {
                $movimiento = $detalles->first();
                $movimiento['cantidad'] = $detalles->sum('cantidad');
                $movimiento['costo_perdida'] = $detalles->every(
                    fn ($detalle) => $detalle['costo_perdida'] === null
                )
                    ? null
                    : round($detalles->sum(fn ($detalle) => (float) ($detalle['costo_perdida'] ?? 0)), 2);
                $movimiento['detalles'] = $detalles->map(fn ($detalle) => [
                    'fecha' => $detalle['fecha'],
                    'cantidad' => $detalle['cantidad'],
                    'origen' => $detalle['origen'],
                    'costo_perdida' => $detalle['costo_perdida'],
                    'costo_unitario' => $detalle['costo_unitario'] ?? null,
                ])->values();
                unset($movimiento['grupo']);

                return $movimiento;
            })
            ->sortByDesc(fn ($movimiento) => Carbon::parse($movimiento['fecha'])->timestamp)
            ->values();

        $resumen = [
            'Quemado' => $movimientos->where('tipo', 'Quemado')->sum('cantidad'),
            'Devuelto' => $movimientos->where('tipo', 'Devuelto')->sum('cantidad'),
            'Pérdida de venta' => $movimientos->where('tipo', 'Pérdida de venta')->sum('cantidad'),
            'Costo de pérdida' => round($movimientos->sum(fn ($movimiento) => (float) ($movimiento['costo_perdida'] ?? 0)), 2),
        ];

        return view('mermas.index', compact('movimientos', 'resumen'));
    }
}