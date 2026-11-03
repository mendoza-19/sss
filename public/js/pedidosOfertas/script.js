/* ============================================================
 *  Gestión de Pedidos y Ofertas - script.js
 *  Panadería El Buen Sabor
 * ============================================================ */

"use strict";

var modalManager;
var clienteSeleccionadoId = null;
var todosLosProductos = [];
var todosLosClientes = [];
var todosLosRepartidores = [];
var detallesPedidos = {};
var esRepartidor = false;

document.addEventListener("DOMContentLoaded", function () {
    esRepartidor = document.getElementById("usuario-es-repartidor")?.dataset.value === "true";
    inicializarModales();
    cargarEstadisticas();
    cargarPedidos();
    cargarProductos();
    cargarClientes();
    bindEventos();
});

/* ------------------------------------------------------------
 *  INICIALIZACIÓN DE MODALES
 * ------------------------------------------------------------ */
function inicializarModales() {
    if (typeof ModalManager !== "undefined") {
        modalManager = new ModalManager();
        modalManager.addModal("modalOferta");
        modalManager.addModal("modal-nueva-oferta");
        modalManager.addModal("modal-nuevo-pedido");
        modalManager.addModal("modal-detalle-pedido");
    }
}

/* ------------------------------------------------------------
 *  EVENT LISTENERS
 * ------------------------------------------------------------ */
function bindEventos() {
    // Filtro de pedidos
    var filtroSelect = document.getElementById("filtro-dropdown-pedidos");
    if (filtroSelect) {
        filtroSelect.addEventListener("change", function () {
            cargarPedidos();
        });
    }

    // Buscador de pedidos
    var searchPedidosInput = document.getElementById("input-search-pedidos");
    if (searchPedidosInput) {
        var debounceTimer;
        searchPedidosInput.addEventListener("input", function (e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                cargarPedidos();
            }, 300);
        });
    }

    // Botón abrir lista de ofertas
    var btnListaOfertas = document.getElementById("btn-lista-ofertas");
    if (btnListaOfertas) {
        btnListaOfertas.addEventListener("click", function () {
            abrirModalOfertas();
        });
    }

    // Botón abrir nueva oferta desde lista
    var btnNuevaOferta = document.getElementById("btn-abrir-nueva-oferta");
    if (btnNuevaOferta) {
        btnNuevaOferta.addEventListener("click", function () {
            abrirModalNuevaOferta();
        });
    }

    // Botón guardar nueva oferta
    var btnGuardarOferta = document.getElementById("btn-guardar-oferta");
    if (btnGuardarOferta) {
        btnGuardarOferta.addEventListener("click", guardarNuevaOferta);
    }

    // Buscador de clientes en modal nueva oferta
    var searchClienteInput = document.getElementById("search-cliente-oferta");
    if (searchClienteInput) {
        var debounceClienteTimer;
        searchClienteInput.addEventListener("input", function (e) {
            clearTimeout(debounceClienteTimer);
            debounceClienteTimer = setTimeout(function () {
                cargarClientes(e.target.value.trim());
            }, 300);
        });
    }

    // Botón nuevo pedido
    var btnNuevoPedido = document.getElementById("btn-abrir-nuevo-pedido");
    if (btnNuevoPedido) {
        btnNuevoPedido.addEventListener("click", function () {
            abrirModalNuevoPedido();
        });
    }

    // Botón guardar nuevo pedido
    var btnGuardarPedido = document.getElementById("btn-guardar-pedido");
    if (btnGuardarPedido) {
        btnGuardarPedido.addEventListener("click", guardarNuevoPedido);
    }

}

/* ------------------------------------------------------------
 *  ESTADÍSTICAS SUPERIORES
 * ------------------------------------------------------------ */
function cargarEstadisticas() {
    getJson("/api/pedidos/estadisticas")
        .then(function (data) {
            if (data.success) {
                var elemPend = document.getElementById("pedidos-pendientes");
                var elemAtras = document.getElementById("pedidos-atrasados");
                var elemProc = document.getElementById("pedidos-procesados");

                if (elemPend) elemPend.textContent = data.pendientes + (data.pendientes === 1 ? " pedido" : " pedidos");
                if (elemAtras) elemAtras.textContent = data.atrasados + (data.atrasados === 1 ? " pedido" : " pedidos");
                if (elemProc) elemProc.textContent = data.procesados_hoy + (data.procesados_hoy === 1 ? " pedido" : " pedidos");
            }
        })
        .catch(function (err) {
            console.error("Error al cargar estadísticas:", err);
        });
}

/* ------------------------------------------------------------
 *  LISTADO Y GESTIÓN DE PEDIDOS
 * ------------------------------------------------------------ */
function cargarPedidos(mostrarLoader) {
    mostrarLoader = mostrarLoader !== false;
    var tbody = document.getElementById("pedidos-table-body");
    if (tbody && mostrarLoader) {
        tbody.innerHTML = '<tr><td colspan="' + (esRepartidor ? '7' : '8') + '" style="text-align:center;padding:24px;"><div class="loader"></div></td></tr>';
    }

    var filtro = document.getElementById("filtro-dropdown-pedidos")
        ? document.getElementById("filtro-dropdown-pedidos").value
        : (esRepartidor ? "mas-reciente" : "por-asignar");
    var search = document.getElementById("input-search-pedidos") ? document.getElementById("input-search-pedidos").value.trim() : "";

    var payload = {
        skip: 0,
        limit: 50,
        orden: (filtro === "mas-antiguo") ? "mas-antiguo" : "mas-reciente",
        estado: (filtro !== "mas-reciente" && filtro !== "mas-antiguo") ? filtro : "todos",
        search: search
    };

    postJson("/api/pedidos/getPedidosPagination", payload)
        .then(function (data) {
            if (data.success) {
                todosLosRepartidores = data.repartidores || [];
                renderTablaPedidos(data.pedidos);
            } else {
                renderErrorPedidos(data.message || "Error al cargar pedidos.");
            }
        })
        .catch(function () {
            renderErrorPedidos("Error de conexión al cargar pedidos.");
        });
}

function construirControlEstadoPedido(estado, idpedido) {
    var estadoNormalizado = (estado || "pendiente").toLowerCase();
    var badgeClass = "badge-pendiente";
    if (estadoNormalizado === "entregado" || estadoNormalizado === "completado") badgeClass = "badge-entregado";
    else if (estadoNormalizado === "atrasado" || estadoNormalizado === "cancelado") badgeClass = "badge-atrasado";
    else if (estadoNormalizado === "en camino" || estadoNormalizado === "en proceso" || estadoNormalizado === "procesado") badgeClass = "badge-encamino";

    if (!esRepartidor) {
        return '<span class="badge ' + badgeClass + '">' + capitalizar(estadoNormalizado === "procesado" ? "procesado" : estadoNormalizado) + '</span>';
    }

    if (estadoNormalizado === "pendiente") {
        return '<select class="select-estado-accion" aria-label="Avanzar estado del pedido ' + idpedido + '" onchange="cambiarEstadoPedido(' + idpedido + ', this.value)">'
            + '<option value="pendiente" selected>Pendiente</option>'
            + '<option value="en camino">En camino</option>'
            + '</select>';
    }
    if (estadoNormalizado === "en camino") {
        return '<select class="select-estado-accion" aria-label="Avanzar estado del pedido ' + idpedido + '" onchange="cambiarEstadoPedido(' + idpedido + ', this.value)">'
            + '<option value="en camino" selected>En camino</option>'
            + '<option value="entregado">Entregado</option>'
            + '<option value="pendiente">Cancelar</option>'
            + '</select>';
    }
    if (estadoNormalizado === "entregado") {
        return '<select class="select-estado-accion" aria-label="Estado entregado" disabled><option selected>Entregado</option></select>';
    }
    if (estadoNormalizado === "cancelado") {
        return '<select class="select-estado-accion" aria-label="Estado cancelado" disabled><option selected>Cancelado</option></select>';
    }

    return '<span class="badge ' + badgeClass + '">' + capitalizar(estadoNormalizado === "procesado" ? "procesado" : estadoNormalizado) + '</span>';
}

function construirControlPagoPedido(estadoPago, idpedido) {
    var estadoNormalizado = (estadoPago || 'pendiente').toLowerCase();
    var pagada = estadoNormalizado === 'pagada';
    if (!esRepartidor) {
        return '<span class="badge ' + (pagada ? 'badge-entregado' : 'badge-pendiente') + '">' + (pagada ? 'Pagada' : 'Pendiente') + '</span>';
    }

    return '<select class="select-pago-accion" aria-label="Estado de pago del pedido ' + idpedido + '" data-estado-actual="' + estadoNormalizado + '" onchange="cambiarEstadoPagoPedido(' + idpedido + ', this.value)">'
        + '<option value="pendiente"' + (pagada ? '' : ' selected') + '>Pendiente</option>'
        + '<option value="pagada"' + (pagada ? ' selected' : '') + '>Pagada</option>'
        + '</select>';
}

function renderTablaPedidos(pedidos) {
    var tbody = document.getElementById("pedidos-table-body");
    if (!tbody) return;
    if (!pedidos || pedidos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="' + (esRepartidor ? '7' : '8') + '" style="text-align:center;color:#888;padding:24px;font-size:14px;">No se encontraron pedidos registrados.</td></tr>';
        return;
    }

    detallesPedidos = {};
    tbody.innerHTML = pedidos.map(function (p) {
        var clienteNombre = p.cliente ? escapeHtml((p.cliente.nombre || "") + " " + (p.cliente.apellido || "")).trim() : "Cliente Desconocido";
        var clienteTel = p.cliente && p.cliente.telefono ? escapeHtml(p.cliente.telefono) : "&mdash;";
        var emision = p.created_at ? formatFecha(p.created_at) : "&mdash;";
        var tipoEntrega = ["encargo", "encargo_local"].indexOf(p.tipo) !== -1
            ? "<strong>" + (p.tipo === "encargo_local" ? "Encargo local" : "Encargo") + "</strong><br><small>" + (p.tipo === "encargo_local" ? "Recogida: " : "Entrega: ") + (p.fecha_entrega ? formatFecha(p.fecha_entrega) : "&mdash;") + "</small>"
            : "Pedido inmediato";
        var estado = (p.estado || "pendiente").toLowerCase();
        var estadoPago = (p.estado_pago || 'pendiente').toLowerCase();
        var repartidorId = p.repartidor ? String(p.repartidor.idempleado) : "";
        var repartidorBloqueado = ["en camino", "entregado", "procesado", "completado"].indexOf(estado) !== -1;
        detallesPedidos[p.idpedido] = {
            cliente: ((p.cliente && p.cliente.nombre) || "Cliente") + " " + ((p.cliente && p.cliente.apellido) || ""),
            direccion: p.direccion || "Sin dirección registrada",
            productos: (p.detalles || []).map(function (detalle) {
                return {
                    nombre: detalle.producto ? detalle.producto.nombre : "Producto",
                    cantidad: Number(detalle.cantidad) || 0
                };
            })
        };

        return '<tr data-idpedido="' + p.idpedido + '">'
            + '<td><strong>' + clienteNombre + '</strong></td>'
            + '<td>' + clienteTel + '</td>'
            + '<td><button type="button" class="btn-ver-detalle-pedido" onclick="abrirDetallePedido(' + p.idpedido + ')">Ver detalle</button></td>'
            + '<td>' + emision + '</td>'
            + '<td>' + tipoEntrega + '</td>'
            + '<td>' + construirControlEstadoPedido(estado, p.idpedido) + '</td>'
            + '<td>' + construirControlPagoPedido(estadoPago, p.idpedido) + '</td>'
            + (esRepartidor ? '' : '<td><select class="select-repartidor-pedido" aria-label="Asignar repartidor al pedido ' + p.idpedido + '" onchange="asignarRepartidorPedido(' + p.idpedido + ', this.value)"' + (repartidorBloqueado ? ' disabled title="El repartidor no puede cambiarse después de iniciar el reparto."' : '') + '>'
                    + '<option value="">Sin asignar</option>'
                    + todosLosRepartidores.map(function (repartidor) {
                        var nombre = escapeHtml(repartidor.nombreCompleto || "Repartidor");
                        return '<option value="' + repartidor.idempleado + '"' + (repartidorId === String(repartidor.idempleado) ? ' selected' : '') + '>' + nombre + '</option>';
                    }).join("")
                    + '</select></td>')
            + '</tr>';
    }).join("");
}

function renderErrorPedidos(msg) {
    var tbody = document.getElementById("pedidos-table-body");
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="' + (esRepartidor ? '7' : '8') + '" style="text-align:center;color:#dc2626;padding:24px;">' + escapeHtml(msg) + '</td></tr>';
    }
}

function abrirDetallePedido(idpedido) {
    var detalle = detallesPedidos[idpedido];
    var titulo = document.getElementById("detalle-pedido-title");
    var direccion = document.getElementById("detalle-pedido-address");
    var tbody = document.getElementById("detalle-pedido-products-body");
    if (!detalle || !tbody) return;

    if (titulo) titulo.textContent = "Pedido de " + detalle.cliente.trim();
    if (direccion) direccion.textContent = detalle.direccion;
    tbody.replaceChildren();
    detalle.productos.forEach(function (producto) {
        var row = document.createElement("tr");
        var nameCell = document.createElement("td");
        var quantityCell = document.createElement("td");
        nameCell.textContent = producto.nombre;
        quantityCell.textContent = producto.cantidad;
        row.append(nameCell, quantityCell);
        tbody.appendChild(row);
    });
    modalManager?.showModalId("modal-detalle-pedido");
}

function actualizarFilaPedidoSinRecargar(idpedido, nuevoEstado) {
    var fila = document.querySelector('tr[data-idpedido="' + idpedido + '"]');
    if (!fila) return;

    var celdaEstado = fila.children[5];
    if (celdaEstado) {
        celdaEstado.innerHTML = construirControlEstadoPedido(nuevoEstado, idpedido);
    }
}

function cambiarEstadoPedido(idpedido, nuevoEstado) {
    postJson("/api/pedidos/cambiarEstado", { idpedido: idpedido, estado: nuevoEstado })
        .then(function (data) {
            if (data.success) {
                actualizarFilaPedidoSinRecargar(idpedido, nuevoEstado);
            } else {
                mostrarNotificacion(data.message || "Error al actualizar estado", "error");
            }
        })
        .catch(function () {
            mostrarNotificacion("Error de conexión al cambiar el estado.", "error");
        });
}

function cambiarEstadoPagoPedido(idpedido, nuevoEstadoPago) {
    var fila = document.querySelector('tr[data-idpedido="' + idpedido + '"]');
    var control = fila?.querySelector('.select-pago-accion');
    var estadoAnterior = control?.dataset.estadoActual || 'pendiente';
    if (control) control.disabled = true;

    postJson('/api/pedidos/cambiarEstadoPago', { idpedido: idpedido, estado_pago: nuevoEstadoPago })
        .then(function (data) {
            if (data.success) {
                if (control) {
                    control.dataset.estadoActual = data.estado_pago || nuevoEstadoPago;
                    control.value = data.estado_pago || nuevoEstadoPago;
                }
            } else {
                if (control) control.value = estadoAnterior;
                mostrarNotificacion(data.message || 'No se pudo actualizar el pago del pedido.', 'error');
            }
        })
        .catch(function () {
            if (control) control.value = estadoAnterior;
            mostrarNotificacion('Error de conexión al actualizar el pago.', 'error');
        })
        .finally(function () {
            if (control) control.disabled = false;
        });
}

function asignarRepartidorPedido(idpedido, repartidorId) {
    postJson("/api/pedidos/asignarRepartidor", {
        idpedido: idpedido,
        repartidor_id: repartidorId || null
    })
        .then(function (data) {
            if (data.success) {
                var fila = document.querySelector('tr[data-idpedido="' + idpedido + '"]');
                if (fila && fila.children.length > 6) {
                    var selectRepartidor = fila.querySelector('.select-repartidor-pedido');
                    if (selectRepartidor) {
                        selectRepartidor.value = repartidorId || "";
                    }
                }
            } else {
                mostrarNotificacion(data.message || "No se pudo asignar el repartidor.", "error");
            }
        })
        .catch(function () {
            mostrarNotificacion("Error de conexión al asignar el repartidor.", "error");
        });
}

/* ------------------------------------------------------------
 *  MODAL Y GESTIÓN DE OFERTAS
 * ------------------------------------------------------------ */
function abrirModalOfertas() {
    if (modalManager) {
        modalManager.showModalId("modalOferta");
    }
    cargarOfertas();
}

function cargarOfertas() {
    var tbody = document.getElementById("ofertas-table-body");
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;"><div class="loader"></div></td></tr>';
    }

    getJson("/api/ofertas/list")
        .then(function (data) {
            if (data.success) {
                renderTablaOfertas(data.ofertas);
            } else {
                if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;padding:16px;">' + escapeHtml(data.message || "Error al cargar ofertas") + '</td></tr>';
            }
        })
        .catch(function () {
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#dc2626;padding:16px;">Error de conexión.</td></tr>';
        });
}

function renderTablaOfertas(ofertas) {
    var tbody = document.getElementById("ofertas-table-body");
    if (!tbody) return;

    if (!ofertas || ofertas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#888;padding:24px;">No hay ofertas registradas en este momento.</td></tr>';
        return;
    }

    tbody.innerHTML = ofertas.map(function (of) {
        var clienteNombre = of.cliente ? escapeHtml((of.cliente.nombre || "") + " " + (of.cliente.apellido || "")).trim() : "Cliente Desconocido";
        var prodNombre = of.producto_rel ? escapeHtml(of.producto_rel.nombre) : "Producto #" + of.producto;
        var fechainicio = of.fechainicio ? formatFecha(of.fechainicio) : "&mdash;";
        var fechafin = of.fechafin ? formatFecha(of.fechafin) : "&mdash;";
        var descuento = of.descuento ? (of.descuento + "%") : "$0.00";

        return '<tr>'
            + '<td><strong>' + clienteNombre + '</strong></td>'
            + '<td>' + fechainicio + '</td>'
            + '<td>' + fechafin + '</td>'
            + '<td><span class="badge-descuento">-' + descuento + '</span></td>'
            + '<td>' + prodNombre + '</td>'
            + '<td>'
            + '  <div class="opciones-table-container">'
            + '    <button type="button" class="btn-eliminar-oferta" title="Eliminar oferta" onclick="eliminarOferta(' + of.idoferta + ')">'
            + '      <img src="/assets/global/icon-borrar.svg" alt="Eliminar">'
            + '    </button>'
            + '  </div>'
            + '</td>'
            + '</tr>';
    }).join("");
}

function eliminarOferta(idoferta) {
    if (!confirm("¿Está seguro de que desea eliminar esta oferta?")) return;

    postJson("/api/ofertas/delete", { idoferta: idoferta })
        .then(function (data) {
            if (data.success) {
                mostrarNotificacion(data.message, "success");
                cargarOfertas();
                cargarClientes(); // Actualiza conteo de ofertas por cliente
            } else {
                mostrarNotificacion(data.message || "Error al eliminar la oferta", "error");
            }
        })
        .catch(function () {
            mostrarNotificacion("Error de conexión al eliminar la oferta.", "error");
        });
}

/* ------------------------------------------------------------
 *  MODAL NUEVA OFERTA
 * ------------------------------------------------------------ */
function abrirModalNuevaOferta() {
    clienteSeleccionadoId = null;

    // Resetear campos del formulario
    var inputInicio = document.getElementById("nueva-oferta-inicio");
    var inputFin = document.getElementById("nueva-oferta-fin");
    var inputDesc = document.getElementById("nueva-oferta-descuento");
    var selectProd = document.getElementById("nueva-oferta-producto");
    var infoCliente = document.getElementById("cliente-seleccionado-info");

    var fechaActual = new Date();
    var hoy = [fechaActual.getFullYear(), String(fechaActual.getMonth() + 1).padStart(2, "0"), String(fechaActual.getDate()).padStart(2, "0")].join("-");
    if (inputInicio) inputInicio.value = hoy;
    if (inputFin) inputFin.value = "";
    if (inputDesc) inputDesc.value = "";
    if (selectProd) selectProd.selectedIndex = 0;
    if (infoCliente) {
        infoCliente.textContent = "Ningún cliente seleccionado";
        infoCliente.className = "cliente-seleccionado-badge warning";
    }

    // Desmarcar filas
    var filas = document.querySelectorAll(".table-clientes tbody tr");
    filas.forEach(function (tr) { tr.classList.remove("selected"); });

    if (modalManager) {
        modalManager.showModalId("modal-nueva-oferta");
    }
}

function cargarProductos() {
    getJson("/api/productos/listForOferta")
        .then(function (data) {
            if (data.success) {
                todosLosProductos = data.productos;
                poblarSelectProductos(data.productos);
            }
        })
        .catch(function (err) {
            console.error("Error al cargar productos:", err);
        });
}

function poblarSelectProductos(productos) {
    var selectOferta = document.getElementById("nueva-oferta-producto");
    if (selectOferta) {
        selectOferta.innerHTML = '<option value="">-- Seleccionar producto --</option>' + productos.map(function (prod) {
            var precio = prod.precio ? (" ($" + parseFloat(prod.precio).toFixed(2) + ")") : "";
            return '<option value="' + prod.idproducto + '">' + escapeHtml(prod.nombre) + precio + '</option>';
        }).join("");
    }

}

function cargarClientes(searchQuery) {
    searchQuery = searchQuery || "";
    var url = "/api/clientes/listForOferta" + (searchQuery ? ("?search=" + encodeURIComponent(searchQuery)) : "");

    getJson(url)
        .then(function (data) {
            if (data.success) {
                todosLosClientes = data.clientes;
                renderTablaClientesOferta(data.clientes);
                poblarSelectClientesPedido(data.clientes);
            }
        })
        .catch(function (err) {
            console.error("Error al cargar clientes:", err);
        });
}

function renderTablaClientesOferta(clientes) {
    var tbody = document.getElementById("clientes-oferta-table-body");
    if (!tbody) return;

    if (!clientes || clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888;padding:16px;">No se encontraron clientes.</td></tr>';
        return;
    }

    tbody.innerHTML = clientes.map(function (c) {
        var nombre = escapeHtml((c.nombre || "") + " " + (c.apellido || "")).trim();
        var email = c.user && c.user.email ? escapeHtml(c.user.email) : "Sin correo";
        var ofertasCount = c.ofertas_count !== undefined ? c.ofertas_count : 0;
        var isSelected = clienteSeleccionadoId === c.idcliente;

        return '<tr class="' + (isSelected ? "selected" : "") + '" onclick="seleccionarClienteParaOferta(' + c.idcliente + ', \'' + escapeHtml(nombre) + '\')">'
            + '  <td><input type="radio" name="cliente_radio" value="' + c.idcliente + '"' + (isSelected ? " checked" : "") + '></td>'
            + '  <td><strong>' + nombre + '</strong></td>'
            + '  <td>' + email + '</td>'
            + '  <td><span class="badge-ofertas-count">' + ofertasCount + '</span></td>'
            + '</tr>';
    }).join("");
}

function seleccionarClienteParaOferta(idcliente, nombreCliente) {
    clienteSeleccionadoId = idcliente;

    var infoCliente = document.getElementById("cliente-seleccionado-info");
    if (infoCliente) {
        infoCliente.textContent = "Cliente: " + nombreCliente;
        infoCliente.className = "cliente-seleccionado-badge ok";
    }

    // Actualizar visual de filas
    var radios = document.querySelectorAll('input[name="cliente_radio"]');
    radios.forEach(function (r) {
        if (parseInt(r.value, 10) === idcliente) {
            r.checked = true;
            r.closest("tr").classList.add("selected");
        } else {
            r.checked = false;
            r.closest("tr").classList.remove("selected");
        }
    });
}

function guardarNuevaOferta() {
    var fechainicio = document.getElementById("nueva-oferta-inicio") ? document.getElementById("nueva-oferta-inicio").value : "";
    var fechafin = document.getElementById("nueva-oferta-fin") ? document.getElementById("nueva-oferta-fin").value : "";
    var descuento = document.getElementById("nueva-oferta-descuento") ? document.getElementById("nueva-oferta-descuento").value : "";
    var producto = document.getElementById("nueva-oferta-producto") ? document.getElementById("nueva-oferta-producto").value : "";

    // Validaciones
    if (!fechainicio) {
        mostrarNotificacion("La fecha de inicio es requerida.", "warning");
        return;
    }
    if (!fechafin) {
        mostrarNotificacion("La fecha de finalización es requerida.", "warning");
        return;
    }
    if (fechafin < fechainicio) {
        mostrarNotificacion("La fecha de fin no puede ser anterior a la de inicio.", "warning");
        return;
    }
    if (!descuento || parseFloat(descuento) <= 0) {
        mostrarNotificacion("Ingrese un monto de descuento válido (mayor a 0).", "warning");
        return;
    }
    if (!producto) {
        mostrarNotificacion("Debe seleccionar un producto para la oferta.", "warning");
        return;
    }
    if (!clienteSeleccionadoId) {
        mostrarNotificacion("Por favor, seleccione un cliente de la tabla.", "warning");
        return;
    }

    var payload = {
        fechainicio: fechainicio,
        fechafin: fechafin,
        descuento: parseInt(descuento, 10),
        producto: parseInt(producto, 10),
        cliente_id: clienteSeleccionadoId
    };

    postJson("/api/ofertas/create", payload)
        .then(function (data) {
            if (data.success) {
                mostrarNotificacion(data.message, "success");
                if (modalManager) {
                    modalManager.hiddenModalId("modal-nueva-oferta");
                }
                cargarOfertas();
                cargarClientes();
            } else {
                mostrarNotificacion(data.message || "Error al registrar oferta", "error");
            }
        })
        .catch(function () {
            mostrarNotificacion("Error de conexión al guardar la oferta.", "error");
        });
}

/* ------------------------------------------------------------
 *  MODAL Y GESTIÓN DE NUEVO PEDIDO
 * ------------------------------------------------------------ */
function abrirModalNuevoPedido() {
    var selectCliente = document.getElementById("nuevo-pedido-cliente");
    var inputDireccion = document.getElementById("nuevo-pedido-direccion");
    var selectEstado = document.getElementById("nuevo-pedido-estado");

    if (selectCliente) selectCliente.selectedIndex = 0;
    if (inputDireccion) inputDireccion.value = "";
    if (selectEstado) selectEstado.value = "pendiente";

    if (modalManager) {
        modalManager.showModalId("modal-nuevo-pedido");
    }
}

function poblarSelectClientesPedido(clientes) {
    var select = document.getElementById("nuevo-pedido-cliente");
    if (!select) return;

    select.innerHTML = '<option value="">-- Seleccionar cliente --</option>' + clientes.map(function (c) {
        var nombre = escapeHtml((c.nombre || "") + " " + (c.apellido || "")).trim();
        return '<option value="' + c.idcliente + '">' + nombre + (c.telefono ? (" - " + escapeHtml(c.telefono)) : "") + '</option>';
    }).join("");
}

function guardarNuevoPedido() {
    var clienteId = document.getElementById("nuevo-pedido-cliente") ? document.getElementById("nuevo-pedido-cliente").value : "";
    var direccion = document.getElementById("nuevo-pedido-direccion") ? document.getElementById("nuevo-pedido-direccion").value.trim() : "";
    var estado = document.getElementById("nuevo-pedido-estado") ? document.getElementById("nuevo-pedido-estado").value : "pendiente";

    if (!clienteId) {
        mostrarNotificacion("Seleccione un cliente para el pedido.", "warning");
        return;
    }
    if (!direccion) {
        mostrarNotificacion("Ingrese la dirección de entrega del pedido.", "warning");
        return;
    }

    var payload = {
        cliente_id: parseInt(clienteId, 10),
        direccion: direccion,
        estado: estado
    };

    postJson("/api/pedidos/crear", payload)
        .then(function (data) {
            if (data.success) {
                mostrarNotificacion(data.message, "success");
                if (modalManager) {
                    modalManager.hiddenModalId("modal-nuevo-pedido");
                }
                cargarEstadisticas();
                cargarPedidos();
            } else {
                mostrarNotificacion(data.message || "Error al crear pedido", "error");
            }
        })
        .catch(function () {
            mostrarNotificacion("Error de conexión al registrar el pedido.", "error");
        });
}

/* ------------------------------------------------------------
 *  HELPERS (HTTP, NOTIFICACIONES, FORMATO)
 * ------------------------------------------------------------ */
function getCsrfToken() {
    return typeof csrf_token !== "undefined" ? csrf_token : "";
}

function postJson(url, body) {
    return fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
            "Accept": "application/json"
        },
        body: JSON.stringify(body)
    }).then(function (res) {
        return res.json();
    });
}

function getJson(url) {
    return fetch(url, {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "X-CSRF-TOKEN": getCsrfToken()
        }
    }).then(function (res) {
        return res.json();
    });
}

function mostrarNotificacion(mensaje, tipo) {
    if (typeof showToast === "function") {
        showToast(mensaje, tipo);
    } else {
        alert(mensaje);
    }
}

function escapeHtml(str) {
    if (str == null) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatFecha(fechaStr) {
    if (!fechaStr) return "&mdash;";
    try {
        var partes = /^(\d{4})-(\d{2})-(\d{2})$/.exec(fechaStr);
        var d = partes
            ? new Date(Number(partes[1]), Number(partes[2]) - 1, Number(partes[3]))
            : new Date(fechaStr);
        if (isNaN(d.getTime())) {
            d = new Date(fechaStr + "T00:00:00");
        }
        return d.toLocaleDateString("es-SV", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        });
    } catch (e) {
        return fechaStr;
    }
}

function capitalizar(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
}
