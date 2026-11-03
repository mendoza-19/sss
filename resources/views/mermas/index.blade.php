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
        <title>Mermas y devoluciones - El Buen Sabor</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <section class="recipe-page loss-page">
                    <header class="recipe-page-heading">
                        <div>
                            <h1>Mermas y devoluciones</h1>
                            <p>Registro unificado de pérdidas de horneado y ventas</p>
                        </div>
                    </header>

                    <div class="loss-summary" aria-label="Resumen de unidades y costos">
                        <div><span>Quemados</span><strong>{{ number_format($resumen['Quemado']) }}</strong></div>
                        <div><span>Devueltos</span><strong>{{ number_format($resumen['Devuelto']) }}</strong></div>
                        <div><span>Perdidos en venta</span><strong>{{ number_format($resumen['Pérdida de venta']) }}</strong></div>
                        <div><span>Costo total de pérdidas</span><strong>${{ number_format($resumen['Costo de pérdida'], 2) }}</strong></div>
                    </div>

                    <div class="recipe-table-container">
                        <table class="recipe-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Pan o producto</th>
                                    <th>Cantidad</th>
                                    <th>Costo de pérdida</th>
                                    <th>Origen</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movimientos as $movimiento)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($movimiento['fecha'])->format('d/m/Y') }}</td>
                                        <td>{{ $movimiento['tipo'] }}</td>
                                        <td>{{ $movimiento['articulo'] }}</td>
                                        <td>{{ number_format($movimiento['cantidad']) }}</td>
                                        <td>{{ $movimiento['costo_perdida'] === null ? '—' : '$' . number_format($movimiento['costo_perdida'], 2) }}</td>
                                        <td>{{ $movimiento['origen'] }}</td>
                                        <td>
                                            @if ($movimiento['tipo'] === 'Quemado')
                                                <button type="button" class="recipe-secondary-button" data-open-loss-details
                                                    data-loss-recipe="{{ $movimiento['articulo'] }}"
                                                    data-loss-details="{{ json_encode($movimiento['detalles'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}">
                                                    Ver detalles
                                                </button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="recipe-empty-state">Todavía no hay mermas ni devoluciones registradas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <div class="recipe-modal-window" id="loss-details-modal" aria-hidden="true">
        <section class="recipe-modal-card baking-detail-modal-card" role="dialog" aria-modal="true"
            aria-labelledby="loss-details-title">
            <div class="recipe-modal-heading">
                <h2 id="loss-details-title">Detalle de panes quemados</h2>
                <p id="loss-details-recipe" class="recipe-info-text"></p>
            </div>

            <div class="recipe-table-container baking-history-table-container">
                <table class="recipe-table">
                    <thead><tr><th>Fecha</th><th>Panes quemados</th><th>Costo de pérdida</th><th>Origen</th></tr></thead>
                    <tbody id="loss-details-rows"></tbody>
                </table>
            </div>

            <div class="recipe-modal-actions">
                <button type="button" class="recipe-primary-button" data-close-loss-details>Cerrar</button>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('loss-details-modal');
            const rows = document.getElementById('loss-details-rows');

            document.body.appendChild(modal);

            function closeModal() {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            document.querySelectorAll('[data-open-loss-details]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('loss-details-recipe').textContent = button.dataset.lossRecipe;
                    rows.replaceChildren();

                    JSON.parse(button.dataset.lossDetails || '[]').forEach(function (detail) {
                        const row = document.createElement('tr');
                        [
                            detail.fecha,
                            detail.cantidad,
                            detail.costo_perdida === null ? '—' : `$${Number(detail.costo_perdida).toFixed(2)}`,
                            detail.origen,
                        ].forEach(function (value) {
                            const cell = document.createElement('td');
                            cell.textContent = value || '—';
                            row.appendChild(cell);
                        });
                        rows.appendChild(row);
                    });

                    modal.setAttribute('aria-hidden', 'false');
                    modal.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                });
            });

            modal.querySelector('[data-close-loss-details]').addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') closeModal();
            });
        });
    </script>
</x-canvas>