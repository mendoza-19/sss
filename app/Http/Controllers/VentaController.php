<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Venta;
use App\Models\Detalleproducto;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Incidencia;
use App\Models\Oferta;
use App\Models\Producto;
use App\Models\Ventaambulante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    public function crearClienteLocal(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'apellido' => ['nullable', 'string', 'max:60'],
            'telefono' => ['nullable', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
        ]);

        $cliente = Cliente::create([
            'nombre' => trim($validated['nombre']),
            'apellido' => trim($validated['apellido'] ?? ''),
            'telefono' => $validated['telefono'] ?? null,
            'fecharegistro' => today(),
            'user_id' => null,
        ]);

        return response()->json([
            'cliente' => [
                'id' => $cliente->idcliente,
                'nombre' => trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')),
                'telefono' => $cliente->telefono,
            ],
        ], 201);
    }

    private function sellerProductLines(?Venta $venta): array
    {
        return $venta?->detalleproductos->map(function (Detalleproducto $detalle) {
            $assigned = (int) $detalle->cantidad;
            $isClosed = $detalle->unidadesdevueltas !== null && $detalle->unidadesperdidas !== null;
            $returned = $isClosed ? (int) $detalle->unidadesdevueltas : null;
            $lost = $isClosed ? (int) $detalle->unidadesperdidas : null;
            $sold = $isClosed ? max(0, $assigned - $returned - $lost) : null;
            $subtotal = (float) $detalle->subtotal;

            return [
                'id' => (int) $detalle->iddetalleproducto,
                'producto_id' => $detalle->producto_id,
                'nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                'asignadas' => $assigned,
                'cantidad' => $assigned,
                'devueltas' => $returned,
                'perdidas' => $lost,
                'vendidas' => $sold,
                'subtotal' => $subtotal,
                'subtotal_vendido' => $isClosed && $assigned > 0
                    ? round($subtotal * $sold / $assigned, 2)
                    : $subtotal,
            ];
        })->values()->all() ?? [];
    }

    private function resolvePrecioUnitario(Producto $producto, $empleado): float
    {
        $esVentaVendedor = false;

        if ($empleado && method_exists($empleado, 'rol')) {
            $empleado->loadMissing('rol');
            $descripcionRol = strtolower(trim((string) ($empleado->rol?->descripcion ?? '')));
            $esVentaVendedor = $descripcionRol === 'vendedor';
        }

        $esLata = stripos((string) $producto->nombre, 'lata') !== false;

        return ($esVentaVendedor && $esLata) ? 1.00 : (float) $producto->precio;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $empleadoActual = $request->user()->empleado()->with('rol')->first();
        $usuarioEsVendedor = strtolower(trim((string) ($empleadoActual?->rol?->descripcion ?? ''))) === 'vendedor';

        if ($usuarioEsVendedor) {
            $asignaciones = Ventaambulante::with(['incidencias', 'venta.detalleproductos.producto'])
                ->where('empleado_id', $empleadoActual->idempleado)
                ->orderByDesc('created_at')
                ->get();
            $asignacionesNuevas = $asignaciones->filter(
                fn ($asignacion) => $asignacion->unidadesdevueltas === null || $asignacion->unidadesperdidas === null
            )->values();
            $asignacionesRealizadas = $asignaciones->reject(
                fn ($asignacion) => $asignacion->unidadesdevueltas === null || $asignacion->unidadesperdidas === null
            )->values();
            $pestanaInicial = $asignacionesNuevas->isNotEmpty() ? 'nuevas' : 'realizadas';

            return response()
                ->view('ventas.vendedor', compact(
                    'asignaciones',
                    'asignacionesNuevas',
                    'asignacionesRealizadas',
                    'pestanaInicial'
                ))
                ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        }

        $ventas = Venta::whereNull('ventaambulante_id')
            ->whereDoesntHave('detalleproductos', fn ($query) => $query->whereNotNull('pedido_id'))
            ->with(['empleado', 'detalleproductos.producto', 'detalleproductos.pedido.cliente.user'])
            ->orderByDesc('idventa')
            ->get();

        $ventas->each(function (Venta $venta) {
            $pedido = $venta->detalleproductos->first(fn ($detalle) => $detalle->pedido !== null)?->pedido;
            $pedidoId = $pedido?->idpedido;
            $clienteNombre = $pedido?->cliente
                ? trim(($pedido->cliente->nombre ?? '') . ' ' . ($pedido->cliente->apellido ?? ''))
                : null;

            $venta->setAttribute('pedido_id', $pedidoId);
            $venta->setAttribute('cliente_nombre', $clienteNombre);
            $venta->setAttribute(
                'origen_texto',
                $pedidoId ? "Pedido #{$pedidoId}" . ($clienteNombre ? " ({$clienteNombre})" : '') : 'Venta local'
            );

            $venta->setAttribute(
                'descripcion',
                $pedidoId ? 'Pedido ' . $pedidoId : 'Venta ' . $venta->idventa
            );

            $subtotalBruto = $venta->detalleproductos->sum(
                fn ($detalle) => (float) $detalle->subtotal + (float) ($detalle->descuento_aplicado ?? 0)
            );
            $descuentoPorLinea = $venta->detalleproductos->sum('descuento_aplicado');
            $descuentoGeneral = $pedidoId
                ? 0
                : round($subtotalBruto * (int) $venta->descuento / 100, 2);

            $venta->setAttribute('detalle_subtotal_bruto', round($subtotalBruto, 2));
            $venta->setAttribute('detalle_descuento_total', round($descuentoPorLinea + $descuentoGeneral, 2));
            $venta->setAttribute(
                'detalle_items_json',
                $venta->detalleproductos->map(function ($detalle) {
                    return [
                        'id' => $detalle->iddetalleproducto,
                        'nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                        'cantidad' => $detalle->cantidad,
                        'subtotal' => $detalle->subtotal,
                        'descuento_aplicado' => $detalle->descuento_aplicado ?? 0,
                        'producto_id' => $detalle->producto_id,
                        'venta_id' => $detalle->venta_id,
                        'created_at' => $detalle->created_at?->format('d/m/Y H:i'),
                        'updated_at' => $detalle->updated_at?->format('d/m/Y H:i'),
                    ];
                })->values()->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)
            );
        });

        $productos = Producto::where('visible', true)
            ->where('stock', '>', 0)
            ->whereRaw('LOWER(TRIM(estado)) IN (?, ?)', ['available', 'disponible'])
            ->orderBy('nombre')
            ->get();
        $productosParaEncargo = Producto::where('visible', true)
            ->orderBy('nombre')
            ->get();
        $clientesLocales = Cliente::orderBy('nombre')
            ->orderBy('apellido')
            ->get(['idcliente', 'nombre', 'apellido', 'telefono']);
        $pedidosPendientes = Pedido::with(['cliente', 'detalles.producto', 'detalles.venta.abonos'])
            ->where('estado', 'pendiente')
            ->whereHas('detalles')
            ->whereDoesntHave('detalles.venta', fn ($query) => $query->whereNotNull('fechapago'))
            ->orderBy('created_at')
            ->get();

        $productoIdsPedido = $pedidosPendientes
            ->flatMap(fn ($pedido) => $pedido->detalles->pluck('producto_id'))
            ->unique()
            ->values();
        $ofertasPedidos = $pedidosPendientes->isEmpty() || $productoIdsPedido->isEmpty()
            ? collect()
            : Oferta::whereIn('cliente_id', $pedidosPendientes->pluck('cliente_id')->unique())
                ->whereIn('producto', $productoIdsPedido)
                ->whereDate('fechainicio', '<=', today())
                ->whereDate('fechafin', '>=', today())
                ->orderByDesc('descuento')
                ->get()
                ->groupBy('cliente_id')
                ->map(fn ($ofertasCliente) => $ofertasCliente->groupBy('producto')->map(fn ($ofertasProducto) => $ofertasProducto->first()));

        $pedidosPendientes->each(function ($pedido) use ($ofertasPedidos) {
            $clienteNombre = trim(($pedido->cliente?->nombre ?? '') . ' ' . ($pedido->cliente?->apellido ?? ''));
            $ofertasCliente = $ofertasPedidos->get($pedido->cliente_id, collect());
            $items = $pedido->detalles->map(function ($detalle) use ($ofertasCliente) {
                $precio = (float) ($detalle->producto?->precio ?? 0);
                $cantidad = (int) $detalle->cantidad;
                $descuentoOferta = (int) ($ofertasCliente->get($detalle->producto_id)?->descuento ?? 0);

                return [
                    'nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'descuento_oferta' => $descuentoOferta,
                    'subtotal' => round($precio * $cantidad, 2),
                ];
            })->values();

            $pedido->setAttribute('cliente_nombre', $clienteNombre !== '' ? $clienteNombre : 'Cliente');
            $pedido->setAttribute('items_json', $items->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP));
            $pedido->setAttribute('subtotal_estimado', round($items->sum('subtotal'), 2));
            $abonoCentavos = $pedido->detalles
                ->pluck('venta')
                ->filter()
                ->unique('idventa')
                ->sum(fn ($venta) => $venta->abonos->sum('cantidadabonada'));
            $pedido->setAttribute('abono_aplicado', round($abonoCentavos / 100, 2));
        });

        $pedidosProcesados = Pedido::with(['cliente', 'detalles.producto', 'detalles.venta'])
            ->whereRaw('LOWER(TRIM(estado)) <> ?', ['pendiente'])
            ->whereHas('detalles', fn ($query) => $query->whereNotNull('venta_id'))
            ->orderByDesc('updated_at')
            ->orderByDesc('idpedido')
            ->get();

        $pedidosProcesados->each(function ($pedido) {
            $clienteNombre = trim(($pedido->cliente?->nombre ?? '') . ' ' . ($pedido->cliente?->apellido ?? ''));
            $items = $pedido->detalles->map(function ($detalle) {
                return [
                    'nombre' => $detalle->producto?->nombre ?? 'Producto eliminado',
                    'cantidad' => (int) $detalle->cantidad,
                    'subtotal' => (float) $detalle->subtotal,
                ];
            })->values();
            $venta = $pedido->detalles->first(fn ($detalle) => $detalle->venta !== null)?->venta;

            $pedido->setAttribute('cliente_nombre', $clienteNombre !== '' ? $clienteNombre : 'Cliente');
            $pedido->setAttribute('items_json', $items->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP));
            $pedido->setAttribute('total_procesado', round((float) ($venta?->total ?? $items->sum('subtotal')), 2));
            $pedido->setAttribute('fecha_procesado', $venta?->emision ?? $pedido->updated_at?->toDateString());
        });

        $claveCliente = fn ($pedido) => $pedido->cliente_id
            ? 'cliente-' . $pedido->cliente_id
            : 'pedido-' . $pedido->idpedido;
        $historialesPorCliente = $pedidosProcesados->groupBy($claveCliente)->map(function ($pedidos) {
            $historial = $pedidos->map(function ($pedido) {
                return [
                    'id' => $pedido->idpedido,
                    'fecha' => $pedido->fecha_procesado,
                    'estado' => $pedido->estado_pago === 'pagada' ? 'Pagado' : ucfirst($pedido->estado_pago ?? $pedido->estado),
                    'total' => $pedido->total_procesado,
                    'productos' => json_decode($pedido->items_json, true) ?? [],
                ];
            })->values();

            return [
                'count' => $historial->count(),
                'items' => $historial->toJson(JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP),
            ];
        });

        $pedidosProcesados->each(function ($pedido) use ($historialesPorCliente, $claveCliente) {
            $historialCliente = $historialesPorCliente->get($claveCliente($pedido));
            $pedido->setAttribute('cliente_historial_count', $historialCliente['count'] ?? 0);
            $pedido->setAttribute('cliente_historial_json', $historialCliente['items'] ?? '[]');
        });
        $pedidosProcesados = $pedidosProcesados->unique($claveCliente)->values();

        $vendedores = Empleado::with('rol')
            ->whereHas('rol', fn ($query) => $query->whereRaw('LOWER(TRIM(descripcion)) = ?', ['vendedor']))
            ->orderBy('nombreCompleto')
            ->get();

        $ventasPorVendedor = Ventaambulante::with([
            'empleado.usuario',
            'empleado.rol',
            'incidencias',
            'venta.detalleproductos.producto',
        ])
            ->whereHas('empleado.rol', fn ($query) => $query->whereRaw('LOWER(TRIM(descripcion)) = ?', ['vendedor']))
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($asignacionesDelEmpleado) {
                $empleado = $asignacionesDelEmpleado->first()->empleado;
                $ventasDelEmpleado = $asignacionesDelEmpleado->map(fn ($asignacion) => $asignacion->venta)->filter();

                return [
                    'id' => $empleado?->idempleado ?? 0,
                    'nombre' => $empleado?->nombreCompleto ?? $empleado?->usuario?->name ?? 'Empleado sin nombre',
                    'ventas' => $asignacionesDelEmpleado->count(),
                    'total' => round($ventasDelEmpleado->sum('total'), 2),
                    'ultima_venta' => $ventasDelEmpleado->max('emision')
                        ?? $asignacionesDelEmpleado->max('created_at'),
                    'registros' => $asignacionesDelEmpleado->map(function (Ventaambulante $asignacion) {
                        $venta = $asignacion->venta;
                        $cerrada = $asignacion->unidadesdevueltas !== null
                            && $asignacion->unidadesperdidas !== null;

                        return [
                            'fecha' => $venta?->emision ?? $asignacion->created_at?->toDateString(),
                            'unidades_asignadas' => $asignacion->unidadesemitidas,
                            'unidades_vendidas' => $cerrada
                                ? $asignacion->unidadesemitidas - $asignacion->unidadesdevueltas - $asignacion->unidadesperdidas
                                : null,
                            'unidades_devueltas' => $asignacion->unidadesdevueltas,
                            'unidades_perdidas' => $asignacion->unidadesperdidas,
                            'total' => (float) ($venta?->total ?? 0),
                            'estado' => $cerrada ? 'Cerrada' : 'Pendiente',
                            'productos' => $this->sellerProductLines($venta),
                            'incidencias' => $asignacion->incidencias->map(fn (Incidencia $incidencia) => [
                                'detalle' => $incidencia->detalle,
                                'gravedad' => $incidencia->gravedad,
                            ])->values() ?? collect(),
                        ];
                    })->values(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return response()
            ->view('ventas.index', compact('ventas', 'productos', 'productosParaEncargo', 'clientesLocales', 'pedidosPendientes', 'pedidosProcesados', 'ventasPorVendedor', 'vendedores'))
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    public function historialVendedores()
    {
        $asignaciones = Ventaambulante::with([
            'empleado.usuario',
            'empleado.rol',
            'incidencias',
            'venta.detalleproductos.producto',
        ])
            ->whereHas('empleado.rol', fn ($query) => $query->whereRaw('LOWER(TRIM(descripcion)) = ?', ['vendedor']))
            ->orderByDesc('created_at')
            ->get();

        $vendedores = $asignaciones
            ->groupBy('empleado_id')
            ->map(function ($ventasDelVendedor) {
                $empleado = $ventasDelVendedor->first()->empleado;

                return [
                    'id' => $empleado?->idempleado,
                    'registros' => $ventasDelVendedor->map(function (Ventaambulante $asignacion) {
                        $venta = $asignacion->venta;
                        $cerrada = $asignacion->unidadesdevueltas !== null
                            && $asignacion->unidadesperdidas !== null;

                        return [
                            'fecha' => $venta?->emision ?? $asignacion->created_at?->toDateString(),
                            'unidades_asignadas' => (int) $asignacion->unidadesemitidas,
                            'unidades_vendidas' => $cerrada
                                ? (int) $asignacion->unidadesemitidas
                                    - (int) $asignacion->unidadesdevueltas
                                    - (int) $asignacion->unidadesperdidas
                                : null,
                            'unidades_devueltas' => $asignacion->unidadesdevueltas,
                            'unidades_perdidas' => $asignacion->unidadesperdidas,
                            'total' => (float) ($venta?->total ?? 0),
                            'productos' => $this->sellerProductLines($venta),
                            'incidencias' => $asignacion->incidencias->map(fn (Incidencia $incidencia) => [
                                'detalle' => $incidencia->detalle,
                                'gravedad' => $incidencia->gravedad,
                            ])->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json(['vendedores' => $vendedores])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'emision' => ['required', 'date'],
            'descuento' => ['required', 'integer', 'min:0', 'max:100'],
            'es_vendedor' => ['nullable', 'boolean'],
            'empleado_id' => ['required_if:es_vendedor,1', 'nullable', 'integer', 'exists:empleados,idempleado'],
            'latas' => ['nullable', 'integer', 'min:0'],
            'incidencia' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,idproducto'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $empleadoActual = $request->user()->empleado()->with('rol')->first();
        $usuarioEsVendedor = strtolower(trim((string) ($empleadoActual?->rol?->descripcion ?? ''))) === 'vendedor';
        $esVentaVendedor = $request->boolean('es_vendedor');
        $empleado = $usuarioEsVendedor
            ? $empleadoActual
            : ($esVentaVendedor
                ? Empleado::with('rol')->find($validated['empleado_id'])
                : ($empleadoActual ?? Empleado::with('rol')->find($validated['empleado_id'] ?? null)));

        if ($esVentaVendedor && !$empleado) {
            return redirect()->route('ventas.index')
                ->with('error', 'Selecciona un vendedor para la venta a vendedores.');
        }

        if ($esVentaVendedor
            && strtolower(trim((string) ($empleado->rol?->descripcion ?? ''))) !== 'vendedor') {
            throw ValidationException::withMessages([
                'empleado_id' => 'Solo puedes asignar panes a un empleado con rol de vendedor.',
            ]);
        }

        if (!$empleado) {
            return redirect()->route('ventas.index')
                ->with('error', 'El usuario actual no tiene un empleado asociado.');
        }

        $latas = (int) ($validated['latas'] ?? 0);
        $panesEquivalentes = $esVentaVendedor
            ? array_sum(array_map(fn ($item) => (int) $item['cantidad'], $validated['items']))
            : $latas * 24;

        DB::transaction(function () use ($validated, $empleado, $usuarioEsVendedor, $esVentaVendedor, $latas, $panesEquivalentes) {
            $items = [];
            $subtotal = 0.0;

            foreach ($validated['items'] as $item) {
                $producto = Producto::whereKey($item['producto_id'])->lockForUpdate()->firstOrFail();
                $cantidad = (int) $item['cantidad'];

                if ($producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Existencias insuficientes para {$producto->nombre}.",
                    ]);
                }

                $precioUnitario = $this->resolvePrecioUnitario($producto, $empleado);
                $lineTotal = round((float) $precioUnitario * $cantidad, 2);
                $subtotal += $lineTotal;
                $items[] = [$producto, $cantidad, $lineTotal, $precioUnitario];
            }

            $descuento = $esVentaVendedor ? 0 : (int) $validated['descuento'];
            $montoDescuento = round($subtotal * $descuento / 100, 2);

            $ventaAmbulante = $esVentaVendedor
                ? Ventaambulante::create([
                    'empleado_id' => $empleado->idempleado,
                    'unidadesemitidas' => $panesEquivalentes,
                ])
                : null;

            $venta = Venta::create([
                'emision' => $validated['emision'],
                'fechapago' => $esVentaVendedor ? null : $validated['emision'],
                'total' => round($subtotal - $montoDescuento, 2),
                'estado' => $esVentaVendedor ? 'Pendiente' : 'Pagada',
                'descuento' => $descuento,
                'latas' => $latas,
                'panes_equivalentes' => $panesEquivalentes,
                'incidencia' => $usuarioEsVendedor ? ($validated['incidencia'] ?? null) : null,
                'empleado_id' => $empleado->idempleado,
                'ventaambulante_id' => $ventaAmbulante?->idventaambulante,
            ]);

            foreach ($items as [$producto, $cantidad, $lineTotal, $precioUnitario]) {
                $detalleData = [
                    'producto_id' => $producto->idproducto,
                    'cantidad' => $cantidad,
                    'subtotal' => $lineTotal,
                    'precio_unitario' => $precioUnitario,
                ];

                if (!Schema::hasColumn('detalleproductos', 'precio_unitario')) {
                    unset($detalleData['precio_unitario']);
                }

                if (Schema::hasColumn('detalleproductos', 'descuento_aplicado')) {
                    $detalleData['descuento_aplicado'] = 0;
                }

                $venta->detalleproductos()->create($detalleData);

                $producto->stock -= $cantidad;
                if ($producto->stock === 0) {
                    $producto->estado = 'unavailable';
                }
                $producto->save();
            }
        });

        return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente.');
    }

    public function cerrarAsignacion(Request $request, Ventaambulante $ventaambulante)
    {
        $empleadoId = $request->user()->empleado?->idempleado;
        abort_unless($empleadoId && (int) $ventaambulante->empleado_id === (int) $empleadoId, 404);

        $validated = $request->validate([
            'productos' => ['sometimes', 'array', 'min:1'],
            'productos.*.unidadesdevueltas' => ['required_with:productos', 'integer', 'min:0'],
            'productos.*.unidadesperdidas' => ['required_with:productos', 'integer', 'min:0'],
            'unidadesdevueltas' => ['required_without:productos', 'nullable', 'integer', 'min:0'],
            'unidadesperdidas' => ['required_without:productos', 'nullable', 'integer', 'min:0'],
            'incidencia' => ['nullable', 'string', 'max:1000'],
            'gravedad' => ['nullable', 'in:Baja,Media,Alta'],
        ]);

        DB::transaction(function () use ($validated, $ventaambulante, $empleadoId) {
            $asignacion = Ventaambulante::whereKey($ventaambulante->idventaambulante)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $asignacion->empleado_id === (int) $empleadoId, 404);

            if ($asignacion->unidadesdevueltas !== null || $asignacion->unidadesperdidas !== null) {
                abort(409, 'Esta asignación ya fue cerrada.');
            }

            $venta = Venta::where('ventaambulante_id', $asignacion->idventaambulante)
                ->lockForUpdate()
                ->first();
            $detalles = $venta?->detalleproductos()->lockForUpdate()->get() ?? collect();

            if ($detalles->isNotEmpty()) {
                $productosCerrados = $validated['productos'] ?? [];
                $idsEsperados = $detalles->pluck('iddetalleproducto')->map(fn ($id) => (int) $id)->sort()->values()->all();
                $idsRecibidos = array_map('intval', array_keys($productosCerrados));
                sort($idsRecibidos);

                if ($idsEsperados !== $idsRecibidos) {
                    throw ValidationException::withMessages([
                        'productos' => 'Registra el cierre para cada producto asignado.',
                    ]);
                }

                $totalAsignadas = 0;
                $totalDevueltas = 0;
                $totalPerdidas = 0;
                $totalVendido = 0.0;

                foreach ($detalles as $detalleProducto) {
                    $productoId = (string) $detalleProducto->iddetalleproducto;
                    $cierreProducto = $productosCerrados[$productoId];
                    $asignadas = (int) $detalleProducto->cantidad;
                    $devueltas = (int) $cierreProducto['unidadesdevueltas'];
                    $perdidas = (int) $cierreProducto['unidadesperdidas'];

                    if ($devueltas + $perdidas > $asignadas) {
                        throw ValidationException::withMessages([
                            "productos.{$productoId}.unidadesdevueltas" => 'Las devoluciones y pérdidas no pueden superar las unidades de este producto.',
                        ]);
                    }

                    $vendidas = $asignadas - $devueltas - $perdidas;
                    $detalleProducto->update([
                        'unidadesdevueltas' => $devueltas,
                        'unidadesperdidas' => $perdidas,
                    ]);

                    $totalAsignadas += $asignadas;
                    $totalDevueltas += $devueltas;
                    $totalPerdidas += $perdidas;
                    if ($asignadas > 0) {
                        $totalVendido += round((float) $detalleProducto->subtotal * $vendidas / $asignadas, 2);
                    }
                }

                $venta->update([
                    'total' => round($totalVendido, 2),
                    'panes_equivalentes' => $totalAsignadas,
                ]);
                $asignacion->update([
                    'unidadesemitidas' => $totalAsignadas,
                    'unidadesdevueltas' => $totalDevueltas,
                    'unidadesperdidas' => $totalPerdidas,
                ]);
            } else {
                $unidadesDevueltas = (int) $validated['unidadesdevueltas'];
                $unidadesPerdidas = (int) $validated['unidadesperdidas'];
                $unidadesNoVendidas = $unidadesDevueltas + $unidadesPerdidas;

                if ($unidadesNoVendidas > (int) $asignacion->unidadesemitidas) {
                    throw ValidationException::withMessages([
                        'unidadesdevueltas' => 'Las unidades devueltas y perdidas no pueden superar las unidades asignadas.',
                    ]);
                }

                $asignacion->update([
                    'unidadesdevueltas' => $unidadesDevueltas,
                    'unidadesperdidas' => $unidadesPerdidas,
                ]);

                $unidadesVendidas = (int) $asignacion->unidadesemitidas - $unidadesNoVendidas;
                if ($venta && (int) $asignacion->unidadesemitidas > 0) {
                    $venta->update([
                        'total' => round(
                            (float) $venta->total * $unidadesVendidas / (int) $asignacion->unidadesemitidas,
                            2
                        ),
                    ]);
                }
            }

            if ($venta) {
                $venta->registrarAbonoPendiente();
                $venta->estado = 'Pagada';
                $venta->fechapago = today()->toDateString();
                $venta->save();
            }

            $detalle = trim($validated['incidencia'] ?? '');

            if ($detalle !== '') {
                Incidencia::create([
                    'fecha' => today(),
                    'hora' => now()->format('H:i:s'),
                    'detalle' => $detalle,
                    'gravedad' => $validated['gravedad'] ?? 'Media',
                    'ventaambulante_id' => $asignacion->idventaambulante,
                ]);
            }
        });

        return redirect()->route('ventas.index');
    }

    public function listarAsignaciones(Request $request)
    {
        $empleadoActual = $request->user()->empleado()->with('rol')->first();
        $usuarioEsVendedor = strtolower(trim((string) ($empleadoActual?->rol?->descripcion ?? ''))) === 'vendedor';

        $consulta = Ventaambulante::with([
            'empleado.usuario',
            'incidencias',
            'venta.detalleproductos.producto',
        ])
            ->orderByDesc('created_at');

        if ($usuarioEsVendedor) {
            $consulta->where('empleado_id', $empleadoActual->idempleado);
        }

        $asignaciones = $consulta->get()->map(function (Ventaambulante $asignacion) {
            $cerrada = $asignacion->unidadesdevueltas !== null
                && $asignacion->unidadesperdidas !== null;

            return [
                'id' => $asignacion->idventaambulante,
                'vendedor' => $asignacion->empleado?->nombreCompleto
                    ?: $asignacion->empleado?->usuario?->name
                    ?: 'Vendedor',
                'fecha' => $asignacion->created_at?->format('d/m/Y'),
                'emitidas' => (int) $asignacion->unidadesemitidas,
                'cerrada' => $cerrada,
                'vendidas' => $cerrada
                    ? (int) $asignacion->unidadesemitidas
                        - (int) $asignacion->unidadesdevueltas
                        - (int) $asignacion->unidadesperdidas
                    : null,
                'devueltas' => $asignacion->unidadesdevueltas,
                'perdidas' => $asignacion->unidadesperdidas,
                'productos' => $this->sellerProductLines($asignacion->venta),
                'incidencias' => $asignacion->incidencias->map(fn (Incidencia $incidencia) => [
                    'detalle' => $incidencia->detalle,
                    'gravedad' => $incidencia->gravedad,
                ])->values(),
            ];
        })->values();

        return response()->json(['asignaciones' => $asignaciones])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function procesarPedido(Request $request)
    {
        $validated = $request->validateWithBag('orderSale', [
            'pedido_id' => ['required', 'integer', 'exists:pedidos,idpedido'],
            'fechapago' => ['required', 'date'],
            'descuento' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $empleado = $request->user()->empleado()->with('rol')->first();

        if (!$empleado) {
            return redirect()->route('ventas.index')
                ->with('error', 'El usuario actual no tiene un empleado asociado.');
        }

        $venta = DB::transaction(function () use ($validated, $empleado) {
            $pedido = Pedido::whereKey($validated['pedido_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower(trim($pedido->estado)) !== 'pendiente') {
                throw ValidationException::withMessages([
                    'pedido_id' => 'Este pedido ya fue procesado o no está pendiente.',
                ])->errorBag('orderSale');
            }

            $detalles = Detalleproducto::where('pedido_id', $pedido->idpedido)
                ->orderBy('iddetalleproducto')
                ->get();

            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'pedido_id' => 'Este pedido no tiene productos para procesar.',
                ])->errorBag('orderSale');
            }

            $ventaIds = $detalles->pluck('venta_id')->filter()->unique()->values();
            if ($ventaIds->count() > 1) {
                throw ValidationException::withMessages([
                    'pedido_id' => 'Los productos del encargo están vinculados a ventas distintas.',
                ])->errorBag('orderSale');
            }

            $ventaExistente = $ventaIds->isNotEmpty()
                ? Venta::whereKey($ventaIds->first())->lockForUpdate()->firstOrFail()
                : null;
            if ($ventaExistente && ($ventaExistente->fechapago || strtolower(trim($ventaExistente->estado)) === 'pagada')) {
                throw ValidationException::withMessages([
                    'pedido_id' => 'Este pedido ya tiene una venta pagada.',
                ])->errorBag('orderSale');
            }

            $productos = Producto::whereIn('idproducto', $detalles->pluck('producto_id'))
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get()
                ->keyBy('idproducto');
            $ofertas = Oferta::where('cliente_id', $pedido->cliente_id)
                ->whereIn('producto', $detalles->pluck('producto_id'))
                ->whereDate('fechainicio', '<=', today())
                ->whereDate('fechafin', '>=', today())
                ->orderByDesc('descuento')
                ->get()
                ->groupBy('producto');

            $lineas = [];
            $subtotalBrutoTotal = 0.0;
            $totalNeto = 0.0;
            $descuentoTotal = 0.0;

            foreach ($detalles as $detalle) {
                $producto = $productos->get((int) $detalle->producto_id);
                $cantidad = (int) $detalle->cantidad;

                if (!$producto || $producto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'pedido_id' => 'No hay existencias suficientes para ' . ($producto?->nombre ?? 'uno de los productos') . '.',
                    ])->errorBag('orderSale');
                }

                $precioUnitario = $this->resolvePrecioUnitario($producto, $empleado);
                $subtotalBruto = (float) $detalle->subtotal;
                $subtotalBruto = round((float) $precioUnitario * $cantidad, 2);
                $oferta = $ofertas->get((string) $detalle->producto_id, collect())->first();
                $descuentoLinea = round($subtotalBruto * (int) ($oferta?->descuento ?? 0) / 100, 2);
                $subtotalNeto = round($subtotalBruto - $descuentoLinea, 2);
                $descuentoManual = round($subtotalNeto * $validated['descuento'] / 100, 2);
                $subtotalNeto = round($subtotalNeto - $descuentoManual, 2);

                $subtotalBrutoTotal += $subtotalBruto;
                $totalNeto += $subtotalNeto;
                $descuentoTotal += $descuentoLinea + $descuentoManual;
                $lineas[] = [$detalle, $producto, $cantidad, $subtotalNeto, $descuentoLinea + $descuentoManual];
            }

            $porcentajeDescuento = $subtotalBrutoTotal > 0
                ? (int) round($descuentoTotal / $subtotalBrutoTotal * 100)
                : 0;
            $totalFinal = round($totalNeto, 2);
            $abonoPrevio = $ventaExistente
                ? (int) $ventaExistente->abonos()->sum('cantidadabonada')
                : 0;
            if ($abonoPrevio > (int) round($totalFinal * 100)) {
                throw ValidationException::withMessages([
                    'pedido_id' => 'El total final no puede ser menor que el adelanto ya recibido.',
                ])->errorBag('orderSale');
            }

            $venta = $ventaExistente ?? new Venta();
            $venta->fill([
                'emision' => today(),
                'fechapago' => $validated['fechapago'],
                'total' => $totalFinal,
                'estado' => 'Pagada',
                'descuento' => $porcentajeDescuento,
                'empleado_id' => $empleado->idempleado,
            ]);
            $venta->save();

            foreach ($lineas as [$detalle, $producto, $cantidad, $subtotalNeto, $descuentoLinea]) {
                $detalle->venta_id = $venta->idventa;
                $detalle->subtotal = $subtotalNeto;
                if (Schema::hasColumn('detalleproductos', 'descuento_aplicado')) {
                    $detalle->descuento_aplicado = $descuentoLinea;
                }
                $detalle->save();

                $producto->stock -= $cantidad;
                if ($producto->stock === 0) {
                    $producto->estado = 'unavailable';
                }
                $producto->save();
            }

            $pedido->estado = 'procesado';
            $pedido->estado_pago = 'pagada';
            $pedido->fechapago = $validated['fechapago'];
            $pedido->save();

            return $venta;
        });

        return redirect()->route('ventas.index')
            ->with('success', 'Venta ' . $venta->idventa . ' registrada y pagada para el pedido #' . $validated['pedido_id'] . '.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venta $venta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Venta $venta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venta $venta)
    {
        //
    }
}
