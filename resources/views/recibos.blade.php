@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recibos/style.css') }}">
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

                modalManager.addModal('modal-new-factura');
                modalManager.addModal('modal-graphi');
                modalManager.addModal('modal-detalle-factura');
                modalManager.addModal('base-image-modal');
                modalManager.addModal('base-modal-loading');
            });
        </script>

        <script src="{{ asset('js/recibos/script.js') }}?v={{ md5_file(public_path('js/recibos/script.js')) }}"></script>
    @endpush
@endonce
<x-canvas>
    <x-slot name="header">
        <title>Gestión de Recibos - El Buen Sabor</title>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <!-- Tarjetas de Estadísticas Principales -->
                <div class="card-stadistics-container">
                    <div class="card-stadistics total-card">
                        <h3>Recibos</h3>
                        <p id="stat-total-recibos">0</p>
                    </div>
                    <div class="card-stadistics pagados-card">
                        <h3>Pagados</h3>
                        <p id="stat-pagados-recibos">0</p>
                    </div>
                    <div class="card-stadistics pendientes-card">
                        <h3>Pendientes</h3>
                        <p id="stat-pendientes-recibos">0</p>
                    </div>
                </div>

                <!-- Barra de Herramientas y Filtros -->
                <div class="table-tools-container">
                    <x-searchbar id="search-bar-factura" classList="search-bar-factura"></x-searchbar>
                    <div class="filter-and-report">
                        <p>Filtrar por:</p>
                        <x-selectc name="filter-by-proveedor" id="filter-by-proveedor" classList="filter-select filter-by-proveedor">
                            <option value="">Todos los Proveedores</option>
                        </x-selectc>
                        <x-selectc name="filter-by-ingrediente" id="filter-by-ingrediente" classList="filter-select filter-by-ingrediente">
                            <option value="">Todos los Ingredientes</option>
                        </x-selectc>
                        <button type="button" class="btn-icon-tool" id="btn-grafica" title="Ver gráfica de gastos por mes">
                            <img src="{{ asset('assets/global/icon-statistics.svg') }}" alt="Gráfica">
                        </button>
                        <button type="button" id="btn-reporte" class="btn-reporte" title="Generar reporte PDF de gastos e ingredientes">Reporte</button>
                        <button type="button" id="btn-nuevo" class="btn-nuevo" title="Registrar nuevo recibo o factura">Nuevo</button>
                    </div>
                </div>

                <!-- Tabla de Recibos y Facturas -->
                <div class="table-facturas-container">
                    <x-tablec tableClass="table-facturas">
                        <x-slot name="colgroup">
                            <col style="width: 14%;">
                            <col style="width: 18%;">
                            <col style="width: 28%;">
                            <col style="width: 14%;">
                            <col style="width: 12%;">
                            <col style="width: 14%;">
                        </x-slot>
                        <x-slot name="thead">
                            <tr>
                                <th>Emisión</th>
                                <th>Pagado</th>
                                <th>Proveedor</th>
                                <th>Total</th>
                                <th>Factura</th>
                                <th>Acciones</th>
                            </tr>
                        </x-slot>
                        <x-slot name="tbody">
                            <tr>
                                <td colspan="6">
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

    <!-- Modal Nueva / Editar Factura -->
    <div class="modal-new-factura" id="modal-new-factura">
        <div class="container-modal">
            <h3 class="modal-header-title" id="modal-title-factura">Nueva Entrada de Factura / Recibo</h3>
            <form id="form-factura" onsubmit="return false;" style="width: 100%;">
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="factura-proveedor">Proveedor *</label>
                        <select id="factura-proveedor" name="proveedor_id" class="form-input" required>
                            <option value="">Cargando proveedores...</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="factura-emicion">Fecha de Emisión *</label>
                        <input type="date" id="factura-emicion" name="emicion" class="form-input" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-field">
                        <label class="checkbox-pagado-container">
                            <input type="checkbox" id="factura-pagado">
                            ¿Factura Pagada?
                        </label>
                    </div>

                    <div class="form-field">
                        <label for="factura-fechapago">Fecha de Pago</label>
                        <input type="date" id="factura-fechapago" name="fechapago" class="form-input" disabled>
                    </div>
                </div>

                <!-- Subida de Foto del Recibo Físico -->
                <div class="form-field">
                    <label>Foto del Recibo Físico</label>
                    <label for="factura-foto" class="photo-upload-area">
                        <img src="{{ asset('assets/global/icon-uploaded.svg') }}" class="upload-icon" alt="Subir">
                        <p>Haga clic para seleccionar o tomar foto de la factura física</p>
                        <img id="photo-preview-img" class="photo-preview" src="" alt="Preview">
                    </label>
                    <input type="file" id="factura-foto" name="foto" accept="image/*" style="display: none;">
                </div>

                <!-- Sección Dinámica: Ingredientes comprados -->
                <div class="section-detalles">
                    <h4>
                        <span><i class="fa-solid fa-boxes-stacked"></i> Ingredientes Comprados</span>
                    </h4>

                    <div class="add-item-row">
                        <div class="form-field">
                            <label style="font-size: 11px;">Ingrediente</label>
                            <select id="item-ingrediente" class="form-input">
                                <option value="">Seleccione ingrediente...</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label style="font-size: 11px;">Cantidad</label>
                            <input type="number" step="any" min="0" id="item-cantidad" class="form-input">
                        </div>
                        <div class="form-field">
                            <label style="font-size: 11px;">Valor Unit. ($)</label>
                            <input type="number" step="0.01" min="0" id="item-valor" class="form-input">
                        </div>
                        <div class="form-field">
                            <label style="font-size: 11px;">Vencimiento</label>
                            <input type="date" id="item-vencimiento" class="form-input">
                        </div>
                        <button type="button" id="btn-add-detalle-item" class="btn-add-item" title="Agregar ingrediente">+</button>
                    </div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Cantidad</th>
                                <th>Valor Unit.</th>
                                <th>Subtotal</th>
                                <th>Vencimiento</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="items-table-body">
                            <tr>
                                <td colspan="6" style="text-align: center; color: #888;">No hay ingredientes agregados todavía.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="total-summary-box">
                        <span class="total-label">Total de la Factura:</span>
                        <span class="total-value" id="display-total-factura">$ 0.00</span>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="modalManager.hiddenModalId('modal-new-factura')">Cancelar</button>
                    <button type="button" class="btn-modal-save" id="btn-save-factura">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Gráfica de Gastos por Mes -->
    <div class="modal-graphi" id="modal-graphi">
        <div class="container-modal">
            <h3 class="modal-header-title">Gastos en Compras por Mes</h3>
            <div class="graphi-facturas-container">
                <canvas id="graphi-gastos-mes"></canvas>
            </div>
            <button type="button" class="btn-modal-cancel" onclick="modalManager.hiddenModalId('modal-graphi')">Cerrar</button>
        </div>
    </div>

    <!-- Modal Detalle de Ingredientes de la Factura -->
    <div class="modal-detalle-factura" id="modal-detalle-factura">
        <div class="container-modal">
            <h3 class="modal-header-title">Ingredientes Comprados en este Recibo</h3>
            <div id="detalle-info-header" class="detalle-info-header"></div>
            <table class="items-table" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th>Ingrediente</th>
                        <th>Cantidad</th>
                        <th>Valor Unitario</th>
                        <th>Subtotal</th>
                        <th>Fecha de Vencimiento</th>
                    </tr>
                </thead>
                <tbody id="detalle-items-tbody"></tbody>
            </table>
            <button type="button" class="btn-modal-cancel" onclick="modalManager.hiddenModalId('modal-detalle-factura')">Cerrar</button>
        </div>
    </div>

    <!-- Modal Ver Foto de Factura Física -->
    <div class="base-image-modal" id="base-image-modal">
        <div class="image-modal">
            <img src="{{ asset('assets/global/icon-factura.svg') }}" alt="Foto de Factura">
        </div>
        <button type="button" class="btn-modal-cancel" style="margin-top: 15px; color: white; background: #4a3525; border: none;" onclick="modalManager.hiddenModalId('base-image-modal')">Cerrar</button>
    </div>

    <!-- Modal Loading -->
    <div class="base-modal-loading" id="base-modal-loading">
        <div class="modal-loading">
            <div class="load-animation"></div>
            <h3>Procesando...</h3>
            <p>Por favor espere unos segundos mientras se completa la operación.</p>
        </div>
    </div>
</x-canvas>