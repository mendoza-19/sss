/* ============================================================
 *  Gestion de Ingredientes - script.js
 *
 *  Endpoints:
 *    POST /api/ingredientes/getIngredientesPagination
 *    POST /api/ingredientes/searchIngrediente
 *    POST /api/ingredientes/crearIngrediente
 *    POST /api/ingredientes/actualizarIngrediente
 *    GET  /api/ingredientes/estadisticas?umbral=&dias=
 *    GET  /api/ingredientes/proximosVencer?dias=
 * ============================================================ */

"use strict";

var todosLosIngredientes = [];
var modoEdicion = false;
var ingredienteEditandoId = null;
var UMBRAL_STOCK_BAJO = 10;
var DIAS_PROXIMO_VENCER = 30;

document.addEventListener("DOMContentLoaded", function () {
    cargarIngredientes();
    cargarEstadisticas();
    bindEventos();
});

function bindEventos() {
    document.getElementById("btn-nuevo-ingrediente")?.addEventListener("click", abrirModalNuevo);
    document.getElementById("btn-save-ingrediente")?.addEventListener("click", guardarIngrediente);
    document.getElementById("btn-faltantes").addEventListener("click", abrirModalFaltantes);
    document.getElementById("btn-proximos-vencer").addEventListener("click", abrirModalProximosVencer);

    var searchInput = document.querySelector("#search-bar-ingrediente input");
    if (searchInput) {
        var debounceTimer;
        searchInput.addEventListener("input", function (e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () { buscarIngrediente(e.target.value.trim()); }, 350);
        });
    }
}

function cargarIngredientes() {
    postJson("/api/ingredientes/getIngredientesPagination", { skip: 0, limit: 100 })
        .then(function (data) {
            if (data.success) {
                todosLosIngredientes = data.ingredientes;
                renderTablaIngredientes(data.ingredientes);
            } else {
                renderTablaError(4, data.message || "Error al cargar.");
            }
        })
        .catch(function () { renderTablaError(4, "Error de conexion. Recargue la pagina."); });
}

function cargarEstadisticas() {
    getJson("/api/ingredientes/estadisticas?umbral=" + UMBRAL_STOCK_BAJO + "&dias=" + DIAS_PROXIMO_VENCER)
        .then(function (data) {
            if (data.success) {
                setStatCard("stat-total-ingredientes", data.total);
                setStatCard("stat-stock-bajo", data.stock_bajo);
                setStatCard("stat-proximos-vencer", data.proximos_vencer);
            }
        })
        .catch(function () { });
}

function buscarIngrediente(query) {
    if (query === "") { renderTablaIngredientes(todosLosIngredientes); return; }
    postJson("/api/ingredientes/searchIngrediente", { search: query })
        .then(function (data) { if (data.success) renderTablaIngredientes(data.ingredientes); })
        .catch(function () { });
}

function renderTablaIngredientes(ingredientes) {
    var tbody = document.querySelector(".table-ingredientes tbody");
    if (!tbody) return;
    var puedeAdministrar = document.querySelector(".table-ingredientes-container")?.dataset.canManage === "true";
    if (!ingredientes || ingredientes.length === 0) {
        tbody.innerHTML = "<tr><td colspan='4' style='text-align:center;color:#888;padding:20px;'>No hay ingredientes registrados.</td></tr>";
        return;
    }
    tbody.innerHTML = ingredientes.map(function (ing) {
        var stockNum = parseFloat(ing.stock);
        var stockClass = stockNum === 0 ? "cero" : (stockNum <= UMBRAL_STOCK_BAJO ? "bajo" : "ok");
        var unidadStock = obtenerUnidadStock(ing.medida);
        var desc = ing.descripcion ? escapeHtml(ing.descripcion) : "&mdash;";
        return "<tr>"
            + "<td><strong>" + escapeHtml(ing.nombre) + "</strong></td>"
            + "<td><span class='stock-badge " + stockClass + "'>" + formatearStock(ing.stock) + " " + unidadStock + "</span></td>"
            + "<td class='td-descripcion' title='" + escapeHtml(ing.descripcion || "") + "'>" + desc + "</td>"
            + "<td><div class='container-icon-table'>"
            + (puedeAdministrar ? "<div class='icon-container-table' title='Editar ingrediente' onclick='abrirModalEditar(" + ing.idingrediente + ")'>"
                + "<img src='/assets/global/editar_black_color.svg' alt='Editar'></div>" : "")
            + "</div></td>"
            + "</tr>";
    }).join("");
}

function obtenerUnidadStock(medida) {
    var unidad = (medida || "").trim().toLowerCase();

    if (["u", "un", "unidad", "unidades", "pieza", "piezas", "huevo", "huevos", "lata", "latas", "barra", "barras"].includes(unidad)) {
        return medida.trim();
    }

    if (["ml", "mililitro", "mililitros", "l", "litro", "litros"].includes(unidad)) {
        return medida.trim();
    }

    return "oz";
}

function formatearStock(stock) {
    var numero = Number(stock || 0);
    return Number.isInteger(numero) ? String(numero) : numero.toFixed(3).replace(/0+$/, "").replace(/\.$/, "");
}

function normalizarMedidaIngrediente(medida) {
    var unidad = (medida || "").trim().toLowerCase();
    var equivalencias = {
        gramo: "g",
        gramos: "g",
        kilogramo: "kg",
        kilogramos: "kg",
        libra: "lb",
        libras: "lb",
        onza: "oz",
        onzas: "oz",
        unidad: "u",
        unidades: "u",
        pieza: "u",
        piezas: "u",
        litro: "L",
        litros: "L",
        mililitro: "ml",
        mililitros: "ml",
    };

    return equivalencias[unidad] || medida;
}

function renderTablaError(colspan, msg) {
    var tbody = document.querySelector(".table-ingredientes tbody");
    if (tbody) {
        tbody.innerHTML = "<tr><td colspan='" + colspan + "' style='text-align:center;color:#c0392b;padding:20px;'>" + escapeHtml(msg) + "</td></tr>";
    }
}

function setStatCard(id, valor) {
    var el = document.getElementById(id);
    if (el) el.textContent = valor;
}

function abrirModalNuevo() {
    modoEdicion = false;
    ingredienteEditandoId = null;
    document.getElementById("modal-title-ingrediente").textContent = "Nuevo Ingrediente";
    document.getElementById("form-ingrediente").reset();
    document.getElementById("ingrediente-id").value = "";
    modalManager.showModalId("modal-new-ingrediente");
}

function abrirModalEditar(id) {
    var ing = todosLosIngredientes.find(function (i) { return i.idingrediente == id; });
    if (!ing) return;
    modoEdicion = true;
    ingredienteEditandoId = ing.idingrediente;
    document.getElementById("modal-title-ingrediente").textContent = "Editar Ingrediente";
    document.getElementById("ingrediente-id").value = ing.idingrediente;
    document.getElementById("ingrediente-nombre").value = ing.nombre;
    document.getElementById("ingrediente-medida").value = normalizarMedidaIngrediente(ing.medida);
    document.getElementById("ingrediente-stock").value = ing.stock;
    document.getElementById("ingrediente-descripcion").value = ing.descripcion || "";
    modalManager.showModalId("modal-new-ingrediente");
}

function guardarIngrediente() {
    var nombre = document.getElementById("ingrediente-nombre").value.trim();
    var stock = document.getElementById("ingrediente-stock").value;
    var descripcion = document.getElementById("ingrediente-descripcion").value.trim();
    var medida = document.getElementById("ingrediente-medida").value.trim();

    if (!nombre) { alert("El nombre del ingrediente es obligatorio."); return; }
    if (stock === "" || !Number.isFinite(parseFloat(stock)) || parseFloat(stock) < 0) { alert("El stock debe ser un numero mayor o igual a 0."); return; }
    if (!medida) { alert("La medida es obligatoria."); return; }

    var endpoint = modoEdicion ? "/api/ingredientes/actualizarIngrediente" : "/api/ingredientes/crearIngrediente";
    var payload = { nombre: nombre, stock: parseFloat(stock), descripcion: descripcion, medida: medida };
    if (modoEdicion) payload.idingrediente = ingredienteEditandoId;

    modalManager.showModalId("base-modal-loading");

    postJson(endpoint, payload)
        .then(function (data) {
            modalManager.hiddenModalId("base-modal-loading");
            if (data.success) {
                modalManager.hiddenModalId("modal-new-ingrediente");
                cargarIngredientes();
                cargarEstadisticas();
            } else {
                var errMsg = data.errors ? Object.values(data.errors).flat().join("\n") : (data.message || "Error al guardar.");
                alert(errMsg);
            }
        })
        .catch(function () {
            modalManager.hiddenModalId("base-modal-loading");
            alert("Error de conexion. Intente nuevamente.");
        });
}

function abrirModalFaltantes() {
    modalManager.showModalId("modal-faltantes");
    var tbody = document.getElementById("faltantes-tbody-placeholder").parentElement;
    setTbodyLoading(tbody, 3);
    setTimeout(function () {
        var faltantes = todosLosIngredientes.filter(function (i) { return parseFloat(i.stock) <= UMBRAL_STOCK_BAJO; });
        if (faltantes.length === 0) {
            tbody.innerHTML = "<tr><td colspan='3' style='text-align:center;color:#888;padding:18px;'>No hay ingredientes con stock bajo en este momento.</td></tr>";
            return;
        }
        tbody.innerHTML = faltantes.map(function (ing) {
            var agotado = parseFloat(ing.stock) === 0;
            var estadoClass = agotado ? "agotado" : "bajo";
            var etiqueta = agotado ? "Agotado" : "Stock bajo";
            return "<tr>"
                + "<td><strong>" + escapeHtml(ing.nombre) + "</strong></td>"
                + "<td>" + formatearStock(ing.stock) + " " + obtenerUnidadStock(ing.medida) + "</td>"
                + "<td><span class='estado-badge " + estadoClass + "'>" + etiqueta + "</span></td>"
                + "</tr>";
        }).join("");
    }, 250);
}

function abrirModalProximosVencer() {
    modalManager.showModalId("modal-proximos-vencer");
    var tbody = document.getElementById("proximos-tbody-placeholder").parentElement;
    setTbodyLoading(tbody, 4);

    getJson("/api/ingredientes/proximosVencer?dias=" + DIAS_PROXIMO_VENCER)
        .then(function (data) {
            if (!data.success) {
                tbody.innerHTML = "<tr><td colspan='4' style='text-align:center;color:#c0392b;padding:18px;'>" + escapeHtml(data.message || "Error al cargar datos.") + "</td></tr>";
                return;
            }
            setStatCard("stat-proximos-vencer", data.lotes.length);
            if (data.lotes.length === 0) {
                tbody.innerHTML = "<tr><td colspan='4' style='text-align:center;color:#888;padding:18px;'>No hay lotes proximos a vencer en los proximos " + DIAS_PROXIMO_VENCER + " dias.</td></tr>";
                return;
            }
            tbody.innerHTML = data.lotes.map(function (d) {
                return "<tr>"
                    + "<td><strong>" + escapeHtml(d.nombre_ingrediente || "&mdash;") + "</strong></td>"
                    + "<td>" + d.cantidad + " Onzas.</td>"
                    + "<td>$" + parseFloat(d.valorunitario).toFixed(2) + "</td>"
                    + "<td>" + formatFecha(d.fechavencimiento) + "</td>"
                    + "</tr>";
            }).join("");
        })
        .catch(function () {
            tbody.innerHTML = "<tr><td colspan='4' style='text-align:center;color:#c0392b;padding:18px;'>Error de conexion. Intente nuevamente.</td></tr>";
        });
}

function getCsrfToken() {
    return csrf_token;
}

function postJson(url, body) {
    return fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": getCsrfToken(), "Accept": "application/json" },
        body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
}

function getJson(url) {
    return fetch(url, {
        headers: { "Accept": "application/json", "X-CSRF-TOKEN": getCsrfToken() },
    }).then(function (r) { return r.json(); });
}

function setTbodyLoading(tbody, colspan) {
    tbody.innerHTML = "<tr><td colspan='" + colspan + "'><div class='load-animation-container'><div class='load-animation'></div></div></td></tr>";
}

function escapeHtml(str) {
    if (str == null) return "";
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function formatFecha(fechaStr) {
    if (!fechaStr) return "&mdash;";
    var d = new Date(fechaStr + "T00:00:00");
    return d.toLocaleDateString("es-SV", { day: "2-digit", month: "2-digit", year: "numeric" });
}