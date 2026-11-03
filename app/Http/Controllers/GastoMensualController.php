<?php

namespace App\Http\Controllers;

use App\Models\GastoMensual;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoMensualController extends Controller
{
    private const CATEGORIAS = [
        'agua' => 'Agua',
        'luz' => 'Luz',
        'gas' => 'Gas',
        'otro' => 'Otro',
    ];

    public function index(Request $request): View
    {
        $mes = $request->query('mes');
        if (!is_string($mes)
            || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)
            || !checkdate((int) substr($mes, 5, 2), 1, (int) substr($mes, 0, 4))) {
            $mes = now()->format('Y-m');
        }

        $inicioMes = Carbon::createFromFormat('!Y-m', $mes);
        $periodo = $inicioMes->toDateString();
        $fechaNueva = $inicioMes->copy()->day(min(now()->day, $inicioMes->daysInMonth));
        $gastos = GastoMensual::whereDate('mes', $periodo)
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
        $totalesPorCategoria = collect(self::CATEGORIAS)->mapWithKeys(
            fn ($nombre, $categoria) => [$categoria => $gastos->where('categoria', $categoria)->sum('monto')]
        );

        return view('gastos.index', [
            'categorias' => self::CATEGORIAS,
            'gastos' => $gastos,
            'mes' => $mes,
            'periodo' => $periodo,
            'fechaNueva' => $fechaNueva->toDateString(),
            'fechaMinima' => $inicioMes->toDateString(),
            'fechaMaxima' => $inicioMes->copy()->endOfMonth()->toDateString(),
            'total' => $gastos->sum('monto'),
            'totalesPorCategoria' => $totalesPorCategoria,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'categoria' => ['required', 'in:agua,luz,gas,otro'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ]);

        $fecha = Carbon::createFromFormat('!Y-m-d', $validated['fecha']);
        $validated['fecha'] = $fecha->toDateString();
        $validated['mes'] = $fecha->copy()->startOfMonth()->toDateString();
        $gasto = GastoMensual::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Gasto registrado correctamente.',
                'gasto' => [
                    'id' => $gasto->id,
                    'mes' => $gasto->mes->format('Y-m'),
                    'mes_label' => $gasto->mes->format('m/Y'),
                    'fecha' => $gasto->fecha->toDateString(),
                    'fecha_label' => $gasto->fecha->format('d/m/Y'),
                    'categoria' => $gasto->categoria,
                    'categoria_nombre' => self::CATEGORIAS[$gasto->categoria],
                    'descripcion' => $gasto->descripcion,
                    'monto' => (float) $gasto->monto,
                    'delete_url' => route('gastos.destroy', $gasto),
                ],
            ], 201);
        }

        return redirect()
            ->route('gastos.index', ['mes' => substr($validated['mes'], 0, 7)])
            ->with('success', 'Gasto registrado correctamente.');
    }

    public function destroy(GastoMensual $gastoMensual): RedirectResponse|JsonResponse
    {
        $mes = $gastoMensual->mes->format('Y-m');
        $gasto = [
            'id' => $gastoMensual->id,
            'categoria' => $gastoMensual->categoria,
            'monto' => (float) $gastoMensual->monto,
        ];
        $gastoMensual->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Registro eliminado.',
                'gasto' => $gasto,
            ]);
        }

        return redirect()
            ->route('gastos.index', ['mes' => $mes])
            ->with('success', 'Registro eliminado.');
    }
}