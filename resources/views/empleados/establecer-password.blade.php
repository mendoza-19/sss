<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer contraseña de empleado</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/password-restore/style.css')
    @endif
</head>

<body>
    <div class="contenedor-bakery">
        <div class="logo-icono">🔑</div>
        <h2>Establece tu contraseña</h2>
        <p>Elige una contraseña para acceder a tu cuenta de empleado.</p>

        @if ($errors->any())
            <div class="alerta alerta-error" style="display: block;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('empleados.password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="grupo-formulario">
                <label for="email">Correo electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->query('email')) }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="grupo-formulario">
                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}"
                    title="Debe incluir una mayúscula, una minúscula, un número, un carácter especial y al menos 8 caracteres."
                    autocomplete="new-password"
                >

                <small>
                    La contraseña debe tener:
                    <br>✓ Mínimo 8 caracteres
                    <br>✓ Una letra mayúscula
                    <br>✓ Una letra minúscula
                    <br>✓ Un número
                    <br>✓ Un carácter especial (@, #, $, !)
                </small>
            </div>

            <div class="grupo-formulario">
                <label for="password_confirmation">Reescribe la contraseña</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-panaderia">Guardar contraseña</button>
        </form>
    </div>
</body>

</html>
