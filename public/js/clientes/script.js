
// ====================================================================
// DATOS GLOBALES
// ====================================================================

/**
 * Variables globales
 */

let listaClientes = [];
let activeCustomerTab = 'clientes';
let tableLoading = false;
let tableHasMore = true;
let tableRequestId = 0;
let customerSearchTimer;


/**
 * Iniciador del sistema
 */
window.addEventListener('DOMContentLoaded', function () {
    cargarCartas();

    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.addEventListener('scroll', function () {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 24) {
                loadActiveTable();
            }
        });
    }

    document.querySelectorAll('[data-customer-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            selectCustomerTab(button.dataset.customerTab);
        });
    });

    const inputSearch = document.getElementById('search-input-table');
    if (inputSearch) {
        inputSearch.addEventListener('input', function () {
            clearTimeout(customerSearchTimer);
            customerSearchTimer = setTimeout(resetAndLoadActiveTable, 300);
        });
    }

    document.querySelectorAll('[data-close-customer-orders]').forEach(function (button) {
        button.addEventListener('click', closeCustomerOrdersModal);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeCustomerOrdersModal();
    });

    let starsContainer = document.querySelector('.starts-content');
    if (starsContainer) {
        starsContainer.addEventListener('click', function (event) {
            const rect = this.getBoundingClientRect();
            const clickX = event.clientX - rect.left;
            const porcentaje = Math.min(100, Math.max(0, Math.round((clickX / rect.width) * 100)));
            setAssessment(Math.round(porcentaje));
        });
    }

    updateCustomerTableHeadings();
    loadActiveTable();
});

function selectCustomerTab(tabName) {
    activeCustomerTab = tabName;
    document.querySelectorAll('[data-customer-tab]').forEach(function (button) {
        const isActive = button.dataset.customerTab === tabName;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-selected', String(isActive));
    });

    const activeButton = document.querySelector('[data-customer-tab="' + tabName + '"]');
    const tablePanel = document.getElementById('customer-table-panel');
    if (tablePanel && activeButton) tablePanel.setAttribute('aria-labelledby', activeButton.id);

    const inputSearch = document.getElementById('search-input-table');
    if (inputSearch) {
        inputSearch.value = '';
        inputSearch.placeholder = tabName === 'pedidos' ? 'Buscar pedido o cliente...' : 'Buscar cliente...';
    }

    updateCustomerTableHeadings();
    resetAndLoadActiveTable();
}

function updateCustomerTableHeadings() {
    const tableHead = document.getElementById('customer-table-head');
    if (!tableHead) return;

    const headings = {
        clientes: ['Nombre', 'Teléfono', 'Acciones'],
        pedidos: ['Cliente', 'Pedidos', 'Último pedido', 'Total acumulado', 'Deuda pendiente', 'Acciones'],
    };

    tableHead.innerHTML = '<tr>' + headings[activeCustomerTab]
        .map(function (heading) { return '<th scope="col">' + heading + '</th>'; })
        .join('') + '</tr>';
}

function resetAndLoadActiveTable() {
    tableRequestId += 1;
    listaClientes = [];
    tableHasMore = true;
    tableLoading = false;

    const bodyTable = document.getElementById('body-table');
    const tableContainer = document.querySelector('.table-container');
    if (bodyTable) bodyTable.replaceChildren();
    if (tableContainer) tableContainer.scrollTop = 0;

    loadActiveTable(tableRequestId);
}

// ====================================================================
// CONSTRUCCION DE LA FUNCION DE CONEXION
// ====================================================================

/**
 * funcion de conexion centralizada
 */

async function httpRequest(url = "", method = 'get', body = new FormData()) {
    try {
        let response;
        if (method !== "get") {
            response = await fetch(url, {
                body: body,
                method: method,
                headers: { 'X-CSRF-TOKEN': csrf_token }
            });
        } else {
            response = await fetch(url, {
                method: method,
                headers: { 'X-CSRF-TOKEN': csrf_token }
            });
        }
        return response.json();
    } catch (error) {
        console.log(error);
        return { success: false, message: error };
    }
}

// ====================================================================
// CONSTRUCCION DE INICIADORES DE DATOS
// ====================================================================

/**
 * iniciador de cartas
 */

function cargarCartas() {
    obtenerClienteMasActivo();
    obtenerProductoEnTendencia();
    obtenerClienteMenosActivo();
}


// ====================================================================
// CONSTRUCCION DE OBTENCION DEL CLIENTE MAS ACTIVO
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function obtenerClienteMasActivo() {
    try {
        let url = '/clienteManager/mas-activo';
        let respuesta = await httpRequest(url);
        if (respuesta.success) {
            if (respuesta.response) {
                cargarClienteMasActivo(respuesta.response.nombre);
            } else {
                cargarClienteMasActivo("No hay datos");
            }
        } else {
            showtoast('Error al cargar el cliente mas activo: ' + respuesta.message, 'error');
        }
    } catch (error) {
        console.log(error);
        showToast('Error al cargar el cliente mas activo', 'error');
    }
}

/**
 * funcion que carga el cliente mas activo
 */

function cargarClienteMasActivo(cliente = "") {
    let carMasActivo = document.getElementById('cliente-mas-activo');
    if (carMasActivo) {
        carMasActivo.textContent = cliente;
    }
}

// ====================================================================
// CONSTRUCCION DE OBTENCION DE PRODUCTO EN TENDENCIA
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function obtenerProductoEnTendencia() {
    try {
        let url = "/clienteManager/tendencia";
        let response = await httpRequest(url);
        if (response.success) {
            if (response.response) {
                cargarProductoEnTendencia(response.response.nombre);
            } else {
                cargarProductoEnTendencia('No hay datos');
            }
        } else {
            showToast('Error al obtener producto en tendencia: ' + response.message, 'error');
        }
    } catch (error) {
        console.log(error);
        showToast('Error al obtener producto en tendencia.', 'error');
    }
}

/**
 * funcion que carga la data
 */

function cargarProductoEnTendencia(producto = "") {
    let carTendencia = document.getElementById('producto-en-tendencia');
    if (carTendencia) {
        carTendencia.textContent = producto;
    }
}

// ====================================================================
// CONSTRUCCION DE OBTENCION DE PRODUCTO EN TENDENCIA
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function obtenerClienteMenosActivo() {
    try {
        let url = "/clienteManager/menos-activo";
        let response = await httpRequest(url);
        if (response.success) {
            if (response.response) {
                cargarClienteMenosActivo(response.response.nombre);
            } else {
                cargarClienteMenosActivo('No hay datos');
            }
        } else {
            showToast('Error al cargar cliente menos activo: ' + response.message, 'error');
        }
    } catch (error) {
        console.log(error);
        showToast('Error al cargar cliente menos activo', 'error');
    }
}

/**
 * funcion que carga la data
 */

function cargarClienteMenosActivo(cliente = "") {
    let carMenosActivo = document.getElementById('cliente-menos-activo');
    if (carMenosActivo) {
        carMenosActivo.textContent = cliente;
    }
}

// ====================================================================
// CONSTRUCCION DE OBTENCION DE clientes
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function loadActiveTable(requestId = tableRequestId) {
    if (tableLoading || !tableHasMore) return;

    const requestTab = activeCustomerTab;
    const search = document.getElementById('search-input-table')?.value.trim() || '';
    const pageSize = requestTab === 'pedidos' ? 10 : 6;
    const form = new FormData();
    form.append('limit', pageSize);
    form.append('skip', listaClientes.length);
    if (search) form.append('search', search);

    let url = '/clienteManager/lita-clientes';
    if (requestTab === 'clientes' && search) url = '/clienteManager/search-cliente';
    if (requestTab === 'pedidos') url = '/api/pedidos/getPedidosPagination';

    tableLoading = true;
    loadModeTable('load');

    try {
        const response = await httpRequest(url, 'post', form);
        if (requestId !== tableRequestId || requestTab !== activeCustomerTab) return;
        if (!response.success) throw new Error(response.message || 'No se pudieron cargar los datos.');

        const records = requestTab === 'pedidos' ? (response.pedidos || []) : (response.response || []);
        listaClientes.push(...records);
        tableHasMore = records.length === pageSize;
        renderTabla();
    } catch (error) {
        if (requestId === tableRequestId) {
            console.error(error);
            showToast(error.message || 'Error al cargar la lista.', 'error');
            renderTabla();
        }
    } finally {
        if (requestId === tableRequestId) {
            tableLoading = false;
            loadModeTable('rest');
        }
    }
}

/**
 * funcion que reinicia la tabla
 */

function resetTabla() {
    let bodyTable = document.getElementById('body-table');
    let tableContainer = document.querySelector('.table-container');
    if (bodyTable && tableContainer) {
        bodyTable.innerHTML = "";
        tableContainer.scrollTop = 0;
    }
}

/**
 * funcion que otorga una animacion de espera a la tabla
 */

function loadModeTable(status = "load") {//estados posibles load y rest
    let loadObject = document.getElementById('loading-table');
    let bodyTable = document.getElementById('body-table');
    switch (status) {
        case "load":
            if (bodyTable && !loadObject) {
                let row = document.createElement('tr');
                row.setAttribute('id', 'loading-table');
                row.innerHTML = `<td colspan="${document.querySelectorAll('#customer-table-head th').length}">
                                    <div class="loading-table-container">
                                        <div class="loading-table"></div>
                                    </div>
                                </td>`;
                bodyTable.appendChild(row);
            }
            break;
        case "rest":
            if (loadObject) {
                loadObject.remove();
            }
            break;

    }
}

/**
 * funcion que carga los nuevos datos obtenidos
 */

function renderTabla() {
    loadModeTable('rest');
    const bodyTable = document.getElementById('body-table');
    if (!bodyTable) return;

    bodyTable.replaceChildren();
    if (listaClientes.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = document.querySelectorAll('#customer-table-head th').length;
        cell.className = 'customer-empty-state';
        cell.textContent = activeCustomerTab === 'pedidos'
            ? 'No hay pedidos registrados.'
            : 'No hay clientes registrados.';
        row.appendChild(cell);
        bodyTable.appendChild(row);
        return;
    }

    if (activeCustomerTab === 'pedidos') {
        groupOrdersByCustomer(listaClientes).forEach(function (group) {
            bodyTable.appendChild(createOrderRow(group));
        });
        return;
    }

    listaClientes.forEach(function (record) {
        bodyTable.appendChild(createCustomerRow(record));
    });
}

function createCustomerRow(customer) {
    const row = document.createElement('tr');
    const fullName = [customer.nombre, customer.apellido].filter(Boolean).join(' ');
    row.appendChild(createTextCell(fullName));
    row.appendChild(createTextCell(customer.telefono || '—'));

    const actionsCell = document.createElement('td');
    const actions = document.createElement('div');
    actions.className = 'Actions-container';
    actions.appendChild(createCustomerAction('Evaluar cliente', '/assets/global/icon-assessment.svg', function () {
        abrirModalAssesment(customer.idcliente);
    }));
    actions.appendChild(createCustomerAction('Ver dirección', '/assets/global/icon-user.svg', function () {
        modalAddressShow(customer.idcliente);
    }));
    actionsCell.appendChild(actions);
    row.appendChild(actionsCell);
    return row;
}

function groupOrdersByCustomer(orders) {
    const groups = new Map();
    orders.forEach(function (order) {
        const customer = order.cliente || {};
        const customerId = order.cliente_id || customer.idcliente || 'pedido-' + order.idpedido;
        if (!groups.has(String(customerId))) {
            groups.set(String(customerId), {
                customerId: customer.idcliente || order.cliente_id,
                customer: customer,
                orders: [],
            });
        }
        groups.get(String(customerId)).orders.push(order);
    });
    return Array.from(groups.values());
}

function createOrderRow(group) {
    const row = document.createElement('tr');
    const customerName = [group.customer.nombre, group.customer.apellido].filter(Boolean).join(' ') || 'Cliente';
    const orderTotals = group.orders.reduce(function (totals, order) {
        const amounts = getOrderTotals(order);
        totals.total += amounts.total;
        totals.debt += amounts.debt;
        return totals;
    }, { total: 0, debt: 0 });
    const latestOrder = group.orders[0];

    row.appendChild(createTextCell(customerName));
    row.appendChild(createBadgeCell(group.orders.length, 'badge-ok'));
    row.appendChild(createTextCell(formatTableDate(latestOrder.created_at)));
    row.appendChild(createTextCell(formatCurrency(orderTotals.total)));
    row.appendChild(createTextCell(formatCurrency(orderTotals.debt)));

    const actionsCell = document.createElement('td');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'customer-orders-open-button';
    button.textContent = 'Ver pedidos';
    button.addEventListener('click', function () {
        openCustomerOrders(group.customerId, customerName);
    });
    actionsCell.appendChild(button);
    row.appendChild(actionsCell);

    return row;
}

function getOrderTotals(order) {
    const details = order.detalles || order.detalleproductos || [];
    const paymentPending = (order.estado_pago || 'pendiente').toLowerCase() === 'pendiente';
    return details.reduce(function (totals, detail) {
        const subtotal = Number(detail.subtotal) || 0;
        totals.total += subtotal;
        if (paymentPending || (detail.venta && !detail.venta.fechapago)) totals.debt += subtotal;
        return totals;
    }, { total: 0, debt: 0 });
}

async function openCustomerOrders(customerId, customerName) {
    const modal = document.getElementById('customer-orders-modal');
    const title = document.getElementById('customer-orders-title');
    const summary = document.getElementById('customer-orders-summary');
    const body = document.getElementById('customer-orders-body');
    if (!modal || !body) return;

    if (title) title.textContent = 'Pedidos de ' + customerName;
    if (summary) summary.textContent = 'Cargando pedidos...';
    body.replaceChildren();
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    try {
        const orders = [];
        const pageSize = 50;
        let skip = 0;
        let total = Infinity;

        while (skip < total) {
            const form = new FormData();
            form.append('cliente_id', customerId);
            form.append('limit', pageSize);
            form.append('skip', skip);

            const response = await httpRequest('/api/pedidos/getPedidosPagination', 'post', form);
            if (!response.success) throw new Error(response.message || 'No se pudieron cargar los pedidos.');

            const page = response.pedidos || [];
            orders.push(...page);
            skip += page.length;
            total = Number(response.total) || 0;
            if (page.length < pageSize) break;
        }

        if (summary) summary.textContent = orders.length + (orders.length === 1 ? ' pedido' : ' pedidos');
        renderCustomerOrders(orders);
    } catch (error) {
        if (summary) summary.textContent = 'No se pudieron cargar los pedidos.';
        console.error(error);
    }
}

function renderCustomerOrders(orders) {
    const body = document.getElementById('customer-orders-body');
    if (!body) return;
    body.replaceChildren();

    if (orders.length === 0) {
        const row = document.createElement('tr');
        const cell = createTextCell('No hay pedidos registrados para este cliente.');
        cell.colSpan = 6;
        row.appendChild(cell);
        body.appendChild(row);
        return;
    }

    orders.forEach(function (order) {
        const row = document.createElement('tr');
        const delivery = ['encargo', 'encargo_local'].includes(order.tipo)
            ? (order.tipo === 'encargo_local' ? 'Recogida: ' : 'Encargo: ') + formatTableDate(order.fecha_entrega)
            : 'Inmediato';
        const totals = getOrderTotals(order);
        [
            '#' + order.idpedido,
            formatTableDate(order.created_at),
            order.estado || 'Pendiente',
            delivery,
            formatCurrency(totals.total),
            formatCurrency(totals.debt),
        ].forEach(function (value) { row.appendChild(createTextCell(value)); });
        body.appendChild(row);
    });
}

function closeCustomerOrdersModal() {
    const modal = document.getElementById('customer-orders-modal');
    if (!modal || modal.getAttribute('aria-hidden') === 'true') return;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function createTextCell(value) {
    const cell = document.createElement('td');
    cell.textContent = String(value ?? '—');
    return cell;
}

function createBadgeCell(value, badgeClass) {
    const cell = document.createElement('td');
    const badge = document.createElement('span');
    badge.className = 'badge ' + badgeClass;
    badge.textContent = typeof value === 'number' ? value.toLocaleString() : String(value ?? '—');
    cell.appendChild(badge);
    return cell;
}

function createCustomerAction(label, iconPath, action) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'customer-row-action';
    button.title = label;
    button.setAttribute('aria-label', label);
    const icon = document.createElement('img');
    icon.src = iconPath;
    icon.alt = '';
    button.appendChild(icon);
    button.addEventListener('click', action);
    return button;
}

function formatCurrency(value) {
    return Number(value || 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
}

function formatTableDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('es-SV', { dateStyle: 'short' }).format(date);
}

/**
 * funcion que cambia el enfoque de carga de la tabla
 */


function switchCargarTabla() {
    tableContainer = document.querySelector('.table-container');
    switch (statusSwitchTable) {
        case "todo":
            obtenerClientes();
            break;
        case "compras":
            clientesPorCompras();
            break;
        case "pedidos":
            clientesConPedidos();
            break;
        case "search":
            obtencionDeClienteBuscado();
            break;
    }
}

// ====================================================================
// CONSTRUCCION DE OBTENCION DE CLIENTES POR COMPRAS
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function clientesPorCompras() {
    let tabla = document.querySelector('.table-clientes');
    if (tabla) {
        try {
            if (tabla.dataset.loadingElements == undefined) tabla.dataset.loadingElements = 'false';
            if (tabla.dataset.loadingElements == 'false') {
                let url = "/clienteManager/lita-cliente-compras";
                let form = new FormData();
                form.append('limit', 6);
                form.append('skip', listaClientes.length);
                loadModeTable('load');//inicia el modo de espera de la tabla
                tabla.dataset.loadingElements = 'true';
                let response = await httpRequest(url, 'post', form);
                if (response.success) {
                    response.response.forEach((data) => {
                        listaClientes.push(data);
                    });
                    renderTabla();
                } else {
                    showToast('Error al obtener los clientes: ' + response.message, 'error');
                    loadModeTable('rest');
                    console.error(response.message);
                }
                tabla.dataset.loadingElements = 'false';
            }
        } catch (error) {
            console.log(error);
            loadModeTable('rest');
            showToast('Error al obtener los clientes', 'error');
        }
    }
}

async function clientesConPedidos() {
    const tabla = document.querySelector('.table-clientes');
    if (!tabla) return;

    try {
        if (tabla.dataset.loadingElements === undefined) tabla.dataset.loadingElements = 'false';
        if (tabla.dataset.loadingElements !== 'false') return;

        const form = new FormData();
        form.append('limit', 6);
        form.append('skip', listaClientes.length);
        loadModeTable('load');
        tabla.dataset.loadingElements = 'true';

        const response = await httpRequest('/clienteManager/clientes-con-pedidos', 'post', form);
        if (response.success) {
            response.response.forEach(data => listaClientes.push(data));
            renderTabla();
        } else {
            showToast('Error al obtener clientes con pedidos: ' + response.message, 'error');
            loadModeTable('rest');
        }
    } catch (error) {
        console.error(error);
        loadModeTable('rest');
        showToast('Error al obtener clientes con pedidos', 'error');
    } finally {
        tabla.dataset.loadingElements = 'false';
    }
}


// ====================================================================
// CONSTRUCCION DE METODOS DE BUSQUEDA
// ====================================================================

/**
 * funcion que obtiene la data
 */

async function obtencionDeClienteBuscado() {
    let tabla = document.querySelector('.table-clientes');
    if (tabla) {
        try {
            if (tabla.dataset.loadingElements == undefined) tabla.dataset.loadingElements = 'false';
            if (tabla.dataset.loadingElements = 'false') {
                let inputSearch = document.getElementById('search-input-table').value;
                let url = "/clienteManager/search-cliente";
                let form = new FormData();
                form.append('limit', 6);
                form.append('skip', listaClientes.length);
                form.append('search', inputSearch);
                loadModeTable('load');//inicia el modo de espera de la tabla
                tabla.dataset.loadingElements = 'true';
                let response = await httpRequest(url, 'post', form);
                if (response.success) {
                    response.response.forEach((data) => {
                        listaClientes.push(data);
                    });
                    renderTabla();
                } else {
                    showToast('Error al obtener los clientes: ' + response.message, 'error');
                    loadModeTable('rest');
                    console.error(response.message);
                }
                searchStatus = false;
                tabla.dataset.loadingElements = 'false';
            }
        } catch (error) {
            console.log(error);
            loadModeTable('rest');
            searchStatus = false;
            showToast('Error al obtener los clientes', 'error');
        }
    }
}

// ====================================================================
// CONSTRUCCION DE CONSULTA DE DIRECCION Y FOTO DE PERFIL
// ====================================================================

/**
 * Obtencion de datos
 */

function setStatusLoadingModal(status = "load") {//posibles estados son load y rest
    let loadingObject = document.getElementById('modal-loading');
    if (loadingObject) {
        switch (status) {
            case "load":
                modalManager.showModalId('modal-loading');
                break;
            case "rest":
                modalManager.hiddenModalId('modal-loading');
                break;
        }
    }
}

// ====================================================================
// CONSTRUCCION DE CONSULTA DE DIRECCION Y FOTO DE PERFIL
// ====================================================================

/**
 * Obtencion de datos
 */

async function obtenerAddressAndProfile(idcliente = 1) {
    try {
        let url = "/clienteManager/address-and-profile";
        let form = new FormData();
        form.append("idcliente", idcliente);
        setStatusLoadingModal('load');
        let response = await httpRequest(url, "post", form);
        if (response.success) {
            renderAddressAndProfile(response.response);
        } else {
            showToast('Error al obtener el address: ' + response.message, 'error');
            console.error(response.message);
        }
        setStatusLoadingModal('rest');
    } catch (error) {
        setStatusLoadingModal('rest');
        console.log(error);
        showToast('Error al obtener el address', 'error');
    }
}

/**
 * Renderizado de datos
 */

function renderAddressAndProfile(address = { 'email': "", "foto": "#" }) {
    let addressObject = document.getElementById('modal-address-address');
    let imgObject = document.getElementById('modal-address-img');
    if (addressObject && imgObject) {
        addressObject.textContent = address.email;
        imgObject.setAttribute("src", address.foto ? address.foto : "/assets/global/profile-picture.png");
    }
}

// ====================================================================
// CONSTRUCCION CARGA DE MODAL ADDRESS AUTONOMA
// ====================================================================

/**
 * Cargador de modal
 */

function modalAddressShow(id = 1) {
    modalManager.showModalId('modal-address');
    obtenerAddressAndProfile(id);
}

// ====================================================================
// MANIPULACION DE ESTRALLAS
// ====================================================================

/**
 * Funcion de manejo de estrellas
 */

function setAssessment(assessment = 20) {
    let stuffed = document.querySelector('.stuffed');
    if (stuffed) {
        stuffed.style.width = assessment + "%";
    }
}

/**
 * Obtener valor de estrellas
 */

function getAssessment() {
    let stuffed = document.querySelector('.stuffed');
    if (stuffed) {
        let valueString = stuffed.style.width.replace('%', '');
        let assessmentValue = parseInt(valueString !== '' ? valueString : 0);
        if (valueString === '') stuffed.style.width = "0%";
        return assessmentValue;
    } else {
        return 0;
    }
}

// ====================================================================
// CONSTRUCCION CARGA DE MODAL ASSESSMENT
// ====================================================================

/**
 * Cargador de modal
 */

async function obtenerAssessment(idcliente = 1) {
    try {
        let url = "http://panaderiasys.test/clienteManager/assessment";
        let form = new FormData();
        form.append('idcliente', idcliente);
        setStatusLoadingModal('load');
        let response = await httpRequest(url, "post", form);
        if (response.success) {
            renderizarAssessment(response.response);
        } else {
            showToast('Error al obtener las evaluaciones: ' + response.message, 'erro');
        }
        setStatusLoadingModal('rest');
    } catch (error) {
        console.error(error);
        setStatusLoadingModal('rest');
        showToast('Error al obtener las evaluaciones.', 'error');
    }
}

/**
 * Renderizar los datos
 */

function renderizarAssessment(assessment = { "assessment": 20, "comment": "", "nombre": "" }) {
    console.log('datos renderizados');
    let commentObject = document.getElementById('text-comment');
    let titleAssessment = document.getElementById('assessment-title');
    if (commentObject && titleAssessment) {
        setAssessment(assessment.assessment);
        commentObject.value = assessment.comment;
        titleAssessment.textContent = "Cliente: " + assessment.nombre;
    }
}

// ====================================================================
// EVENTO DE CARGAR ASSESMENT
// ====================================================================

/**
 * Evento de abrir modal assessment
 */

function abrirModalAssesment(idCliente = 1) {
    modalManager.showModalId('modal-assessment');
    obtenerAssessment(idCliente);
    let buttonAssesst = document.querySelector('.modal-cerrar');
    if (buttonAssesst) {
        buttonAssesst.dataset.idcliente = idCliente;
    }
}

/**
 * Evento de cerrar modal assessment
 */

async function cerrarModalAssesment() {
    try {
        let url = "/clienteManager/update-assessment";
        let comment = document.getElementById('text-comment').value;
        let idcliente = document.querySelector('.modal-cerrar').dataset.idcliente;
        let assessment = getAssessment();
        let form = new FormData();
        form.append('comment', comment);
        form.append('assessment', assessment);
        form.append('idcliente', idcliente);
        setStatusLoadingModal('load');
        let response = await httpRequest(url, "post", form);
        if (response.success) {
            showToast('Evaluaciones almacenadas con exito', 'success');
        } else {
            showToast('Error al guardar las evaluaciones: ' + response.message, 'error');
        }
        setStatusLoadingModal('rest');
        modalManager.hiddenModalId('modal-assessment');
    } catch (error) {
        console.error(error);
        setStatusLoadingModal('rest');
        modalManager.hiddenModalId('modal-assessment');
        showToast('Error al guardar las evaluaciones', 'error');
    }
}