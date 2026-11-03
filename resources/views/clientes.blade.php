@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/clientes/style.css') }}">
@endpush
@push('js')
<script src="/js/components/toast.js"></script>
<script src="{{ asset('js/tools/modalManager.js') }}"></script>
<script>
    var modalManager;
    window.addEventListener('DOMContentLoaded', function() {
        modalManager = new ModalManager();

        modalManager.addModal('modal-assessment');
        modalManager.addModal('modal-address');
        modalManager.addModal('modal-loading');
    })
</script>
@include('principal.sidebar-menu')
<script src="/js/clientes/script.js"></script>
@endpush
@endonce
<x-canvas>
    <x-slot name="header">
        <title>Control de clientes - El Buen Sabor</title>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <!-- Tarjetas de resumen -->
                <div class="cars-container">
                    <div class="card card-activo">
                        <div class="card-header">
                            <h2>Cliente mas activo</h2>
                        </div>
                        <div class="card-body">
                            <p id="cliente-mas-activo">Jose Monje</p>
                        </div>
                    </div>
                    <div class="card card-tendencia">
                        <div class="card-header">
                            <h2>Producto en tendencia</h2>
                        </div>
                        <div class="card-body">
                            <p id="producto-en-tendencia">Pan largo</p>
                        </div>
                    </div>
                    <div class="card card-inactivo">
                        <div class="card-header">
                            <h2>Cliente menos activo</h2>
                        </div>
                        <div class="card-body">
                            <p id="cliente-menos-activo">Fernando Gonzales</p>
                        </div>
                    </div>
                </div>

                <nav class="customer-tabs" role="tablist" aria-label="Listas de clientes">
                    <button type="button" class="customer-tab-btn active" id="tab-clientes" role="tab" aria-selected="true" aria-controls="customer-table-panel" data-customer-tab="clientes">Clientes</button>
                    <button type="button" class="customer-tab-btn" id="tab-pedidos-clientes" role="tab" aria-selected="false" aria-controls="customer-table-panel" data-customer-tab="pedidos">Pedidos</button>
                </nav>

                <div class="table-bar-container">
                    <div class="search-container">
                        <input type="text" id="search-input-table" placeholder="Buscar cliente...">
                        <button>
                            <img src="/assets/global/lupa.png" alt="">
                        </button>
                    </div>
                </div>
                <!--tabla de los clientes-->
                <div class="table-container" id="customer-table-panel" role="tabpanel" aria-labelledby="tab-clientes">
                    <table class="table-clientes">
                        <colgroup>
                            <col styles="width: 30%">
                            <col styles="width: 15%">
                            <col styles="width: 15%">
                            <col styles="width: 20%;">
                            <col styles="width: 20%;">
                        </colgroup>
                        <thead id="customer-table-head">
                            <tr>
                                <th>Nombre</th>
                                <th>Compras totales</th>
                                <th>Deuda total</th>
                                <th>Telefono</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="body-table">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <!--toast component-->
    <x-toast></x-toast>

    <!--Modal assessment-->
    <div class="modal-assessment" id="modal-assessment">
        <div class="form-modal-container">
            <h2 id="assessment-title">Cliente: Jorge Muños</h2>
            <span>Puntuar</span>
            <div class="starts-content">
                <img src="/assets/global/bar-star.png" alt="">
                <div class="stuffed"></div>
            </div>
            <span>Comentar</span>
            <textarea name="text-comment" id="text-comment" placeholder="comentario a cliente..."></textarea>
            <button class="modal-cerrar" onclick="cerrarModalAssesment();">
                Cerrar
            </button>
        </div>
    </div>

    <!--Modal Address-->
    <div class="modal-address" id="modal-address">
        <div class="address-modal-container">
            <img src="/assets/global/profile-picture.png" id="modal-address-img" alt="profile-picture">
            <span>Direccion:</span>
            <span id="modal-address-address">jorgecanua@gmail.com</span>
            <div class="btn-content">
                <button onclick="modalManager.hiddenModalId('modal-address');">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!--Modal loading...-->
    <div class="modal-loading" id="modal-loading">
        <div class="modal-loading-content">
            <h2>Esto puede tardar...</h2>
            <div class="loader">

            </div>
        </div>
    </div>

    <div class="customer-orders-modal" id="customer-orders-modal" aria-hidden="true">
        <div class="customer-orders-backdrop" data-close-customer-orders></div>
        <section class="customer-orders-dialog" role="dialog" aria-modal="true" aria-labelledby="customer-orders-title">
            <header class="customer-orders-heading">
                <div>
                    <h2 id="customer-orders-title">Pedidos del cliente</h2>
                    <p id="customer-orders-summary"></p>
                </div>
                <button type="button" class="customer-orders-close" data-close-customer-orders aria-label="Cerrar">×</button>
            </header>
            <div class="customer-orders-table-wrap">
                <table class="customer-orders-table">
                    <thead>
                        <tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Entrega</th><th>Total</th><th>Deuda</th></tr>
                    </thead>
                    <tbody id="customer-orders-body"></tbody>
                </table>
            </div>
        </section>
    </div>
</x-canvas>