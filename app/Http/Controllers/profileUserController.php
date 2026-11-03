<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class profileUserController extends Controller
{
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'nombre_usuario' => 'required|string|min:3|max:60',
            'nombre' => 'required|string|min:3|max:60',
            'apellido' => 'required|string|min:3|max:60',
            'telefono' => ['required', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 3 caracteres.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya está registrado por otro usuario.',
            'fotografia.image' => 'El archivo debe ser una imagen válida.',
        ]);

        $emailChanged = $request->email !== $user->email;

        // Si se intenta cambiar el correo electrónico, exigir validación de la contraseña actual para evitar fraudes
        if ($emailChanged) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Debes ingresar tu contraseña actual para autorizar el cambio de correo electrónico.'])->withInput();
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual ingresada es incorrecta.'])->withInput();
            }
        }

        $cliente = $user->cliente ? $user->cliente : null;
        $updateClient = false;
        $updateUser = false;

        if ($cliente) {
            if ($cliente->nombre != $request->nombre) {
                $cliente->nombre = $request->nombre;
                $updateClient = true;
            }
            if ($cliente->apellido != $request->apellido) {
                $cliente->apellido = $request->apellido;
                $updateClient = true;
            }
            if ($cliente->telefono != $request->telefono) {
                $cliente->telefono = $request->telefono;
                $updateClient = true;
            }
            if ($request->hasFile('fotografia')) {
                $filename = url('/imagenes_perfil') . "/" . time() . "." . $request->fotografia->extension();
                $request->fotografia->move(public_path("imagenes_perfil"), $filename);
                $cliente->foto = $filename;
                $updateClient = true;
            }
        }

        if ($user->name != $request->nombre_usuario) {
            $user->name = $request->nombre_usuario;
            $updateUser = true;
        }

        if ($emailChanged) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $updateUser = true;
        }

        if ($updateClient && $cliente) {
            $cliente->save();
        }

        if ($updateUser) {
            $user->save();
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            return redirect()->route("perfil")->with("success", "Perfil actualizado correctamente. Se ha enviado un enlace de verificación a tu nuevo correo (en bandeja o logs).");
        }

        return redirect()->route("perfil")->with("success", "Perfil actualizado correctamente.");
    }

    public function updateEmpleado(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'nombre_usuario' => 'required|string|min:3|max:60',
            'nombre' => 'required|string|min:3|max:60',
            'apellido' => 'required|string|min:3|max:60',
            'telefono' => ['nullable', 'regex:/^[567][0-9]{3}-[0-9]{4}$/'],
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'fotografia' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'nombre_usuario.required' => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'El correo electrónico ya está registrado por otro usuario.',
            'fotografia.image' => 'El archivo debe ser una imagen válida.',
        ]);

        $emailChanged = $request->email !== $user->email;

        // Si se intenta cambiar el correo electrónico, exigir validación de la contraseña actual para evitar fraudes
        if ($emailChanged) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Debes ingresar tu contraseña actual para autorizar el cambio de correo electrónico.'])->withInput();
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual ingresada es incorrecta.'])->withInput();
            }
        }

        $empleado = $user->empleado ? $user->empleado : null;
        $updateEmpleado = false;
        $updateUser = false;


        if ($empleado) {
            if ($empleado->nombre != $request->nombre) {
                $empleado->nombre = $request->nombre;
                $updateEmpleado = true;
            }
            if ($empleado->apellido != $request->apellido) {
                $empleado->apellido = $request->apellido;
                $updateEmpleado = true;
            }
            if ($empleado->telefono != $request->telefono) {
                $empleado->telefono = $request->telefono;
                $updateEmpleado = true;
            }
            if ($request->hasFile('fotografia')) {
                $filename = url('/imagenes_perfil') . "/" . time() . "." . $request->fotografia->extension();
                $request->fotografia->move(public_path("imagenes_perfil"), $filename);
                $empleado->foto = $filename;
                $updateEmpleado = true;
            }
        }

        if ($user->name != $request->nombre_usuario) {
            $user->name = $request->nombre_usuario;
            $updateUser = true;
        }

        if ($emailChanged) {
            $user->email = $request->email;
            $user->email_verified_at = null;
            $updateUser = true;
        }

        if ($updateEmpleado && $empleado) {
            $empleado->save();
        }

        if ($updateUser) {
            $user->save();
        }

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
            return redirect()->route("perfil")->with("success", "Perfil actualizado correctamente. Se ha enviado un enlace de verificación a tu nuevo correo (en bandeja o logs).");
        }

        return redirect()->route("perfil")->with("success", "Perfil actualizado correctamente.");
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|max:16|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[!@#$%^&*]/|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La nueva contraseña debe tener menos de 16 caracteres.',
            'password.regex' => 'La nueva contraseña debe incluir mayúscula, minúscula, número y un carácter especial (!@#$%^&*).',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        /** @var \App\Models\User $user */
        $user = User::find(Auth::id());

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password_sec' => 'La contraseña actual es incorrecta.'])->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('perfil')->with('success', 'Contraseña actualizada correctamente.');
    }

    public function requestLoggedUser(Request $request)
    {
        $user = User::where("email", Auth::user()->email)->first();
        $cliente = $user->cliente ? $user->cliente : null;
        return view("perfil", compact("user", "cliente"));
    }



    public static function getProfilePicture()
    {
        $user = Auth::user();

        if (!$user) {
            return asset('assets/global/profile-picture.png');
        }

        $cliente = $user->cliente;
        if ($user->iscustomer == 1 || $cliente) {
            return $cliente && filled($cliente->foto)
                ? $cliente->foto
                : asset('assets/global/profile-picture.png');
        }

        $empleado = $user->empleado;

        if ($empleado && $empleado->foto) {
            $foto = $empleado->foto;

            if (is_resource($foto)) {
                $foto = stream_get_contents($foto);
            }

            if (is_string($foto) && !empty($foto)) {
                $tipo = 'image/jpeg';

                if (str_starts_with($foto, "\x89PNG")) {
                    $tipo = 'image/png';
                } elseif (str_starts_with($foto, 'GIF8')) {
                    $tipo = 'image/gif';
                } elseif (str_starts_with($foto, 'RIFF') && substr($foto, 8, 4) === 'WEBP') {
                    $tipo = 'image/webp';
                }

                return 'data:' . $tipo . ';base64,' . base64_encode($foto);
            }
        }

        return asset('assets/global/profile-picture.png');
    }


    public static function destroy(Request $request) {}
}
