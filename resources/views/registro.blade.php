<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Usuario</title>
    <!--<link rel="stylesheet" href="style.css">-->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/registro/style.css')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
</head>

<body>

    <div class="container">
        <div class="header">
            <h1>Nuevo Usuario</h1>
            <img src="{{ asset('assets/global/bread.png') }}" alt="pan" class="icon1">
            <img src="{{ asset('assets/global/bread.png') }}" alt="pan" class="icon2">
        </div>

        @if (session('error'))
        <div class="alert-danger">
            {{ session('error') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="alert-danger">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif
        <form method="POST" action="{{ route('validar-registro') }}" id="form-registro" novalidate>
            @csrf
            <div class="form-group" id="group-nombre">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" value="{{ old('nombre') }}">
                <span class="error-message" id="error-nombre"></span>
            </div>

            <div class="form-group" id="group-apellido">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" placeholder="Tu apellido" value="{{ old('apellido') }}">
                <span class="error-message" id="error-apellido"></span>
            </div>

            <div class="form-group" id="group-email">
                <label for="correo">Correo</label>
                <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" value="{{ old('email') }}">
                <span class="error-message" id="error-email"></span>
            </div>

            <div class="form-group" id="group-password">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="********" value="{{ old('password') }}">
                <span class="error-message" id="error-password"></span>
            </div>

            <div class="form-group" id="group-confirmar">
                <label for="confirmar">Confirmar Contraseña</label>
                <input type="password" name="confirmar" id="confirmar" placeholder="********" value="{{ old('confirmar') }}">
                <span class="error-message" id="error-confirmar"></span>
            </div>

            <!--<button type="submit" class="btn btn-register">Registrarse</button>-->
            <input type="submit" value="Registrarse" class="btn btn-register">

            <button type="button" class="btn btn-google" onclick="window.location.href = '{{ route('google.login') }}'">
                <img src="{{ asset('assets/google/google-icon.png') }}" alt="Google">
                Acceder con Google
            </button>
        </form>
    </div>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/registro/script.js')
    @else
    <script src="{{ asset('js/registro/script.js') }}"></script>
    @endif
</body>

</html>