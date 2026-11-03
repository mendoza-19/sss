@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/dashboard/style.css') }}">
    @endpush

    @push('js')
        <script src="/js/components/toast.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src = "{{asset('js/dashboard/script.js')}}"></script>
        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Dashboard</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>

        <div class="body-content">
            <x-navegation></x-navegation>

            <div class="page-content">
                <div class="dashboard-shell">
                    <header class="dashboard-heading">
                        <div>
                            <p class="dashboard-eyebrow">OPERACIONES</p>
                            <h1>Resumen</h1>
                            <p class="dashboard-description">Actividad de ventas y cobros de la panadería.</p>
                        </div>
                        <div class="dashboard-date">
                            <span>HOY</span>
                            <strong>{{ now()->format('d/m/Y') }}</strong>
                        </div>
                    </header>

                    <section class="dashboard-period-filter" aria-label="Período de análisis">
                        <div class="dashboard-period-controls">
                            <label for="dashboard-view-mode">Ver por</label>
                            <select id="dashboard-view-mode">
                                <option value="mes" selected>Mes</option>
                                <option value="dia">Día</option>
                            </select>
                            <label for="dashboard-period" id="dashboard-period-label">Año</label>
                            <input type="number" id="dashboard-period" min="2000" max="2100" step="1" value="{{ now()->year }}">
                        </div>
                        <span id="dashboard-period-caption" aria-live="polite">Tendencia mensual del año seleccionado</span>
                    </section>

                    <section class="dashboard-kpis" aria-label="Indicadores principales">
                        <article class="dashboard-kpi dashboard-kpi-products">
                            <p class="kpi-label">Productos vendidos</p>
                            <p class="kpi-value" id="total-productos-vendidos">0</p>
                            <p class="kpi-note" id="kpi-products-period">Unidades del período</p>
                        </article>
                        <article class="dashboard-kpi dashboard-kpi-income">
                            <p class="kpi-label">Cobrado en el período</p>
                            <p class="kpi-value" id="ganancias-dia">$0.00</p>
                            <p class="kpi-note" id="kpi-cash-period">Abonos recibidos</p>
                        </article>
                        <article class="dashboard-kpi dashboard-kpi-customers">
                            <p class="kpi-label">Pendiente de clientes</p>
                            <p class="kpi-value" id="deuda-clientes">$0.00</p>
                            <p class="kpi-note">Pedidos y ventas de clientes</p>
                        </article>
                        <article class="dashboard-kpi dashboard-kpi-pending">
                            <p class="kpi-label">Venta ambulante pendiente</p>
                            <p class="kpi-value" id="deuda-ambulante">$0.00</p>
                            <p class="kpi-note">Cobros pendientes de vendedores</p>
                        </article>
                    </section>

                    <section class="dashboard-content" aria-label="Análisis de ventas">
                        <article class="dashboard-panel chart-container">
                            <header class="panel-heading">
                                <div>
                                    <h2 id="sales-chart-title">Ventas y cobros por mes</h2>
                                    <p id="sales-chart-description">Totales vendidos y abonos recibidos del año seleccionado</p>
                                </div>
                                <span class="panel-period" id="sales-chart-period">{{ now()->year }}</span>
                            </header>
                            <div class="chart-body">
                                <canvas id="container-chart" aria-label="Gráfica de ventas y cobros por mes"></canvas>
                                <div class="chart-empty-state" id="chart-empty-state" hidden>
                                    <strong>Sin actividad registrada</strong>
                                    <span>Las ventas y cobros aparecerán aquí para este período.</span>
                                </div>
                            </div>
                        </article>

                        <article class="dashboard-panel trend-container">
                            <header class="panel-heading">
                                <div>
                                    <h2>Productos más vendidos</h2>
                                    <p id="products-chart-description">Unidades vendidas en el año seleccionado</p>
                                </div>
                            </header>
                            <div class="products-chart-body">
                                <canvas id="productos-mas-vendidos-chart" aria-label="Barras horizontales de productos más vendidos"></canvas>
                                <div class="chart-empty-state" id="products-chart-empty" hidden>
                                    <strong>Sin ventas registradas</strong>
                                    <span>Los productos aparecerán aquí durante el mes.</span>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="dashboard-production-section" aria-label="Producción y mermas">
                        <article class="dashboard-panel production-panel">
                            <header class="panel-heading">
                                <div>
                                    <h2 id="production-chart-title">Producción y unidades quemadas</h2>
                                    <p id="production-chart-description">Unidades buenas frente a mermas durante el año seleccionado</p>
                                </div>
                                <span class="panel-period" id="production-chart-period">{{ now()->year }}</span>
                            </header>
                            <div class="production-chart-body">
                                <canvas id="production-chart" aria-label="Barras apiladas de unidades producidas y quemadas por mes"></canvas>
                                <div class="chart-empty-state" id="production-chart-empty" hidden>
                                    <strong>Sin horneados registrados</strong>
                                    <span>La producción aparecerá aquí durante el año.</span>
                                </div>
                            </div>
                        </article>
                    </section>

                    <section class="dashboard-financial-section" aria-label="Distribución mensual de ventas y gastos">
                        <article class="dashboard-panel financial-panel">
                            <header class="panel-heading financial-panel-heading">
                                <div>
                                    <h2 id="financial-chart-title">Ventas y gastos del año</h2>
                                    <p id="financial-chart-description">Costos de producción, otros gastos y saldo del período</p>
                                </div>
                                <span class="panel-period" id="financial-chart-period">{{ now()->year }}</span>
                            </header>
                            <div class="financial-chart-layout">
                                <div class="financial-chart-body">
                                    <canvas id="financial-breakdown-chart" aria-label="Distribución de costos de producción, otros gastos y saldo de ventas"></canvas>
                                    <div class="chart-empty-state" id="financial-chart-empty" hidden>
                                        <strong>Sin ventas registradas</strong>
                                        <span>El desglose aparecerá cuando haya ventas en el período.</span>
                                    </div>
                                </div>
                                <dl class="financial-breakdown-list">
                                    <div><dt>Ventas totales</dt><dd id="financial-sales-total">$0.00</dd></div>
                                    <div><dt>Costos de producción</dt><dd id="financial-product-cost">$0.00</dd></div>
                                    <div><dt>Otros gastos</dt><dd id="financial-other-expenses">$0.00</dd></div>
                                    <div class="financial-balance-row"><dt id="financial-balance-label">Saldo estimado</dt><dd id="financial-net-balance">$0.00</dd></div>
                                </dl>
                            </div>
                            <p class="financial-cost-note">Los costos de producción corresponden a la suma de los costos registrados en cada preparación.</p>
                        </article>
                    </section>
                    <footer class="dashboard-footer">Datos actualizados al cargar esta página.</footer>
                </div>
            </div>
        </div>
    </main>
    <x-toast></x-toast>
</x-canvas>