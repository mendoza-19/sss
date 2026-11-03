<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - El Buen Sabor</title>
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link href="https://googleapis.com/css2?family=Impact&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <!--<link rel="stylesheet" href="styles.css">-->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/login/styles.css')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
</head>

<body>
    <input type="hidden" name="userId" value="{{  session('error')  }}">
    <input type="hidden" name="userId" value="{{  session('error')  }}">
    <div class="login-container">
        <!-- Sección Izquierda: Logo y Fondo -->
        <div class="brand-section">
            <div class="logo-container">
                <div class="logo-text">EL BUEN<br>SABOR</div>
                <div class="logo-circle">
                    <div>
                        <div>
                            <!-- Aquí puedes colocar una imagen de pan si lo deseas -->
                            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-log-left">
                            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-log-right">
                            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-log-left">
                            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-log-right">
                            <img src="{{ asset('assets/login/trigo-left.png') }}" alt="bread" class="trigo-logo-left">
                            <img src="{{ asset('assets/login/trigo-right.png') }}" alt="bread" class="trigo-logo-right">
                        </div>
                    </div>
                </div>

            </div>
            <!-- Elementos decorativos (simulados con texto/emojis, reemplazables por imágenes) -->
            <img src="{{ asset('assets/login/baguette.png') }}" alt="baguette" class="baguette-top-left">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-top-right">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-one">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-two">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-three">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-four">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-top-right">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-one">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-two">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-three">
            <img src="{{ asset('assets/global/bread.png') }}" alt="bread" class="bread-bottom-left-four">
            <img src="{{ asset('assets/login/baguette.png') }}" alt="baguette" class="baguette-bottom-right">
            <!--<div class="decor baguette-bottom-right">🥖</div>-->
        </div>

        <!-- Sección Derecha: Formulario -->
        <div class="form-section">
            <h2>BIENVENIDO</h2>
            <h3>INICIAR SESIÓN</h3>

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

            <form method="POST" action="{{ route('validar-login') }}" id="form-login" novalidate>
                @csrf
                <div class="input-group" id="group-username">
                    <label for="username">correo electrónico</label>
                    <input type="email" id="username" name="email" value="{{ old('email') }}" placeholder="Tu correo electrónico">
                    <span class="error-message" id="error-username"></span>
                </div>

                <div class="input-group" id="group-password">
                    <label for="password">contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Tu contraseña">
                    <span class="error-message" id="error-password"></span>
                </div>

                <!--<button type="submit" class="btn-submit">ACCEDER</button>-->
                <input type="submit" value="ACCEDER" class="btn-submit">
            </form>

            <button class="btn-google" type="button" onclick="window.location.href = '{{ route('google.login') }}'">
                <img src="{{ asset('assets/google/google-icon.png') }}" alt="Google" class="google-icon"> ACCEDER CON GOOGLE
            </button>

            <div class="form-links">
                <a href="{{ route('password-restore') }}">Olvidé mi contraseña</a>
                <span class="separator">ó</span>
                <a href="{{ route('registro') }}">Crear cuenta</a>
            </div>
        </div>
    </div>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/login/script.js')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif

</body>

</html>