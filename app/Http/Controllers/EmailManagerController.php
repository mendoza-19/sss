<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class EmailManagerController extends Controller
{
    public function emailVerify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->intended(route('dashboard'));
    }

    public function resendEmailVeirify(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Email de verificación enviado');
    }
}
