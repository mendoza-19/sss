@php($puedeAdministrarIngredientes = auth()->user()->empleado?->rol?->descripcion === 'Administrador')

@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/ingredientes/style.css') }}">
        <!-- Google Fonts & FontAwesome CDN -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @endpush
    @push('js')
        <script src="{{ asset('js/tools/modalManager.js') }}"></script>

        <script>
            var modalManager;
            var puedeAdministrarIngredientes = @json($puedeAdministrarIngredientes);

            document.addEventListener('DOMContentLoaded', function() {
                modalManager = new ModalManager();

                if (puedeAdministrarIngredientes) {
                    modalManager.addModal('modal-new-ingrediente');
                }
                modalManager.addModal('modal-faltantes');
                modalManager.addModal('modal-proximos-vencer');
                modalManager.addModal('base-modal-loading');
            });
        </script>

        <script src="{{ asset('js/ingredientes/script.js') }}"></script>

        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Gestión de Ingredientes - El Buen Sabor</title>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">

                <!-- Sección: Estados de Ingredientes -->
                <div class="estados-section">
                    <h2 class="estados-title">Estados de Ingredientes</h2>
                    <div class="estados-buttons">
                        <button type="button" id="btn-faltantes" class="btn-estado btn-faltantes"
                            title="Ver ingredientes con stock bajo o agotados">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            Listar faltantes
                        </button>
                        <button type="button" id="btn-proximos-vencer" class="btn-estado btn-proximos"
                            title="Ver ingredientes próximos a vencer">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            Listar próximos a expirar
                        </button>
                    </div>
                </div>

                <!-- Tarjetas de Estadísticas -->
                <div class="card-stadistics-container">
                    <div class="card-stadistics total-ingredientes-card">
                        <h3>Total Ingredientes</h3>
                        <p id="stat-total-ingredientes">0</p>
                    </div>
                    <div class="card-stadistics stock-bajo-card">
                        <h3>Stock Bajo / Faltantes</h3>
                        <p id="stat-stock-bajo">0</p>
                    </div>
                    <div class="card-stadistics proximos-vencer-card">
                        <h3>Próximos a Vencer</h3>
                        <p id="stat-proximos-vencer">0</p>
                    </div>
                </div>

                <!-- Barra de Herramientas -->
                <div class="table-tools-container">
                    <x-searchbar id="search-bar-ingrediente" classList="search-bar-ingrediente"></x-searchbar>
                    <div class="filter-and-report">
                        @if ($puedeAdministrarIngredientes)
                            <button type="button" id="btn-nuevo-ingrediente" class="btn-nuevo"
                                title="Agregar nuevo ingrediente">
                                <i class="fa-solid fa-plus"></i>
                                Nuevo ingrediente
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Tabla de Ingredientes -->
                <div class="table-ingredientes-container" data-can-manage="{{ $puedeAdministrarIngredientes ? 'true' : 'false' }}">
                    <x-tablec tableClass="table-ingredientes">
                        <x-slot name="colgroup">
                            <col style="width: 25%;">
                            <col style="width: 15%;">
                            <col style="width: 45%;">
                            <col style="width: 15%;">
                        </x-slot>
                        <x-slot name="thead">
                            <tr>
                                <th>Nombre</th>
                                <th>Stock</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
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

            </div>
        </div>
    </main>

    <x-toast></x-toast>

    @if ($puedeAdministrarIngredientes)
    <!-- Modal Nuevo / Editar Ingrediente -->
    <div class="modal-new-ingrediente" id="modal-new-ingrediente">
        <div class="container-modal">
            <h3 class="modal-header-title" id="modal-title-ingrediente">Nuevo Ingrediente</h3>
            <form id="form-ingrediente" onsubmit="return false;" style="width: 100%;">
                <input type="hidden" id="ingrediente-id" name="idingrediente" value="">

                <div class="form-field">
                    <label for="ingrediente-nombre">Nombre del Ingrediente *</label>
                    <input type="text" class="form-input" id="ingrediente-nombre" name="nombre"
                        placeholder="Ej. Harina de trigo" maxlength="30" required>
                </div>
                <div class="form-grid-2">
                    <div class="form-field">
                        <label for="ingrediente-medida">Medida *</label>
                        <select class="form-input" id="ingrediente-medida" name="medida" required>
                            <option value="" selected disabled>Seleccione una medida</option>
                            <option value="g">Gramo (g)</option>
                            <option value="kg">Kilogramo (kg)</option>
                            <option value="lb">Libra (lb)</option>
                            <option value="oz">Onza (oz)</option>
                            <option value="u">Unidad (u)</option>
                            <option value="L">Litro (L)</option>
                            <option value="ml">Mililitro (ml)</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="ingrediente-stock">Stock actual</label>
                        <input type="number" class="form-input" id="ingrediente-stock" name="stock"
                            placeholder="Ej. 50" min="0" step="0.001" required>
                    </div>
                </div>

                <div class="form-field" style="margin-bottom: 14px;">
                    <label for="ingrediente-descripcion">Descripción</label>
                    <textarea class="form-input form-textarea" id="ingrediente-descripcion" name="descripcion"
                        placeholder="Descripción del ingrediente, notas de almacenamiento, etc." rows="3"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel"
                        onclick="modalManager.hiddenModalId('modal-new-ingrediente')">Cancelar</button>
                    <button type="button" class="btn-modal-save" id="btn-save-ingrediente">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    @endif

    <!-- Modal: Ingredientes Faltantes -->
    <div class="modal-faltantes" id="modal-faltantes">
        <div class="container-modal">
            <h3 class="modal-header-title">
                <i class="fa-solid fa-triangle-exclamation" style="color: #c0392b; margin-right: 8px;"></i>
                Ingredientes Faltantes / Stock Bajo
            </h3>
            <div class="modal-list-container">
                <x-tablec tableClass="table-modal-list">
                    <x-slot name="colgroup">
                        <col style="width: 40%;">
                        <col style="width: 30%;">
                        <col style="width: 30%;">
                    </x-slot>
                    <x-slot name="thead">
                        <tr>
                            <th>Ingrediente</th>
                            <th>Stock Actual</th>
                            <th>Estado</th>
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        <tr id="faltantes-tbody-placeholder">
                            <td colspan="3">
                                <div class="load-animation-container">
                                    <div class="load-animation"></div>
                                </div>
                            </td>
                        </tr>
                    </x-slot>
                </x-tablec>
            </div>
            <button type="button" class="btn-modal-cancel"
                onclick="modalManager.hiddenModalId('modal-faltantes')">Cerrar</button>
        </div>
    </div>

    <!-- Modal: Ingredientes Próximos a Vencer -->
    <div class="modal-proximos-vencer" id="modal-proximos-vencer">
        <div class="container-modal">
            <h3 class="modal-header-title">
                <i class="fa-solid fa-calendar-xmark" style="color: #e67e22; margin-right: 8px;"></i>
                Ingredientes Próximos a Vencer
            </h3>
            <p class="modal-subtitle">Lotes con vencimiento en los próximos 30 días</p>
            <div class="modal-list-container">
                <x-tablec tableClass="table-modal-list">
                    <x-slot name="colgroup">
                        <col style="width: 30%;">
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col style="width: 30%;">
                    </x-slot>
                    <x-slot name="thead">
                        <tr>
                            <th>Ingrediente</th>
                            <th>Cantidad (uds.)</th>
                            <th>Valor Unit.</th>
                            <th>Fecha Vencimiento</th>
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        <tr id="proximos-tbody-placeholder">
                            <td colspan="4">
                                <div class="load-animation-container">
                                    <div class="load-animation"></div>
                                </div>
                            </td>
                        </tr>
                    </x-slot>
                </x-tablec>
            </div>
            <button type="button" class="btn-modal-cancel"
                onclick="modalManager.hiddenModalId('modal-proximos-vencer')">Cerrar</button>
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
