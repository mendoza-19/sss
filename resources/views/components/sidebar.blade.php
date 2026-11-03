@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/sidebar.css') }}?v={{ md5_file(public_path('css/components/sidebar.css')) }}">
@endpush
@push('js')
<script src="{{ asset('js/services/getSVGUrl.js') }}?v={{ md5_file(public_path('js/services/getSVGUrl.js')) }}"></script>
<script src="{{ asset('js/components/sidebar.js') }}?v={{ md5_file(public_path('js/components/sidebar.js')) }}"></script>
@endpush
@endonce

<div class="sidebar-contain sidebar-contain-show">
    <div class="sidebar-header">
        <img src="{{ asset('assets/global/icon-sidebar.svg') }}" alt="toggle-sidebar" class="sidebar-toggle-button ">
        <img src="{{ asset('assets/global/logo-panaderia.png') }}" alt="logo" class="sidebar-logo" onClick='window.location.href="{{ route("menu") }}";' />
    </div>
    <div class="sidebar-menu">
        <!--<div class="option-menu" data-menu-key="Dashboard" onclick="window.location.href='{{ route('dashboard') }}'">
            <img src="{{ asset('assets/global/icon-home.svg') }}" alt="Dashboard">
            <a>Dashboard</a>
        </div>
        <div class="option-menu" data-menu-key="Proveedores" onclick="window.location.href='{{ route('Proveedores') }}'">
            <img src="{{ asset('assets/global/icon-proveedor.svg') }}" alt="Proveedores">
            <a>Proveedores</a>
        </div>
        <div class="option-menu" data-menu-key="Recibos" onclick="window.location.href='{{ route('recibos') }}'">
            <img src="{{ asset('assets/global/icon-factura.svg') }}" alt="Recibos">
            <a>Recibos</a>
        </div>
        <div class="option-menu" data-menu-key="Empleados" onclick="window.location.href='{{ route('empleados.index') }}'">
            <img src="{{ asset('assets/global/icon-proveedor.svg') }}" alt="Empleados">
            <a>Empleados</a>
        </div>
        <div class="option-menu" data-menu-key="Recetas" onclick="window.location.href='{{ route('recetas.index') }}'">
            <img src="{{ asset('assets/global/icon-file.svg') }}" alt="Recetas">
            <a>Recetas</a>
        </div>
        <div class="option-menu" data-menu-key="Productos" onclick="window.location.href='{{ route('productos') }}'">
            <img src="{{ asset('assets/global/icon-file.svg') }}" alt="Productos">
            <a>Productos</a>
        </div>
        <div class="option-menu" data-menu-key="Ventas" onclick="window.location.href='{{ route('ventas.index') }}'">
            <img src="{{ asset('assets/global/icon-factura.svg') }}" alt="Ventas">
            <a>Ventas</a>
        </div>-->
    </div>
    <div class="sidebar-footer">
        <div class="sidebar-settings-icon" role="img" aria-label="Configuración" title="Configuración">
            <img src="{{ asset('assets/global/icon-settings.svg') }}" alt="">
        </div>
    </div>
</div>
