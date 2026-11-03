@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/ventas/style.css') }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
        <script src="{{ asset('js/ventas/script.js') }}"></script>
        <script src="{{ asset('js/ventas/asignaciones-refresh.js') }}"></script>
    @endpush
@endonce

<x-canvas>
    <x-slot name="header"><title>Ventas - El Buen Sabor</title></x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <section class="recipe-page sales-page">
                    <header class="recipe-page-heading">
                        <div>
                            <h1>Ventas</h1>
                        </div>
                        <div class="sales-heading-actions">
                            <button type="button" id="btn-new-local-encargo" class="recipe-secondary-button">Encargo local</button>
                            <button type="button" id="btn-new-sale" class="recipe-primary-button"><span aria-hidden="true">+</span> Nueva venta</button>
                        </div>
                    </header>

                    @if (session('error'))<div class="recipe-feedback recipe-feedback-error" role="alert">{{ session('error') }}</div>@endif
                    @if ($errors->any())<div class="recipe-feedback recipe-feedback-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                    <nav class="sales-tabs-nav" role="tablist" aria-label="Secciones de ventas">
                        <button type="button" class="sales-tab-btn active" data-sales-tab="ventas" role="tab" aria-selected="true" id="tab-ventas">
                            Venta local ({{ $ventas->count() }})
                        </button>
                        <button type="button" class="sales-tab-btn" data-sales-tab="vendedores" role="tab" aria-selected="false" id="tab-vendedores">
                            Venta ambulante ({{ $ventasPorVendedor->count() }})
                        </button>
                        <button type="button" class="sales-tab-btn" data-sales-tab="pedidos" role="tab" aria-selected="false" id="tab-pedidos">
                            Procesar pedidos
                            @if ($pedidosPendientes->count() > 0)
                                <span class="sales-badge-count pending-pulse">{{ $pedidosPendientes->count() }}</span>
                            @endif
                        </button>
                        <button type="button" class="sales-tab-btn" data-sales-tab="historial-pedidos" role="tab" aria-selected="false" id="tab-historial-pedidos">
                            Pedidos ({{ $pedidosProcesados->count() }})
                        </button>
                    </nav>

                    <!-- TAB 1: HISTORIAL DE VENTAS REALIZADAS -->
                    <div class="sales-tab-content active" id="tab-content-ventas">
                        <div class="recipe-table-container">
                            <x-tablec tableClass="recipe-table">
                                <x-slot name="colgroup"><col style="width:12%"><col style="width:16%"><col style="width:24%"><col style="width:16%"><col style="width:16%"><col style="width:16%"></x-slot>
                                <x-slot name="thead"><tr><th>Correlativo</th><th>Emisión</th><th>Origen</th><th>Estado</th><th>Total</th><th>Detalle</th></tr></x-slot>
                                <x-slot name="tbody">
                                    @forelse ($ventas as $venta)
                                        <tr data-sale-row>
                                            <td>#{{ $loop->iteration }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($venta->emision)->format('d/m/Y') }}</td>
                                            <td>
                                                @if ($venta->pedido_id)
                                                    <span class="badge-tag badge-pedido" title="Cliente: {{ $venta->cliente_nombre ?? 'Cliente' }}">📦 Pedido #{{ $venta->pedido_id }}</span>
                                                @else
                                                    <span class="badge-tag badge-local">🏪 Venta local</span>
                                                @endif
                                            </td>
                                            <td><span class="badge-tag badge-estado-pagada">{{ $venta->estado }}</span></td>
                                            <td>${{ number_format($venta->total, 2) }}</td>
                                            <td>
                                                <button type="button" class="recipe-secondary-button" data-view-sale
                                                    data-sale-id="{{ $venta->idventa }}"
                                                    data-sale-items="{{ $venta->detalle_items_json }}"
                                                    data-sale-origin="{{ $venta->origen_texto }}"
                                                    data-sale-subtotal="{{ $venta->detalle_subtotal_bruto }}"
                                                    data-sale-discount-amount="{{ $venta->detalle_descuento_total }}"
                                                    data-sale-total="{{ $venta->total }}"
                                                    aria-label="Ver detalle de la venta #{{ $venta->idventa }}">
                                                    Ver detalle
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="recipe-empty-state">Todavía no hay ventas registradas.</td></tr>
                                    @endforelse
                                    <tr id="sales-no-results" hidden><td colspan="6" class="recipe-empty-state">No se encontraron ventas.</td></tr>
                                </x-slot>
                            </x-tablec>
                        </div>
                    </div>

                    <!-- TAB 2: PEDIDOS PENDIENTES POR PROCESAR -->
                    <div class="sales-tab-content" id="tab-content-pedidos">
                        <div class="recipe-table-container">
                            <x-tablec tableClass="recipe-table">
                                <x-slot name="colgroup"><col style="width:8%"><col style="width:17%"><col style="width:20%"><col style="width:13%"><col style="width:12%"><col style="width:16%"><col style="width:14%"></x-slot>
                                <x-slot name="thead"><tr><th>Pedido</th><th>Cliente</th><th>Dirección</th><th>Fecha</th><th>Total est.</th><th>Producción</th><th>Acciones</th></tr></x-slot>
                                <x-slot name="tbody">
                                    @forelse ($pedidosPendientes as $pedido)
                                        @php
                                            $etapasProduccion = [
                                                'pendiente' => 'Pendiente',
                                                'en_preparacion' => 'En preparación',
                                                'horneado' => 'Horneado',
                                                'terminado' => 'Terminado',
                                            ];
                                            $estadoProduccion = $pedido->estado_produccion ?? 'pendiente';
                                            $indiceProduccion = array_search($estadoProduccion, array_keys($etapasProduccion), true);
                                            $indiceProduccion = $indiceProduccion === false ? 0 : $indiceProduccion;
                                            $produccionTerminada = $estadoProduccion === 'terminado';
                                        @endphp
                                        <tr data-pedido-row>
                                            <td><strong>#{{ $pedido->idpedido }}</strong></td>
                                            <td>
                                                <strong>{{ $pedido->cliente_nombre }}</strong>
                                                @if ($pedido->cliente?->telefono)
                                                    <br><small style="color:#656565;">📞 {{ $pedido->cliente->telefono }}</small>
                                                @endif
                                            </td>
                                            <td title="{{ $pedido->direccion }}">{{ \Illuminate\Support\Str::limit($pedido->direccion, 40) }}</td>
                                            <td>
                                                {{ $pedido->created_at?->format('d/m/Y') }}
                                                <br><small style="color:#656565;">{{ $pedido->created_at?->format('H:i') }}</small>
                                            </td>
                                            <td><strong>${{ number_format($pedido->subtotal_estimado, 2) }}</strong></td>
                                            <td>
                                                <select class="sales-production-select" data-production-status
                                                    data-pedido-id="{{ $pedido->idpedido }}"
                                                    data-current-status="{{ $estadoProduccion }}"
                                                    data-endpoint="{{ route('api.pedidos.cambiarEstadoProduccion') }}"
                                                    aria-label="Avanzar producción del pedido {{ $pedido->idpedido }}">
                                                    @foreach ($etapasProduccion as $valorEtapa => $etiquetaEtapa)
                                                        @php($indiceEtapa = array_search($valorEtapa, array_keys($etapasProduccion), true))
                                                        @if ($indiceEtapa >= $indiceProduccion && $indiceEtapa <= $indiceProduccion + 1)
                                                            <option value="{{ $valorEtapa }}" @selected($valorEtapa === $estadoProduccion)>{{ $etiquetaEtapa }}</option>
                                                        @endif
                                                    @endforeach
                                                    @if ($estadoProduccion !== 'pendiente')
                                                        <option value="pendiente">Cancelar</option>
                                                    @endif
                                                </select>
                                            </td>
                                            <td>
                                                <button type="button" class="recipe-primary-button btn-cobrar-action"
                                                    data-open-cobrar-modal
                                                    data-pedido-id="{{ $pedido->idpedido }}"
                                                    data-production-ready="{{ $produccionTerminada ? 'true' : 'false' }}"
                                                    @disabled(!$produccionTerminada)
                                                    data-pedido-client="{{ $pedido->cliente_nombre }}"
                                                    data-pedido-phone="{{ $pedido->cliente?->telefono ?? 'No registrado' }}"
                                                    data-pedido-address="{{ $pedido->direccion }}"
                                                    data-pedido-items="{{ $pedido->items_json }}"
                                                    data-pedido-subtotal="{{ $pedido->subtotal_estimado }}"
                                                    title="{{ $produccionTerminada ? 'Procesar y cobrar pedido #' . $pedido->idpedido : 'La producción debe estar terminada antes de cobrar.' }}">
                                                    Cobrar pedido
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="recipe-empty-state">No hay pedidos pendientes de procesar. Todos los pedidos se encuentran al día.</td></tr>
                                    @endforelse
                                    <tr id="pedidos-no-results" hidden><td colspan="7" class="recipe-empty-state">No se encontraron pedidos coincidentes.</td></tr>
                                </x-slot>
                            </x-tablec>
                        </div>

                    </div>

                    <!-- TAB: HISTORIAL DE PEDIDOS -->
                    <div class="sales-tab-content" id="tab-content-historial-pedidos">
                        <div class="recipe-table-container">
                            <x-tablec tableClass="recipe-table">
                                <x-slot name="colgroup"><col style="width:48%"><col style="width:26%"><col style="width:26%"></x-slot>
                                <x-slot name="thead"><tr><th>Cliente</th><th>Estado</th><th>Historial</th></tr></x-slot>
                                <x-slot name="tbody">
                                    @forelse ($pedidosProcesados as $pedido)
                                        <tr data-order-history-row>
                                            <td><strong>{{ $pedido->cliente_nombre }}</strong></td>
                                            <td><span class="badge-tag badge-estado-pagada">{{ ucfirst($pedido->estado_pago ?? 'pagada') }}</span></td>
                                            <td>
                                                <button type="button" class="recipe-secondary-button" data-view-client-history
                                                    data-client-name="{{ $pedido->cliente_nombre }}"
                                                    data-client-history="{{ $pedido->cliente_historial_json }}">
                                                    Ver historial ({{ $pedido->cliente_historial_count }})
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="recipe-empty-state">Todavía no hay pedidos registrados.</td></tr>
                                    @endforelse
                                    <tr id="historial-pedidos-no-results" hidden><td colspan="3" class="recipe-empty-state">No se encontraron pedidos.</td></tr>
                                </x-slot>
                            </x-tablec>
                        </div>
                    </div>

                    <!-- TAB 3: VENTAS AMBULANTES -->
                    <div class="sales-tab-content" id="tab-content-vendedores">
                        <span id="seller-history-refresh-settings" data-endpoint="{{ route('api.ventas.vendedores.historial') }}" hidden></span>
                        <div class="recipe-table-container">
                            <x-tablec tableClass="recipe-table">
                                <x-slot name="colgroup"><col style="width:30%"><col style="width:15%"><col style="width:20%"><col style="width:20%"><col style="width:15%"></x-slot>
                                <x-slot name="thead"><tr><th>Vendedor</th><th>Ventas</th><th>Total vendido</th><th>Última venta</th><th>Historial</th></tr></x-slot>
                                <x-slot name="tbody">
                                    @forelse ($ventasPorVendedor as $vendedor)
                                        <tr data-vendedor-row data-search-row>
                                            <td><strong>{{ $vendedor['nombre'] }}</strong></td>
                                            <td>{{ $vendedor['ventas'] }}</td>
                                            <td data-seller-total-id="{{ $vendedor['id'] }}"><strong>${{ number_format($vendedor['total'], 2) }}</strong></td>
                                            <td>{{ $vendedor['ultima_venta'] ? \Illuminate\Support\Carbon::parse($vendedor['ultima_venta'])->format('d/m/Y') : '—' }}</td>
                                            <td>
                                                <button type="button" class="recipe-action-button" data-view-seller-sales
                                                    data-seller-id="{{ $vendedor['id'] }}"
                                                    data-seller-name="{{ $vendedor['nombre'] }}"
                                                    data-seller-sales="{{ json_encode($vendedor['registros'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
                                                    title="Ver ventas de {{ $vendedor['nombre'] }}"
                                                    aria-label="Ver ventas de {{ $vendedor['nombre'] }}">
                                                    <img src="{{ asset('assets/global/vista.svg') }}" alt="">
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="recipe-empty-state">Todavía no hay ventas registradas por vendedores.</td></tr>
                                    @endforelse
                                    <tr id="vendedores-no-results" hidden><td colspan="5" class="recipe-empty-state">No se encontraron vendedores.</td></tr>
                                </x-slot>
                            </x-tablec>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <x-toast></x-toast>
    <span id="sales-success-message" hidden>{{ session('success') }}</span>

    <div class="recipe-modal-window sales-modal" id="sales-search-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card sales-search-card" role="dialog" aria-modal="true" aria-labelledby="sales-search-title">
            <div class="recipe-modal-heading">
                <h2 id="sales-search-title">Buscar en ventas</h2>
                <button type="button" class="sales-close" data-close-sales-search aria-label="Cerrar búsqueda">&times;</button>
            </div>
            <div class="sales-search-form">
                <label class="recipe-form-group" for="sales-search-input">Buscar en la pestaña actual</label>
                <input type="search" id="sales-search-input" placeholder="Buscar" autocomplete="off">
                <label class="recipe-form-group" for="sales-client-combobox">Cliente</label>
                <div class="sales-client-search-row">
                    <div class="sales-client-combobox-wrap">
                        <input type="search" id="sales-client-combobox" role="combobox" aria-controls="sales-client-options" aria-expanded="false" placeholder="Buscar o seleccionar cliente" autocomplete="off">
                        <div id="sales-client-options" class="sales-client-options" role="listbox" hidden>
                        @foreach ($clientesLocales as $cliente)
                            <button type="button" class="sales-client-option" role="option" data-client-option data-client-id="{{ $cliente->idcliente }}" data-client-name="{{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}">
                                {{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}
                            </button>
                        @endforeach
                        </div>
                    </div>
                    <button type="button" class="recipe-secondary-button" id="btn-register-client-from-search" hidden>Registrar cliente</button>
                </div>
                <p class="sales-inline-error" id="sales-client-not-found" hidden>No existe un cliente con ese nombre. Regístralo para continuar.</p>
            </div>
            <div class="recipe-modal-actions">
                <button type="button" class="recipe-secondary-button" data-close-sales-search>Cancelar</button>
                <button type="button" class="recipe-primary-button" id="btn-apply-sales-search">Buscar</button>
            </div>
        </section>
    </div>

    <!-- MODAL 1: NUEVA VENTA LOCAL -->
    <div class="recipe-modal-window sales-modal" id="sale-modal" aria-hidden="true" data-open-on-load="{{ $errors->has('items') ? 'true' : 'false' }}">
        <section class="recipe-modal-card sales-modal-card sale-create-card" role="dialog" aria-modal="true" aria-labelledby="sale-modal-title">
            <div class="recipe-modal-heading"><h2 id="sale-modal-title">Nueva venta local</h2></div>
            <form method="POST" action="{{ route('ventas.store') }}" id="sale-form" data-old-items='@json(old("items", []))'>
                @csrf
                <div class="recipe-form-grid">
                    <div class="recipe-form-group"><label for="sale-date">Fecha de emisión</label><input type="date" name="emision" id="sale-date" value="{{ old('emision', now()->toDateString()) }}" required></div>
                    <div class="recipe-form-group"><label for="sale-discount">Descuento (%)</label><input type="number" name="descuento" id="sale-discount" min="0" max="100" step="1" value="{{ old('descuento', 0) }}" @disabled(old('es_vendedor')) required><input type="hidden" name="descuento" id="sale-discount-zero" value="0" @disabled(!old('es_vendedor'))></div>
                    <div class="recipe-form-group sale-vendor-toggle">
                        <label><input type="checkbox" name="es_vendedor" id="sale-is-vendedor" value="1" {{ old('es_vendedor') ? 'checked' : '' }}> Asignar a vendedor</label>
                    </div>
                    <div class="recipe-form-group" id="sale-seller-block">
                        <label for="sale-seller">Vendedor</label>
                        <select name="empleado_id" id="sale-seller" @disabled(!old('es_vendedor'))>
                            <option value="">Seleccionar vendedor</option>
                            @foreach ($vendedores as $vendedor)
                                <option value="{{ $vendedor->idempleado }}" {{ old('empleado_id') == $vendedor->idempleado ? 'selected' : '' }}>
                                    {{ $vendedor->nombreCompleto ?: $vendedor->usuario?->name ?? 'Empleado' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="sales-add-item">
                    <div class="recipe-form-group sales-product-select">
                        <label for="sale-product-search">Producto</label>
                        <div class="sales-client-combobox-wrap">
                            <input type="search" id="sale-product-search" role="combobox" aria-controls="sale-product-options" aria-expanded="false" aria-autocomplete="list" placeholder="Escribe para buscar un producto" autocomplete="off">
                            <div id="sale-product-options" class="sales-client-options sale-product-options" role="listbox" hidden>
                                @forelse ($productos as $producto)
                                    <button type="button" class="sales-client-option" role="option" data-sale-product-option data-product-id="{{ $producto->idproducto }}" data-product-name="{{ $producto->nombre }}">
                                        {{ $producto->nombre }}
                                    </button>
                                @empty
                                    <p class="sales-inline-error">No hay productos disponibles con existencia.</p>
                                @endforelse
                                <p class="sales-inline-error" id="sale-product-no-results" hidden>No hay productos que coincidan.</p>
                            </div>
                        </div>
                        <select id="sale-product" hidden>
                            <option value="">Seleccionar producto</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->idproducto }}" data-price="{{ $producto->precio }}" data-seller-price="{{ stripos($producto->nombre, 'lata') !== false ? 1 : $producto->precio }}" data-stock="{{ $producto->stock }}">{{ $producto->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="recipe-form-group sales-quantity"><label for="sale-quantity">Unidades</label><input type="number" id="sale-quantity" min="1" step="1" value="1" disabled></div>
                    <div class="recipe-form-group sales-quantity" id="sale-money-group" hidden><label for="sale-money">Monto ($)</label><input type="number" id="sale-money" min="0.01" step="0.01" placeholder="5.00" disabled></div>
                    <button type="button" id="btn-add-sale-item" class="recipe-secondary-button">Agregar</button>
                </div>

                <div class="recipe-table-container sales-lines-container">
                    <table class="recipe-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th><th>Acción</th></tr></thead><tbody id="sale-items"><tr id="sale-items-empty"><td colspan="4" class="recipe-empty-state">Agrega productos a la venta.</td></tr></tbody></table>
                </div>
                <p class="sales-total-line">Descuento aplicado <strong id="sale-discount-amount">$0.00</strong></p>
                <p class="sales-total-line sale-grand-total">Total <strong id="sale-total">$0.00</strong></p>
                <p class="sales-inline-error" id="sale-form-error" role="alert" hidden></p>
                <div class="recipe-modal-actions"><button type="button" class="recipe-secondary-button" data-close-sale>Cancelar</button><button type="submit" class="recipe-primary-button">Registrar venta</button></div>
            </form>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="local-order-client-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card local-order-client-card" role="dialog" aria-modal="true" aria-labelledby="local-order-client-title">
            <div class="recipe-modal-heading"><h2 id="local-order-client-title">Seleccionar cliente</h2></div>
            <label class="recipe-form-group" for="local-order-client-select">Cliente</label>
            <div class="local-client-picker">
                <div class="sales-client-combobox-wrap">
                    <input type="search" id="local-order-client-combobox" role="combobox" aria-controls="local-order-client-options" aria-expanded="false" aria-autocomplete="list" placeholder="Escribe para buscar un cliente" autocomplete="off">
                    <div id="local-order-client-options" class="sales-client-options" role="listbox" hidden>
                        @foreach ($clientesLocales as $cliente)
                            <button type="button" class="sales-client-option" role="option" data-client-option data-client-id="{{ $cliente->idcliente }}" data-client-name="{{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}">
                                {{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}{{ $cliente->telefono ? ' · ' . $cliente->telefono : '' }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <select id="local-order-client-select" hidden>
                    <option value="">Buscar o seleccionar cliente</option>
                    @foreach ($clientesLocales as $cliente)
                        <option value="{{ $cliente->idcliente }}" data-name="{{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}" data-phone="{{ $cliente->telefono }}">
                            {{ trim($cliente->nombre . ' ' . ($cliente->apellido ?? '')) }}{{ $cliente->telefono ? ' · ' . $cliente->telefono : '' }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="recipe-secondary-button" id="btn-register-local-client">Registrar cliente</button>
            </div>
            <p class="local-client-selected" id="local-order-selected-client" hidden></p>
            <div class="recipe-modal-actions">
                <button type="button" class="recipe-secondary-button" data-close-local-order>Cancelar</button>
                <button type="button" class="recipe-primary-button" id="btn-continue-local-order" disabled>Continuar a productos</button>
            </div>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="local-order-register-client-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card local-order-client-card" role="dialog" aria-modal="true" aria-labelledby="local-register-client-title">
            <div class="recipe-modal-heading"><h2 id="local-register-client-title">Registrar cliente</h2></div>
            <form id="local-register-client-form" data-store-url="{{ route('api.ventas.clientes.store') }}">
                @csrf
                <div class="recipe-form-grid">
                    <div class="recipe-form-group"><label for="local-register-client-name">Nombre</label><input type="text" name="nombre" id="local-register-client-name" maxlength="60" required></div>
                    <div class="recipe-form-group"><label for="local-register-client-lastname">Apellido</label><input type="text" name="apellido" id="local-register-client-lastname" maxlength="60"></div>
                    <div class="recipe-form-group"><label for="local-register-client-phone">Teléfono (opcional)</label><input type="tel" name="telefono" id="local-register-client-phone" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7" placeholder="7000-0000"></div>
                </div>
                <p class="sales-inline-error" id="local-register-client-error" role="alert" hidden></p>
                <div class="recipe-modal-actions">
                    <button type="button" class="recipe-secondary-button" data-close-register-client>Volver a buscar</button>
                    <button type="submit" class="recipe-primary-button" id="local-register-client-submit">Guardar cliente</button>
                </div>
            </form>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="local-order-modal" aria-hidden="true" data-open-on-load="{{ $errors->localOrder->any() ? 'true' : 'false' }}">
        <section class="recipe-modal-card sales-modal-card sale-create-card" role="dialog" aria-modal="true" aria-labelledby="local-order-title">
            <div class="recipe-modal-heading"><h2 id="local-order-title">Registrar encargo local</h2></div>
            <form method="POST" action="{{ route('ventas.encargos.local.store') }}" id="local-order-form" data-old-items='@json(old("items", []))'>
                @csrf
                <input type="hidden" name="cliente_id" id="local-order-client-id" value="{{ old('cliente_id') }}">
                <input type="hidden" name="abono_inicial" id="local-order-deposit-value" value="{{ old('abono_inicial', '0.00') }}">
                <p class="sale-origin-meta" id="local-order-customer-summary"></p>
                <div class="recipe-form-grid">
                    <div class="recipe-form-group"><label for="local-order-date">Fecha de recogida</label><input type="date" name="fecha_entrega" id="local-order-date" min="{{ today()->toDateString() }}" value="{{ old('fecha_entrega', today()->toDateString()) }}" required></div>
                    <div class="recipe-form-group"><label for="local-order-notes">Nota (opcional)</label><input type="text" name="notas" id="local-order-notes" maxlength="255" value="{{ old('notas') }}"></div>
                </div>
                <div class="sales-add-item">
                    <div class="recipe-form-group sales-product-select">
                        <label for="local-order-product-search">Producto</label>
                        <div class="sales-client-combobox-wrap">
                            <input type="search" id="local-order-product-search" role="combobox" aria-controls="local-order-product-options" aria-expanded="false" aria-autocomplete="list" placeholder="Escribe para buscar un producto" autocomplete="off">
                            <div id="local-order-product-options" class="sales-client-options sale-product-options" role="listbox" hidden>
                                @forelse ($productosParaEncargo as $producto)
                                    <button type="button" class="sales-client-option" role="option" data-local-order-product-option data-product-id="{{ $producto->idproducto }}" data-product-name="{{ $producto->nombre }}">
                                        {{ $producto->nombre }}
                                    </button>
                                @empty
                                    <p class="sales-inline-error">No hay productos disponibles para encargo.</p>
                                @endforelse
                                <p class="sales-inline-error" id="local-order-product-no-results" hidden>No hay productos que coincidan.</p>
                            </div>
                        </div>
                        <select id="local-order-product" hidden>
                            <option value="">Seleccionar producto</option>
                            @foreach ($productosParaEncargo as $producto)
                                <option value="{{ $producto->idproducto }}" data-price="{{ $producto->precio }}">{{ $producto->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="sales-inline-error" id="local-order-client-not-found" hidden>No hay clientes que coincidan con la búsqueda.</p>
                    <div class="recipe-form-group sales-quantity"><label for="local-order-quantity">Unidades</label><input type="number" id="local-order-quantity" min="1" step="1" value="1" disabled></div>
                    <div class="recipe-form-group sales-quantity" id="local-order-money-group" hidden><label for="local-order-money">Monto ($)</label><input type="number" id="local-order-money" min="0.01" step="0.01" placeholder="5.00" disabled></div>
                    <button type="button" id="btn-add-local-order-item" class="recipe-secondary-button">Agregar producto</button>
                </div>

                <div class="recipe-table-container sales-lines-container">
                    <table class="recipe-table"><thead><tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th>Acción</th></tr></thead><tbody id="local-order-items"></tbody></table>
                </div>
                <p class="sales-total-line sale-grand-total">Total estimado <strong id="local-order-total">$0.00</strong></p>
                <div class="local-order-deposit-actions">
                    <p id="local-order-deposit-summary" aria-live="polite">Sin adelanto registrado.</p>
                </div>
                <p class="sales-inline-error" id="local-order-form-error" role="alert" hidden></p>
                @if ($errors->localOrder->any())
                    <p class="sales-inline-error" role="alert">{{ $errors->localOrder->first() }}</p>
                @endif
                <div class="recipe-modal-actions">
                    <button type="button" class="recipe-secondary-button" data-close-local-order>Cancelar</button>
                    <button type="button" class="recipe-secondary-button" id="btn-open-local-order-deposit">Registrar adelanto</button>
                    <button type="submit" class="recipe-primary-button">Guardar encargo</button>
                </div>
            </form>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="local-order-deposit-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card local-order-deposit-card" role="dialog" aria-modal="true" aria-labelledby="local-order-deposit-title">
            <div class="recipe-modal-heading"><h2 id="local-order-deposit-title">Registrar adelanto</h2></div>
            <p class="local-order-deposit-total">Total estimado <strong id="local-order-deposit-total">$0.00</strong></p>
            <div class="recipe-form-group">
                <label for="local-order-deposit-amount">Monto recibido</label>
                <input type="number" id="local-order-deposit-amount" min="0" step="0.01" value="0.00" inputmode="decimal">
            </div>
            <p class="local-order-deposit-balance">Saldo al recoger <strong id="local-order-deposit-balance">$0.00</strong></p>
            <p class="sales-inline-error" id="local-order-deposit-error" role="alert" hidden></p>
            <div class="recipe-modal-actions">
                <button type="button" class="recipe-secondary-button" data-close-local-order-deposit>Cancelar</button>
                <button type="button" class="recipe-primary-button" id="btn-apply-local-order-deposit">Guardar adelanto</button>
            </div>
        </section>
    </div>

    <!-- MODAL 2: PROCESAR PEDIDO DE CLIENTE -->
    <div class="recipe-modal-window sales-modal" id="process-order-modal" aria-hidden="true" data-open-on-load="{{ $errors->orderSale->any() ? 'true' : 'false' }}">
            <section class="recipe-modal-card sales-modal-card" role="dialog" aria-modal="true" aria-label="Procesar pedido como venta">
            <form method="POST" action="{{ route('ventas.procesar-pedido') }}" id="process-order-form">
                @csrf
                <input type="hidden" name="pedido_id" id="process-selected-pedido-id" value="{{ old('pedido_id') }}">

                <div class="recipe-form-grid" hidden style="display: none;">
                    <div class="recipe-form-group" style="grid-column: 1 / -1;">
                        <label for="process-order-select">Seleccionar pedido pendiente</label>
                            <select id="process-order-select">
                            <option value="">-- Elige un pedido a procesar --</option>
                            @foreach ($pedidosPendientes as $pend)
                                <option value="{{ $pend->idpedido }}"
                                    data-client="{{ $pend->cliente_nombre }}"
                                    data-phone="{{ $pend->cliente?->telefono ?? 'No registrado' }}"
                                    data-address="{{ $pend->direccion }}"
                                    data-items="{{ $pend->items_json }}"
                                    data-subtotal="{{ $pend->subtotal_estimado }}"
                                    data-abono="{{ number_format($pend->abono_aplicado, 2, '.', '') }}"
                                    {{ old('pedido_id') == $pend->idpedido ? 'selected' : '' }}>
                                    Pedido #{{ $pend->idpedido }} &mdash; {{ $pend->cliente_nombre }} (${{ number_format($pend->subtotal_estimado, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="recipe-table-container sales-lines-container">
                    <table class="recipe-table">
                        <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Oferta</th><th>Subtotal con oferta</th></tr></thead>
                        <tbody id="process-order-items">
                            <tr><td colspan="5" class="recipe-empty-state">Selecciona un pedido para visualizar sus productos.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="recipe-form-grid" style="margin-top: 14px;">
                    <div class="recipe-form-group">
                        <label>Estado actual del pedido</label>
                        <span class="badge-tag badge-estado-pendiente">Pendiente de pago</span>
                    </div>
                    <div class="recipe-form-group" id="process-order-payment-date-group">
                        <label for="process-order-date">Fecha de pago</label>
                        <input type="date" name="fechapago" id="process-order-date" value="{{ old('fechapago', now()->toDateString()) }}" required>
                    </div>
                    <div class="recipe-form-group">
                        <label for="process-order-discount">Descuento (%)</label>
                        <input type="number" name="descuento" id="process-order-discount" min="0" max="100" step="1" value="{{ old('descuento', 0) }}" required>
                    </div>
                </div>

                <p class="sales-total-line">Subtotal antes de descuentos <strong id="process-order-subtotal-amount">$0.00</strong></p>
                <p class="sales-total-line">Descuento de oferta <strong id="process-order-offer-discount-amount">$0.00</strong></p>
                <p class="sales-total-line">Descuento manual <strong id="process-order-discount-amount">$0.00</strong></p>
                <p class="sales-total-line">Adelanto registrado <strong id="process-order-advance-amount">$0.00</strong></p>
                <p class="sales-total-line"><span id="process-order-total-label">Total a cobrar</span> <strong id="process-order-total-amount">$0.00</strong></p>
                @if ($errors->orderSale->any())
                    <p class="sales-inline-error" role="alert">{{ $errors->orderSale->first() }}</p>
                @endif
                <p class="sales-inline-error" id="process-order-form-error" role="alert" hidden></p>

                <div class="recipe-modal-actions">
                    <button type="button" class="recipe-secondary-button" data-close-process>Cancelar</button>
                    <button type="submit" class="recipe-primary-button" id="btn-submit-process" disabled>Registrar venta y cobrar</button>
                </div>
            </form>
        </section>
    </div>

    <!-- MODAL 3: DETALLE DE VENTA -->
    <div class="recipe-modal-window sales-modal" id="sale-details-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card sales-details-card" role="dialog" aria-modal="true" aria-labelledby="sale-details-title">
            <div class="recipe-modal-heading"><h2 id="sale-details-title">Detalle de venta</h2></div>
            <div class="sale-origin-meta">
                <span>Origen de venta: </span><strong id="sale-details-origin-text">Venta local</strong>
            </div>
            <div class="recipe-table-container sales-lines-container"><table class="recipe-table"><thead><tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th></tr></thead><tbody id="sale-details-items"></tbody></table></div>
            <div class="sales-details-summary">
                <p><span>Subtotal</span><strong id="sale-details-subtotal">$0.00</strong></p>
                <p><span id="sale-details-discount-label">Descuento</span><strong id="sale-details-discount-amount">$0.00</strong></p>
                <p class="sales-details-total"><span>Total</span><strong id="sale-details-total">$0.00</strong></p>
            </div>
            <div class="recipe-modal-actions"><button type="button" class="recipe-secondary-button" data-close-details>Cerrar</button></div>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="seller-sales-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card sales-details-card seller-sales-history-modal" role="dialog" aria-modal="true" aria-labelledby="seller-sales-title">
            <div class="recipe-modal-heading"><h2 id="seller-sales-title">Ventas del vendedor</h2></div>
            <div class="recipe-table-container sales-lines-container">
                <table class="recipe-table seller-sales-history-table">
                    <colgroup><col style="width:11%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:12%"><col style="width:37%"></colgroup>
                    <thead>
                        <tr><th>Fecha</th><th>Asignadas</th><th>Vendidas</th><th>Devueltas</th><th>Perdidas</th><th>Total</th><th>Detalle e incidencias</th></tr>
                    </thead>
                    <tbody id="seller-sales-items"></tbody>
                </table>
            </div>
            <div class="recipe-modal-actions">
                <button type="button" class="recipe-secondary-button" data-close-seller-sales>Cerrar</button>
            </div>
        </section>
    </div>

    <div class="recipe-modal-window sales-modal" id="client-order-history-modal" aria-hidden="true">
        <section class="recipe-modal-card sales-modal-card sales-details-card" role="dialog" aria-modal="true" aria-labelledby="client-order-history-title">
            <div class="recipe-modal-heading"><h2 id="client-order-history-title">Historial del cliente</h2></div>
            <div class="recipe-table-container sales-lines-container">
                <table class="recipe-table">
                    <thead><tr><th>Fecha</th><th>Producto</th><th>Total</th></tr></thead>
                    <tbody id="client-order-history-items"></tbody>
                </table>
            </div>
            <div class="recipe-modal-actions">
                <button type="button" class="recipe-secondary-button" data-close-client-history>Cerrar</button>
            </div>
        </section>
    </div>
</x-canvas>