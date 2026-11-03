<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Exception;

class LoginController extends Controller
{

    public function registro(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|min:3',
            'apellido' => 'required|string|min:3',
            'email' => 'required|email',
            'password' => 'required|min:8|max:16|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[!@#$%^&*]/',
            'confirmar' => 'required|min:8|max:16|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[!@#$%^&*]/',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'apellido.required' => 'El apellido es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Por favor, ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.max' => 'La contraseña debe tener menos de 16 caracteres.',
            'password.regex' => 'La contraseña debe tener mayúscula, minúscula, número y carácter especial.',
            'confirmar.required' => 'La confirmación de la contraseña es obligatoria.',
            'confirmar.min' => 'La confirmación de la contraseña debe tener al menos 8 caracteres.',
            'confirmar.max' => 'La confirmación de la contraseña debe tener menos de 16 caracteres.',
            'confirmar.regex' => 'La confirmación de la contraseña debe tener mayúscula, minúscula, número y carácter especial.',
        ]);

        $usuario =  User::where('email', $request->email);
        if ($usuario->exists()) {
            return redirect()->route('login')->with('error', 'El usuario en realidad existe.');
        } else {
            try {
                $usuario = new User();
                $usuario->name = $request->nombre . $request->apellido;
                $usuario->email = $request->email;
                $usuario->password = Hash::make($request->password); //encriptar contraseña
                $usuario->save();
                $usuario->cliente()->create([
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'fecharegistro' => now(),
                ]);
                Auth::login($usuario); //logear al usuario
                return redirect()->route('dashboard');
            } catch (Exception $e) {
                return redirect()->route('login')->with('error', 'Error al registrar el usuario');
            }
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('menu');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Por favor, ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        $remember = ($request->has('remember') ? true : false);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        } else {
            return redirect()->route('login')->withInput($request->only('email'))->with('error', 'Usuario o contraseña incorrectos');
        }
    }
}
