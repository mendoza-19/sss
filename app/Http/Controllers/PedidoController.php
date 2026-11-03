<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Detalleproducto;
use App\Models\Producto;
use App\Models\Empleado;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    /**
     * Muestra la vista principal de pedidos y ofertas.
     */
    public function index()
    {
        $empleado = request()->user()?->empleado()->with('rol')->first();
        $esRepartidor = strtolower((string) $empleado?->rol?->descripcion) === 'repartidor';

        return view('pedidosOfertas', compact('esRepartidor'));
    }

    private function filtrarPedidosListosParaDespacho(Builder $query): Builder
    {
        return $query->where(function ($subQuery) {
            $subQuery->where('tipo', 'pedido')
                ->orWhere('estado_produccion', 'terminado');
        });
    }

    /**
     * Retorna las estadísticas principales de los pedidos.
     * GET /api/pedidos/estadisticas
     */
    public function estadisticas(Request $request)
    {
        try {
            $empleado = $request->user()?->empleado()->with('rol')->first();
            $esRepartidor = strtolower((string) $empleado?->rol?->descripcion) === 'repartidor';
            $pedidoScope = function ($query) use ($empleado, $esRepartidor) {
                if ($esRepartidor) {
                    $query->where('repartidor_id', $empleado->idempleado);
                }
            };

            $pendientesQuery = $this->filtrarPedidosListosParaDespacho(Pedido::where('estado', 'pendiente'));
            $pedidoScope($pendientesQuery);
            $pendientes = $pendientesQuery->count();

            // Considera atrasados los explícitamente marcados o los pendientes creados hace más de 24 horas
            $atrasadosQuery = $this->filtrarPedidosListosParaDespacho(Pedido::where(function ($query) {
                $query->where('estado', 'atrasado')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('estado', 'pendiente')
                            ->where('created_at', '<', Carbon::now()->subDay());
                    });
            }));
            $pedidoScope($atrasadosQuery);
            $atrasados = $atrasadosQuery->count();

            $procesadosQuery = $this->filtrarPedidosListosParaDespacho(
                Pedido::whereIn('estado', ['entregado', 'procesado', 'completado'])
                    ->whereDate('updated_at', Carbon::today())
            );
            $pedidoScope($procesadosQuery);
            $procesadosHoy = $procesadosQuery->count();

            return response()->json([
                'success'        => true,
                'pendientes'     => $pendientes,
                'atrasados'      => $atrasados,
                'procesados_hoy' => $procesadosHoy,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
                'pendientes' => 0,
                'atrasados' => 0,
                'procesados_hoy' => 0,
            ], 500);
        }
    }

    /**
     * Listado paginado y filtrado de pedidos.
     * POST /api/pedidos/getPedidosPagination
     */
    public function getPedidosPagination(Request $request)
    {
        try {
            $skip   = (int)($request->skip ?? 0);
            $limit  = (int)($request->limit ?? 10);
            $orden  = $request->orden ?? 'mas-reciente';
            $estado = $request->estado ?? null;
            $search = trim($request->search ?? '');

            $query = $this->filtrarPedidosListosParaDespacho(
                Pedido::with(['cliente.user', 'repartidor', 'detalles.producto', 'detalles.venta'])
            );
            $empleado = $request->user()?->empleado()->with('rol')->first();
            $rol = strtolower((string) $empleado?->rol?->descripcion);
            if ($rol === 'repartidor') {
                $query->where('repartidor_id', $empleado->idempleado);
            }

            if ($estado === 'por-asignar') {
                if ($rol !== 'administrador') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solo el administrador puede ver los pedidos pendientes por asignar.',
                        'pedidos' => [],
                        'total' => 0,
                        'repartidores' => [],
                    ], 403);
                }

                $query->where('estado', 'pendiente')->whereNull('repartidor_id');
            } elseif (!empty($estado) && $estado !== 'todos') {
                if ($estado === 'atrasado') {
                    $query->where(function ($q) {
                        $q->where('estado', 'atrasado')
                          ->orWhere(function ($sub) {
                              $sub->where('estado', 'pendiente')
                                  ->where('created_at', '<', Carbon::now()->subDay());
                          });
                    });
                } else {
                    $query->where('estado', $estado);
                }
            }

            // Filtro de búsqueda por nombre de cliente, teléfono o dirección
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('direccion', 'like', "%{$search}%")
                      ->orWhereHas('cliente', function ($clienteQuery) use ($search) {
                          $clienteQuery->where('nombre', 'like', "%{$search}%")
                                       ->orWhere('apellido', 'like', "%{$search}%")
                                       ->orWhere('telefono', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', (int) $request->input('cliente_id'));
            }

            $total = $query->count();

            // Ordenamiento
            if ($orden === 'mas-antiguo') {
                $query->orderBy('created_at', 'asc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $pedidos = $query->skip($skip)->take($limit)->get();
            $repartidores = Empleado::with('rol')
                ->whereHas('rol', function ($roleQuery) {
                    $roleQuery->whereRaw('LOWER(descripcion) = ?', ['repartidor']);
                })
                ->orderBy('nombreCompleto')
                ->get(['idempleado', 'nombreCompleto', 'estado']);

            return response()->json([
                'success' => true,
                'pedidos' => $pedidos,
                'total'   => $total,
                'repartidores' => $repartidores,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage(),
                'pedidos' => [],
                'total'   => 0,
                'repartidores' => [],
            ], 500);
        }
    }

    /**
     * Actualiza el estado de un pedido.
     * POST /api/pedidos/cambiarEstado
     */
    public function cambiarEstado(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idpedido' => 'required|exists:pedidos,idpedido',
                'estado'   => 'required|string|max:17',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $empleado = $request->user()?->empleado()->with('rol')->first();
            $esRepartidor = strtolower((string) $empleado?->rol?->descripcion) === 'repartidor';
            $pedidoQuery = Pedido::whereKey($request->idpedido);
            if ($esRepartidor) {
                $pedidoQuery->where('repartidor_id', $empleado->idempleado);
            }

            $pedido = $pedidoQuery->firstOrFail();
            $estadoActual = strtolower(trim($pedido->estado));
            $nuevoEstado = strtolower(trim($request->estado));
            $siguienteEstado = [
                'pendiente' => 'en camino',
                'en camino' => 'entregado',
            ][$estadoActual] ?? null;

            $puedeVolverPendiente = $esRepartidor && $estadoActual === 'en camino' && $nuevoEstado === 'pendiente';
            if ($esRepartidor && $nuevoEstado !== $siguienteEstado && !$puedeVolverPendiente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido debe avanzar de Pendiente a En camino y luego a Entregado.',
                ], 422);
            }

            $pedido->estado = $nuevoEstado;
            $pedido->save();

            return response()->json([
                'success' => true,
                'message' => "Estado del pedido #{$pedido->idpedido} actualizado a '{$pedido->estado}'.",
                'pedido'  => $pedido,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarEstadoProduccion(Request $request)
    {
        $empleado = $request->user()?->empleado()->with('rol')->first();
        if (strtolower((string) $empleado?->rol?->descripcion) !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'Solo el administrador puede actualizar la producción del pedido.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'idpedido' => 'required|exists:pedidos,idpedido',
            'estado_produccion' => 'required|in:pendiente,en_preparacion,horneado,terminado',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $etapas = ['pendiente', 'en_preparacion', 'horneado', 'terminado'];
        $pedido = Pedido::findOrFail($request->idpedido);
        $estadoActual = $pedido->estado_produccion ?: 'pendiente';
        $indiceActual = array_search($estadoActual, $etapas, true);
        $indiceNuevo = array_search($request->estado_produccion, $etapas, true);
        $cancelandoProduccion = $request->estado_produccion === 'pendiente' && $estadoActual !== 'pendiente';

        if (!$cancelandoProduccion && $indiceNuevo !== $indiceActual + 1) {
            return response()->json([
                'success' => false,
                'message' => 'La producción debe avanzar una etapa a la vez o cancelarse para volver a Pendiente.',
            ], 422);
        }

        $pedido->estado_produccion = $request->estado_produccion;
        $pedido->save();

        return response()->json([
            'success' => true,
            'estado_produccion' => $pedido->estado_produccion,
        ]);
    }

    public function cambiarEstadoPago(Request $request)
    {
        $empleado = $request->user()?->empleado()->with('rol')->first();
        $rol = strtolower((string) $empleado?->rol?->descripcion);
        if (!in_array($rol, ['administrador', 'repartidor'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actualizar el pago del pedido.',
            ], 403);
        }

        $validated = $request->validate([
            'idpedido' => ['required', 'integer', 'exists:pedidos,idpedido'],
            'estado_pago' => ['required', 'in:pendiente,pagada'],
        ]);

        $query = Pedido::whereKey($validated['idpedido']);
        if ($rol === 'repartidor') {
            $query->where('repartidor_id', $empleado->idempleado);
        }

        $pedido = $query->firstOrFail();
        $estaPagada = $validated['estado_pago'] === 'pagada';

        $pedido = DB::transaction(function () use ($pedido, $empleado, $rol, $estaPagada) {
            $pedidoQuery = Pedido::whereKey($pedido->idpedido)->lockForUpdate();
            if ($rol === 'repartidor') {
                $pedidoQuery->where('repartidor_id', $empleado->idempleado);
            }
            $pedido = $pedidoQuery->firstOrFail();
            $fechaPago = $estaPagada
                ? ($pedido->fechapago ?: today()->toDateString())
                : null;

            $pedido->estado_pago = $estaPagada ? 'pagada' : 'pendiente';
            $pedido->fechapago = $fechaPago;
            $pedido->save();

            $ventaIds = DB::table('detalleproductos')
                ->where('pedido_id', $pedido->idpedido)
                ->whereNotNull('venta_id')
                ->pluck('venta_id')
                ->unique();

            if ($ventaIds->isNotEmpty()) {
                $ventas = Venta::whereIn('idventa', $ventaIds)->lockForUpdate()->get();
                foreach ($ventas as $venta) {
                    $venta->estado = $estaPagada ? 'Pagada' : 'Pendiente';
                    $venta->fechapago = $fechaPago;
                    $venta->save();
                }
            }

            return $pedido;
        });

        return response()->json([
            'success' => true,
            'message' => $estaPagada ? 'Pedido marcado como pagado.' : 'Pago del pedido marcado como pendiente.',
            'estado_pago' => $pedido->estado_pago,
            'fechapago' => $pedido->fechapago?->toDateString(),
        ]);
    }

    public function asignarRepartidor(Request $request)
    {
        $empleadoActual = $request->user()?->empleado()->with('rol')->first();
        if (strtolower((string) $empleadoActual?->rol?->descripcion) !== 'administrador') {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede asignar repartidores.',
            ], 403);
        }

        $validated = $request->validate([
            'idpedido' => ['required', 'exists:pedidos,idpedido'],
            'repartidor_id' => ['nullable', 'exists:empleados,idempleado'],
        ]);

        $pedido = Pedido::findOrFail($validated['idpedido']);
        if (in_array(strtolower(trim($pedido->estado)), ['en camino', 'entregado', 'procesado', 'completado'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'El repartidor ya no puede cambiarse después de iniciar el reparto.',
            ], 422);
        }

        if ($validated['repartidor_id']) {
            $esRepartidor = Empleado::whereKey($validated['repartidor_id'])
                ->whereHas('rol', function ($roleQuery) {
                    $roleQuery->whereRaw('LOWER(descripcion) = ?', ['repartidor']);
                })
                ->exists();

            if (!$esRepartidor) {
                return response()->json([
                    'success' => false,
                    'message' => 'El empleado seleccionado no tiene rol de repartidor.',
                ], 422);
            }
        }

        $pedido->repartidor_id = $validated['repartidor_id'];
        $pedido->save();
        $pedido->load('repartidor');

        return response()->json([
            'success' => true,
            'message' => $pedido->repartidor
                ? 'Repartidor asignado correctamente.'
                : 'Repartidor retirado del pedido.',
            'pedido' => $pedido,
        ]);
    }

    /**
     * Crea un nuevo pedido.
     * POST /api/pedidos/crear
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'required|exists:clientes,idcliente',
                'direccion'  => 'required|string|max:255',
                'estado'     => 'nullable|string|max:17',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campos obligatorios faltantes o inválidos.',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $pedido = Pedido::create([
                'cliente_id' => $request->cliente_id,
                'direccion'  => $request->direccion,
                'estado'     => strtolower($request->estado ?? 'pendiente'),
            ]);

            $pedido->load('cliente.user');

            return response()->json([
                'success' => true,
                'message' => 'Pedido registrado correctamente.',
                'pedido'  => $pedido,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function crearEncargoLocal(Request $request)
    {
        $validated = $request->validateWithBag('localOrder', [
            'cliente_id' => ['required', 'integer', 'exists:clientes,idcliente'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notas' => ['nullable', 'string', 'max:255'],
            'abono_inicial' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:productos,idproducto'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $abonoInicial = round((float) ($validated['abono_inicial'] ?? 0), 2);
        $empleado = $abonoInicial > 0 ? $request->user()->empleado()->first() : null;
        if ($abonoInicial > 0 && !$empleado) {
            throw ValidationException::withMessages([
                'abono_inicial' => 'No se encontró el empleado que registra el abono.',
            ])->errorBag('localOrder');
        }

        $pedido = DB::transaction(function () use ($validated, $abonoInicial, $empleado) {
            $items = collect($validated['items'])->keyBy('id');
            $productos = Producto::whereIn('idproducto', $items->keys())
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get()
                ->keyBy('idproducto');

            foreach ($items as $item) {
                $producto = $productos->get((int) $item['id']);
                if (!$producto || !$producto->visible) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no está disponible para encargo.',
                    ])->errorBag('localOrder');
                }
            }

            $totalEstimado = round($items->sum(function ($item) use ($productos) {
                $producto = $productos->get((int) $item['id']);
                return (float) $producto->precio * (int) $item['quantity'];
            }), 2);
            if ($abonoInicial > $totalEstimado) {
                throw ValidationException::withMessages([
                    'abono_inicial' => 'El adelanto no puede superar el total estimado del encargo.',
                ])->errorBag('localOrder');
            }

            $cliente = Cliente::findOrFail($validated['cliente_id']);

            $direccion = 'Recogida en local';
            if (!empty($validated['notas'])) {
                $direccion .= ' (Nota: ' . trim($validated['notas']) . ')';
            }

            $pedido = Pedido::create([
                'cliente_id' => $cliente->idcliente,
                'direccion' => $direccion,
                'estado' => 'pendiente',
                'tipo' => 'encargo_local',
                'fecha_entrega' => $validated['fecha_entrega'],
            ]);

            $venta = $abonoInicial > 0
                ? Venta::create([
                    'emision' => today(),
                    'fechapago' => null,
                    'total' => $totalEstimado,
                    'estado' => 'Pendiente',
                    'descuento' => 0,
                    'empleado_id' => $empleado->idempleado,
                ])
                : null;

            foreach ($items as $item) {
                $producto = $productos->get((int) $item['id']);
                $cantidad = (int) $item['quantity'];

                Detalleproducto::create([
                    'pedido_id' => $pedido->idpedido,
                    'producto_id' => $producto->idproducto,
                    'cantidad' => $cantidad,
                    'subtotal' => round((float) $producto->precio * $cantidad, 2),
                    'venta_id' => $venta?->idventa,
                ]);
            }

            if ($venta) {
                $venta->abonos()->create([
                    'fechaabono' => today()->toDateString(),
                    'cantidadabonada' => (int) round($abonoInicial * 100),
                    'metodopago' => 'Tarjeta simulada',
                ]);
            }

            return $pedido;
        });

        return redirect()->route('ventas.index')
            ->with('success', 'Encargo local #' . $pedido->idpedido . ' registrado para recoger en el local.');
    }

    public function crearDesdeCarrito(Request $request)
    {
        $validated = $request->validate([
            'direccion' => ['required', 'string', 'min:5', 'max:255'],
            'notas' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,idproducto'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);


        $cliente = $request->user()->cliente;
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta actual no tiene un perfil de cliente para registrar pedidos.',
            ], 403);
        }

        $pedido = DB::transaction(function () use ($validated, $cliente) {
            $items = collect($validated['items'])->keyBy('producto_id');
            $productos = Producto::whereIn('idproducto', $items->keys())
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get()
                ->keyBy('idproducto');

            foreach ($items as $productoId => $item) {
                $producto = $productos->get((int) $productoId);
                $cantidad = (int) $item['cantidad'];

                if (!$producto || !$producto->visible || $producto->stock < $cantidad
                    || !in_array(strtolower(trim($producto->estado)), ['available', 'disponible', 'activo'], true)) {
                    throw ValidationException::withMessages([
                        'items' => 'No hay existencias suficientes para ' . ($producto?->nombre ?? 'uno de los productos') . '.',
                    ]);
                }
            }

            $direccion = trim($validated['direccion']);
            if (!empty($validated['notas'])) {
                $direccion .= ' (Nota: ' . trim($validated['notas']) . ')';
            }

            $pedido = Pedido::create([
                'cliente_id' => $cliente->idcliente,
                'direccion' => $direccion,
                'estado' => 'pendiente',
            ]);

            foreach ($items as $productoId => $item) {
                $producto = $productos->get((int) $productoId);
                $cantidad = (int) $item['cantidad'];

                Detalleproducto::create([
                    'pedido_id' => $pedido->idpedido,
                    'producto_id' => $producto->idproducto,
                    'cantidad' => $cantidad,
                    'subtotal' => round((float) $producto->precio * $cantidad, 2),
                ]);
            }

            return $pedido;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tu pedido fue enviado correctamente.',
            'pedido_id' => $pedido->idpedido,
        ], 201);
    }

    public function crearEncargo(Request $request)
    {
        $validated = $request->validate([
            'direccion' => ['required', 'string', 'min:5', 'max:255'],
            'telefono' => ['nullable', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
            'notas' => ['nullable', 'string', 'max:255'],
            'fecha_entrega' => ['required', 'date_format:Y-m-d', 'after:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:productos,idproducto'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $cliente = $user->cliente;

        if (!$cliente) {
            $nombrePartes = explode(' ', trim($user->name), 2);
            $cliente = Cliente::create([
                'nombre' => $nombrePartes[0] ?? $user->name,
                'apellido' => $nombrePartes[1] ?? '',
                'telefono' => $validated['telefono'] ?? null,
                'fecharegistro' => now(),
                'user_id' => $user->id,
            ]);
        } elseif (!empty($validated['telefono']) && empty($cliente->telefono)) {
            $cliente->telefono = $validated['telefono'];
            $cliente->save();
        }

        $pedido = DB::transaction(function () use ($validated, $cliente) {
            $items = collect($validated['items'])->keyBy('id');
            $productos = Producto::whereIn('idproducto', $items->keys())
                ->orderBy('idproducto')
                ->lockForUpdate()
                ->get()
                ->keyBy('idproducto');

            foreach ($items as $item) {
                $producto = $productos->get((int) $item['id']);
                if (!$producto || !$producto->visible) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no está visible para encargo.',
                    ]);
                }
            }

            $direccion = trim($validated['direccion']);
            if (!empty($validated['notas'])) {
                $direccion .= ' (Nota: ' . trim($validated['notas']) . ')';
            }

            $pedido = Pedido::create([
                'cliente_id' => $cliente->idcliente,
                'direccion' => $direccion,
                'estado' => 'pendiente',
                'tipo' => 'encargo',
                'fecha_entrega' => $validated['fecha_entrega'],
            ]);

            foreach ($items as $item) {
                $producto = $productos->get((int) $item['id']);
                $cantidad = (int) $item['quantity'];

                Detalleproducto::create([
                    'pedido_id' => $pedido->idpedido,
                    'producto_id' => $producto->idproducto,
                    'cantidad' => $cantidad,
                    'subtotal' => round((float) $producto->precio * $cantidad, 2),
                ]);
            }

            return $pedido;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tu encargo fue registrado correctamente.',
            'pedido_id' => $pedido->idpedido,
        ], 201);
    }

    /**
     * Permite a un cliente autenticado realizar un pedido oficial con sus productos seleccionados.
     * POST /api/pedidos/cliente/crear
     */
    public function crearPedidoCliente(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar sesión para realizar un pedido.',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'direccion'        => 'required|string|min:5|max:255',
                'telefono'         => 'nullable|regex:/^[567][0-9]{3}-[0-9]{4}$/',
                'notas'            => 'nullable|string|max:255',
                'items'            => 'required|array|min:1',
                'items.*.id'       => 'required|integer|exists:productos,idproducto',
                'items.*.quantity' => 'required|integer|min:1',
            ], [
                'direccion.required' => 'La dirección de entrega es obligatoria.',
                'direccion.min'      => 'Por favor especifica una dirección más detallada.',
                'items.required'     => 'Tu carrito no contiene productos válidos.',
                'items.min'          => 'Debes incluir al menos un producto en tu pedido.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Obtener o crear el cliente asociado al usuario
            $cliente = $user->cliente;
            if (!$cliente) {
                $nombrePartes = explode(' ', trim($user->name), 2);
                $cliente = Cliente::create([
                    'nombre'        => $nombrePartes[0] ?? $user->name,
                    'apellido'      => $nombrePartes[1] ?? '',
                    'telefono'      => $request->telefono ?? null,
                    'fecharegistro' => now(),
                    'user_id'       => $user->id,
                ]);
            } else if (!empty($request->telefono) && empty($cliente->telefono)) {
                $cliente->telefono = $request->telefono;
                $cliente->save();
            }

            $direccionFinal = trim($request->direccion);
            if (!empty($request->notas)) {
                $direccionFinal .= ' (Nota: ' . trim($request->notas) . ')';
            }

            $pedidoCreado = DB::transaction(function () use ($request, $cliente, $direccionFinal) {
                // Validar productos y existencias
                $itemsDetalle = [];

                foreach ($request->items as $item) {
                    $producto = Producto::whereKey($item['id'])->lockForUpdate()->firstOrFail();
                    $cantidad = (int) $item['quantity'];

                    if (!$producto->visible || $producto->stock < $cantidad) {
                        throw new Exception("Existencias insuficientes para '{$producto->nombre}' (disponibles: {$producto->stock}).");
                    }

                    $subtotal = $producto->precio * $cantidad;
                    $itemsDetalle[] = [
                        'producto' => $producto,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal,
                    ];
                }

                $pedido = Pedido::create([
                    'cliente_id' => $cliente->idcliente,
                    'direccion'  => $direccionFinal,
                    'estado'     => 'pendiente',
                ]);

                foreach ($itemsDetalle as $detalle) {
                    $producto = $detalle['producto'];
                    $cantidad = $detalle['cantidad'];
                    $subtotal = $detalle['subtotal'];

                    Detalleproducto::create([
                        'pedido_id'   => $pedido->idpedido,
                        'producto_id' => $producto->idproducto,
                        'cantidad'    => $cantidad,
                        'subtotal'    => $subtotal,
                    ]);

                }

                return $pedido;
            });

            return response()->json([
                'success'   => true,
                'message'   => "¡Tu pedido #{$pedidoCreado->idpedido} fue registrado con éxito! Pronto nos pondremos en contacto.",
                'pedido_id' => $pedidoCreado->idpedido,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}
