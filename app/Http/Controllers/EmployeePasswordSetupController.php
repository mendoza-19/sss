<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class EmployeePasswordSetupController extends Controller
{
    public function create(Request $request, string $token): View
    {
        $this->findEmployee($request->query('email'));

        return view('empleados.establecer-password', [
            'request' => $request,
            'token' => $token,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->findEmployee($validated['email']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ]);

                if ($user->empleado()->exists() && empty($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Tu contraseña fue configurada correctamente. Inicia sesión con tu nueva contraseña.');
        }

        $error = match ($status) {
            Password::INVALID_TOKEN => 'El enlace para establecer la contraseña no es válido o venció. Solicita uno nuevo.',
            Password::INVALID_USER => 'No se encontró una cuenta con ese correo electrónico.',
            Password::RESET_THROTTLED => 'Espera un momento antes de volver a intentarlo.',
            default => 'No se pudo establecer la contraseña. Solicita un enlace nuevo e inténtalo otra vez.',
        };

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => $error]);
    }

    private function findEmployee(?string $email): User
    {
        return User::where('email', $email)
            ->whereHas('empleado')
            ->firstOrFail();
    }
}
