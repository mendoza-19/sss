<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Recuperar Contraseña - El Horno Dorado</title>
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
        <!-- PANTALLA 1: Verificar Correo -->
        <div id="pantalla-verificar">
            <div class="logo-icono">🥐</div>
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Ingresa tu correo electrónico registrado para verificar tu cuenta de cliente.</p>

            <div id="error-pantalla1" class="alerta alerta-error">El correo electrónico no se encuentra registrado.</div>

            <form id="form-verificar" onsubmit="validarCorreo(event)">
                <div class="grupo-formulario">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" placeholder="ejemplo@panaderia.com" required autocomplete="email">
                </div>
                <button type="submit" id="btn-verificar" class="btn-panaderia">Verificar Cuenta</button>
            </form>

            <a href="{{ route('login') }}" class="enlace-secundario" style="margin-top: 1.25rem; display: block;">Volver al inicio de sesión</a>
        </div>

        <!-- PANTALLA 2: Enviar Enlace -->
        <div id="pantalla-enviar" class="oculto">
            <div class="logo-icono">✉️</div>
            <h2>¡Cuenta encontrada!</h2>
            <p>Hemos verificado que perteneces a nuestra comunidad. Haz clic abajo para recibir un enlace de restablecimiento en <strong id="correo-confirmado"></strong>.</p>

            <div id="exito-pantalla2" class="alerta alerta-exito">¡Enlace enviado con éxito! Revisa tu bandeja de entrada.</div>
            <div id="error-pantalla2" class="alerta alerta-error"></div>

            <form id="form-enviar" onsubmit="enviarCodigo(event)">
                <button type="submit" id="btn-enviar" class="btn-panaderia">Enviar Enlace de Acceso</button>
            </form>

            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                <a href="#" class="enlace-secundario" onclick="regresarPantalla(event)">Usar otro correo</a>
                <a href="{{ route('login') }}" class="enlace-secundario">Volver al inicio de sesión</a>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let correoVerificado = '';

        async function validarCorreo(event) {
            event.preventDefault();
            const correoInput = document.getElementById('correo').value.trim();
            const errorAlerta = document.getElementById('error-pantalla1');
            const btnVerificar = document.getElementById('btn-verificar');

            if (!correoInput) return;

            errorAlerta.style.display = 'none';
            btnVerificar.disabled = true;
            btnVerificar.innerText = "Verificando...";

            try {
                const response = await fetch("{{ route('password.check-email') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email: correoInput })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    correoVerificado = data.email || correoInput;
                    document.getElementById('correo-confirmado').innerText = correoVerificado;

                    // Pasar a la pantalla 2
                    document.getElementById('pantalla-verificar').classList.add('oculto');
                    document.getElementById('pantalla-enviar').classList.remove('oculto');
                } else {
                    errorAlerta.innerText = data.message || 'El correo electrónico no se encuentra registrado.';
                    errorAlerta.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
                errorAlerta.innerText = 'Ocurrió un error al conectar con el servidor. Intenta de nuevo.';
                errorAlerta.style.display = 'block';
            } finally {
                btnVerificar.disabled = false;
                btnVerificar.innerText = "Verificar Cuenta";
            }
        }

        async function enviarCodigo(event) {
            event.preventDefault();
            const exitoAlerta = document.getElementById('exito-pantalla2');
            const errorAlerta = document.getElementById('error-pantalla2');
            const btnEnviar = document.getElementById('btn-enviar');

            exitoAlerta.style.display = 'none';
            errorAlerta.style.display = 'none';
            btnEnviar.disabled = true;
            btnEnviar.innerText = "Enviando Enlace...";

            try {
                const response = await fetch("{{ route('password.email') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email: correoVerificado })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    exitoAlerta.innerText = data.message || '¡Enlace enviado con éxito! Revisa tu bandeja de entrada.';
                    exitoAlerta.style.display = 'block';
                    btnEnviar.innerText = "Enlace Enviado";
                    btnEnviar.disabled = true;
                } else {
                    errorAlerta.innerText = data.message || 'Ocurrió un error al enviar el enlace. Intenta de nuevo.';
                    errorAlerta.style.display = 'block';
                    btnEnviar.disabled = false;
                    btnEnviar.innerText = "Enviar Enlace de Acceso";
                }
            } catch (err) {
                console.error(err);
                errorAlerta.innerText = 'Error de conexión con el servidor. Intenta de nuevo.';
                errorAlerta.style.display = 'block';
                btnEnviar.disabled = false;
                btnEnviar.innerText = "Enviar Enlace de Acceso";
            }
        }

        function regresarPantalla(event) {
            if (event) event.preventDefault();
            document.getElementById('pantalla-enviar').classList.add('oculto');
            document.getElementById('pantalla-verificar').classList.remove('oculto');
            document.getElementById('exito-pantalla2').style.display = 'none';
            document.getElementById('error-pantalla2').style.display = 'none';

            const btnEnviar = document.getElementById('btn-enviar');
            btnEnviar.disabled = false;
            btnEnviar.innerText = "Enviar Enlace de Acceso";
        }
    </script>
</body>

</html>