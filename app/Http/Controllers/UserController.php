<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with(['empleado.rol'])->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Rol::all();

        return view('usuarios.guardar', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'idrol' => ['required', 'exists:rol,idrol']
        ]);

        DB::transaction(function () use ($request) {
            $usuario = new User();

            $usuario->name = $request->name;
            $usuario->email = $request->email;
            $usuario->password = Hash::make($request->password);
            $usuario->iscustomer = false;
            $usuario->googlepassword = false;

            $usuario->save();

            $empleado = new Empleado();

            $empleado->idusuario = $usuario->id;
            $empleado->idrol = $request->idrol;
            $empleado->estado = true;

            $empleado->save();
        });

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario y empleado registrados correctamente.');
    }

    public function actualizarConfiguracion(Request $request, User $usuario)
    {
        $request->validate([
            'estado' => ['required', 'boolean']
        ]);

        $empleado = $usuario->empleado;

        if (!$empleado) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'El usuario no tiene un empleado asociado.');
        }

        $empleado->estado = $request->estado;
        $empleado->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function edit(User $usuario)
    {
        $usuario->load('empleado');
        $roles = Rol::all();

        return view('empleados.editar', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $usuario->id],
            'idrol' => ['required', 'exists:rol,idrol'],
            'password' => ['nullable', 'string', 'min:8']
        ]);

        DB::transaction(function () use ($request, $usuario) {
            $usuario->name = $request->name;
            $usuario->email = $request->email;

            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }

            $usuario->save();

            $empleado = $usuario->empleado;

            if ($empleado) {
                $empleado->idrol = $request->idrol;
                $empleado->save();
            }
        });

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function darDeBaja(User $usuario)
    {
        $empleado = $usuario->empleado;

        if (!$empleado) {
            return redirect()
                ->route('usuarios.index')
                ->with('error', 'El usuario no tiene un empleado asociado.');
        }

        $empleado->estado = false;
        $empleado->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Empleado dado de baja correctamente.');
    }
}