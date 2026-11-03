<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Municipio;
use App\Models\Pedido;
use App\Models\Rol;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Notifications\EmployeeSetPasswordNotification;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (session()->has('editar_empleado_id')) {
            $empleado = Empleado::with([
                'usuario',
                'rol',
                'distrito.municipio.departamento',
            ])->findOrFail(session('editar_empleado_id'));

            session()->forget('editar_empleado_id');
            $departamentos = Departamento::orderBy('nombre')->get();

            return view('empleados.editar', compact('empleado', 'departamentos'));
        }

        return view('empleados');
    }

    public function perfil(Request $request)
    {
        $user = $request->user();
        $cliente = $user->cliente;

        if ($user->iscustomer || $cliente) {
            $pedidosPendientes = $cliente
                ? Pedido::with('detalles.producto')
                    ->where('cliente_id', $cliente->idcliente)
                    ->orderByDesc('updated_at')
                    ->get()
                : collect();
            $pedidosHistorial = $pedidosPendientes
                ->filter(fn ($pedido) => strtolower(trim((string) $pedido->estado_produccion)) === 'terminado'
                    && strtolower(trim((string) $pedido->estado_pago)) === 'pagada')
                ->values();
            $pedidosPagoPendiente = $pedidosPendientes
                ->filter(fn ($pedido) => strtolower(trim((string) $pedido->estado_produccion)) === 'terminado'
                    && strtolower(trim((string) $pedido->estado_pago)) !== 'pagada')
                ->values();
            $pedidosPendientes = $pedidosPendientes
                ->reject(fn ($pedido) => strtolower(trim((string) $pedido->estado_produccion)) === 'terminado')
                ->values();
            $ventasCliente = $cliente
                ? Venta::with(['abonos', 'detalleproductos.producto', 'detalleproductos.pedido'])
                    ->whereHas('detalleproductos.pedido', fn ($query) => $query->where('cliente_id', $cliente->idcliente))
                    ->orderByDesc('emision')
                    ->get()
                : collect();

            $ventasPendientes = $ventasCliente
                ->filter(fn ($venta) => empty($venta->fechapago) || strtolower(trim($venta->estado)) === 'pendiente')
                ->each(function ($venta) {
                    $abonado = $venta->abonos->sum('cantidadabonada') / 100;
                    $venta->setAttribute('abonado_total', round($abonado, 2));
                    $venta->setAttribute('saldo_pendiente', round(max(0, (float) $venta->total - $abonado), 2));
                })
                ->values();

            $historialCompras = $ventasCliente
                ->reject(fn ($venta) => empty($venta->fechapago) || strtolower(trim($venta->estado)) === 'pendiente')
                ->values();

            return view('perfil', [
                'user' => $user,
                'cliente' => $cliente,
                'pedidosPendientes' => $pedidosPendientes,
                'pedidosPagoPendiente' => $pedidosPagoPendiente,
                'pedidosHistorial' => $pedidosHistorial,
                'ventasPendientes' => $ventasPendientes,
                'historialCompras' => $historialCompras,
            ]);
        }

        $empleado = $user->empleado()
            ->with(['usuario', 'rol', 'distrito.municipio.departamento'])
            ->first();

        if (!$empleado) {
            abort(404, 'No se encontró el registro de empleado asociado a esta cuenta.');
        }

        $departamentos = Departamento::orderBy('nombre')->get();

        return view('empleados.perfil', compact('empleado', 'departamentos'));
    }

    public function getEmpleadosPagination(Request $request)
    {
        $skip = max(0, $request->integer('skip', 0));
        $limit = min(50, max(1, $request->integer('limit', 8)));
        $userId = $request->user()?->id;

        $empleados = Empleado::select([
            'idempleado',
            'idusuario',
            'idrol',
            'duiempleado',
            'nombreCompleto',
            'telefono',
            'iniciocontrato',
            'estado',
            'iddistrito',
            'Residencia',
        ])
            ->with(['usuario', 'rol', 'distrito.municipio.departamento'])
            ->when($userId !== null, fn($query) => $query->where('idusuario', '!=', $userId))
            ->orderBy('nombreCompleto')
            ->skip($skip)
            ->take($limit + 1)
            ->get();

        $hasMore = $empleados->count() > $limit;
        $empleados = $empleados->take($limit)->values();

        return response()->json([
            'empleados' => $empleados,
            'hasMore' => $hasMore,
            'success' => true,
        ]);
    }

    public function searchEmpleado(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $userId = $request->user()?->id;

        $empleados = Empleado::select([
            'idempleado',
            'idusuario',
            'idrol',
            'duiempleado',
            'nombreCompleto',
            'telefono',
            'iniciocontrato',
            'estado',
            'iddistrito',
            'Residencia',
        ])
            ->with(['usuario', 'rol', 'distrito.municipio.departamento'])
            ->when($userId !== null, fn($query) => $query->where('idusuario', '!=', $userId))
            ->where(function ($query) use ($search) {
                $query->where('nombreCompleto', 'like', '%' . $search . '%')
                    ->orWhere('duiempleado', 'like', '%' . $search . '%')
                    ->orWhere('telefono', 'like', '%' . $search . '%')
                    ->orWhereHas('usuario', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            })
            ->orderBy('nombreCompleto')
            ->take(50)
            ->get();

        return response()->json([
            'empleados' => $empleados,
            'success' => true,
        ]);
    }

    public function opcionesFormulario()
    {
        $roles = Rol::orderBy('descripcion')->get(['idrol', 'descripcion']);

        return response()->json([
            'roles' => $roles,
            'success' => true,
        ]);
    }

    public function detalleEmpleado($idempleado)
    {
        $empleado = Empleado::with([
            'usuario:id,name,email',
            'rol:idrol,descripcion',
            'distrito:iddistrito,nombre,idmunicipio',
            'distrito.municipio:idmunicipio,nombre,iddepartamento',
            'distrito.municipio.departamento:iddepartamento,nombre',
        ])->findOrFail($idempleado);

        $empleado->foto = $empleado->foto
            ? base64_encode($empleado->foto)
            : null;

        return response()->json([
            'empleado' => $empleado,
            'success' => true,
        ]);
    }

    public function crearEmpleado(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'idrol' => ['required', 'exists:rol,idrol'],
        ]);

        $empleado = DB::transaction(function () use ($validated) {
            $usuario = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Str::random(64),
                'iscustomer' => false,
                'googlepassword' => false,
            ]);

            return $usuario->empleado()->create([
                'nombreCompleto' => $validated['name'],
                'idrol' => $validated['idrol'],
                'estado' => 'Activo',
            ]);
        });

        $token = Password::broker()->createToken($empleado->usuario);
        $empleado->usuario->notify(new EmployeeSetPasswordNotification($token));

        $empleado->load(['usuario', 'rol', 'distrito.municipio.departamento']);
        $empleado->makeHidden('foto');

        return response()->json([
            'empleado' => $empleado,
            'success' => true,
            'message' => 'Empleado registrado correctamente.',
        ]);
    }

    public function reenviarEnlacePassword($idempleado)
    {
        $empleado = Empleado::with('usuario')->findOrFail($idempleado);

        if (!$empleado->usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El empleado no tiene una cuenta de usuario asociada.',
            ], 422);
        }

        $token = Password::broker()->createToken($empleado->usuario);

        $empleado->usuario->notify(
            new EmployeeSetPasswordNotification($token)
        );

        return response()->json([
            'success' => true,
            'message' => 'Se envió un nuevo enlace para establecer la contraseña.',
        ]);
    }

    public function actualizarEmpleado(Request $request)
    {
        $empleado = Empleado::findOrFail($request->input('idempleado'));
        $usuario = User::findOrFail($empleado->idusuario);

        $validated = $request->validate([
            'idempleado' => ['required', 'exists:empleados,idempleado'],
            'name' => ['required', 'string', 'max:60'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'idrol' => ['required', 'exists:rol,idrol'],
            'estado' => ['required', Rule::in(['Activo', 'Inactivo'])],
        ]);

        DB::transaction(function () use ($validated, $usuario, $empleado) {
            $usuario->name = $validated['name'];
            $usuario->email = $validated['email'];

            if (!empty($validated['password'])) {
                $usuario->password = $validated['password'];
            }

            $usuario->save();

            $empleado->nombreCompleto = $validated['name'];
            $empleado->idrol = $validated['idrol'];
            $empleado->estado = $validated['estado'];
            $empleado->save();
        });

        $empleado->load(['usuario', 'rol', 'distrito.municipio.departamento']);
        $empleado->makeHidden('foto');

        return response()->json([
            'empleado' => $empleado,
            'success' => true,
            'message' => 'Empleado actualizado correctamente.',
        ]);
    }


    public function seleccionar(Request $request)
    {
        $request->validate([
            'idempleado' => ['required', 'exists:empleados,idempleado'],
        ]);

        session(['editar_empleado_id' => $request->idempleado]);

        return redirect()->route('empleados.index');
    }

    public function actualizar(Request $request)
    {
        $validated = $request->validate([
            'idempleado' => ['required', 'exists:empleados,idempleado'],
            'duiempleado' => ['required', 'string', 'max:20'],
            'nombreCompleto' => ['required', 'string', 'max:60'],
            'telefono' => ['required', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
            'iniciocontrato' => ['required', 'date'],
            'iddistrito' => ['required', 'exists:distrito,iddistrito'],
            'residencia' => ['required', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $empleado = Empleado::findOrFail($validated['idempleado']);
        $empleado->duiempleado = $validated['duiempleado'];
        $empleado->nombreCompleto = $validated['nombreCompleto'];
        $empleado->telefono = $validated['telefono'];
        $empleado->iniciocontrato = $validated['iniciocontrato'];
        $empleado->iddistrito = $validated['iddistrito'];
        $empleado->Residencia = $validated['residencia'];

        if ($request->hasFile('foto')) {
            $empleado->foto = file_get_contents($request->file('foto')->getRealPath());
        }

        $empleado->save();

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Información del empleado actualizada correctamente.');
    }

    public function actualizarPerfil(Request $request)
    {
        $validated = $request->validate([
            'duiempleado' => ['required', 'string', 'max:20'],
            'nombreCompleto' => ['required', 'string', 'max:60'],
            'telefono' => ['required', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
            'iddistrito' => ['required', 'exists:distrito,iddistrito'],
            'residencia' => ['required', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $empleado = $request->user()->empleado;

        if (!$empleado) {
            abort(404, 'No se encontró el registro de empleado asociado a esta cuenta.');
        }

        DB::transaction(function () use ($request, $validated, $empleado) {
            $empleado->duiempleado = $validated['duiempleado'];
            $empleado->nombreCompleto = $validated['nombreCompleto'];
            $empleado->telefono = $validated['telefono'];
            $empleado->iddistrito = $validated['iddistrito'];
            $empleado->Residencia = $validated['residencia'];

            if ($request->hasFile('foto')) {
                $empleado->foto = file_get_contents($request->file('foto')->getRealPath());
            }

            $empleado->save();
        });

        return redirect()
            ->route('perfil')
            ->with('success', 'Información del empleado actualizada correctamente.');
    }

    public function municipios($iddepartamento)
    {
        return response()->json(
            Municipio::where('iddepartamento', $iddepartamento)->orderBy('nombre')->get()
        );
    }

    public function distritos($idmunicipio)
    {
        return response()->json(
            Distrito::where('idmunicipio', $idmunicipio)->orderBy('nombre')->get()
        );
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('empleados.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return redirect()->route('empleados.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Empleado $empleado)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Empleado $empleado)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Empleado $empleado)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empleado $empleado)
    {
        //
    }
}
