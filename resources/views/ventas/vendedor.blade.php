@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/ventas/style.css') }}?v={{ md5_file(public_path('css/ventas/style.css')) }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
        <script src="{{ asset('js/ventas/asignaciones-refresh.js') }}"></script>
    @endpush
@endonce

<x-canvas>
    <x-slot name="header"><title>Mis asignaciones - El Buen Sabor</title></x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <section class="recipe-page sales-page seller-assignments-page">
                    <header class="recipe-page-heading seller-assignments-heading">
                        <div>
                            <h1>Mis asignaciones</h1>
                            <p class="seller-assignment-subtitle">Revisa tu carga y registra el cierre al terminar la venta.</p>
                        </div>
                    </header>

                    @if (session('success'))
                        <div class="recipe-feedback recipe-feedback-success" role="status">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <nav class="sales-tabs-nav seller-assignment-tabs" role="tablist" aria-label="Mis asignaciones">
                        <button type="button" class="sales-tab-btn {{ $pestanaInicial === 'nuevas' ? 'active' : '' }}"
                            id="tab-asignaciones-nuevas" data-assignment-tab="nuevas" role="tab"
                            aria-selected="{{ $pestanaInicial === 'nuevas' ? 'true' : 'false' }}">
                            Nuevas asignaciones <span class="sales-badge-count" data-assignment-count>{{ $asignacionesNuevas->count() }}</span>
                        </button>
                        <button type="button" class="sales-tab-btn {{ $pestanaInicial === 'realizadas' ? 'active' : '' }}"
                            id="tab-asignaciones-realizadas" data-assignment-tab="realizadas" role="tab"
                            aria-selected="{{ $pestanaInicial === 'realizadas' ? 'true' : 'false' }}">
                            Ya realizadas <span class="sales-badge-count" data-assignment-count>{{ $asignacionesRealizadas->count() }}</span>
                        </button>
                    </nav>

                    <div class="recipe-table-container seller-assignment-table-container">
                        <span id="seller-assignment-settings" data-view="seller" data-csrf-token="{{ csrf_token() }}" hidden></span>
                        <x-tablec tableClass="recipe-table seller-assignments-table" id="seller-assignments-table">
                            <x-slot name="colgroup"><col style="width:16%"><col style="width:16%"><col style="width:68%"></x-slot>
                            <x-slot name="thead"><tr><th>Fecha</th><th>Panes asignados</th><th>Cierre e incidencia</th></tr></x-slot>
                            <x-slot name="tbody">
                                @foreach ($asignaciones as $asignacion)
                                    @php
                                        $cerrada = $asignacion->unidadesdevueltas !== null && $asignacion->unidadesperdidas !== null;
                                        $vendidas = $cerrada
                                            ? $asignacion->unidadesemitidas - $asignacion->unidadesdevueltas - $asignacion->unidadesperdidas
                                            : null;
                                        $estadoAsignacion = $cerrada ? 'realizadas' : 'nuevas';
                                    @endphp
                                    <tr data-assignment-id="{{ $asignacion->idventaambulante }}"
                                        data-assignment-state="{{ $estadoAsignacion }}"
                                        class="seller-assignment-row" @if ($estadoAsignacion !== $pestanaInicial) hidden @endif>
                                        <td data-label="Fecha"><time class="seller-assignment-date">{{ $asignacion->created_at?->format('d/m/Y') }}</time></td>
                                        <td data-label="Panes asignados"><strong class="seller-assigned-units">{{ number_format($asignacion->unidadesemitidas) }}</strong><small class="seller-units-caption">panes asignados</small></td>
                                        <td data-label="Cierre e incidencia">
                                            @if ($cerrada)
                                                @if ($asignacion->venta?->detalleproductos->isNotEmpty() && $asignacion->venta->detalleproductos->every(fn ($detalle) => $detalle->unidadesdevueltas !== null && $detalle->unidadesperdidas !== null))
                                                    <div class="seller-product-close-summary">
                                                        @foreach ($asignacion->venta->detalleproductos as $detalle)
                                                            @php
                                                                $vendidasProducto = (int) $detalle->cantidad - (int) $detalle->unidadesdevueltas - (int) $detalle->unidadesperdidas;
                                                            @endphp
                                                            <p><strong>{{ $detalle->producto?->nombre ?? 'Producto eliminado' }}</strong><span>Vendidas {{ number_format($vendidasProducto) }}</span><span>Devueltas {{ number_format($detalle->unidadesdevueltas) }}</span><span>Perdidas {{ number_format($detalle->unidadesperdidas) }}</span></p>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="seller-close-summary">
                                                        <p>Vendidas <strong>{{ number_format($vendidas) }}</strong></p>
                                                        <p>Devueltas <strong>{{ number_format($asignacion->unidadesdevueltas) }}</strong></p>
                                                        <p>Perdidas <strong>{{ number_format($asignacion->unidadesperdidas) }}</strong></p>
                                                    </div>
                                                @endif
                                                @foreach ($asignacion->incidencias as $incidencia)
                                                    <p class="seller-incident"><strong>Incidencia:</strong> {{ $incidencia->detalle }} <small>{{ $incidencia->gravedad }}</small></p>
                                                @endforeach
                                                <span class="seller-assignment-status is-closed seller-closeout-complete">Cierre realizado</span>
                                            @else
                                                <form class="seller-closeout-form" method="POST" action="{{ route('ventas.asignaciones.cerrar', $asignacion->idventaambulante) }}">
                                                    @csrf
                                                    <div class="seller-product-closeout-list">
                                                        @forelse ($asignacion->venta?->detalleproductos ?? [] as $detalle)
                                                            <div class="seller-product-closeout-row" data-closeout-product-id="{{ $detalle->iddetalleproducto }}">
                                                                <div class="seller-product-line-meta">
                                                                    <strong>{{ $detalle->producto?->nombre ?? 'Producto eliminado' }}</strong>
                                                                    <small>{{ number_format($detalle->cantidad) }} asignadas · ${{ number_format($detalle->cantidad > 0 ? $detalle->subtotal / $detalle->cantidad : 0, 2) }} c/u</small>
                                                                </div>
                                                                <div class="recipe-form-group seller-closeout-line-input">
                                                                    <label for="devueltas-{{ $detalle->iddetalleproducto }}">Devueltas</label>
                                                                    <input type="number" id="devueltas-{{ $detalle->iddetalleproducto }}" name="productos[{{ $detalle->iddetalleproducto }}][unidadesdevueltas]" data-closeout-returned min="0" max="{{ $detalle->cantidad }}" value="0" required>
                                                                </div>
                                                                <div class="recipe-form-group seller-closeout-line-input">
                                                                    <label for="perdidas-{{ $detalle->iddetalleproducto }}">Perdidas</label>
                                                                    <input type="number" id="perdidas-{{ $detalle->iddetalleproducto }}" name="productos[{{ $detalle->iddetalleproducto }}][unidadesperdidas]" data-closeout-lost min="0" max="{{ $detalle->cantidad }}" value="0" required>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="seller-product-closeout-row seller-product-closeout-legacy">
                                                                <div class="seller-product-line-meta"><strong>Asignación general</strong><small>{{ number_format($asignacion->unidadesemitidas) }} unidades</small></div>
                                                                <div class="recipe-form-group seller-closeout-line-input"><label for="devueltas-{{ $asignacion->idventaambulante }}">Devueltas</label><input type="number" id="devueltas-{{ $asignacion->idventaambulante }}" name="unidadesdevueltas" data-closeout-returned min="0" max="{{ $asignacion->unidadesemitidas }}" value="0" required></div>
                                                                <div class="recipe-form-group seller-closeout-line-input"><label for="perdidas-{{ $asignacion->idventaambulante }}">Perdidas</label><input type="number" id="perdidas-{{ $asignacion->idventaambulante }}" name="unidadesperdidas" data-closeout-lost min="0" max="{{ $asignacion->unidadesemitidas }}" value="0" required></div>
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                    <div class="seller-closeout-footer">
                                                        <div class="recipe-form-group seller-incident-group">
                                                            <label for="incidencia-{{ $asignacion->idventaambulante }}">Incidencia (opcional)</label>
                                                            <textarea id="incidencia-{{ $asignacion->idventaambulante }}" name="incidencia" rows="2" maxlength="1000"></textarea>
                                                        </div>
                                                        <div class="recipe-form-group">
                                                            <label for="gravedad-{{ $asignacion->idventaambulante }}">Gravedad</label>
                                                            <select id="gravedad-{{ $asignacion->idventaambulante }}" name="gravedad">
                                                                <option value="Baja">Baja</option>
                                                                <option value="Media" selected>Media</option>
                                                                <option value="Alta">Alta</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="recipe-primary-button">Registrar cierre</button>
                                                    </div>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr data-assignment-empty="nuevas" @if ($pestanaInicial !== 'nuevas' || $asignacionesNuevas->isNotEmpty()) hidden @endif>
                                    <td colspan="3" class="recipe-empty-state">No tienes nuevas asignaciones.</td>
                                </tr>
                                <tr data-assignment-empty="realizadas" @if ($pestanaInicial !== 'realizadas' || $asignacionesRealizadas->isNotEmpty()) hidden @endif>
                                    <td colspan="3" class="recipe-empty-state">Todavía no has realizado asignaciones.</td>
                                </tr>
                            </x-slot>
                        </x-tablec>
                    </div>
                </section>
            </div>
        </div>
    </main>
</x-canvas>