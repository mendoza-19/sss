<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado - El Buen Sabor</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f5f4ed;
            color: #252b25;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            background: linear-gradient(135deg, #f5f4ed 0%, #e8eee6 100%);
        }

        main {
            width: min(100%, 520px);
            padding: 40px;
            border: 1px solid #d5ddd3;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 18px 50px rgb(34 54 37 / 10%);
        }

        .code {
            margin: 0 0 12px;
            color: #52775b;
            font-size: 14px;
            font-weight: 700;
        }

        h1 { margin: 0 0 12px; font-size: 30px; }
        p { margin: 0; color: #5c665d; line-height: 1.6; }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        a, button {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border: 1px solid #52775b;
            border-radius: 5px;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        button { background: #52775b; color: #fff; }
        a { background: #fff; color: #35593d; }
        a:hover, button:hover { filter: brightness(.95); }

        @media (max-width: 480px) {
            main { padding: 28px 22px; }
            .actions { flex-direction: column; }
            .actions a, .actions button { width: 100%; }
        }
    </style>
</head>
<body>
    <main>
        <p class="code">ERROR 403</p>
        <h1>Acceso denegado</h1>
        <p>No tienes permiso para ver esta página. Regresa a la página anterior o vuelve al inicio.</p>
        <div class="actions">
            <button type="button" id="go-back">Volver</button>
            <a href="{{ route('menu') }}">Ir al inicio</a>
        </div>
    </main>
    <script>
        document.getElementById('go-back').addEventListener('click', function () {
            try {
                if (document.referrer && new URL(document.referrer).origin === window.location.origin) {
                    window.history.back();
                    return;
                }
            } catch (error) {
                // Use the public home page when the referrer cannot be parsed.
            }

            window.location.assign(@json(route('menu')));
        });
    </script>
</body>
</html>