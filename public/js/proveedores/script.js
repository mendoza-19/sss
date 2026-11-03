/**
 * ====================================================================
 * GESTIÓN DE PROVEEDORES - SCRIPT OPTIMIZADO
 * ====================================================================
 */

// Estado global de la aplicación
let chartInstance = null;
let proveedoresList = [];
let backupElementsInSearch = [];
let searchMode = false;
let stopFetch = false;
let isFetching = false;

// Estado del modal de facturas del proveedor
let facturasList = [];
let idProveedorInModalFacturas = -1;
let stopFetchFacturas = false;
let isFetchingFacturas = false;

// Estado de edición y calificación
let editMode = false;
let elementOnEdit = null;
let supplierAssessmentOnEdit = null;

// Elementos de carga (spinners)
let loadingElement = null;
let loadingElementFacturas = null;

// Nombres de meses para la gráfica
const MONTH_NAMES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
];

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar referencias a elementos de carga
    const loaderContainer = document.querySelector('.table-providers .load-animation-container');
    if (loaderContainer) {
        loadingElement = loaderContainer.closest('tr');
    }

    const loaderFacturasContainer = document.querySelector('.table-facturas .load-animation-container');
    if (loaderFacturasContainer) {
        loadingElementFacturas = loaderFacturasContainer.closest('tr');
    }

    // Cargar estadísticas superiores
    cargarEstadisticasProveedores();

    // Cargar lista de ingredientes para los filtros
    cargarFiltroIngredientes();

    // Cargar el primer lote de proveedores
    nextBatchOfSuppliers();

    // Evento de scroll infinito en la tabla de proveedores
    const tableContainer = document.querySelector('.table-providers-container');
    if (tableContainer) {
        tableContainer.addEventListener('scroll', function () {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20 && !stopFetch && !searchMode && !isFetching) {
                nextBatchOfSuppliers();
            }
        });
    }

    // Evento de scroll infinito en el modal de facturas del proveedor
    const facturaContainer = document.querySelector('.factura-proveedor-container');
    if (facturaContainer) {
        facturaContainer.addEventListener('scroll', function () {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20 && !stopFetchFacturas && !isFetchingFacturas) {
                nextBatchOfBills(idProveedorInModalFacturas);
            }
        });
    }

    // Botones de acción principales
    const btnNuevo = document.getElementById('btn-nuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', openCreateSupplierModal);
    }

    const btnSave = document.getElementById('btn-save');
    if (btnSave) {
        btnSave.addEventListener('click', saveSupplierForm);
    }

    const btnReporte = document.getElementById('btn-reporte');
    if (btnReporte) {
        btnReporte.addEventListener('click', descargarReporte);
    }

    // Buscador
    const searchBar = document.getElementById('search-bar-provider');
    if (searchBar) {
        const inputSearch = searchBar.querySelector('input') || searchBar;
        inputSearch.addEventListener('keydown', eventSearchProveedor);

        const btnSearch = searchBar.querySelector('button');
        if (btnSearch) {
            btnSearch.addEventListener('click', function () {
                ejecutarBusqueda(inputSearch.value.trim());
            });
        }
    }

    // Filtros de ingrediente y costo
    const filterIng = document.getElementById('filter-by-ingredient');
    if (filterIng) {
        filterIng.addEventListener('change', eventChangeFilterByIngredient);
    }

    const filterCost = document.getElementById('filter-by-cost');
    if (filterCost) {
        filterCost.disabled = true;
        filterCost.addEventListener('change', function () {
            const searchInput = document.querySelector('#search-bar-provider input') || document.getElementById('search-bar-provider');
            if (searchInput && searchInput.value.trim() !== '') {
                ejecutarBusqueda(searchInput.value.trim());
            }
        });
    }

    // Evento para calificación con estrellas
    const starsContainer = document.querySelector('.assessment-container-stars');
    if (starsContainer) {
        starsContainer.addEventListener('click', function (event) {
            const rect = this.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const porcentaje = Math.min(100, Math.max(0, Math.round((clickX / rect.width) * 100)));
            setStars(porcentaje);
            if (supplierAssessmentOnEdit) {
                supplierAssessmentOnEdit.puntuacion = porcentaje;
                updateScore();
            }
        });
    }
});

// ====================================================================
// CARGA DE DATOS Y ESTADÍSTICAS
// ====================================================================

/**
 * Carga las 3 tarjetas de estadísticas superiores
 */
async function cargarEstadisticasProveedores() {
    ingredientMasComprado();
    ingredientMenosMasComprado();
    proveedroMenosSolicitado();
}

async function ingredientMasComprado() {
    try {
        const response = await fetch('/api/ingredientes/ingredienteMasComprado', {
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });
        const res = await response.json();
        const pElement = document.querySelector('.mascomprado-card p');
        if (pElement) {
            pElement.textContent = (res.success && res.ingrediente) ? res.ingrediente.nombre : 'No hay datos';
        }
    } catch (e) {
        console.error('Error al obtener ingrediente más comprado:', e);
    }
}

async function ingredientMenosMasComprado() {
    try {
        const response = await fetch('/api/ingredientes/ingredienteMenosComprado', {
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });
        const res = await response.json();
        const pElement = document.querySelector('.menoscomprado-card p');
        if (pElement) {
            pElement.textContent = (res.success && res.ingrediente) ? res.ingrediente.nombre : 'No hay datos';
        }
    } catch (e) {
        console.error('Error al obtener ingrediente menos comprado:', e);
    }
}

async function proveedroMenosSolicitado() {
    try {
        const response = await fetch('/api/proveedores/proveedroMenosSolicitado', {
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });
        const res = await response.json();
        const pElement = document.querySelector('.proveedorMenosSolicitado-card p');
        if (pElement) {
            pElement.textContent = (res.success && res.proveedor) ? res.proveedor.nombre : 'No hay datos';
        }
    } catch (e) {
        console.error('Error al obtener proveedor menos solicitado:', e);
    }
}

async function cargarFiltroIngredientes() {
    try {
        const response = await fetch('/api/ingredientes/allIngredients', {
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });
        const res = await response.json();
        const select = document.getElementById('filter-by-ingredient');
        if (select && res.success && res.ingredientes) {
            select.innerHTML = '<option value="">Todos los Ingredientes</option>';
            res.ingredientes.forEach(ing => {
                const opt = document.createElement('option');
                opt.value = ing.idingrediente;
                opt.textContent = ing.nombre;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Error al cargar ingredientes:', e);
    }
}

function eventChangeFilterByIngredient(event) {
    const ingredienteId = event.target.value;
    const filtroCosto = document.getElementById('filter-by-cost');
    if (!filtroCosto) return;

    if (ingredienteId === "") {
        filtroCosto.innerHTML = `<option value="">Todos los Costos</option>`;
        filtroCosto.disabled = true;
    } else {
        filtroCosto.innerHTML = `
            <option value="">Todos los Costos</option>
            <option value="barato">Más Baratos</option>
            <option value="caro">Más Caros</option>
        `;
        filtroCosto.disabled = false;
    }

    const searchInput = document.querySelector('#search-bar-provider input') || document.getElementById('search-bar-provider');
    if (searchInput && searchInput.value.trim() !== '') {
        ejecutarBusqueda(searchInput.value.trim());
    }
}

// ====================================================================
// CONSTRUCCIÓN DE FILAS Y RENDERIZADO DE TABLA
// ====================================================================

/**
 * Función unificada para construir la fila HTML de un proveedor
 */
function createSupplierRow(prov) {
    const tr = document.createElement('tr');
    tr.dataset.id = prov.idproveedor;

    tr.innerHTML = `
        <td>
            <strong title="${prov.descripcion || 'Sin descripción'}">${prov.nombre}</strong>
        </td>
        <td>${prov.telefono || 'N/A'}</td>
        <td>${prov.correo || 'N/A'}</td>
        <td>
            <div class="container-icon-table">
                <div class="icon-container-table" onclick="showModalFactura(${prov.idproveedor})" title="Ver facturas del proveedor">
                    <img src="/assets/global/icon-factura.svg" alt="Facturas">
                </div>
            </div>
        </td>
        <td>
            <div class="container-icon-table">
                <div class="icon-container-table edit-option-table" onclick="eventActiveEditMode(${prov.idproveedor})" title="Editar información del proveedor">
                    <img src="/assets/global/editar_black_color.svg" alt="Editar">
                </div>
                <div class="icon-container-table" onclick="openModalGraphi(${prov.idproveedor})" title="Ver gráfica de gastos">
                    <img src="/assets/global/icon-statistics.svg" alt="Gráfica">
                </div>
                <div class="icon-container-table" onclick="openModalAssessment(${prov.idproveedor})" title="Calificar proveedor">
                    <img src="/assets/global/icon-assessment.svg" alt="Evaluación">
                </div>
            </div>
        </td>
    `;
    return tr;
}

/**
 * Carga el siguiente lote de proveedores (Infinite Scroll)
 */
async function nextBatchOfSuppliers() {
    if (stopFetch || isFetching) return;
    isFetching = true;

    const tbody = document.querySelector('.table-providers tbody');
    if (loadingElement && !loadingElement.parentElement && tbody) {
        tbody.appendChild(loadingElement);
    }

    try {
        const formData = new FormData();
        formData.append('skip', proveedoresList.length);
        formData.append('limit', 6);

        const response = await fetch("/api/proveedores/getProveedoresPagination", {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });

        const res = await response.json();
        if (res.success && res.proveedores) {
            const data = res.proveedores;
            if (data.length > 0) {
                data.forEach(prov => {
                    proveedoresList.push(prov);
                    const tr = createSupplierRow(prov);
                    if (loadingElement && loadingElement.parentElement) {
                        loadingElement.before(tr);
                    } else if (tbody) {
                        tbody.appendChild(tr);
                    }
                });

                if (data.length < 6) {
                    stopFetch = true;
                    if (loadingElement && loadingElement.parentElement) {
                        loadingElement.remove();
                    }
                }
            } else {
                stopFetch = true;
                if (loadingElement && loadingElement.parentElement) {
                    loadingElement.remove();
                }
                if (proveedoresList.length === 0 && tbody) {
                    tbody.innerHTML = '<tr><td colspan="5" style="padding: 20px; color: #777;">No se encontraron proveedores registrados.</td></tr>';
                }
            }
        }
    } catch (error) {
        console.error('Error al obtener proveedores:', error);
        showToast('Error al obtener proveedores', 'error');
    } finally {
        isFetching = false;
    }
}

// ====================================================================
// BÚSQUEDA Y FILTRADO
// ====================================================================

function eventSearchProveedor(event) {
    if (event.key === 'Enter') {
        const searchVal = event.target.value.trim();
        ejecutarBusqueda(searchVal);
    }
}

async function ejecutarBusqueda(searchVal) {
    const tbody = document.querySelector('.table-providers tbody');
    if (!tbody) return;

    if (searchVal === "") {
        // Restaurar lista previa si salimos del modo búsqueda
        if (searchMode && backupElementsInSearch.length > 0) {
            searchMode = false;
            proveedoresList = [...backupElementsInSearch];
            backupElementsInSearch = [];
            stopFetch = false;

            tbody.innerHTML = '';
            proveedoresList.forEach(p => tbody.appendChild(createSupplierRow(p)));
            if (loadingElement) tbody.appendChild(loadingElement);
        }
        return;
    }

    if (!searchMode) {
        backupElementsInSearch = [...proveedoresList];
        searchMode = true;
    }

    tbody.innerHTML = '';
    if (loadingElement) tbody.appendChild(loadingElement);

    const filtroIngrediente = document.getElementById('filter-by-ingredient')?.value || '';
    const filtroCosto = document.getElementById('filter-by-cost')?.value || '';

    try {
        const datos = new FormData();
        datos.append('search', searchVal);
        datos.append('filtroIngrediente', filtroIngrediente);
        datos.append('filtroCosto', filtroCosto);

        const response = await fetch('/api/proveedores/searchProveedor', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf_token },
            body: datos
        });

        const res = await response.json();
        if (loadingElement && loadingElement.parentElement) {
            loadingElement.remove();
        }

        if (res.success && res.proveedores && res.proveedores.length > 0) {
            res.proveedores.forEach(prov => {
                tbody.appendChild(createSupplierRow(prov));
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="padding: 20px; color: #777;">No se encontraron proveedores que coincidan con la búsqueda.</td></tr>';
        }
    } catch (e) {
        if (loadingElement && loadingElement.parentElement) loadingElement.remove();
        console.error('Error al buscar proveedor:', e);
        showToast('Error al buscar proveedor', 'error');
    }
}

// ====================================================================
// CREACIÓN Y EDICIÓN DE PROVEEDORES
// ====================================================================

function openCreateSupplierModal() {
    editMode = false;
    elementOnEdit = null;

    const form = document.getElementById('form-add-proveedor');
    if (form) form.reset();

    const title = document.getElementById('modal-title-supplier');
    if (title) title.textContent = 'Nuevo Proveedor';

    const btnSave = document.getElementById('btn-save');
    if (btnSave) btnSave.textContent = 'Guardar';

    modalManager.showModalId('modal-new-supplier');
}

function eventActiveEditMode(idProveedor) {
    const prov = proveedoresList.find(x => x.idproveedor === idProveedor) || backupElementsInSearch.find(x => x.idproveedor === idProveedor);
    if (!prov) {
        showToast('Proveedor no encontrado', 'error');
        return;
    }

    editMode = true;
    elementOnEdit = idProveedor;

    const title = document.getElementById('modal-title-supplier');
    if (title) title.textContent = `Editar Proveedor: ${prov.nombre}`;

    const btnSave = document.getElementById('btn-save');
    if (btnSave) btnSave.textContent = 'Actualizar';

    document.getElementById('nameNewSupplier').value = prov.nombre || '';
    document.getElementById('phoneNewSupplier').value = prov.telefono || '';
    document.getElementById('emailNewSupplier').value = prov.correo || '';
    document.getElementById('descriptionNewSupplier').value = prov.descripcion || '';

    modalManager.showModalId('modal-new-supplier');
}

function verifiDataFormAddProveedor() {
    const name = document.getElementById('nameNewSupplier')?.value.trim();
    const phone = document.getElementById('phoneNewSupplier')?.value.trim();
    const email = document.getElementById('emailNewSupplier')?.value.trim();

    if (!name || !phone || !email) {
        showToast('Por favor complete todos los campos obligatorios (*)', 'error');
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showToast('Ingrese un formato de correo electrónico válido', 'error');
        return false;
    }

    if (phone.length < 7) {
        showToast('El teléfono debe tener al menos 7 dígitos', 'error');
        return false;
    }

    return true;
}

async function saveSupplierForm() {
    if (!verifiDataFormAddProveedor()) return;

    modalManager.showModalId('base-modal-loading');

    const name = document.getElementById('nameNewSupplier').value.trim();
    const phone = document.getElementById('phoneNewSupplier').value.trim();
    const email = document.getElementById('emailNewSupplier').value.trim();
    const description = document.getElementById('descriptionNewSupplier').value.trim();

    const datos = new FormData();
    datos.append('nameNewSupplier', name);
    datos.append('phoneNewSupplier', phone);
    datos.append('emailNewSupplier', email);
    datos.append('descriptionNewSupplier', description);

    let url = "/api/proveedores/create";
    if (editMode && elementOnEdit) {
        url = "/api/proveedores/updateProveedor";
        datos.append('idNewSupplier', elementOnEdit);
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: datos,
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });

        const res = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (res.success) {
            showToast(res.message, 'success');
            modalManager.hiddenModalId('modal-new-supplier');

            const provData = res.proveedor;
            if (!editMode) {
                // Agregar nueva fila al inicio de la tabla
                proveedoresList.unshift(provData);
                const tbody = document.querySelector('.table-providers tbody');
                if (tbody) {
                    const firstTr = tbody.firstElementChild;
                    const newTr = createSupplierRow(provData);
                    if (firstTr) {
                        firstTr.before(newTr);
                    } else {
                        tbody.appendChild(newTr);
                    }
                }
            } else {
                // Actualizar fila existente
                const index = proveedoresList.findIndex(x => x.idproveedor === elementOnEdit);
                if (index !== -1) proveedoresList[index] = provData;

                const trExistente = document.querySelector(`tr[data-id="${elementOnEdit}"]`);
                if (trExistente) {
                    const trActualizado = createSupplierRow(provData);
                    trExistente.replaceWith(trActualizado);
                }
                editMode = false;
                elementOnEdit = null;
            }
        } else {
            showToast(res.message || 'Error al procesar proveedor', 'error');
            if (res.errors) {
                Object.values(res.errors).forEach(errArray => {
                    errArray.forEach(err => showToast(err, 'error'));
                });
            }
        }
    } catch (e) {
        modalManager.hiddenModalId('base-modal-loading');
        console.error('Error al guardar proveedor:', e);
        showToast('Error de conexión con el servidor', 'error');
    }
}

// ====================================================================
// MODAL GRÁFICA DE GASTOS EN COMPRAS
// ====================================================================

function openModalGraphi(idProveedor) {
    modalManager.showModalId('modal-graphi');
    graficaGastosEnCompras(idProveedor);
}

async function graficaGastosEnCompras(idProveedor) {
    modalManager.showModalId('base-modal-loading');

    try {
        const formData = new FormData();
        formData.append('idProveedor', idProveedor);

        const response = await fetch('/api/proveedores/graphiGastosEnCompras', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrf_token }
        });

        const res = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (res.success && res.gastosEnCompras) {
            const canvas = document.getElementById('graphi-supplier');
            renderBarChart(canvas, res.gastosEnCompras);
        } else {
            showToast(res.message || 'No se encontraron gastos para este proveedor', 'error');
        }
    } catch (error) {
        modalManager.hiddenModalId('base-modal-loading');
        showToast('Error al obtener gastos en compras', 'error');
        console.error('Error al obtener gráfica:', error);
    }
}

function renderBarChart(canvas, data) {
    if (!canvas) return;

    // Mapear los 12 meses del año
    const montos = new Array(12).fill(0);
    if (data && data.length > 0) {
        data.forEach(item => {
            const idx = parseInt(item.mes) - 1;
            if (idx >= 0 && idx < 12) {
                montos[idx] = parseFloat(item.total_comprado || 0);
            }
        });
    }

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: MONTH_NAMES,
            datasets: [{
                label: 'Gastos en compras ($)',
                data: montos,
                backgroundColor: 'rgba(193, 140, 93, 0.85)',
                borderColor: 'rgba(139, 90, 43, 1)',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: 'rgba(109, 68, 31, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        font: { family: 'Inter', size: 13, weight: 'bold' },
                        color: '#4a3525'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ` Gasto: $${context.raw.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (val) { return '$' + val; },
                        font: { family: 'Inter' },
                        color: '#6d4c41'
                    },
                    grid: { color: 'rgba(215, 204, 200, 0.4)' }
                },
                x: {
                    ticks: { font: { family: 'Inter' }, color: '#6d4c41' },
                    grid: { display: false }
                }
            }
        }
    });
}

// ====================================================================
// MODAL DE FACTURAS DEL PROVEEDOR
// ====================================================================

function showModalFactura(idProveedor) {
    idProveedorInModalFacturas = idProveedor;
    facturasList = [];
    stopFetchFacturas = false;

    const tbody = document.querySelector('.table-facturas tbody');
    if (tbody) {
        tbody.innerHTML = '';
        if (loadingElementFacturas) {
            tbody.appendChild(loadingElementFacturas);
        }
    }

    nextBatchOfBills(idProveedor);
    modalManager.showModalId('modal-factura');
}

async function nextBatchOfBills(idProveedor) {
    if (stopFetchFacturas || isFetchingFacturas) return;
    isFetchingFacturas = true;

    const tbody = document.querySelector('.table-facturas tbody');

    try {
        const formData = new FormData();
        formData.append('idproveedor', idProveedor);
        formData.append('skip', facturasList.length);
        formData.append('limit', 6);

        const response = await fetch('/api/facturas/getFacturasByProveedorPagination', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf_token },
            body: formData
        });

        const res = await response.json();
        if (res.success && res.facturas) {
            const data = res.facturas;
            if (data.length > 0) {
                data.forEach(factura => {
                    facturasList.push(factura);
                    const tr = document.createElement('tr');
                    const fotoSrc = factura.foto ? factura.foto : '/assets/global/icon-factura.svg';
                    const pagadoTexto = factura.fechapago ? formatDateDisplay(factura.fechapago) : 'Pendiente';

                    tr.innerHTML = `
                        <td>${formatDateDisplay(factura.emicion)}</td>
                        <td>${pagadoTexto}</td>
                        <td><strong>$ ${parseFloat(factura.total || 0).toFixed(2)}</strong></td>
                        <td>
                            <div class="icon-container-table-factura">
                                <div onclick="showModalImageFactura('${fotoSrc}')" title="Ver foto de factura">
                                    <img src="/assets/global/icon-factura.svg" alt="Factura">
                                </div>
                            </div>
                        </td>
                    `;

                    if (loadingElementFacturas && loadingElementFacturas.parentElement) {
                        loadingElementFacturas.before(tr);
                    } else if (tbody) {
                        tbody.appendChild(tr);
                    }
                });

                if (data.length < 6) {
                    stopFetchFacturas = true;
                    if (loadingElementFacturas && loadingElementFacturas.parentElement) {
                        loadingElementFacturas.remove();
                    }
                }
            } else {
                stopFetchFacturas = true;
                if (loadingElementFacturas && loadingElementFacturas.parentElement) {
                    loadingElementFacturas.remove();
                }
                if (facturasList.length === 0 && tbody) {
                    tbody.innerHTML = '<tr><td colspan="4" style="padding: 20px; color: #777;">No hay facturas registradas para este proveedor.</td></tr>';
                }
            }
        }
    } catch (e) {
        console.error('Error al cargar facturas de proveedor:', e);
    } finally {
        isFetchingFacturas = false;
    }
}

function closeModalFacturas() {
    const container = document.querySelector('.factura-proveedor-container');
    if (container) container.scrollTop = 0;
    stopFetchFacturas = true;
    idProveedorInModalFacturas = -1;
    facturasList = [];
    modalManager.hiddenModalId('modal-factura');
}

function showModalImageFactura(url) {
    if (!url) return;
    const imgModal = document.querySelector('.base-image-modal img');
    if (imgModal) {
        imgModal.src = url;
    }
    modalManager.showModalId('base-image-modal');
}

// ====================================================================
// EVALUACIÓN CON ESTRELLAS
// ====================================================================

function openModalAssessment(idproveedor) {
    supplierAssessmentOnEdit = proveedoresList.find(e => e.idproveedor === idproveedor);
    if (supplierAssessmentOnEdit) {
        setStars(supplierAssessmentOnEdit.puntuacion || 0);
    }
    modalManager.showModalId('modal-assessment');
}

function closeMOdalAssement() {
    modalManager.hiddenModalId('modal-assessment');
    supplierAssessmentOnEdit = null;
}

function setStars(puntuacion = 0) {
    const starFill = document.querySelector('.assessment-container-stars div');
    let lowerRange = 0;
    let upperRange = 20;
    while (upperRange <= 100) {
        if (puntuacion >= lowerRange && puntuacion <= upperRange) {
            puntuacion = upperRange;
            break;
        }
        lowerRange += 20;
        upperRange += 20;
    }
    if (starFill) {
        starFill.style.width = Math.min(100, Math.max(0, puntuacion)) + '%';
    }
}

async function updateScore() {
    if (!supplierAssessmentOnEdit) return;

    try {
        const form = new FormData();
        form.append('idproveedor', supplierAssessmentOnEdit.idproveedor);
        form.append('puntuacion', supplierAssessmentOnEdit.puntuacion);

        const response = await fetch('/api/proveedores/updateScore', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf_token },
            body: form
        });

        const res = await response.json();
        if (res.success) {
            showToast('Calificación guardada exitosamente', 'success');
        } else {
            showToast('Error al actualizar puntuación', 'error');
        }
    } catch (e) {
        console.error('Error al actualizar score:', e);
        showToast('Error al actualizar puntuación', 'error');
    }
}

// ====================================================================
// REPORTE PDF
// ====================================================================

function descargarReporte() {
    window.location.href = '/proveedores/reporte';
}
