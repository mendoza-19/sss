@php

use App\Http\Controllers\profileUserController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Empleado;

$user = User::where('email', Auth::user()->email)->first();

$isCustomer = $user && ($user->iscustomer == 1 || $user->cliente()->exists());

if ($isCustomer) {
$cliente = $user->cliente()->first();
} else if ($user) {
$empleado = $user->empleado()->first();
}

@endphp

<!DOCTYPE html>
<html lang="es-SV">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Panadería Delicia</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/css/perfil/style.css')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
    <script src="{{ asset('js/components/form-formats.js') }}" defer></script>
</head>

<body>
    @if (session('success'))
        <div class="alert-box profile-success" role="status" aria-live="polite" data-auto-dismiss>
            <strong>¡Éxito!</strong> {{ session('success') }}
        </div>
    @endif

    <div class="container">
        <!-- Panel Lateral -->
        <div class="sidebar">
            <div class="header-sidebar-container" onclick="window.location.href = '{{ route('menu') }}'">
                <div class="icon-home">
                    <svg
                        class="icon-home-img"
                        width="210mm"
                        height="222mm"
                        viewBox="0 0 210 222"
                        version="1.1"
                        id="svg1"
                        xml:space="preserve"
                        inkscape:version="1.4.4 (dcaf3e7, 2026-05-05)"
                        sodipodi:docname="icon-home.svg"
                        xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
                        xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:svg="http://www.w3.org/2000/svg">
                        <sodipodi:namedview
                            id="namedview1"
                            pagecolor="#ffffff"
                            bordercolor="#000000"
                            borderopacity="0.25"
                            inkscape:showpageshadow="2"
                            inkscape:pageopacity="0.0"
                            inkscape:pagecheckerboard="0"
                            inkscape:deskcolor="#d1d1d1"
                            inkscape:document-units="mm"
                            inkscape:zoom="0.44097222"
                            inkscape:cx="396.85039"
                            inkscape:cy="561.25984"
                            inkscape:window-width="1366"
                            inkscape:window-height="697"
                            inkscape:window-x="-8"
                            inkscape:window-y="-8"
                            inkscape:window-maximized="1"
                            inkscape:current-layer="layer1" />
                        <defs
                            id="defs1" />
                        <g
                            inkscape:label="Capa 1"
                            inkscape:groupmode="layer"
                            id="layer1">
                            <path
                                d="m 30.060606,212.96957 -4.594801,-0.0916 v -52.18808 -52.18808 l -4.240981,4.21414 -4.240981,4.21414 -6.304465,-6.43391 c -3.4674557,-3.53865 -6.3044653,-6.53038 -6.3044653,-6.64829 0,-0.20911 92.4810233,-92.327909 95.8564973,-95.481073 l 1.70074,-1.588722 51.93039,47.499915 c 28.56171,26.12496 52.36077,47.9263 52.88679,48.44744 l 0.95639,0.94751 -6.17243,6.71047 -6.17242,6.71047 -1.00045,-0.83515 c -0.55025,-0.45933 -2.23552,-1.96735 -3.74506,-3.35115 l -2.74461,-2.516 0.16327,37.61728 c 0.0898,20.6895 0.29204,40.21564 0.44941,43.39142 0.15738,3.17579 0.28614,9.37877 0.28614,13.78441 v 8.01025 l -77.05709,-0.0669 c -42.381385,-0.0368 -79.124733,-0.1081 -81.651874,-0.15849 z m 44.516849,-52.35123 v -34.19737 h 30.883085 30.88309 v 34.19737 34.19738 h 16.8914 16.8914 l -0.17011,-23.8779 c -0.0936,-13.13285 -0.24596,-33.53533 -0.33866,-45.33885 l -0.16856,-21.46095 5.71732,-0.0819 5.71732,-0.0819 -39.21809,-35.90286 -39.2181,-35.902858 -7.080501,6.977396 C 91.472767,42.983466 75.190308,59.17365 59.183808,75.12408 L 30.08108,104.12487 h 6.881966 6.881965 v 45.34542 45.34542 h 15.366222 15.366222 z m 43.614675,9.11428 -0.0771,-25.0831 -12.57921,-0.0786 -12.579198,-0.0786 v 25.16173 25.16173 h 12.656258 12.65627 z"
                                id="path1" />
                                </g>
                    </svg>
                </div>
                <div class="profile-summary">
                    @if ($cliente && filled($cliente->foto))
                        <img src="{{ $cliente->foto }}" alt="Avatar" id="sidebar-avatar">
                    @endif
                    <h3 id="sidebar-username">{{ Auth::user()->name }}</h3>
                </div>
            </div>
            <button class="tab-btn active" onclick="switchTab('datos')" value="datos" id="tab-datos">Mis Datos
            </button>
            <button class="tab-btn" onclick="switchTab('seguridad')" value="seguridad" id="tab-seguridad">Seguridad
            </button>
            <button class="tab-btn" onclick="switchTab('pendientes')" value="pendientes"
                id="tab-pendientes">Mis Pedidos</button>
            <button class="tab-btn" onclick="switchTab('pagos')" value="pagos" id="tab-pagos">
                Pagos Pendientes</button>
            <button class="tab-btn" onclick="switchTab('historial')" value="historial"
                id="tab-historial">Historial de Compras</button>
        </div>

        <!-- Contenido Derecho -->
        <div class="main-content">

            @if (session('error'))
            <div class="alert-box" style="background-color: #ffebee; border-left-color: #c62828; color: #b71c1c; margin-bottom: 20px;">
                <strong>Error:</strong> {{ session('error') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="alert-box" style="background-color: #ffebee; border-left-color: #c62828; color: #b71c1c; margin-bottom: 20px;">
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul style="margin-left: 20px; margin-top: 5px;">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- PESTAÑA: DATOS -->
            <div id="datos" class="tab-content {{ $errors->has('password') || $errors->has('current_password_sec') ? '' : 'active' }}">
                <h2>Modificar Perfil</h2>
                <form method="POST" action="{{ $isCustomer ? route('actualizarPerfil') : route('actualizarPerfilEmpleado') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="photo-upload">
                        <img
                            src="{{ $isCustomer ? ($cliente?->foto ?: asset('assets/global/profile-picture.png')) : profileUserController::getProfilePicture() }}"
                            alt="Avatar"
                            class="avart-img">
                        <div class="image-edit">
                            <label for="avatar">
                                <img src="{{ asset('/assets/global/editar_black_color.svg') }}" alt="editar avatar"
                                    class="pencil">
                            </label>
                            <br>
                            <input type="file" name="fotografia" id="avatar" accept="image/*" style="margin-top:5px;">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Usuario</label>
                            <input type="text" name="nombre_usuario" value="{{ old('nombre_usuario', Auth::user()->name) }}" required>
                            @error('nombre_usuario') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" required>
                            @error('email') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $user && $isCustomer ? ($cliente ? $cliente->nombre : '') : ($empleado ? $empleado->nombre : '')) }}" required>
                            @error('nombre') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" value="{{ old('apellido', $user && $isCustomer ? ($cliente ? $cliente->apellido : '') : ($empleado ? $empleado->apellido : '')) }}" required>
                            @error('apellido') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group full-width">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000" value="{{ old('telefono', $user && $isCustomer ? ($cliente ? $cliente->telefono : '') : ($empleado ? $empleado->telefono : '')) }}" required>
                            @error('telefono') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group full-width">
                            <label>Contraseña Actual (Requerida si cambias de correo electrónico)</label>
                            <div class="password_group">
                                <input type="password" name="current_password" placeholder="Ingresa tu contraseña para confirmar cambio de correo">
                                <img src="{{ asset('/assets/global/esconder.svg') }}" alt="ocultar contraseña" class="show-password">
                                <img src="{{ asset('/assets/global/vista.svg') }}" alt="ver contraseña" class="password_img_inactive show-password">
                            </div>
                            @error('current_password') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn">Guardar Cambios</button>
                </form>
            </div>

            <!-- PESTAÑA: SEGURIDAD -->
            <div id="seguridad" class="tab-content {{ $errors->has('password') || $errors->has('current_password') || $errors->has('current_password_sec') ? 'active' : '' }}">
                <h2>Cambiar Contraseña</h2>
                <div class="alert-box" style="margin-bottom: 20px;">
                    Por motivos de seguridad, para modificar tu contraseña debes confirmar tu contraseña actual y crear una nueva contraseña segura.
                </div>
                <form method="POST" action="{{ route('actualizarPassword') }}">
                    @csrf
                    <div class="form-grid" style="grid-template-columns: 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <div class="password_group">
                                <input type="password" name="current_password" required placeholder="••••••••">
                                <img src="{{ asset('/assets/global/esconder.svg') }}" alt="ocultar contraseña" class="show-password">
                                <img src="{{ asset('/assets/global/vista.svg') }}" alt="ver contraseña" class="password_img_inactive show-password">
                            </div>
                            @error('current_password') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                            @error('current_password_sec') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <div class="password_group">
                                <input type="password" name="password" required placeholder="••••••••">
                                <img src="{{ asset('/assets/global/esconder.svg') }}" alt="ocultar contraseña" class="show-password">
                                <img src="{{ asset('/assets/global/vista.svg') }}" alt="ver contraseña" class="password_img_inactive show-password">
                            </div>
                            <small style="color: #666; font-size: 12px;">Debe tener de 8 a 16 caracteres, e incluir mayúscula, minúscula, número y carácter especial (!@#$%^&*).</small>
                            @error('password') <span style="color: #c62828; font-size: 12px;">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <div class="password_group">
                                <input type="password" name="password_confirmation" required placeholder="••••••••">
                                <img src="{{ asset('/assets/global/esconder.svg') }}" alt="ocultar contraseña" class="show-password">
                                <img src="{{ asset('/assets/global/vista.svg') }}" alt="ver contraseña" class="password_img_inactive show-password">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn" style="margin-top: 15px;">Actualizar Contraseña</button>
                </form>
            </div>

            <!-- PESTAÑA: PEDIDOS PENDIENTES -->
            <div id="pendientes" class="tab-content">
                <h2>Estado de mis pedidos</h2>
                <div class="item-list">
                    @forelse ($pedidosPendientes as $pedido)
                        <div class="item-card">
                            <div class="item-info">
                                <h4>Encargo #{{ $pedido->idpedido }}</h4>
                                <p>
                                    @foreach ($pedido->detalles as $detalle)
                                        {{ $detalle->cantidad }}x {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}@unless ($loop->last), @endunless
                                    @endforeach
                                </p>
                                <p><small>Fecha de entrega: {{ $pedido->fecha_entrega?->format('d/m/Y') ?? 'Sin fecha' }}</small></p>
                                <p><small>Total: ${{ number_format($pedido->detalles->sum('subtotal'), 2) }}</small></p>
                                <p><small>Pago: {{ strtolower(trim((string) $pedido->estado_pago)) === 'pagada' ? 'Pagado' : 'Pago pendiente' }}</small></p>
                            </div>
                            <span class="status">{{ ucfirst(str_replace('_', ' ', $pedido->estado_produccion ?: 'pendiente')) }}</span>
                        </div>
                    @empty
                        <p>No tienes pedidos pendientes.</p>
                    @endforelse
                </div>
            </div>

            <!-- PESTAÑA: PAGOS PENDIENTES -->
            <div id="pagos" class="tab-content">
                <h2>Pagos Pendientes</h2>
                <div class="item-list">
                    @foreach ($pedidosPagoPendiente as $pedido)
                        <div class="item-card">
                            <div class="item-info">
                                <h4>Encargo #{{ $pedido->idpedido }}</h4>
                                <p>
                                    @foreach ($pedido->detalles as $detalle)
                                        {{ $detalle->cantidad }}x {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}@unless ($loop->last), @endunless
                                    @endforeach
                                </p>
                                <p><small>Monto estimado: ${{ number_format($pedido->detalles->sum('subtotal'), 2) }}</small></p>
                                <p><small>El cobro se registra al procesar el encargo.</small></p>
                            </div>
                            <span class="status pendiente">Pago pendiente</span>
                        </div>
                    @endforeach

                    @foreach ($ventasPendientes as $venta)
                        <div class="item-card">
                            <div class="item-info">
                                <h4>Saldo de venta #{{ $venta->idventa }}</h4>
                                <p>
                                    @foreach ($venta->detalleproductos as $detalle)
                                        {{ $detalle->cantidad }}x {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}@unless ($loop->last), @endunless
                                    @endforeach
                                </p>
                                <p><small>Total: ${{ number_format($venta->total, 2) }} · Abonado: ${{ number_format($venta->abonado_total, 2) }}</small></p>
                                <p><small>Saldo pendiente: ${{ number_format($venta->saldo_pendiente, 2) }}</small></p>
                            </div>
                            <span class="status pendiente">Pendiente</span>
                        </div>
                    @endforeach

                    @if ($pedidosPagoPendiente->isEmpty() && $ventasPendientes->isEmpty())
                        <p>No tienes pagos pendientes.</p>
                    @endif
                </div>
            </div>

            <!-- PESTAÑA: HISTORIAL DE COMPRAS -->
            <div id="historial" class="tab-content">
                <h2>Historial de Compras</h2>
                <div class="item-list">
                    @foreach ($pedidosHistorial as $pedido)
                        <div class="item-card">
                            <div class="item-info">
                                <h4>Pedido #{{ $pedido->idpedido }}</h4>
                                <p>
                                    @foreach ($pedido->detalles as $detalle)
                                        {{ $detalle->cantidad }}x {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}@unless ($loop->last), @endunless
                                    @endforeach
                                </p>
                                <p><small>Última actualización: {{ $pedido->updated_at?->format('d/m/Y') }}</small></p>
                                <p><small>Pago: {{ strtolower(trim((string) $pedido->estado_pago)) === 'pagada' ? 'Pagado' : 'Pago pendiente' }}</small></p>
                            </div>
                            <span class="status">{{ ucfirst(str_replace('_', ' ', $pedido->estado_produccion ?: 'pendiente')) }}</span>
                            <span class="price">${{ number_format($pedido->detalles->sum('subtotal'), 2) }}</span>
                        </div>
                    @endforeach

                    @foreach ($historialCompras as $venta)
                        <div class="item-card">
                            <div class="item-info">
                                <h4>Compra #{{ $venta->idventa }}</h4>
                                <p>
                                    @foreach ($venta->detalleproductos as $detalle)
                                        {{ $detalle->cantidad }}x {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}@unless ($loop->last), @endunless
                                    @endforeach
                                </p>
                                <p><small>Fecha: {{ \Illuminate\Support\Carbon::parse($venta->emision)->format('d/m/Y') }}</small></p>
                            </div>
                            <span class="status entregado">{{ $venta->estado }}</span>
                            <span class="price">${{ number_format($venta->total, 2) }}</span>
                        </div>
                    @endforeach

                    @if ($pedidosHistorial->isEmpty() && $historialCompras->isEmpty())
                        <p>Aún no tienes compras registradas.</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/perfil/script.js')
    @else
    <style>
        /*! tailwindcss v4.0.7 | MIT License | https://tailwindcss.com */
    </style>
    @endif
</body>

</html>