
@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/proveedores/style.css') }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @endpush

    @push('js')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        @include('principal.sidebar-menu')

        <script src="{{ asset('js/tools/modalManager.js') }}"></script>

        <script>
            var modalManager;

            document.addEventListener('DOMContentLoaded', function () {
                modalManager = new ModalManager();

                modalManager.addModal('modal-assessment');
                modalManager.addModal('modal-new-supplier');
                modalManager.addModal('base-modal-loading');
                modalManager.addModal('modal-graphi');
                modalManager.addModal('modal-factura');
                modalManager.addModal('base-image-modal');
            });
        </script>

        <script src="{{ asset('js/proveedores/script.js') }}"></script>
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Gestión de Proveedores - El Buen Sabor</title>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <!-- Tarjetas de Estadísticas Principales -->
                <div class="card-stadistics-container">
                    <div class="card-stadistics mascomprado-card">
                        <h3>Ingrediente más comprado</h3>
                        <p>Cargando...</p>
                    </div>
                    <div class="card-stadistics menoscomprado-card">
                        <h3>Ingrediente menos comprado</h3>
                        <p>Cargando...</p>
                    </div>
                    <div class="card-stadistics proveedorMenosSolicitado-card">
                        <h3>Proveedor menos solicitado</h3>
                        <p>Cargando...</p>
                    </div>
                </div>

                <!-- Barra de Herramientas y Filtros -->
                <div class="table-tools-container">
                    <x-searchbar id="search-bar-provider" classList="search-bar-provider"></x-searchbar>
                    <div class="filter-and-report">
                        <p>Filtrar por:</p>
                        <x-selectc name="filter-by-ingredient" id="filter-by-ingredient" classList="filter-select filter-by-ingredient">
                            <option value="">Todos los Ingredientes</option>
                        </x-selectc>
                        <x-selectc name="filter-by-cost" id="filter-by-cost" classList="filter-select filter-by-cost">
                            <option value="">Todos los Costos</option>
                        </x-selectc>
                        <button type="button" id="btn-reporte" class="btn-reporte" title="Descargar reporte PDF">Reporte</button>
                        <button type="button" id="btn-nuevo" class="btn-nuevo" title="Registrar nuevo proveedor">Nuevo</button>
                    </div>
                </div>

                <!-- Tabla de Proveedores -->
                <div class="table-providers-container">
                    <x-tablec tableClass="table-providers">
                        <x-slot name="colgroup">
                            <col style="width: 25%;">
                            <col style="width: 15%;">
                            <col style="width: 32%;">
                            <col style="width: 12%;">
                            <col style="width: 16%;">
                        </x-slot>
                        <x-slot name="thead">
                            <tr>
                                <th>Proveedor</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Facturas</th>
                                <th>Acciones</th>
                            </tr>
                        </x-slot>
                        <x-slot name="tbody">
                            <tr>
                                <td colspan="5">
                                    <div class="load-animation-container">
                                        <div class="load-animation"></div>
                                    </div>
                                </td>
                            </tr>
                        </x-slot>
                    </x-tablec>
                </div>
            </div>
        </div>
    </main>

    <x-toast></x-toast>

    <!-- Modal Nuevo / Editar Proveedor -->
    <div class="modal-new-supplier" id="modal-new-supplier">
        <div class="container-modal">
            <h3 class="modal-header-title" id="modal-title-supplier">Nuevo Proveedor</h3>
            <form id="form-add-proveedor" onsubmit="return false;" style="width: 100%;">
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="nameNewSupplier">Nombre del Proveedor *</label>
                        <input type="text" class="form-input" id="nameNewSupplier" name="nameNewSupplier" placeholder="Ej. Panificadora Central" required>
                    </div>

                    <div class="form-field">
                        <label for="phoneNewSupplier">Teléfono *</label>
                        <input type="tel" class="form-input" id="phoneNewSupplier" name="phoneNewSupplier" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7" placeholder="Ej. 7890-1234" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="emailNewSupplier">Correo Electrónico *</label>
                        <input type="email" class="form-input" id="emailNewSupplier" name="emailNewSupplier" placeholder="proveedor@correo.com" required>
                    </div>

                    <div class="form-field">
                        <label for="descriptionNewSupplier">Descripción / Notas</label>
                        <input type="text" class="form-input" id="descriptionNewSupplier" name="descriptionNewSupplier" placeholder="Notas sobre el proveedor">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="modalManager.hiddenModalId('modal-new-supplier')">Cancelar</button>
                    <button type="button" class="btn-modal-save" id="btn-save">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Gráfica de Proveedor -->
    <div class="modal-graphi" id="modal-graphi">
        <div class="container-modal">
            <h3 class="modal-header-title">Gastos en Compras del Proveedor</h3>
            <div class="graphi-supplier-container">
                <canvas id="graphi-supplier"></canvas>
            </div>
            <button type="button" class="btn-modal-cancel" onclick="modalManager.hiddenModalId('modal-graphi')">Cerrar</button>
        </div>
    </div>

    <!-- Modal Facturas del Proveedor -->
    <div class="modal-factura" id="modal-factura">
        <div class="container-modal">
            <h3 class="modal-header-title">Facturas del Proveedor</h3>
            <div class="factura-proveedor-container">
                <x-tablec tableClass="table-facturas">
                    <x-slot name="colgroup">
                        <col style="width: 30%;">
                        <col style="width: 30%;">
                        <col style="width: 25%;">
                        <col style="width: 15%;">
                    </x-slot>
                    <x-slot name="thead">
                        <tr>
                            <th>Emitido</th>
                            <th>Pagado</th>
                            <th>Total</th>
                            <th>Foto</th>
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        <tr>
                            <td colspan="4">
                                <div class="load-animation-container">
                                    <div class="load-animation"></div>
                                </div>
                            </td>
                        </tr>
                    </x-slot>
                </x-tablec>
            </div>
            <button type="button" class="btn-modal-cancel" onclick="closeModalFacturas()">Cerrar</button>
        </div>
    </div>

    <!-- Modal Ver Imagen de Factura -->
    <div class="base-image-modal" id="base-image-modal">
        <div class="image-modal" id="image-modal">
            <img src="{{ asset('assets/global/icon-factura.svg') }}" alt="Factura">
        </div>
        <button type="button" class="btn-modal-cancel" style="margin-top: 15px; color: white; background: #4a3525; border: none;" onclick="modalManager.hiddenModalId('base-image-modal')">Cerrar</button>
    </div>

    <!-- Modal Evaluación / Calificación de Estrellas -->
    <div class="modal-assessment" id="modal-assessment">
        <div class="container-modal">
            <h3 class="modal-header-title">Evaluación del Proveedor</h3>
            <div class="assessment-container-stars" title="Haga clic para calificar">
                <img src="{{ asset('assets/global/bar-star.png') }}" alt="Estrellas">
                <div></div>
            </div>
            <button type="button" class="btn-modal-cancel" style="margin-top: 15px;" onclick="closeMOdalAssement()">Cerrar</button>
        </div>
    </div>

    <!-- Modal Loading -->
    <div class="base-modal-loading" id="base-modal-loading">
        <div class="modal-loading" id="modal-loading">
            <div class="load-animation"></div>
            <h3>Procesando...</h3>
            <p>Esto puede tomar algunos segundos...</p>
        </div>
    </div>

</x-canvas>