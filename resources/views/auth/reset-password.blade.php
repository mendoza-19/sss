<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - El Horno Dorado</title>
    <link href="https://googleapis.com" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/password-restore/style.css')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
</head>

<body>

    <div class="contenedor-bakery">
        <div class="logo-icono">🔑</div>
        <h2>Crea tu nueva contraseña</h2>
        <p>Ingresa tu nueva contraseña para volver a acceder a tu cuenta de cliente.</p>

        @if ($errors->any())
        <div class="alerta alerta-error" style="display: block;">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div class="grupo-formulario">
                <label for="email">Correo Electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" placeholder="ejemplo@panaderia.com">
            </div>

            <!-- Password -->
            <div class="grupo-formulario">
                <label for="password">Nueva Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Tu nueva contraseña">
            </div>

            <!-- Confirm Password -->
            <div class="grupo-formulario">
                <label for="password_confirmation">Confirmar Contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Confirma tu contraseña">
            </div>

            <button type="submit" class="btn-panaderia">Restablecer Contraseña</button>
        </form>

        <a href="{{ route('login') }}" class="enlace-secundario" style="margin-top: 1.25rem; display: block;">Volver al inicio de sesión</a>
    </div>

</body>

</html>

