@once
@push('styless')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/pedidosOfertas/styles.css') }}">
@endpush
@push('js')
<script src="{{ asset('js/tools/modalManager.js') }}"></script>
<script src="{{ asset('js/pedidosOfertas/script.js') }}"></script>
@include('principal.sidebar-menu')
@endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Pedidos y Ofertas - El Buen Sabor</title>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <!-- Tarjetas de resumen -->
                <div class="cars-container">
                    <div class="card card-pendientes">
                        <div class="card-header">
                            <h2>Pedidos pendientes</h2>
                        </div>
                        <div class="card-body">
                            <p id="pedidos-pendientes">0 pedidos</p>
                        </div>
                    </div>
                    <div class="card card-atrasados">
                        <div class="card-header">
                            <h2>Pedidos atrasados</h2>
                        </div>
                        <div class="card-body">
                            <p id="pedidos-atrasados">0 pedidos</p>
                        </div>
                    </div>
                    <div class="card card-procesados">
                        <div class="card-header">
                            <h2>Total pedidos procesados hoy</h2>
                        </div>
                        <div class="card-body">
                            <p id="pedidos-procesados">0 pedidos</p>
                        </div>
                    </div>
                </div>

                <!-- Barra y Tabla de Pedidos -->
                <div class="pedidos-table-container">
                    <div class="bar-pedidos-container">
                        <div class="filtro-bar-container">
                            @if ($esRepartidor)
                                <p>Filtrar por:</p>
                                <select name="filtro-dropdown-pedidos" class="filter-table" id="filtro-dropdown-pedidos">
                                    <option value="mas-reciente" selected>Más reciente</option>
                                    <option value="mas-antiguo">Más antiguo</option>
                                    <option value="pendiente">Solo Pendientes</option>
                                    <option value="en camino">Solo En Camino</option>
                                    <option value="atrasado">Solo Atrasados</option>
                                    <option value="entregado">Solo Entregados</option>
                                </select>
                            @else
                                <p class="pedidos-admin-filter-label">Pendientes por asignar repartidor</p>
                            @endif

                            <div class="search-pedidos-container">
                                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                <input type="text" id="input-search-pedidos" placeholder="Buscar por cliente o dirección...">
                            </div>
                        </div>

                        <div class="toolbar-buttons">
                            <!--el boton nuevo pedido no tendria que estar disponible para los empleados solo para el cliente que quiere hacer un pedido-->
                            <!--<button class="bar-inputs button-nuevo-pedido" id="btn-abrir-nuevo-pedido" type="button">
                                <i class="fa-solid fa-plus"></i> Nuevo pedido
                            </button>-->
                            @unless ($esRepartidor)
                                <button class="bar-inputs button-oferta" id="btn-lista-ofertas" type="button">
                                    <i class="fa-solid fa-tags"></i> Lista de ofertas
                                </button>
                            @endunless
                        </div>
                    </div>

                    <!-- Tabla de pedidos -->
                    <div class="table-container">
                        <div class="scroll-table-container">
                            <table>
                                <colgroup>
                                    @if ($esRepartidor)
                                        <col style="width: 16%" />
                                        <col style="width: 11%" />
                                        <col style="width: 18%" />
                                        <col style="width: 12%" />
                                        <col style="width: 15%" />
                                        <col style="width: 14%" />
                                        <col style="width: 14%" />
                                    @else
                                        <col style="width: 15%" />
                                        <col style="width: 9%" />
                                        <col style="width: 16%" />
                                        <col style="width: 10%" />
                                        <col style="width: 12%" />
                                        <col style="width: 10%" />
                                        <col style="width: 10%" />
                                        <col style="width: 18%" />
                                    @endif
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Nombre completo</th>
                                        <th>Teléfono</th>
                                        <th>Detalle</th>
                                        <th>Emisión</th>
                                        <th>Tipo / entrega</th>
                                        <th>Estado</th>
                                        <th>Pago</th>
                                        @unless ($esRepartidor)
                                            <th>Repartidor</th>
                                        @endunless
                                    </tr>
                                </thead>
                                <tbody class="table-body-content" id="pedidos-table-body">
                                    <tr id="loader-row">
                                        <td colspan="{{ $esRepartidor ? 7 : 8 }}" style="text-align:center;padding:24px;">
                                            <div class="loader"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Lista de Ofertas -->
    <div class="modalOferta" id="modalOferta">
        <div class="title-modal">
            <h3>Lista de ofertas</h3>
        </div>
        <div class="buttom-nueva-oferta-container">
            <div class="button-options">
                <button class="buttom-cerrar-modal" type="button" onclick="modalManager.hiddenModalId('modalOferta')">
                    Cerrar
                </button>
                <button class="butttom-nueva-oferta" id="btn-abrir-nueva-oferta" type="button">
                    <i class="fa-solid fa-plus"></i> Realizar oferta
                </button>
            </div>
        </div>
        <div class="table-ofertas-container">
            <table class="table-oferta">
                <colgroup>
                    <col style="width: 22%">
                    <col style="width: 14%">
                    <col style="width: 14%">
                    <col style="width: 14%">
                    <col style="width: 22%">
                    <col style="width: 14%">
                </colgroup>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Descuento</th>
                        <th>Producto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="ofertas-table-body">
                    <tr>
                        <td colspan="6" style="text-align:center;padding:20px;">
                            <div class="loader"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva Oferta -->
    <div class="modal-nueva-oferta" id="modal-nueva-oferta">
        <div class="title-modal">
            <h3>Nueva oferta</h3>
        </div>
        <div class="form-oferta-grid">
            <div class="form-group-oferta">
                <label for="nueva-oferta-inicio">Inicia</label>
                <input type="date" id="nueva-oferta-inicio">
            </div>
            <div class="form-group-oferta">
                <label for="nueva-oferta-fin">Finaliza</label>
                <input type="date" id="nueva-oferta-fin">
            </div>
            <div class="form-group-oferta">
                <label for="nueva-oferta-descuento">Descuento (%)</label>
                <input type="number" id="nueva-oferta-descuento" min="1" max="100" placeholder="Ej: 15">
            </div>
            <div class="form-group-oferta">
                <label for="nueva-oferta-producto">Producto</label>
                <select id="nueva-oferta-producto">
                    <option value="">Cargando productos...</option>
                </select>
            </div>
        </div>

        <div class="search-cliente-header">
            <div class="cliente-search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="search-cliente-oferta" placeholder="Buscar cliente por nombre o correo...">
            </div>
            <div id="cliente-seleccionado-info" class="cliente-seleccionado-badge warning">
                Ningún cliente seleccionado
            </div>
        </div>

        <div class="table-clientes-wrapper">
            <table class="table-clientes">
                <colgroup>
                    <col style="width: 8%;">
                    <col style="width: 32%;">
                    <col style="width: 44%;">
                    <col style="width: 16%;">
                </colgroup>
                <thead>
                    <tr>
                        <th></th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Ofertas</th>
                    </tr>
                </thead>
                <tbody id="clientes-oferta-table-body">
                    <tr>
                        <td colspan="4" style="text-align:center;padding:16px;">
                            <div class="loader"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="option-modal-container">
            <button type="button" class="guardar-oferta" id="btn-guardar-oferta">
                Guardar oferta
            </button>
            <button type="button" class="cerrar-modal-oferta" onclick="modalManager.hiddenModalId('modal-nueva-oferta')">
                Cerrar
            </button>
        </div>
    </div>

    <!-- Modal Nuevo Pedido -->
    <div class="modal-nuevo-pedido modal-container" id="modal-nuevo-pedido">
        <div class="title-modal">
            <h3>Registrar nuevo pedido</h3>
        </div>
        <div class="form-pedido-vertical">
            <div class="form-group">
                <label for="nuevo-pedido-cliente">Cliente</label>
                <select id="nuevo-pedido-cliente">
                    <option value="">Cargando clientes...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="nuevo-pedido-direccion">Dirección de entrega</label>
                <textarea id="nuevo-pedido-direccion" rows="3" placeholder="Ingresa la dirección completa para el pedido..."></textarea>
            </div>
            <div class="form-group">
                <label for="nuevo-pedido-estado">Estado inicial</label>
                <select id="nuevo-pedido-estado">
                    <option value="pendiente" selected>Pendiente</option>
                    <option value="en camino">En camino</option>
                    <option value="entregado">Entregado</option>
                </select>
            </div>
        </div>
        <div class="option-modal-container">
            <button type="button" class="guardar-oferta" id="btn-guardar-pedido">
                Guardar pedido
            </button>
            <button type="button" class="cerrar-modal-oferta" onclick="modalManager.hiddenModalId('modal-nuevo-pedido')">
                Cancelar
            </button>
        </div>
    </div>

    <div class="modal-detalle-pedido modal-container" id="modal-detalle-pedido">
        <div class="title-modal">
            <h3 id="detalle-pedido-title">Detalle del pedido</h3>
        </div>
        <div class="detalle-pedido-address">
            <span>Dirección de entrega</span>
            <strong id="detalle-pedido-address">-</strong>
        </div>
        <div class="detalle-pedido-products">
            <h4>Productos</h4>
            <table>
                <thead><tr><th>Producto</th><th>Cantidad</th></tr></thead>
                <tbody id="detalle-pedido-products-body"></tbody>
            </table>
        </div>
        <div class="detalle-pedido-actions">
            <button type="button" class="buttom-cerrar-modal" onclick="modalManager.hiddenModalId('modal-detalle-pedido')">Cerrar</button>
        </div>
    </div>

    <x-toast></x-toast>
    <span id="usuario-es-repartidor" data-value="{{ $esRepartidor ? 'true' : 'false' }}" hidden></span>
</x-canvas>