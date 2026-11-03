@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}?v={{ md5_file(public_path('css/recetas/style.css')) }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Horneados - El Buen Sabor</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <div class="recipe-page">
                    <div class="recipe-page-heading">
                        <div><h1>Horneado</h1></div>
                    </div>

                    @if (session('success'))
                        <div class="recipe-feedback" role="status">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <nav class="baking-tabs-nav" role="tablist" aria-label="Órdenes de horneado">
                        <button type="button" class="baking-tab-btn {{ $pestanaInicial === 'pendientes' ? 'active' : '' }}"
                            id="tab-ordenes-pendientes" data-baking-tab="pendientes" role="tab"
                            aria-selected="{{ $pestanaInicial === 'pendientes' ? 'true' : 'false' }}">
                            Órdenes por hornear <span>{{ $ordenesPendientes->count() }}</span>
                        </button>
                        <button type="button" class="baking-tab-btn {{ $pestanaInicial === 'horneadas' ? 'active' : '' }}"
                            id="tab-ordenes-horneadas" data-baking-tab="horneadas" role="tab"
                            aria-selected="{{ $pestanaInicial === 'horneadas' ? 'true' : 'false' }}">
                            Órdenes horneadas <span>{{ $ordenesHorneadas->count() }}</span>
                        </button>
                    </nav>

                    <section class="baking-tab-panel {{ $pestanaInicial === 'pendientes' ? 'active' : '' }}"
                        id="panel-ordenes-pendientes" data-baking-panel="pendientes" role="tabpanel"
                        aria-labelledby="tab-ordenes-pendientes" @if ($pestanaInicial !== 'pendientes') hidden @endif>
                        <div class="recipe-table-container">
                            <table class="recipe-table">
                                <thead><tr><th>Fecha</th><th>Receta</th><th>Costo</th><th>Acción</th></tr></thead>
                                <tbody>
                                    @forelse ($ordenesPendientes as $preparacion)
                                        <tr>
                                            <td>{{ \Illuminate\Support\Carbon::parse($preparacion->fecha)->format('d/m/Y') }}</td>
                                            <td>{{ $preparacion->receta->nombre ?? 'Receta eliminada' }}</td>
                                            <td>—</td>
                                            <td>
                                                <button type="button" class="recipe-secondary-button" data-open-baking
                                                    data-action="{{ route('preparaciones.hornear', $preparacion) }}"
                                                    data-recipe="{{ $preparacion->receta->nombre ?? 'Receta eliminada' }}"
                                                    data-description="{{ $preparacion->receta->descripcion ?? '' }}"
                                                    data-planned="{{ $preparacion->unidades_planeadas }}">
                                                    Registrar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="recipe-empty-state">No hay órdenes pendientes de hornear.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="baking-tab-panel {{ $pestanaInicial === 'horneadas' ? 'active' : '' }}"
                        id="panel-ordenes-horneadas" data-baking-panel="horneadas" role="tabpanel"
                        aria-labelledby="tab-ordenes-horneadas" @if ($pestanaInicial !== 'horneadas') hidden @endif>
                        <div class="recipe-table-container">
                            <table class="recipe-table">
                                <thead><tr><th>Receta</th><th>Costo</th><th>Acción</th></tr></thead>
                                <tbody>
                                    @forelse ($ordenesHorneadas as $preparacion)
                                        <tr>
                                            <td>{{ $preparacion['receta']->nombre ?? 'Receta eliminada' }}</td>
                                            <td>{{ $preparacion['costo_total'] === null ? '—' : '$' . number_format($preparacion['costo_total'], 2) }}</td>
                                            <td>
                                                <button type="button" class="recipe-secondary-button" data-open-baking-detail
                                                    data-detail-recipe="{{ $preparacion['receta']->nombre ?? 'Receta eliminada' }}"
                                                    data-detail-description="{{ $preparacion['receta']->descripcion ?? '' }}"
                                                    data-detail-batches="{{ json_encode(
                                                        $preparacion['lotes']->map(function ($lote) {
                                                            return [
                                                                'date' => $lote->fecha,
                                                                'baked' => $lote->unidades_horneadas,
                                                                'good' => $lote->unidades_producidas,
                                                                'burnt' => $lote->unidades_quemadas,
                                                                'distribution' => collect($lote->detalle_latas ?? [])->map(fn ($lata) => $lata['cantidad'] . ' × ' . $lata['panes_por_lata'] . ' panes')->implode(' · '),
                                                                'cost' => $lote->costo_total,
                                                                'unit_cost' => $lote->unidades_horneadas > 0 ? (float) $lote->costo_total / $lote->unidades_horneadas : null,
                                                            ];
                                                        })->values(),
                                                        JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                                                    ) }}">
                                                    Ver
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="recipe-empty-state">Todavía no hay órdenes horneadas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <div class="recipe-modal-window" id="baking-detail-modal" aria-hidden="true">
        <section class="recipe-modal-card baking-detail-modal-card" role="dialog" aria-modal="true"
            aria-labelledby="baking-detail-title">
            <div class="recipe-modal-heading">
                <h2 id="baking-detail-title">Detalle del horneado</h2>
                <p id="baking-detail-recipe" class="recipe-info-text"></p>
                <p id="baking-detail-description" class="baking-recipe-description"></p>
            </div>

            <div class="recipe-table-container baking-history-table-container">
                <table class="recipe-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horneados</th>
                            <th>Buenos</th>
                            <th>Quemados</th>
                            <th>Distribución</th>
                            <th>Costo</th>
                            <th>Costo por pan</th>
                        </tr>
                    </thead>
                    <tbody id="baking-detail-batches"></tbody>
                </table>
            </div>

            <div class="recipe-modal-actions">
                <button type="button" class="recipe-primary-button" data-close-baking-detail>Cerrar</button>
            </div>
        </section>
    </div>

    <div class="recipe-modal-window" id="baking-result-modal" aria-hidden="true">
        <section class="recipe-modal-card" role="dialog" aria-modal="true" aria-labelledby="baking-modal-title">
            <div class="recipe-modal-heading">
                <h2 id="baking-modal-title">Registrar horneado</h2>
                <p id="baking-recipe-name" class="recipe-info-text"></p>
                <p id="baking-recipe-description" class="baking-recipe-description"></p>
            </div>

            <form method="POST" id="baking-result-form" class="recipe-form-grid">
                @csrf
                @include('receta.partials.latas-fields')
                <div class="recipe-form-group">
                    <label for="baking-burnt">Quemados o dañados</label>
                    <input type="number" id="baking-burnt" name="unidades_quemadas" min="0" step="1" value="0" required>
                </div>
                <div class="recipe-form-group">
                    <label>Unidades buenas</label>
                    <p id="baking-good-preview" class="recipe-info-text"></p>
                </div>
                <div class="recipe-modal-actions">
                    <button type="button" class="recipe-secondary-button" id="baking-cancel">Cancelar</button>
                    <button type="submit" class="recipe-primary-button">Guardar horneado</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('baking-result-modal');
            const form = document.getElementById('baking-result-form');
            const detailModal = document.getElementById('baking-detail-modal');
            const trayBreakdown = form.querySelector('[data-tray-breakdown]');
            const burntInput = document.getElementById('baking-burnt');
            const goodPreview = document.getElementById('baking-good-preview');

            document.body.appendChild(modal);
            document.body.appendChild(detailModal);

            function closeDetailModal() {
                detailModal.setAttribute('aria-hidden', 'true');
                detailModal.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('[data-open-baking-detail]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('baking-detail-recipe').textContent = button.dataset.detailRecipe;
                    document.getElementById('baking-detail-description').textContent = button.dataset.detailDescription || 'Sin descripción disponible.';
                    const batches = JSON.parse(button.dataset.detailBatches || '[]');
                    const batchBody = detailModal.querySelector('#baking-detail-batches');
                    batchBody.replaceChildren();

                    batches.forEach(function (batch) {
                        const row = document.createElement('tr');
                        const values = [
                            formatDateDisplay(batch.date) || '—',
                            batch.baked ?? '—',
                            batch.good ?? '—',
                            batch.burnt ?? '—',
                            batch.distribution || '—',
                            batch.cost === null ? '—' : `$${Number(batch.cost).toFixed(2)}`,
                            batch.unit_cost === null ? '—' : `$${Number(batch.unit_cost).toFixed(2)}`,
                        ];

                        values.forEach(function (value) {
                            const cell = document.createElement('td');
                            cell.textContent = value;
                            row.appendChild(cell);
                        });

                        batchBody.appendChild(row);
                    });
                    detailModal.setAttribute('aria-hidden', 'false');
                    detailModal.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                });
            });

            document.querySelectorAll('[data-baking-tab]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const selectedTab = button.dataset.bakingTab;

                    document.querySelectorAll('[data-baking-tab]').forEach(function (tab) {
                        const selected = tab === button;
                        tab.classList.toggle('active', selected);
                        tab.setAttribute('aria-selected', String(selected));
                    });

                    document.querySelectorAll('[data-baking-panel]').forEach(function (panel) {
                        const selected = panel.dataset.bakingPanel === selectedTab;
                        panel.classList.toggle('active', selected);
                        panel.hidden = !selected;
                    });
                });
            });

            detailModal.querySelector('[data-close-baking-detail]').addEventListener('click', closeDetailModal);
            detailModal.addEventListener('click', function (event) {
                if (event.target === detailModal) closeDetailModal();
            });

            function closeModal() {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('[data-open-baking]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action;
                    document.getElementById('baking-recipe-name').textContent =
                        `${button.dataset.recipe} · Lote de ${button.dataset.planned} panes`;
                    document.getElementById('baking-recipe-description').textContent =
                        button.dataset.description || 'Sin descripción disponible.';
                    trayBreakdown.dispatchEvent(new Event('reset-trays'));
                    burntInput.value = '0';
                    updateGoodCount();
                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                    trayBreakdown.querySelector('[data-tray-count]').focus();
                });
            });

            function updateGoodCount() {
                const baked = Number(trayBreakdown.querySelector('[data-tray-total]').textContent || 0);
                goodPreview.textContent = Math.max(0, baked - Number(burntInput.value || 0));
                burntInput.setCustomValidity(Number(burntInput.value || 0) > baked
                    ? 'Los dañados no pueden superar los panes horneados.'
                    : '');
            }

            trayBreakdown.addEventListener('tray-breakdown:change', updateGoodCount);
            burntInput.addEventListener('input', updateGoodCount);
            document.getElementById('baking-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
                if (event.key === 'Escape' && detailModal.getAttribute('aria-hidden') === 'false') closeDetailModal();
            });
        });
    </script>
</x-canvas>