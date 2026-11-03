<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function pagePasswordReset(): View
    {
        return view('password-restore');
    }

    /**
     * Check if user email exists in the database.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Por favor, ingresa un correo electrónico válido.',
        ]);

        $exists = User::where('email', $request->email)->exists();

        if ($exists) {
            return response()->json([
                'success' => true,
                'exists' => true,
                'email' => $request->email
            ]);
        }

        return response()->json([
            'success' => false,
            'exists' => false,
            'message' => 'El correo electrónico no se encuentra registrado.'
        ], 404);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Por favor, ingresa un correo electrónico válido.',
        ]);

        // Verificar primero que el correo exista
        if (!User::where('email', $request->email)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correo electrónico no se encuentra registrado.'
                ], 404);
            }
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El correo electrónico no se encuentra registrado.']);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->wantsJson() || $request->ajax()) {
            if ($status == Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Enlace enviado con éxito! Revisa tu bandeja de entrada.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __($status)
            ], 422);
        }

        return $status == Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
