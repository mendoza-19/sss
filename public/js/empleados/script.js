
let employeeOptions = null;
let employeeSkip = 0;
const employeeLimit = 8;
let employeeHasMore = true;
let employeeLoading = false;
let employeeSearchMode = false;
let employeeSearchTerm = "";
let employeeRows = [];
let employeeEditId = null;

document.addEventListener("DOMContentLoaded", function () {
    const employeeContainer = document.querySelector(
        ".employee-table-container",
    );
    const searchInput = document.querySelector(
        ".search-bar-employee .input-search",
    );
    const searchButton = document.querySelector(
        ".search-bar-employee .search-bar-btn",
    );
    const newEmployeeButton = document.getElementById("btn-nuevo");
    const employeeForm = document.getElementById("form-employee");
    const resendPasswordButton = document.getElementById(
        "btn-resend-employee-password",
    );

    setEmployeeHidden("employee-status-field", true);

    newEmployeeButton?.addEventListener("click", openNewEmployeeModal);
    employeeForm?.addEventListener("submit", saveEmployee);
    resendPasswordButton?.addEventListener(
        "click",
        resendEmployeePasswordLink,
    );

    searchInput?.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            searchEmployees(searchInput.value.trim());
        }
    });

    searchButton?.addEventListener("click", function () {
        searchEmployees(searchInput.value.trim());
    });

    searchInput?.addEventListener("input", function () {
        if (searchInput.value.trim() === "" && employeeSearchMode) {
            searchEmployees("");
        }
    });

    employeeContainer?.addEventListener("scroll", function () {
        if (
            !employeeSearchMode &&
            employeeHasMore &&
            !employeeLoading &&
            this.scrollTop + this.clientHeight >= this.scrollHeight - 20
        ) {
            loadEmployeeBatch();
        }
    });

    loadEmployeeBatch();
});

async function employeeRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            "X-CSRF-TOKEN": csrf_token,
            Accept: "application/json",
            ...(options.headers || {}),
        },
    });

    const contentType = response.headers.get("content-type") || "";
    const payload = contentType.includes("application/json")
        ? await response.json()
        : {};

    if (!response.ok) {
        const validationMessages = payload.errors
            ? Object.values(payload.errors).flat().join("\n")
            : "";

        throw new Error(
            validationMessages ||
                payload.message ||
                "No se pudo completar la solicitud.",
        );
    }

    return payload;
}

function formatEmployeeContractDate(value) {
    if (!value) return "";

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

    if (!match) return "";

    const [, year, month, day] = match;

    const yearNumber = Number(year);
    const monthNumber = Number(month);
    const dayNumber = Number(day);

    if (yearNumber < 1 || monthNumber < 1 || monthNumber > 12) {
        return "";
    }

    const daysInMonth = new Date(
        yearNumber,
        monthNumber,
        0,
    ).getDate();

    if (dayNumber < 1 || dayNumber > daysInMonth) {
        return "";
    }

    return `${day}/${month}/${year}`;
}

async function loadEmployeeBatch() {
    if (employeeLoading || !employeeHasMore) return;

    employeeLoading = true;

    const formData = new FormData();
    formData.append("skip", employeeSkip);
    formData.append("limit", employeeLimit);

    try {
        const result = await employeeRequest(employeeEndpoints.pagination, {
            method: "POST",
            body: formData,
        });

        if (employeeSkip === 0) {
            clearEmployeeTable();
        }

        (result.empleados || []).forEach(appendEmployeeRow);

        employeeSkip += (result.empleados || []).length;
        employeeHasMore = Boolean(result.hasMore);

        if (employeeRows.length === 0) {
            showEmployeeEmptyState();
        } else {
            removeEmployeePlaceholder();
        }
    } catch (error) {
        showToast(error.message, "error");
    } finally {
        employeeLoading = false;
    }
}

async function searchEmployees(search) {
    if (!search) {
        employeeSearchMode = false;
        employeeSearchTerm = "";
        employeeSkip = 0;
        employeeHasMore = true;
        employeeRows = [];
        clearEmployeeTable();
        await loadEmployeeBatch();
        return;
    }

    employeeSearchMode = true;
    employeeSearchTerm = search;
    employeeSkip = 0;
    employeeHasMore = false;
    employeeRows = [];
    clearEmployeeTable();

    const formData = new FormData();
    formData.append("search", search);

    try {
        const result = await employeeRequest(employeeEndpoints.search, {
            method: "POST",
            body: formData,
        });

        (result.empleados || []).forEach(appendEmployeeRow);

        if (employeeRows.length === 0) {
            showEmployeeEmptyState("No se encontraron empleados.");
        } else {
            removeEmployeePlaceholder();
        }
    } catch (error) {
        showToast(error.message, "error");
    }
}

function appendEmployeeRow(employee) {
    employeeRows.push(employee);

    const row = document.createElement("tr");
    const account = employee.usuario || {};

    [account.name || "Sin nombre", account.email || "Sin correo"].forEach(
        (value) => {
            const cell = document.createElement("td");
            cell.textContent = value;
            row.appendChild(cell);
        },
    );

    const actionCell = document.createElement("td");

    const viewButton = document.createElement("button");
    viewButton.type = "button";
    viewButton.className = "employee-action-button";
    viewButton.title = "Ver datos del empleado";
    viewButton.setAttribute(
        "aria-label",
        `Ver datos de ${employee.nombreCompleto || account.name || "empleado"}`,
    );

    const viewIcon = document.createElement("img");
    viewIcon.src = empleadoAssets.view;
    viewIcon.alt = "";

    viewButton.appendChild(viewIcon);
    viewButton.addEventListener("click", () => openEmployeeDetails(employee));
    actionCell.appendChild(viewButton);

    const editButton = document.createElement("button");
    editButton.type = "button";
    editButton.className = "employee-action-button";
    editButton.title = "Editar empleado";
    editButton.setAttribute(
        "aria-label",
        `Editar ${account.name || "empleado"}`,
    );

    const editIcon = document.createElement("img");
    editIcon.src = empleadoAssets.edit;
    editIcon.alt = "";

    editButton.appendChild(editIcon);
    editButton.addEventListener("click", () => openEditEmployeeModal(employee));
    actionCell.appendChild(editButton);

    row.appendChild(actionCell);
    document.querySelector(".employee-table tbody")?.appendChild(row);
}

function clearEmployeeTable() {
    document.querySelector(".employee-table tbody")?.replaceChildren();
    employeeRows = [];
}

function showEmployeeEmptyState(message = "No hay empleados registrados.") {
    const tbody = document.querySelector(".employee-table tbody");

    if (!tbody) return;

    const row = document.createElement("tr");
    const cell = document.createElement("td");

    cell.colSpan = 3;
    cell.textContent = message;

    row.appendChild(cell);
    tbody.replaceChildren(row);
}

function removeEmployeePlaceholder() {
    document.querySelector(".employee-loading-row")?.remove();
}

async function loadEmployeeOptions() {
    if (employeeOptions) return employeeOptions;

    employeeOptions = await employeeRequest(employeeEndpoints.options);

    fillEmployeeSelect(
        "idrol",
        employeeOptions.roles || [],
        "Seleccione rol",
        (role) => role.descripcion,
        "idrol",
    );

    return employeeOptions;
}

function fillEmployeeSelect(name, options, placeholder, label, valueKey) {
    const select = document.querySelector(`#form-employee [name="${name}"]`);

    if (!select) return;

    select.replaceChildren(new Option(placeholder, ""));

    options.forEach((option) => {
        select.add(new Option(label(option), option[valueKey]));
    });
}

function setEmployeeEmailReadOnly(readOnly) {
    const form = document.getElementById("form-employee");

    if (form?.elements.email) {
        form.elements.email.readOnly = readOnly;
    }
}

async function openNewEmployeeModal() {
    employeeEditId = null;

    const form = document.getElementById("form-employee");

    if (!form) return;

    form.reset();

    setEmployeeHidden("employee-status-field", true);
    setEmployeeEmailReadOnly(false);

    if (form.elements.idempleado) {
        form.elements.idempleado.value = "";
    }

    if (form.elements.password) {
        form.elements.password.value = "Empleado2026@";
        form.elements.password.required = true;
    }

    setEmployeeText("employee-modal-title", "Nuevo empleado");
    setEmployeeText("btn-save-employee", "Guardar");

    setEmployeeHidden("employee-password-field", true);
    setEmployeeHidden("btn-resend-employee-password", true);

    try {
        await loadEmployeeOptions();
        modalManager.showModalId("modal-employee-form");
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function openEditEmployeeModal(employee) {
    employeeEditId = employee.idempleado;

    const form = document.getElementById("form-employee");

    if (!form) return;

    form.reset();

    setEmployeeHidden("employee-status-field", false);
    setEmployeeEmailReadOnly(true);

    try {
        await loadEmployeeOptions();

        if (form.elements.idempleado) {
            form.elements.idempleado.value = employee.idempleado;
        }

        if (form.elements.name) {
            form.elements.name.value = employee.usuario?.name || "";
        }

        if (form.elements.email) {
            form.elements.email.value = employee.usuario?.email || "";
        }

        if (form.elements.idrol) {
            form.elements.idrol.value =
                employee.idrol ?? employee.rol?.idrol ?? "";
        }

        if (form.elements.estado) {
            form.elements.estado.value =
                employee.estado === "Inactivo" ? "Inactivo" : "Activo";
        }

        if (form.elements.password) {
            form.elements.password.value = "";
            form.elements.password.required = false;
        }

        setEmployeeHidden("employee-password-field", false);
        setEmployeeHidden("btn-resend-employee-password", false);

        setEmployeeText("employee-modal-title", "Editar empleado");
        setEmployeeText("btn-save-employee", "Actualizar");

        modalManager.showModalId("modal-employee-form");
    } catch (error) {
        showToast(error.message, "error");
    }
}

async function resendEmployeePasswordLink(event) {
    if (!employeeEditId) return;

    const button = event.currentTarget;
    const endpoint = employeeEndpoints.resendPassword.replace(
        "__ID__",
        encodeURIComponent(employeeEditId),
    );

    button.disabled = true;

    try {
        const result = await employeeRequest(endpoint, {
            method: "POST",
        });

        showToast(result.message || "Solicitud completada.", "success");
    } catch (error) {
        showToast(error.message, "error");
    } finally {
        button.disabled = false;
    }
}

async function saveEmployee(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (form.dataset.submitting === "true") return;

    const formData = new FormData(form);
    const isEditing = employeeEditId !== null;
    const endpoint = isEditing
        ? employeeEndpoints.update
        : employeeEndpoints.create;
    const saveButton = document.getElementById("btn-save-employee");

    if (!isEditing) {
        formData.delete("estado");
    }

    form.dataset.submitting = "true";

    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = "Procesando...";
    }

    modalManager.showModalId("base-modal-loading");

    try {
        const result = await employeeRequest(endpoint, {
            method: "POST",
            body: formData,
        });

        showToast(
            result.message || "Empleado guardado correctamente.",
            "success",
        );

        closeEmployeeModal();
        form.reset();

        setEmployeeHidden("employee-status-field", true);
        setEmployeeEmailReadOnly(false);

        employeeEditId = null;
        employeeOptions = null;

        await refreshEmployeeTable();
    } catch (error) {
        showToast(error.message, "error");
    } finally {
        modalManager.hiddenModalId("base-modal-loading");
        form.dataset.submitting = "false";

        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = isEditing ? "Actualizar" : "Guardar";
        }
    }
}

async function refreshEmployeeTable() {
    employeeSkip = 0;
    employeeHasMore = true;
    employeeRows = [];

    clearEmployeeTable();

    if (employeeSearchMode && employeeSearchTerm) {
        const searchInput = document.querySelector(
            ".search-bar-employee .input-search",
        );

        if (searchInput) {
            searchInput.value = employeeSearchTerm;
        }

        await searchEmployees(employeeSearchTerm);
    } else {
        await loadEmployeeBatch();
    }
}

function closeEmployeeModal() {
    modalManager.hiddenModalId("modal-employee-form");
    setEmployeeHidden("employee-status-field", true);
}

async function openEmployeeDetails(employee) {
    const detailsUrl = employeeEndpoints.details.replace(
        "__ID__",
        encodeURIComponent(employee.idempleado),
    );

    try {
        const result = await employeeRequest(detailsUrl);

        if (!result.success || !result.empleado) {
            throw new Error(
                result.message ||
                    "No se pudieron cargar los datos del empleado.",
            );
        }

        const details = result.empleado;

        const incomplete = document.getElementById(
            "employee-details-incomplete",
        );
        const information = document.getElementById(
            "employee-details-information",
        );

        if (!incomplete || !information) {
            throw new Error(
                "No se encontraron los elementos del modal de detalles.",
            );
        }

        const values = {
            name: details.nombreCompleto,
            role: details.rol?.descripcion,
            dui: details.duiempleado,
            phone: details.telefono,
            contract: formatEmployeeContractDate(details.iniciocontrato),
            status: details.estado,
            department: details.distrito?.municipio?.departamento?.nombre,
            municipality: details.distrito?.municipio?.nombre,
            district: details.distrito?.nombre,
            address: details.Residencia,
        };

        let hasAnyData = false;

        Object.entries(values).forEach(([key, value]) => {
            const element = document.getElementById(
                `employee-details-${key}`,
            );

            if (!element) return;

            const container = element.closest("div");
            const hasValue =
                value !== null &&
                value !== undefined &&
                String(value).trim() !== "";

            if (hasValue) {
                element.textContent = value;

                if (container) {
                    container.hidden = false;
                }

                hasAnyData = true;
            } else {
                element.textContent = "";

                if (container) {
                    container.hidden = true;
                }
            }
        });

        const photo = document.getElementById("employee-details-photo");
        const photoPlaceholder = document.getElementById(
            "employee-details-photo-placeholder",
        );

        if (details.foto && photo) {
            photo.src = details.foto.startsWith("data:image/")
                ? details.foto
                : `data:image/jpeg;base64,${details.foto}`;

            photo.hidden = false;
            hasAnyData = true;

            if (photoPlaceholder) {
                photoPlaceholder.hidden = true;
            }
        } else {
            if (photo) {
                photo.removeAttribute("src");
                photo.hidden = true;
            }

            if (photoPlaceholder) {
                photoPlaceholder.hidden = true;
            }
        }

        incomplete.hidden = hasAnyData;
        information.hidden = !hasAnyData;

        if (!hasAnyData) {
            incomplete.textContent = "Datos incompletos de empleado";
        }

        modalManager.showModalId("modal-employee-details");
    } catch (error) {
        showToast(error.message, "error");
    }
}

function closeEmployeeDetails() {
    modalManager.hiddenModalId("modal-employee-details");
}

function setEmployeeText(id, value) {
    const element = document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}

function setEmployeeHidden(id, hidden) {
    const element = document.getElementById(id);

    if (element) {
        element.hidden = hidden;
    }
}
