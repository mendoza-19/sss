@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}?v={{ md5_file(public_path('css/recetas/style.css')) }}-measure-selector">
    @endpush

    @push('js')
        @include('principal.sidebar-menu')
        <script src="{{ asset('js/tools/modalManager.js') }}"></script>
        <script src="{{ asset('js/recetas/script.js') }}?v={{ md5_file(public_path('js/recetas/script.js')) }}-measure-selector"></script>
    @endpush
@endonce

<x-canvas>
    @php($puedeAdministrarRecetas = auth()->user()->empleado?->rol?->descripcion === 'Administrador')

    <x-slot name="header">
        <title>Gestión de Recetas - El Buen Sabor</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>

        <div class="body-content">
            <x-navegation></x-navegation>

            <div class="page-content">
                <div class="recipe-page">
                    <div class="recipe-page-heading">
                        <div>
                            <h1>Recetas</h1>
                        </div>

                        @if ($puedeAdministrarRecetas)
                            <button type="button" id="btn-new-recipe" class="recipe-primary-button"
                                title="Registrar nueva receta">
                                <span aria-hidden="true">+</span>
                                Nueva receta
                            </button>
                        @endif
                    </div>

                    @if (session()->has('error'))
                        <div class="recipe-feedback recipe-feedback-error" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="recipe-table-tools">
                        <x-searchbar id="search-bar-recipe" classList="search-bar-recipe">
                        </x-searchbar>
                    </div>

                    <div class="recipe-table-container">
                        <x-tablec tableClass="recipe-table">
                            <x-slot name="colgroup">
                                <col style="width: 35%;">
                                <col style="width: 45%;">
                                <col style="width: 20%;">
                            </x-slot>

                            <x-slot name="thead">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </x-slot>

                            <x-slot name="tbody">
                                @forelse ($recetas as $receta)
                                    <tr data-recipe-row data-recipe-id="{{ $receta->idreceta }}"
                                        data-recipe-name="{{ $receta->nombre }}"
                                        data-recipe-description="{{ $receta->descripcion }}"
                                        data-recipe-instructivo="{{ $receta->instructivo }}"
                                        data-recipe-ingredients="{{ json_encode(
                                            $receta->ingredientes->map(function ($ingrediente) {
                                                    return [
                                                        'id' => $ingrediente->idingrediente,
                                                        'nombre' => $ingrediente->nombre,
                                                        'medida' => $ingrediente->medida,
                                                        'peso' => $ingrediente->pivot?->peso,
                                                        'medida_receta' => $ingrediente->pivot?->medida ?? $ingrediente->medida,
                                                        'stock' => (float) $ingrediente->stock,
                                                    ];
                                                })->values(),
                                            JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP,
                                        ) }}">

                                        <td>{{ $receta->nombre }}</td>
                                        <td>{{ $receta->descripcion }}</td>

                                        <td>
                                            <div class="recipe-actions">
                                                {{-- Ver receta --}}
                                                <button type="button" class="recipe-action-button" data-view-recipe
                                                    title="Ver receta" aria-label="Ver receta">
                                                    <img src="{{ asset('assets/global/vista.svg') }}" alt="">
                                                </button>

                                                @if ($puedeAdministrarRecetas)
                                                    {{-- Editar receta --}}
                                                    <button type="button" class="recipe-action-button" data-edit-recipe
                                                        title="Editar receta" aria-label="Editar receta">
                                                        <img src="{{ asset('assets/global/editar_black_color.svg') }}"
                                                            alt="">
                                                    </button>
                                                @endif

                                                {{-- Procesar receta --}}
                                                <button type="button" class="recipe-action-button" data-process-recipe
                                                    title="Procesar receta" aria-label="Procesar receta">
                                                    <span aria-hidden="true">🍳</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="recipe-empty-state">
                                            No hay recetas registradas.
                                        </td>
                                    </tr>
                                @endforelse

                                <tr id="recipe-no-results" hidden>
                                    <td colspan="3" class="recipe-empty-state">
                                        No se encontraron recetas.
                                    </td>
                                </tr>
                            </x-slot>
                        </x-tablec>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if ($puedeAdministrarRecetas)
    {{-- =====================================================
         MODAL 1: NUEVA RECETA Y EDICIÓN
    ====================================================== --}}

    <div class="recipe-modal-window" id="modal-new-recipe" aria-hidden="true"
        data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}">

        <section class="recipe-modal-card" role="dialog" aria-modal="true" aria-labelledby="recipe-modal-title">

            <div class="recipe-modal-heading">
                <h2 id="recipe-modal-title">Nueva receta</h2>
            </div>

            <form id="recipe-form" method="POST" action="{{ route('crearreceta') }}"
                data-create-url="{{ route('crearreceta') }}"
                data-update-url="{{ route('recetas.update', ['receta' => '__ID__']) }}">

                @csrf

                <input type="hidden" id="idreceta" name="idreceta" value="{{ old('idreceta') }}">

                <input type="hidden" name="_method" value="PUT" @disabled(!old('idreceta'))>

                <div class="recipe-form-grid">
                    <div class="recipe-form-group">
                        <label for="nombre">Nombre</label>

                        <input type="text" id="nombre" name="nombre" maxlength="60" value="{{ old('nombre') }}"
                            required>

                        @error('nombre')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="recipe-form-group">
                        <label for="descripcion">Descripción</label>

                        <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion') }}"
                            required>

                        @error('descripcion')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="recipe-form-group recipe-instructivo-group">
                        <label for="instructivo">Instructivo</label>

                        <textarea id="instructivo" name="instructivo" rows="6" required>{{ old('instructivo') }}</textarea>

                        @error('instructivo')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="recipe-ingredients-section">
                    <button type="button" id="btn-open-ingredient-modal" class="recipe-primary-button">
                        <span aria-hidden="true">+</span>
                        Agregar ingredientes
                    </button>
                </div>

                <div class="recipe-modal-actions">
                    <button type="button" id="btn-cancel-recipe" class="recipe-secondary-button">
                        Cancelar
                    </button>

                    <button type="submit" id="btn-save" class="recipe-primary-button">
                        Guardar receta
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- =====================================================
         MODAL 2: AGREGAR INGREDIENTES
    ====================================================== --}}

    <div class="recipe-modal-window" id="modal-new-ingredient" aria-hidden="true">

        <section class="recipe-modal-card" role="dialog" aria-modal="true"
            aria-labelledby="ingredient-modal-title">

            <div class="recipe-modal-heading">
                <h2 id="ingredient-modal-title">Agregar ingredientes</h2>
            </div>

            <div class="ingredient-input-row">
                <div class="recipe-form-group">
                    <label for="ingredient-name">Nombre del ingrediente</label>

                    <input type="text" id="ingredient-name" maxlength="100" placeholder="Buscar ingrediente..."
                        autocomplete="off" aria-controls="ingredient-search-results" aria-autocomplete="list">

                    <div id="ingredient-search-results" class="ingredient-search-results" role="listbox"
                        aria-label="Resultados de ingredientes" hidden>
                    </div>
                </div>

                <div class="recipe-form-group ingredient-quantity-group">
                    <label for="ingredient-amount">Cantidad</label>
                    <input type="text" id="ingredient-amount" inputmode="decimal"
                        pattern="(?:[0-9]+(?:[.,][0-9]{1,3})?|[.,][0-9]{1,3})" maxlength="12" required>
                </div>

                <div class="recipe-form-group ingredient-measure-group">
                    <label for="ingredient-measure">Unidad</label>
                    <select id="ingredient-measure" required>
                        <option value="" selected disabled>Selecciona una unidad</option>
                        <option value="u">u</option>
                        <option value="L">L</option>
                        <option value="ml">ml</option>
                        <option value="g">g</option>
                        <option value="kg">kg</option>
                        <option value="lb">lb</option>
                        <option value="oz">oz</option>
                    </select>
                </div>

                <button type="button" id="btn-confirm-ingredient"
                    class="recipe-primary-button ingredient-add-button">
                    <span aria-hidden="true">+</span>
                    Agregar
                </button>
            </div>

            <div class="recipe-table-container ingredient-table-scroll">
                <table class="recipe-table">
                    <colgroup>
                        <col style="width: 52%;">
                        <col style="width: 20%;">
                        <col style="width: 28%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Ingrediente</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="recipe-ingredients-list">
                        <tr id="recipe-ingredients-empty">
                            <td colspan="3" class="recipe-empty-state">
                                Todavía no has agregado ingredientes.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="recipe-modal-actions ingredient-modal-footer">
                <button type="button" class="recipe-secondary-button" id="btn-cancel-ingredient-modal">
                    Cerrar
                </button>
            </div>
        </section>
    </div>

    @endif

    {{-- =====================================================
         MODAL 3: VER DETALLES DE LA RECETA
    ====================================================== --}}

    <div class="recipe-modal-window" id="modal-view-recipe" aria-hidden="true">

        <section class="recipe-modal-card" role="dialog" aria-modal="true" aria-labelledby="view-recipe-title">

            <div class="recipe-modal-heading">
                <h2 id="view-recipe-title">Detalles de la receta</h2>
            </div>

            <div class="recipe-details-content">
                <div class="recipe-detail-group">
                    <h3>Nombre</h3>
                    <p id="view-recipe-name" class="recipe-detail-text"></p>
                </div>

                <div class="recipe-detail-group">
                    <h3>Ingredientes</h3>
                    <ul id="view-recipe-ingredients"
                        class="recipe-detail-text recipe-ingredient-list"></ul>
                </div>

                <div class="recipe-detail-group">
                    <h3>Instructivo</h3>
                    <p id="view-recipe-instructivo" class="recipe-detail-text recipe-instructivo-text"></p>
                </div>
            </div>

            <div class="recipe-modal-actions">
                <button type="button" id="btn-cancel-details" class="recipe-secondary-button">
                    Cerrar
                </button>
            </div>
        </section>
    </div>

    {{-- =====================================================
         MODAL 4: PROCESAR RECETA
    ====================================================== --}}

    <div class="recipe-modal-window" id="modal-process-recipe" aria-hidden="true">

        <section class="recipe-modal-card" role="dialog" aria-modal="true" aria-labelledby="process-recipe-title">

            <div class="recipe-modal-heading">
                <h2 id="process-recipe-title">Procesar receta</h2>
            </div>

            <form id="process-recipe-form" method="POST" action="{{ route('preparaciones.procesar') }}">

                @csrf

                <input type="hidden" name="receta_id" id="process-receta-id">

                <div class="recipe-form-grid">
                    <div class="recipe-form-group">
                        <label>Receta</label>
                        <p id="process-receta-nombre" class="recipe-info-text"></p>
                    </div>

                    <div class="recipe-form-group">
                        <label>Fecha de preparación</label>
                        <p id="process-fecha-texto" class="recipe-info-text"></p>

                        <input type="hidden" name="fecha" id="process-fecha">
                    </div>
                    <div class="recipe-form-group process-flour-input-group">
                        <label for="process-flour-count">Libras de harina a usar</label>
                        <input type="number" id="process-flour-count" name="libras_harina"
                            min="1" step="1" value="1" required>
                        <div id="process-flour-stock-message" class="process-flour-stock-message"
                            role="alert" hidden></div>
                    </div>
                </div>

                <div class="recipe-table-container">
                    <table class="recipe-table">
                        <colgroup>
                            <col style="width: 32%;">
                            <col style="width: 22%;">
                            <col style="width: 24%;">
                            <col style="width: 22%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Ingrediente</th>
                                <th>Por pan</th>
                                <th>Consumo estimado</th>
                                <th>Stock</th>
                            </tr>
                        </thead>

                        <tbody id="process-ingredients-list">
                            <tr>
                                <td colspan="4" class="recipe-empty-state">
                                    Selecciona una receta para ver sus ingredientes.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="recipe-modal-actions">
                    <button type="button" id="btn-cancel-process" class="recipe-secondary-button">
                        Cancelar
                    </button>

                    <button type="submit" id="btn-save-process" class="recipe-primary-button">
                        Enviar a Horneado
                    </button>
                </div>
            </form>
        </section>
    </div>

    <x-toast></x-toast>

    <span id="recipe-success-message" hidden>{{ session('success') }}</span>

    <div class="base-modal-loading" id="recipe-saving-overlay" hidden>

        <div class="modal-loading" id="modal-loading">
            <div class="load-animation"></div>
            <h3>Procesando...</h3>
            <p>Esto puede tomar algunos segundos...</p>
        </div>
    </div>
</x-canvas>
