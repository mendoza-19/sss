<!DOCTYPE html>
<html lang="es">
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica tu cuenta - Panadería El Horno Dulce</title>
    <!-- Vinculación del archivo CSS externo -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/auth/verify-email/style.css')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
    <!-- Iconos de Google Fonts (Material Symbols) -->
    <link rel="stylesheet" href="https://googleapis.com" />
</head>

<body>

    <div class="verification-container">
        <!-- Encabezado con temática de panadería -->
        <div class="brand-header">
            <span class="material-symbols-outlined bakery-icon">EL BUEN SABOR</span>
            <h1>¡Ya huele a pan!</h1>
        </div>

        <!-- Ilustración visual del correo -->
        <div class="email-illustration">
            <span class="material-symbols-outlined mail-icon">{{ Auth::user()->email }}</span>
        </div>

        <!-- Mensaje principal -->
        <div class="message-content">
            <h2>Verifica tu correo electrónico</h2>
            <p>Hemos enviado un enlace de confirmación a tu correo. Por favor, revisa tu bandeja de entrada para activar
                tu cuenta.</p>
        </div>

        <!-- Acciones disponibles -->
        <form action="{{ route('verification.send') }}" method="post">
            @csrf
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">Reenviar enlace de verificación</button>
            </div>
        </form>

        <!-- Enlace de salida o soporte -->
        <div class="footer-support">
            <p>¿Tienes problemas? <a href="#">Contactar a soporte</a> o <a href="{{ route('logout') }}">Cerrar sesión</a></p>
        </div>
    </div>
</body>

</html>