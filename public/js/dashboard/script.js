/*********************************************************************************
 *********NOTA: no se a testeado para verificar que todo este funcionando*********
 *********************************************************************************/



/**
 * variables globales
 */
let productosMasVendidos = [];
let chartInstance = null;
let productsChartInstance = null;
let productionChartInstance = null;
let financialChartInstance = null;
let analyticsRequestId = 0;

const MONTH_NAMES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

/**
 * iniciador de instancias o parametros pos carga
 */
window.addEventListener('DOMContentLoaded', function () {
    cargarTotalPorCobrar();
    setupDashboardPeriodFilter();
});

/**
 * caragador de la carta total por cobrar
 */
async function cargarTotalPorCobrar(){
    let url = '/dashboard/total-por-cobrar';

    try{
        const data = await requestHttp(url);
        if(data.success){
            const deudaClientes = document.getElementById('deuda-clientes');
            const deudaAmbulante = document.getElementById('deuda-ambulante');
            if (deudaClientes) deudaClientes.textContent = '$' + Number(data.response?.clientes || 0).toFixed(2);
            if (deudaAmbulante) deudaAmbulante.textContent = '$' + Number(data.response?.ventas_ambulantes || 0).toFixed(2);
        }else{
            console.error('Error al cargar el total por cobrar: ', data.message);
        }
    }catch(error){
        console.error('Error al cargar el total por cobrar: ', error);
    }
}

/**
 * cargador de la tabla productos mas vendidos
 */
async function cargarProductosMasVendidos() {
    let url = '/dashboard/productos-mas-vendidos';
    if (!document.getElementById('productos-mas-vendidos-chart')) return;

    try {
        const data = await requestHttp(url);
        if (data.success) {
            productosMasVendidos = data.response;
            renderProductosMasVendidosChart();
        } else {
            console.error('Error al cargar los productos más vendidos:', data.message);
        }
    } catch (error) {
        console.error('Error al cargar los productos más vendidos:', error);
    }
}

/**
 * Renderizador de la tabla productos mas vendidos
 */
function renderProductosMasVendidosChart() {
    const canvas = document.getElementById('productos-mas-vendidos-chart');
    const emptyState = document.getElementById('products-chart-empty');
    const hasProducts = productosMasVendidos.length > 0;
    if (emptyState) emptyState.hidden = hasProducts;
    if (canvas) canvas.hidden = !hasProducts;
    if (productsChartInstance) {
        productsChartInstance.destroy();
        productsChartInstance = null;
    }
    if (!canvas || !hasProducts) return;

    productsChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: productosMasVendidos.map(producto => producto.nombre),
            datasets: [{
                label: 'Unidades',
                data: productosMasVendidos.map(producto => Number(producto.total_vendidos || 0)),
                backgroundColor: ['#277968', '#d08032', '#75a9a0', '#7798a8', '#b08b65'],
                borderRadius: 4,
                maxBarThickness: 26,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => Number(context.raw || 0).toLocaleString('es-SV') + ' unidades',
                    },
                },
            },
            scales: {
                x: { beginAtZero: true, grid: { color: '#e8ede8' }, ticks: { precision: 0, color: '#637168' } },
                y: { grid: { display: false }, ticks: { color: '#4a3525' } },
            },
        },
    });
}

/**
 * catgador de la targeta total productos vendidos
 */
async function totalProductosVendidos() {
    let url = '/dashboard/total-productos-vendidos';
    try {
        const data = await requestHttp(url);
        if (data.success) {
            const totalProductosVendidosElement = document.getElementById('total-productos-vendidos');
            if (data.response && data.response.total_vendidos !== undefined && data.response.total_vendidos !== null) {
                const totalProductosVendidos = data.response.total_vendidos;
                totalProductosVendidosElement.textContent = totalProductosVendidos + " productos.";
            } else {
                showToast('No hay suficientes datos, para datos mas vendidos.','warning');
                totalProductosVendidosElement.textContent = '0 productos.';
            }
        } else {
            showToast('Error al cargar los productos mas vendidos: '+data.message,'error');
            console.error('Error al cargar los productos más vendidos:', data.message);
        }
    } catch (error) {
        showToast('Error al cargar los productos mas vendidos.','error');
        console.error('Error al cargar los productos más vendidos:', error);
    }
}


/**
 * cargador de datos de la targeta ganancias del dia
 */
async function cargarGananciasDelDia() {
    let url = '/dashboard/ganancias-del-dia';
    try {
        const data = await requestHttp(url);
        if (data.success) {
            const gananciasDelDiaElement = document.getElementById('ganancias-dia');
            if (data.response && data.response.total_ganancias !== undefined && data.response.total_ganancias !== null) {
                const gananciasDelDia = Number(data.response.total_ganancias) || 0;
                gananciasDelDiaElement.textContent = '$' + gananciasDelDia.toFixed(2);
            } else {
                gananciasDelDiaElement.textContent = '$0.00';
            }
        } else {
            console.error('Error al cargar las ganancias del día:', data.message);
            showToast('Error al cargar las ganancias del dia: '+data.message,'error');
        }
    } catch (error) {
        showToast('Error al cargar las ganancias del dia.','error');
        console.error('Error al cargar las ganancias del día:', error);
    }
}

/**
 * 
 * api de conexion del servidor
 */
async function requestHttp(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            "X-CSRF-TOKEN": csrf_token,
            Accept: "application/json",
        },
    });
    return response.json();
}

function setupDashboardPeriodFilter() {
    const modeInput = document.getElementById('dashboard-view-mode');
    const periodInput = document.getElementById('dashboard-period');
    const periodLabel = document.getElementById('dashboard-period-label');
    const periodCaption = document.getElementById('dashboard-period-caption');
    if (!modeInput || !periodInput) return;

    function syncPeriodControl() {
        const mode = modeInput.value;
        const currentValue = periodInput.value;
        const currentYear = String(new Date().getFullYear());
        const selectedYear = currentValue.match(/^\d{4}/)?.[0] || currentYear;

        if (mode === 'mes') {
            periodInput.type = 'number';
            periodInput.min = '2000';
            periodInput.max = '2100';
            periodInput.step = '1';
            periodInput.value = selectedYear;
            if (periodLabel) periodLabel.textContent = 'Año';
            if (periodCaption) periodCaption.textContent = 'Totales agrupados por mes';
            return;
        }

        const month = currentValue.match(/^\d{4}-(\d{2})$/)?.[1]
            || (selectedYear === currentYear ? String(new Date().getMonth() + 1).padStart(2, '0') : '01');
        periodInput.type = 'month';
        periodInput.value = selectedYear + '-' + month;
        if (periodLabel) periodLabel.textContent = 'Mes';
        if (periodCaption) periodCaption.textContent = 'Totales agrupados por día';
    }

    async function refreshDashboard() {
        const requestId = ++analyticsRequestId;
        const params = new URLSearchParams({ modo: modeInput.value, periodo: periodInput.value });

        try {
            const data = await requestHttp('/dashboard/analitica?' + params.toString());
            if (requestId === analyticsRequestId && data.success) {
                renderDashboardAnalytics(data.response);
            }
        } catch (error) {
            console.error('Error al cargar la analítica del período:', error);
        }
    }

    modeInput.addEventListener('change', function () {
        syncPeriodControl();
        refreshDashboard();
    });
    periodInput.addEventListener('change', refreshDashboard);
    syncPeriodControl();
    refreshDashboard();
}

function renderDashboardAnalytics(data) {
    const isMonthly = data.modo === 'mes';
    const periodLabel = data.periodo_label;
    const productUnits = document.getElementById('total-productos-vendidos');
    const cashTotal = document.getElementById('ganancias-dia');
    const productNote = document.getElementById('kpi-products-period');
    const cashNote = document.getElementById('kpi-cash-period');
    const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

    if (productUnits) productUnits.textContent = Number(data.unidades_vendidas || 0).toLocaleString('es-SV') + ' productos.';
    if (cashTotal) cashTotal.textContent = currency.format(Number(data.cobros_totales || 0));
    if (productNote) productNote.textContent = isMonthly ? 'Unidades durante el año' : 'Unidades durante el mes';
    if (cashNote) cashNote.textContent = isMonthly ? 'Abonos recibidos durante el año' : 'Abonos recibidos durante el mes';

    const salesTitle = document.getElementById('sales-chart-title');
    const salesDescription = document.getElementById('sales-chart-description');
    const productDescription = document.getElementById('products-chart-description');
    const productionDescription = document.getElementById('production-chart-description');
    const periodBadge = isMonthly ? periodLabel : periodLabel;
    if (salesTitle) salesTitle.textContent = isMonthly ? 'Ventas y cobros por mes' : 'Ventas y cobros por día';
    if (salesDescription) salesDescription.textContent = isMonthly
        ? 'Totales vendidos y abonos recibidos durante ' + periodLabel
        : 'Totales vendidos y abonos recibidos durante ' + periodLabel;
    if (productDescription) productDescription.textContent = 'Unidades vendidas durante ' + periodLabel;
    if (productionDescription) productionDescription.textContent = isMonthly
        ? 'Unidades buenas frente a mermas por mes durante ' + periodLabel
        : 'Unidades buenas frente a mermas por día durante ' + periodLabel;

    ['sales-chart-period', 'production-chart-period', 'financial-chart-period'].forEach(function (id) {
        const element = document.getElementById(id);
        if (element) element.textContent = periodBadge;
    });

    const financialTitle = document.getElementById('financial-chart-title');
    if (financialTitle) financialTitle.textContent = isMonthly ? 'Ventas y gastos del año' : 'Ventas y gastos del mes';

    renderBarChart(document.getElementById('container-chart'), data.serie, data.modo);
    productosMasVendidos = data.productos || [];
    renderProductosMasVendidosChart();
    renderProductionChart(data.serie, data.modo);
    renderDesgloseMensual({
        ventas: data.ventas_totales,
        costo_productos: data.costo_produccion,
        otros_gastos: data.otros_gastos,
        saldo_estimado: data.saldo,
        deficit_estimado: data.deficit,
    });
}

/**
 * cargador de grafia
 */

async function cargarVentasPorMes(){
    try{
        let url = '/dashboard/ventas-por-mes';
        let data = await requestHttp(url);

        if(data.success){
            let canvas = document.getElementById('container-chart');
            renderBarChart(canvas, data.response);
        }else{
            console.error('Error al cargar las ventas por mes: ',data.message);
        }
    }catch(error){
        console.error('Error al cargar las ventas por mes: ',error);
    }
}

async function cargarDesgloseMensual(mes) {
    const url = '/dashboard/desglose-mensual?mes=' + encodeURIComponent(mes);
    try {
        const data = await requestHttp(url);
        if (data.success) renderDesgloseMensual(data.response);
    } catch (error) {
        console.error('Error al cargar el desglose mensual:', error);
    }
}

async function cargarProduccionPorMes() {
    try {
        const data = await requestHttp('/dashboard/produccion-por-mes');
        if (data.success) renderProduccionChart(data.response);
    } catch (error) {
        console.error('Error al cargar la producción mensual:', error);
    }
}

function renderProductionChart(data) {
    const canvas = document.getElementById('production-chart');
    const emptyState = document.getElementById('production-chart-empty');
    const puntos = data || [];
    const labels = puntos.map(item => item.label);
    const buenas = puntos.map(item => Number(item.buenas || 0));
    const quemadas = puntos.map(item => Number(item.quemadas || 0));

    const hasProduction = buenas.some(value => value > 0) || quemadas.some(value => value > 0);
    if (emptyState) emptyState.hidden = hasProduction;
    if (canvas) canvas.hidden = !hasProduction;
    if (productionChartInstance) {
        productionChartInstance.destroy();
        productionChartInstance = null;
    }
    if (!canvas || !hasProduction) return;

    productionChartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Unidades buenas', data: buenas, backgroundColor: '#277968', borderRadius: 3, stack: 'produccion' },
                { label: 'Quemadas', data: quemadas, backgroundColor: '#d08032', borderRadius: 3, stack: 'produccion' },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } },
                tooltip: {
                    callbacks: {
                        label: context => context.dataset.label + ': ' + Number(context.raw || 0).toLocaleString('es-SV'),
                    },
                },
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { color: '#637168' } },
                y: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: '#637168' }, grid: { color: '#e8ede8' } },
            },
        },
    });
}

function renderDesgloseMensual(data) {
    const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    const ventas = Number(data.ventas || 0);
    const costoProductos = Number(data.costo_productos || 0);
    const otrosGastos = Number(data.otros_gastos || 0);
    const saldo = Number(data.saldo_estimado || 0);
    const deficit = Number(data.deficit_estimado || 0);
    const amounts = [
        ['financial-sales-total', ventas],
        ['financial-product-cost', costoProductos],
        ['financial-other-expenses', otrosGastos],
        ['financial-net-balance', deficit > 0 ? -deficit : saldo],
    ];

    amounts.forEach(function ([id, amount]) {
        const element = document.getElementById(id);
        if (element) element.textContent = currency.format(amount);
    });

    const balanceLabel = document.getElementById('financial-balance-label');
    const balanceValue = document.getElementById('financial-net-balance');
    if (balanceLabel) balanceLabel.textContent = deficit > 0 ? 'Déficit estimado' : 'Saldo estimado';
    if (balanceValue) balanceValue.classList.toggle('is-negative', deficit > 0);

    const canvas = document.getElementById('financial-breakdown-chart');
    const emptyState = document.getElementById('financial-chart-empty');
    const labels = ['Costos de producción', 'Otros gastos'];
    const values = [costoProductos, otrosGastos];
    if (saldo > 0) {
        labels.push('Saldo restante');
        values.push(saldo);
    }

    const hasAmounts = values.some(value => value > 0);
    if (emptyState) emptyState.hidden = hasAmounts;
    if (canvas) canvas.hidden = !hasAmounts;
    if (financialChartInstance) {
        financialChartInstance.destroy();
        financialChartInstance = null;
    }

    if (!canvas || !hasAmounts) return;

    financialChartInstance = new Chart(canvas, {
        type: 'pie',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: ['#277968', '#d08032', '#75a9a0'],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.label + ': ' + currency.format(Number(context.raw || 0));
                        },
                    },
                },
            },
        },
    });
}


/**
 * Renderizador de la grafica
 */
function renderBarChart(canvas, data) {
    if (!canvas) return;

    const puntos = data || [];
    const labels = puntos.map(item => item.label);
    const ventas = puntos.map(item => Number(item.ventas || 0));
    const cobros = puntos.map(item => Number(item.cobros || 0));

    const emptyState = document.getElementById('chart-empty-state');
    const hasActivity = ventas.some(amount => amount > 0) || cobros.some(amount => amount > 0);
    if (emptyState) emptyState.hidden = hasActivity;
    canvas.hidden = !hasActivity;

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    if (!hasActivity) return;

    chartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ventas',
                    data: ventas,
                    borderColor: '#277968',
                    backgroundColor: 'rgba(39, 121, 104, 0.12)',
                    pointBackgroundColor: '#277968',
                    pointRadius: 3,
                    tension: 0.28,
                },
                {
                    label: 'Cobros',
                    data: cobros,
                    borderColor: '#d08032',
                    backgroundColor: 'rgba(208, 128, 50, 0.1)',
                    pointBackgroundColor: '#d08032',
                    pointRadius: 3,
                    tension: 0.28,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 18 } },
                tooltip: {
                    callbacks: {
                        label: context => context.dataset.label + ': $' + Number(context.raw || 0).toFixed(2),
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (val) { return '$' + Number(val).toLocaleString('es-SV'); },
                        color: '#637168'
                    },
                    grid: { color: '#e8ede8' }
                },
                x: {
                    ticks: { color: '#637168' },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
}