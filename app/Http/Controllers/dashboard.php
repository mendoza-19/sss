<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class dashboard extends Controller
{
    public function index()
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        $descripcionRol = strtolower(trim((string) ($user->empleado?->rol?->descripcion ?? '')));

        if ($descripcionRol === 'vendedor') {
            return redirect()->route('ventas.index');
        }

        // Verificar si es cliente
        if ($user->iscustomer) {
            // Si es cliente, redirigirlo al menú
            return redirect()->route('menu');
        }

        // Si no es cliente (es admin o empleado), mostrar el dashboard
        return view('dashboard');
    }

    public function ventasPorMesChart(Request $request){
        try{
            $inicioAnio = now()->startOfYear()->toDateString();
            $finAnio = now()->endOfYear()->toDateString();
            $ventas = DB::table('ventas')
                ->select('emision', 'total')
                ->whereBetween('emision', [$inicioAnio, $finAnio])
                ->get();
            $abonos = DB::table('abonos')
                ->select('fechaabono', 'cantidadabonada')
                ->whereBetween('fechaabono', [$inicioAnio, $finAnio])
                ->get();

            $ventasPorMes = collect(range(1, 12))
                ->map(fn ($mes) => [
                    'mes' => $mes,
                    'ventas' => round((float) $ventas
                        ->filter(fn ($venta) => Carbon::parse($venta->emision)->month === $mes)
                        ->sum('total'), 2),
                    'cobros' => round((float) $abonos
                        ->filter(fn ($abono) => Carbon::parse($abono->fechaabono)->month === $mes)
                        ->sum('cantidadabonada') / 100, 2),
                ])
                ->values();

            return response()->json(['success' => true, 'response' => $ventasPorMes]);
        }catch(Exception $e){
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function produccionPorMes(Request $request)
    {
        $inicioAnio = now()->startOfYear()->toDateString();
        $finAnio = now()->endOfYear()->toDateString();
        $preparaciones = DB::table('preparaciones')
            ->select('fecha', 'unidades_producidas', 'unidades_quemadas')
            ->whereBetween('fecha', [$inicioAnio, $finAnio])
            ->get();

        $produccionPorMes = collect(range(1, 12))
            ->map(function ($mes) use ($preparaciones) {
                $preparacionesDelMes = $preparaciones->filter(
                    fn ($preparacion) => Carbon::parse($preparacion->fecha)->month === $mes
                );

                return [
                    'mes' => $mes,
                    'buenas' => (int) $preparacionesDelMes->sum('unidades_producidas'),
                    'quemadas' => (int) $preparacionesDelMes->sum('unidades_quemadas'),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'response' => $produccionPorMes]);
    }

    public function desgloseMensual(Request $request)
    {
        $validated = $request->validate([
            'mes' => ['nullable', 'date_format:Y-m'],
        ]);
        $mes = $validated['mes'] ?? now()->format('Y-m');
        $inicioMes = Carbon::createFromFormat('!Y-m', $mes);
        $finMes = $inicioMes->copy()->endOfMonth();
        $rango = [$inicioMes->toDateString(), $finMes->toDateString()];

        $ventas = (float) DB::table('ventas')
            ->whereBetween('emision', $rango)
            ->sum('total');

        $costoProductos = (float) DB::table('preparaciones')
            ->whereBetween('fecha', $rango)
            ->sum('costo_total');

        $otrosGastos = (float) DB::table('gastos_mensuales')
            ->whereDate('mes', $inicioMes->toDateString())
            ->sum('monto');
        $saldoEstimado = $ventas - $costoProductos - $otrosGastos;

        return response()->json([
            'success' => true,
            'response' => [
                'mes' => $mes,
                'ventas' => round($ventas, 2),
                'costo_productos' => round($costoProductos, 2),
                'otros_gastos' => round($otrosGastos, 2),
                'saldo_estimado' => round(max(0, $saldoEstimado), 2),
                'deficit_estimado' => round(max(0, -$saldoEstimado), 2),
            ],
        ]);
    }


    public function analitica(Request $request)
    {
        $modo = $request->query('modo', 'mes');
        $periodoRule = $modo === 'dia'
            ? ['required', 'date_format:Y-m']
            : ['required', 'digits:4', 'integer', 'min:2000', 'max:2100'];
        $validated = $request->validate([
            'modo' => ['required', 'in:mes,dia'],
            'periodo' => $periodoRule,
        ]);

        $modo = $validated['modo'];
        $periodo = $validated['periodo'];
        $inicio = $modo === 'mes'
            ? Carbon::createFromFormat('!Y', $periodo)
            : Carbon::createFromFormat('!Y-m', $periodo);
        $fin = $modo === 'mes' ? $inicio->copy()->endOfYear() : $inicio->copy()->endOfMonth();
        $rango = [$inicio->toDateString(), $fin->toDateString()];
        $nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $labels = $modo === 'mes' ? $nombresMeses : array_map('strval', range(1, $inicio->daysInMonth));
        $serie = array_map(fn ($label) => [
            'label' => $label,
            'ventas' => 0.0,
            'cobros' => 0.0,
            'buenas' => 0,
            'quemadas' => 0,
        ], $labels);
        $indicePeriodo = fn ($fecha) => $modo === 'mes'
            ? Carbon::parse($fecha)->month - 1
            : Carbon::parse($fecha)->day - 1;

        $ventas = DB::table('ventas')
            ->select('emision', 'total')
            ->whereBetween('emision', $rango)
            ->get();
        foreach ($ventas as $venta) {
            $indice = $indicePeriodo($venta->emision);
            $serie[$indice]['ventas'] += (float) $venta->total;
        }

        $abonos = DB::table('abonos')
            ->select('fechaabono', 'cantidadabonada')
            ->whereBetween('fechaabono', $rango)
            ->get();
        foreach ($abonos as $abono) {
            $indice = $indicePeriodo($abono->fechaabono);
            $serie[$indice]['cobros'] += (float) $abono->cantidadabonada / 100;
        }

        $preparaciones = DB::table('preparaciones')
            ->select('fecha', 'unidades_producidas', 'unidades_quemadas', 'costo_total')
            ->whereBetween('fecha', $rango)
            ->get();
        foreach ($preparaciones as $preparacion) {
            $indice = $indicePeriodo($preparacion->fecha);
            $serie[$indice]['buenas'] += (int) ($preparacion->unidades_producidas ?? 0);
            $serie[$indice]['quemadas'] += (int) ($preparacion->unidades_quemadas ?? 0);
        }

        foreach ($serie as &$punto) {
            $punto['ventas'] = round($punto['ventas'], 2);
            $punto['cobros'] = round($punto['cobros'], 2);
        }
        unset($punto);

        $campoFechaGasto = $modo === 'mes' ? 'mes' : 'fecha';
        $gastos = DB::table('gastos_mensuales')
            ->whereBetween($campoFechaGasto, $rango)
            ->get(['monto']);
        $ventasTotales = (float) collect($serie)->sum('ventas');
        $cobrosTotales = (float) collect($serie)->sum('cobros');
        $costoProduccion = (float) $preparaciones->sum('costo_total');
        $otrosGastos = (float) $gastos->sum('monto');
        $saldo = $ventasTotales - $costoProduccion - $otrosGastos;

        $productosQuery = DB::table('productos')
            ->join('detalleproductos', 'productos.idproducto', '=', 'detalleproductos.producto_id')
            ->join('ventas', 'detalleproductos.venta_id', '=', 'ventas.idventa')
            ->whereNotNull('ventas.fechapago')
            ->whereBetween('ventas.emision', $rango);
        $unidadesVendidas = (int) (clone $productosQuery)->sum('detalleproductos.cantidad');
        $productos = $productosQuery
            ->select('productos.nombre', DB::raw('SUM(detalleproductos.cantidad) as total_vendidos'))
            ->groupBy('productos.idproducto', 'productos.nombre')
            ->orderByDesc('total_vendidos')
            ->limit(7)
            ->get();

        return response()->json([
            'success' => true,
            'response' => [
                'modo' => $modo,
                'periodo' => $periodo,
                'periodo_label' => $modo === 'mes' ? $periodo : $inicio->translatedFormat('F Y'),
                'serie' => $serie,
                'productos' => $productos,
                'unidades_vendidas' => $unidadesVendidas,
                'ventas_totales' => round($ventasTotales, 2),
                'cobros_totales' => round($cobrosTotales, 2),
                'costo_produccion' => round($costoProduccion, 2),
                'otros_gastos' => round($otrosGastos, 2),
                'saldo' => round(max(0, $saldo), 2),
                'deficit' => round(max(0, -$saldo), 2),
            ],
        ]);
    }

    public function totalProductosVendidos(Request $request)
    {
        try {
            $productosVendidos = DB::table('ventas')
                ->select(DB::raw('sum(d.cantidad) as total_vendidos'))
                ->join('detalleproductos as d', 'ventas.idventa', '=', 'd.venta_id')
                ->whereBetween('ventas.emision', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->whereNotNull('ventas.fechapago')
                ->first();
            return response()->json(['success' => true, 'response' => $productosVendidos]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function gananciasDelDia(Request $request)
    {
        try {
            $gananciasDelDia = DB::table('abonos')
                ->selectRaw('COALESCE(SUM(abonos.cantidadabonada), 0) / 100.0 as total_ganancias')
                ->whereDate('created_at', now()->toDateString())
                ->first();
            return response()->json(['success' => true, 'response' => $gananciasDelDia]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function totalPorCobrar(Request $request)
    {
        try {
            $abonosPorVenta = DB::table('abonos')
                ->select('venta_id')
                ->selectRaw('SUM(cantidadabonada) as total_abonado')
                ->groupBy('venta_id');

            $saldoVentas = DB::table('ventas as v')
                ->leftJoinSub($abonosPorVenta, 'a', 'a.venta_id', '=', 'v.idventa')
                ->whereNull('v.fechapago')
                ->selectRaw('COALESCE(SUM(CASE WHEN v.total > COALESCE(a.total_abonado, 0) / 100.0 THEN v.total - COALESCE(a.total_abonado, 0) / 100.0 ELSE 0 END), 0) as saldo')
                ->value('saldo');

            $saldoVentasAmbulantes = DB::table('ventas as v')
                ->leftJoinSub($abonosPorVenta, 'a', 'a.venta_id', '=', 'v.idventa')
                ->whereNull('v.fechapago')
                ->whereNotNull('v.ventaambulante_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN v.total > COALESCE(a.total_abonado, 0) / 100.0 THEN v.total - COALESCE(a.total_abonado, 0) / 100.0 ELSE 0 END), 0) as saldo')
                ->value('saldo');

            $saldoVentasClientes = DB::table('ventas as v')
                ->leftJoinSub($abonosPorVenta, 'a', 'a.venta_id', '=', 'v.idventa')
                ->whereNull('v.fechapago')
                ->whereNull('v.ventaambulante_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN v.total > COALESCE(a.total_abonado, 0) / 100.0 THEN v.total - COALESCE(a.total_abonado, 0) / 100.0 ELSE 0 END), 0) as saldo')
                ->value('saldo');

            $saldoPedidosClientes = DB::table('pedidos as p')
                ->join('detalleproductos as d', 'd.pedido_id', '=', 'p.idpedido')
                ->where('p.estado_pago', 'pendiente')
                ->whereNull('d.venta_id')
                ->sum('d.subtotal');

            $clientes = round((float) $saldoVentasClientes + (float) $saldoPedidosClientes, 2);
            $ventasAmbulantes = round((float) $saldoVentasAmbulantes, 2);

            return response()->json([
                'success' => true,
                'response' => [
                    'total_cobrar' => round((float) $saldoVentas + (float) $saldoPedidosClientes, 2),
                    'clientes' => $clientes,
                    'ventas_ambulantes' => $ventasAmbulantes,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function productosMasVendidos(Request $request)
    {
        try {
            $productosMasVendidos = DB::table('productos')
                ->select('productos.nombre as nombre', DB::raw('sum(detalleproductos.cantidad) as total_vendidos'))
                ->join('detalleproductos', 'productos.idproducto', '=', 'detalleproductos.producto_id')
                ->join('ventas', 'detalleproductos.venta_id', '=', 'ventas.idventa')
                ->whereNotNull('ventas.fechapago')
                ->whereBetween('ventas.emision', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->groupBy('productos.nombre')
                ->orderByDesc('total_vendidos')
                ->limit(5)
                ->get();

            return response()->json(['success' => true, 'response' => $productosMasVendidos]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
