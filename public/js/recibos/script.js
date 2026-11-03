let chartInstance = null;
let facturasList = [];
let proveedoresList = [];
let ingredientesList = [];
let tempItemsDetalle = [];
let editMode = false;
let elementOnEdit = null;
let stopFetch = false;
let isFetching = false;
let searchMode = false;

let loadingElement = null;

function fechaLocalParaInput() {
    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');

    return `${hoy.getFullYear()}-${mes}-${dia}`;
}

function formatReceiptDate(value) {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : (value || '');
}

function formatReceiptQuantity(value) {
    const text = String(value ?? '').trim();
    if (!text) return '';

    const quantity = Number(text.replace(',', '.'));
    return Number.isFinite(quantity) ? quantity.toString() : text;
}

document.addEventListener('DOMContentLoaded', async function () {
    // Referencia al spinner de carga en la tabla
    const animContainer = document.querySelector('.table-facturas .load-animation-container');
    if (animContainer) {
        loadingElement = animContainer.closest('tr');
    }

    // Cargar estadísticas superiores (Recibos, Pagados, Pendientes)
    fetchEstadisticas();

    // Cargar listas de proveedores e ingredientes para los selectores
    await loadSelectOptions();

    // Cargar primer lote de facturas
    await nextBatchOfFacturas();

    // Infinite scroll en la tabla de facturas
    const tableContainer = document.querySelector('.table-facturas-container');
    if (tableContainer) {
        tableContainer.addEventListener('scroll', function () {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 20 && !stopFetch && !isFetching) {
                nextBatchOfFacturas();
            }
        });
    }

    // Buscador
    const searchBar = document.getElementById('search-bar-factura');
    if (searchBar) {
        const inputElem = searchBar.querySelector('input') || searchBar;
        inputElem.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        const btnSearch = searchBar.querySelector('button');
        if (btnSearch) {
            btnSearch.addEventListener('click', applyFilters);
        }
    }

    // Selects de filtro
    const filterProv = document.getElementById('filter-by-proveedor');
    if (filterProv) {
        filterProv.addEventListener('change', applyFilters);
    }

    const filterIng = document.getElementById('filter-by-ingrediente');
    if (filterIng) {
        filterIng.addEventListener('change', applyFilters);
    }

    // Botón Gráfica de gastos por mes
    const btnGrafica = document.getElementById('btn-grafica');
    if (btnGrafica) {
        btnGrafica.addEventListener('click', openModalGraphi);
    }

    // Botón Reporte
    const btnReporte = document.getElementById('btn-reporte');
    if (btnReporte) {
        btnReporte.addEventListener('click', descargarReporte);
    }

    // Botón Nueva Factura
    const btnNuevo = document.getElementById('btn-nuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', openModalCreateFactura);
    }

    // Botón Guardar en modal factura
    const btnSaveFactura = document.getElementById('btn-save-factura');
    if (btnSaveFactura) {
        btnSaveFactura.addEventListener('click', saveFacturaForm);
    }

    // Checkbox Pagado en formulario
    const chkPagado = document.getElementById('factura-pagado');
    const inputFechaPago = document.getElementById('factura-fechapago');
    if (chkPagado && inputFechaPago) {
        chkPagado.addEventListener('change', function () {
            if (this.checked) {
                inputFechaPago.disabled = false;
                if (!inputFechaPago.value) {
                    inputFechaPago.value = fechaLocalParaInput();
                }
            } else {
                inputFechaPago.disabled = true;
                inputFechaPago.value = '';
            }
        });
    }

    // Botón agregar ingrediente al detalle en el formulario
    const btnAddItem = document.getElementById('btn-add-detalle-item');
    if (btnAddItem) {
        btnAddItem.addEventListener('click', addItemToDetalle);
    }

    // Previsualización de imagen al seleccionarla
    const inputFoto = document.getElementById('factura-foto');
    if (inputFoto) {
        inputFoto.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photo-preview-img');
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function (ev) {
                    preview.src = ev.target.result;
                    preview.style.display = 'inline-block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
});

/**
 * Obtener estadísticas globales y actualizar tarjetas superiores
 */
async function fetchEstadisticas() {
    try {
        const response = await fetch('/api/facturas/estadisticas');
        const data = await response.json();
        if (data.success) {
            const elTotal = document.getElementById('stat-total-recibos');
            const elPagados = document.getElementById('stat-pagados-recibos');
            const elPendientes = document.getElementById('stat-pendientes-recibos');

            if (elTotal) elTotal.textContent = data.total;
            if (elPagados) elPagados.textContent = data.pagados;
            if (elPendientes) elPendientes.textContent = data.pendientes;
        }
    } catch (error) {
        console.error('Error al obtener estadísticas:', error);
    }
}

/**
 * Carga los selectores de proveedores e ingredientes
 */
async function loadSelectOptions() {
    try {
        // Cargar proveedores
        const resProv = await fetch('/api/proveedores/getProveedoresPagination', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf_token
            },
            body: JSON.stringify({ skip: 0, limit: 100 })
        });
        const dataProv = await resProv.json();
        if (dataProv.success && dataProv.proveedores) {
            proveedoresList = dataProv.proveedores;
            populateProveedoresSelects(proveedoresList);
        }

        // Cargar ingredientes
        const resIng = await fetch('/api/ingredientes/allIngredients');
        const dataIng = await resIng.json();
        if (dataIng.success && dataIng.ingredientes) {
            ingredientesList = dataIng.ingredientes;
            populateIngredientesSelects(ingredientesList);
        }
    } catch (error) {
        console.error('Error al cargar opciones de selectores:', error);
    }
}

function populateProveedoresSelects(proveedores) {
    const filterSelect = document.getElementById('filter-by-proveedor');
    const formSelect = document.getElementById('factura-proveedor');

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">Todos los Proveedores</option>';
        proveedores.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.idproveedor;
            opt.textContent = p.nombre;
            filterSelect.appendChild(opt);
        });
    }

    if (formSelect) {
        formSelect.innerHTML = '<option value="">Seleccione proveedor...</option>';
        proveedores.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.idproveedor;
            opt.textContent = p.nombre;
            formSelect.appendChild(opt);
        });
    }
}

function populateIngredientesSelects(ingredientes) {
    const filterSelect = document.getElementById('filter-by-ingrediente');
    const formItemSelect = document.getElementById('item-ingrediente');

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">Todos los Ingredientes</option>';
        ingredientes.forEach(ing => {
            const opt = document.createElement('option');
            opt.value = ing.idingrediente;
            opt.textContent = ing.nombre;
            filterSelect.appendChild(opt);
        });
    }

    if (formItemSelect) {
        formItemSelect.innerHTML = '<option value="">Seleccionar ingrediente...</option>';
        ingredientes.forEach(ing => {
            const opt = document.createElement('option');
            opt.value = ing.idingrediente;
            opt.textContent = `${ing.nombre} (${ing.medida || 'u'})`;
            formItemSelect.appendChild(opt);
        });
    }
}

/**
 * Carga el siguiente lote de facturas
 */
async function nextBatchOfFacturas() {
    if (stopFetch || isFetching) return;
    isFetching = true;

    const tbody = document.querySelector('.table-facturas tbody');
    if (loadingElement && !loadingElement.parentElement && tbody) {
        tbody.appendChild(loadingElement);
    }

    const searchInput = document.querySelector('#search-bar-factura input') || document.getElementById('search-bar-factura');
    const searchVal = searchInput ? searchInput.value.trim() : '';
    const provVal = document.getElementById('filter-by-proveedor')?.value || '';
    const ingVal = document.getElementById('filter-by-ingrediente')?.value || '';

    try {
        const formData = new FormData();
        formData.append('skip', facturasList.length);
        formData.append('limit', 8);
        formData.append('search', searchVal);
        formData.append('idproveedor', provVal);
        formData.append('idingrediente', ingVal);

        const response = await fetch('/api/facturas/getFacturasPagination', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf_token
            },
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            const facturas = data.facturas;
            if (facturas && facturas.length > 0) {
                facturas.forEach(factura => {
                    facturasList.push(factura);
                    const tr = createFacturaTableRow(factura);
                    if (loadingElement && loadingElement.parentElement) {
                        loadingElement.before(tr);
                    } else if (tbody) {
                        tbody.appendChild(tr);
                    }
                });

                if (facturas.length < 8) {
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
                if (facturasList.length === 0 && tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" style="padding: 20px; color: #777;">No se encontraron facturas ni recibos.</td></tr>';
                }
            }
        }
    } catch (error) {
        console.error('Error al cargar facturas:', error);
        showToast('Error al cargar facturas', 'error');
    } finally {
        isFetching = false;
    }
}

/**
 * Crea una fila HTML de la tabla para una factura
 */
function createFacturaTableRow(factura) {
    const tr = document.createElement('tr');
    tr.dataset.id = factura.idfactura;

    const nombreProveedor = factura.proveedors ? factura.proveedors.nombre : 'Proveedor Desconocido';
    const totalFormateado = `$ ${parseFloat(factura.total || 0).toFixed(2)}`;

    // Estado de pago
    let pagadoHtml = '';
    if (factura.fechapago) {
        pagadoHtml = `<span class="badge-pagado"><i class="fa-solid fa-check"></i> ${formatReceiptDate(factura.fechapago)}</span>`;
    } else {
        pagadoHtml = `<span class="badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>`;
    }

    // Foto del recibo físico
    let fotoHtml = '';
    if (factura.foto) {
        fotoHtml = `
            <div class="factura-photo-btn" onclick="showModalImageFactura('${factura.foto}')" title="Ver foto del recibo">
                <img src="/assets/global/icon-factura.svg" alt="Recibo">
            </div>
        `;
    } else {
        fotoHtml = `
            <div class="factura-photo-btn" onclick="showModalImageFactura('/assets/global/icon-factura.svg')" title="Sin foto física adjunta">
                <img src="/assets/global/icon-factura.svg" alt="Recibo">
            </div>
        `;
    }

    tr.innerHTML = `
        <td>${formatReceiptDate(factura.emicion)}</td>
        <td>${pagadoHtml}</td>
        <td><strong>${nombreProveedor}</strong></td>
        <td>${totalFormateado}</td>
        <td>${fotoHtml}</td>
        <td>
            <div class="container-icon-table">
                <div class="icon-container-table" onclick="openModalEditFactura(${factura.idfactura})" title="Editar recibo o confirmar pago">
                    <img src="/assets/global/editar_black_color.svg" alt="Editar">
                </div>
                <div class="icon-container-table" onclick="openModalDetalleFactura(${factura.idfactura})" title="Ver ingredientes comprados">
                    <img src="/assets/global/icon-factura.svg" alt="Detalle">
                </div>
            </div>
        </td>
    `;
    return tr;
}

/**
 * Aplicar filtros de búsqueda, proveedor o ingrediente
 */
function applyFilters() {
    facturasList = [];
    stopFetch = false;
    const tbody = document.querySelector('.table-facturas tbody');
    if (tbody) {
        tbody.innerHTML = '';
        if (loadingElement) {
            tbody.appendChild(loadingElement);
        }
    }
    nextBatchOfFacturas();
}

/**
 * Modal Crear Factura
 */
function openModalCreateFactura() {
    editMode = false;
    elementOnEdit = null;
    tempItemsDetalle = [];

    const form = document.getElementById('form-factura');
    if (form) form.reset();

    document.getElementById('modal-title-factura').textContent = 'Nueva Entrada de Factura / Recibo';
    document.getElementById('btn-save-factura').textContent = 'Guardar';
    document.getElementById('factura-emicion').value = fechaLocalParaInput();

    const chkPagado = document.getElementById('factura-pagado');
    const inputFechaPago = document.getElementById('factura-fechapago');
    if (chkPagado) chkPagado.checked = false;
    if (inputFechaPago) {
        inputFechaPago.disabled = true;
        inputFechaPago.value = '';
    }

    const preview = document.getElementById('photo-preview-img');
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }

    renderTempItemsTable();
    modalManager.showModalId('modal-new-factura');
}

/**
 * Modal Editar Factura (actualizar datos o confirmar pago)
 */
async function openModalEditFactura(idfactura) {
    editMode = true;
    elementOnEdit = idfactura;
    tempItemsDetalle = [];

    modalManager.showModalId('base-modal-loading');

    try {
        const response = await fetch(`/api/facturas/detalle/${idfactura}`);
        const data = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (data.success && data.factura) {
            const f = data.factura;
            document.getElementById('modal-title-factura').textContent = `Editar Recibo #${f.idfactura}`;
            document.getElementById('btn-save-factura').textContent = 'Actualizar';

            document.getElementById('factura-emicion').value = f.emicion || '';
            document.getElementById('factura-proveedor').value = f.proveedor_id || '';

            const chkPagado = document.getElementById('factura-pagado');
            const inputFechaPago = document.getElementById('factura-fechapago');
            if (f.fechapago) {
                chkPagado.checked = true;
                inputFechaPago.disabled = false;
                inputFechaPago.value = f.fechapago;
            } else {
                chkPagado.checked = false;
                inputFechaPago.disabled = true;
                inputFechaPago.value = '';
            }

            // Cargar preview de foto existente si tiene
            const preview = document.getElementById('photo-preview-img');
            if (f.foto && preview) {
                preview.src = f.foto;
                preview.style.display = 'inline-block';
            } else if (preview) {
                preview.style.display = 'none';
            }

            // Cargar detalles de ingredientes
            if (f.detallefacturas && f.detallefacturas.length > 0) {
                tempItemsDetalle = f.detallefacturas.map(item => ({
                    ingrediente_id: item.ingrediente_id,
                    ingrediente_nombre: item.ingredientes ? item.ingredientes.nombre : 'Ingrediente',
                    medida: item.ingredientes ? item.ingredientes.medida : 'u',
                    cantidad: item.cantidad,
                    valorunitario: item.valorunitario,
                    fechavencimiento: item.fechavencimiento || '',
                    subtotal: parseFloat(item.cantidad * item.valorunitario).toFixed(2)
                }));
            }

            renderTempItemsTable();
            modalManager.showModalId('modal-new-factura');
        } else {
            showToast('No se pudo cargar la información del recibo', 'error');
        }
    } catch (error) {
        modalManager.hiddenModalId('base-modal-loading');
        console.error('Error al abrir modal de edición:', error);
        showToast('Error al cargar recibo para edición', 'error');
    }
}

/**
 * Agrega un ingrediente temporal a la lista en el formulario
 */
function addItemToDetalle() {
    const selIng = document.getElementById('item-ingrediente');
    const inputCant = document.getElementById('item-cantidad');
    const inputVal = document.getElementById('item-valor');
    const inputVenc = document.getElementById('item-vencimiento');

    const idIng = selIng.value;
    const cant = parseFloat(inputCant.value);
    const val = parseFloat(inputVal.value);
    const venc = inputVenc.value;

    if (!idIng) {
        showToast('Seleccione un ingrediente', 'error');
        return;
    }
    if (isNaN(cant) || cant <= 0) {
        showToast('Ingrese una cantidad válida mayor a 0', 'error');
        return;
    }
    if (isNaN(val) || val < 0) {
        showToast('Ingrese un valor unitario válido', 'error');
        return;
    }

    const ingObj = ingredientesList.find(x => x.idingrediente == idIng);
    const nombreIng = ingObj ? ingObj.nombre : selIng.options[selIng.selectedIndex].text;
    const medida = ingObj ? ingObj.medida : 'u';
    const subtotal = (cant * val).toFixed(2);

    tempItemsDetalle.push({
        ingrediente_id: idIng,
        ingrediente_nombre: nombreIng,
        medida: medida,
        cantidad: cant,
        valorunitario: val,
        fechavencimiento: venc,
        subtotal: subtotal
    });

    // Limpiar inputs de la fila
    selIng.value = '';
    inputCant.value = '';
    inputVal.value = '';
    inputVenc.value = '';

    renderTempItemsTable();
}

/**
 * Renderiza la tabla temporal de items y calcula el total
 */
function renderTempItemsTable() {
    const tbody = document.getElementById('items-table-body');
    if (!tbody) return;

    tbody.innerHTML = '';
    let totalGeneral = 0;

    tempItemsDetalle.forEach((item, index) => {
        totalGeneral += parseFloat(item.subtotal || (item.cantidad * item.valorunitario));
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.ingrediente_nombre}</td>
            <td>${formatReceiptQuantity(item.cantidad)} ${item.medida || ''}</td>
            <td>$${parseFloat(item.valorunitario).toFixed(2)}</td>
            <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
            <td>${formatReceiptDate(item.fechavencimiento) || 'N/A'}</td>
            <td>
                <button type="button" class="btn-remove-item" onclick="removeItemFromDetalle(${index})" title="Eliminar item">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    if (tempItemsDetalle.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #888;">No hay ingredientes agregados todavía.</td></tr>';
    }

    const totalDisplay = document.getElementById('display-total-factura');
    if (totalDisplay) {
        totalDisplay.textContent = `$ ${totalGeneral.toFixed(2)}`;
    }
}

function removeItemFromDetalle(index) {
    tempItemsDetalle.splice(index, 1);
    renderTempItemsTable();
}

/**
 * Guardar o actualizar la factura
 */
async function saveFacturaForm() {
    const emicion = document.getElementById('factura-emicion').value;
    const proveedor_id = document.getElementById('factura-proveedor').value;
    const chkPagado = document.getElementById('factura-pagado').checked;
    const fechapago = chkPagado ? document.getElementById('factura-fechapago').value : '';
    const fileInput = document.getElementById('factura-foto');

    if (!emicion) {
        showToast('Debe ingresar la fecha de emisión', 'error');
        return;
    }
    if (!proveedor_id) {
        showToast('Debe seleccionar un proveedor', 'error');
        return;
    }

    modalManager.showModalId('base-modal-loading');

    const formData = new FormData();
    formData.append('emicion', emicion);
    formData.append('proveedor_id', proveedor_id);
    if (chkPagado && fechapago) {
        formData.append('fechapago', fechapago);
    }
    if (fileInput && fileInput.files[0]) {
        formData.append('foto', fileInput.files[0]);
    }

    // Calcular total acumulado de items
    let totalGeneral = 0;
    tempItemsDetalle.forEach(item => {
        totalGeneral += (parseFloat(item.cantidad) * parseFloat(item.valorunitario));
    });
    formData.append('total', totalGeneral.toFixed(2));
    formData.append('detalles', JSON.stringify(tempItemsDetalle));

    let url = '/api/facturas/create';
    if (editMode && elementOnEdit) {
        url = '/api/facturas/update';
        formData.append('idfactura', elementOnEdit);
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf_token
            },
            body: formData
        });

        const resData = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (resData.success) {
            showToast(resData.message, 'success');
            modalManager.hiddenModalId('modal-new-factura');

            // Actualizar lista y estadísticas
            fetchEstadisticas();
            applyFilters();
        } else {
            showToast(resData.message || 'Error al procesar la factura', 'error');
            if (resData.errors) {
                Object.values(resData.errors).forEach(errArray => {
                    errArray.forEach(msg => showToast(msg, 'error'));
                });
            }
        }
    } catch (error) {
        modalManager.hiddenModalId('base-modal-loading');
        console.error('Error al guardar factura:', error);
        showToast('Error al conectar con el servidor', 'error');
    }
}

/**
 * Mostrar foto física del recibo
 */
function showModalImageFactura(url) {
    if (!url) return;
    const modalImg = document.querySelector('.base-image-modal img');
    if (modalImg) {
        modalImg.src = url;
    }
    modalManager.showModalId('base-image-modal');
}

/**
 * Mostrar modal con ingredientes de una factura
 */
async function openModalDetalleFactura(idfactura) {
    modalManager.showModalId('base-modal-loading');

    try {
        const response = await fetch(`/api/facturas/detalle/${idfactura}`);
        const data = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (data.success && data.factura) {
            const f = data.factura;
            const containerInfo = document.getElementById('detalle-info-header');
            const tbody = document.getElementById('detalle-items-tbody');

            if (containerInfo) {
                const nombreProv = f.proveedors ? f.proveedors.nombre : 'Proveedor Desconocido';
                const estado = f.fechapago ? `Pagado (${formatReceiptDate(f.fechapago)})` : 'Pendiente';
                containerInfo.innerHTML = `
                    <div><span class="label">Factura N°:</span> <span class="val">#${f.idfactura}</span></div>
                    <div><span class="label">Proveedor:</span> <span class="val">${nombreProv}</span></div>
                    <div><span class="label">Emisión:</span> <span class="val">${formatReceiptDate(f.emicion)}</span></div>
                    <div><span class="label">Estado:</span> <span class="val">${estado}</span></div>
                    <div><span class="label">Total:</span> <span class="val" style="font-weight:bold; color:#8b5a2b;">$ ${parseFloat(f.total || 0).toFixed(2)}</span></div>
                `;
            }

            if (tbody) {
                tbody.innerHTML = '';
                if (f.detallefacturas && f.detallefacturas.length > 0) {
                    f.detallefacturas.forEach(det => {
                        const ingNombre = det.ingredientes ? det.ingredientes.nombre : 'Ingrediente';
                        const ingMedida = det.ingredientes ? det.ingredientes.medida : 'u';
                        const sub = (parseFloat(det.cantidad) * parseFloat(det.valorunitario)).toFixed(2);
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${ingNombre}</td>
                            <td>${formatReceiptQuantity(det.cantidad)} ${ingMedida}</td>
                            <td>$${parseFloat(det.valorunitario).toFixed(2)}</td>
                            <td>$${sub}</td>
                            <td>${formatReceiptDate(det.fechavencimiento) || 'N/A'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #888;">No se registraron ingredientes específicos en este recibo.</td></tr>';
                }
            }

            modalManager.showModalId('modal-detalle-factura');
        } else {
            showToast('No se encontró el detalle de la factura', 'error');
        }
    } catch (error) {
        modalManager.hiddenModalId('base-modal-loading');
        console.error('Error al cargar detalle de factura:', error);
        showToast('Error al obtener detalles', 'error');
    }
}

/**
 * Abrir modal de gráfica de gastos por mes
 */
async function openModalGraphi() {
    modalManager.showModalId('modal-graphi');
    modalManager.showModalId('base-modal-loading');

    try {
        const response = await fetch('/api/facturas/gastosMensuales');
        const data = await response.json();
        modalManager.hiddenModalId('base-modal-loading');

        if (data.success) {
            renderMonthlyChart(data.gastos, data.year);
        } else {
            showToast('No se pudieron obtener los datos de la gráfica', 'error');
        }
    } catch (error) {
        modalManager.hiddenModalId('base-modal-loading');
        console.error('Error al cargar gráfica:', error);
        showToast('Error al cargar gráfica', 'error');
    }
}

/**
 * Renderizar gráfico con Chart.js
 */
function renderMonthlyChart(gastosData, year) {
    const ctx = document.getElementById('graphi-gastos-mes');
    if (!ctx) return;

    const mesesLabels = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    const montos = new Array(12).fill(0);

    if (gastosData && gastosData.length > 0) {
        gastosData.forEach(g => {
            const index = parseInt(g.mes) - 1;
            if (index >= 0 && index < 12) {
                montos[index] = parseFloat(g.total_gastado || 0);
            }
        });
    }

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: mesesLabels,
            datasets: [{
                label: `Gastos en Compras ($) - ${year || new Date().getFullYear()}`,
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
                        callback: function (value) {
                            return '$' + value;
                        },
                        font: { family: 'Inter' },
                        color: '#6d4c41'
                    },
                    grid: {
                        color: 'rgba(215, 204, 200, 0.4)'
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Inter' },
                        color: '#6d4c41'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Descarga del reporte PDF
 */
function descargarReporte() {
    window.location.href = '/recibos/reporte';
}
