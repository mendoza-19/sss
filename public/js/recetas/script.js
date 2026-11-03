document.addEventListener("DOMContentLoaded", function () {
    // =====================================================
    // MENSAJE DE ÉXITO
    // =====================================================

    const successMessage = document
        .getElementById("recipe-success-message")
        ?.textContent.trim();

    if (successMessage && typeof showToast === "function") {
        showToast(successMessage, "success");
    }

    // =====================================================
    // ELEMENTOS PRINCIPALES
    // =====================================================

    const recipeModal = document.getElementById("modal-new-recipe");
    const detailsModal = document.getElementById("modal-view-recipe");
    const ingredientModal = document.getElementById("modal-new-ingredient");
    const processModal = document.getElementById("modal-process-recipe");

    const form = document.getElementById("recipe-form");
    const processForm = document.getElementById("process-recipe-form");

    const newButton = document.getElementById("btn-new-recipe");
    const cancelRecipeButton = document.getElementById("btn-cancel-recipe");
    const cancelDetailsButton = document.getElementById("btn-cancel-details");
    const cancelProcessButton = document.getElementById("btn-cancel-process");

    const searchBar = document.getElementById("search-bar-recipe");
    const saveButton = document.getElementById("btn-save");

    const saveProcessButton = document.getElementById("btn-save-process");

    const processRecipeId = document.getElementById("process-receta-id");
    const processRecipeName = document.getElementById("process-receta-nombre");
    const processDate = document.getElementById("process-fecha");
    const processFlourInput = document.getElementById("process-flour-count");
    const processFlourStockMessage = document.getElementById("process-flour-stock-message");
    const processIngredientsList = document.getElementById(
        "process-ingredients-list",
    );

    const openIngredientButton = document.getElementById(
        "btn-open-ingredient-modal",
    );

    const cancelIngredientButton = document.getElementById(
        "btn-cancel-ingredient-modal",
    );

    const confirmIngredientButton = document.getElementById(
        "btn-confirm-ingredient",
    );

    const ingredientName = document.getElementById("ingredient-name");
    const ingredientAmount = document.getElementById("ingredient-amount");
    const ingredientMeasure = document.getElementById("ingredient-measure");
    const ingredientModalTitle = document.getElementById(
        "ingredient-modal-title",
    );

    const ingredientSearchResults = document.getElementById(
        "ingredient-search-results",
    );

    const ingredientList = document.getElementById("recipe-ingredients-list");

    if (!detailsModal) {
        console.error("Faltan elementos necesarios de la vista de Recetas.");
        return;
    }

    if (recipeModal) {
        document.body.appendChild(recipeModal);
    }
    document.body.appendChild(detailsModal);

    if (ingredientModal) {
        document.body.appendChild(ingredientModal);
    }

    if (processModal) {
        document.body.appendChild(processModal);
    }

    let submittingForm = false;
    let submittingProcess = false;
    let selectedIngredient = null;
    let ingredientInputIndex = 0;
    let ingredientSearchTimeout = null;
    let ingredientSearchRequest = 0;

    // =====================================================
    // MODALES
    // =====================================================

    function isModalOpen(modal) {
        return !!modal && modal.getAttribute("aria-hidden") === "false";
    }

    function updateBodyOverflow() {
        const anyModalOpen =
            isModalOpen(recipeModal) ||
            isModalOpen(detailsModal) ||
            isModalOpen(ingredientModal) ||
            isModalOpen(processModal);

        document.body.style.overflow = anyModalOpen ? "hidden" : "";
    }

    function showModal(modal) {
        if (!modal) return;

        modal.style.display = "flex";
        modal.setAttribute("aria-hidden", "false");

        updateBodyOverflow();
    }

    function hideModal(modal) {
        if (!modal) return;

        modal.style.display = "none";
        modal.style.zIndex = "";
        modal.setAttribute("aria-hidden", "true");

        updateBodyOverflow();
    }

    function closeIngredientModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        hideModal(ingredientModal);
    }

    function openIngredientModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!ingredientModal) return;

        if (!isModalOpen(recipeModal)) {
            showModal(recipeModal);
        }

        updateIngredientButtonText();

        recipeModal.style.zIndex = "10000";
        ingredientModal.style.display = "flex";
        ingredientModal.style.zIndex = "10001";
        ingredientModal.setAttribute("aria-hidden", "false");

        updateBodyOverflow();

        ingredientName?.focus();
    }

    function updateIngredientButtonText() {
        if (!form) return;

        const idInput = form.elements.namedItem("idreceta");
        const isEditing = !!idInput?.value.trim();

        if (openIngredientButton) {
            openIngredientButton.textContent = isEditing
                ? "Modificar ingredientes"
                : "Agregar ingredientes";
        }

        if (ingredientModalTitle) {
            ingredientModalTitle.textContent = isEditing
                ? "Modificar ingredientes"
                : "Agregar ingredientes";
        }
    }

    // =====================================================
    // INGREDIENTES: UTILIDADES
    // =====================================================

    function getIngredientId(ingrediente) {
        const id =
            ingrediente?.id ??
            ingrediente?.idingrediente ??
            ingrediente?.ingrediente_id;

        if (id === undefined || id === null || String(id).trim() === "") {
            return null;
        }

        return String(id);
    }

    function normalizarMedidaIngrediente(medida) {
        const unidad = String(medida || "").trim().toLowerCase();
        const equivalencias = {
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
            huevo: "u",
            huevos: "u",
            pieza: "u",
            piezas: "u",
            litro: "L",
            litros: "L",
            mililitro: "ml",
            mililitros: "ml",
        };

        return equivalencias[unidad] || medida;
    }

    function normalizarCantidadIngrediente(value) {
        const cantidad = String(value ?? "").trim().replace(",", ".");

        if (!/^(?:\d+(?:\.\d{1,3})?|\.\d{1,3})$/.test(cantidad)) {
            return null;
        }

        const numero = Number(cantidad);
        if (!Number.isFinite(numero) || numero <= 0 || numero > 1000000) {
            return null;
        }

        return cantidad.startsWith(".") ? `0${cantidad}` : cantidad;
    }

    function formatearCantidadIngrediente(value) {
        if (value === null || value === undefined || value === "") return "";

        const cantidad = Number(value);
        return Number.isFinite(cantidad) ? cantidad.toString() : String(value);
    }

    function showEmptyIngredientRow() {
        if (!ingredientList) return;

        if (ingredientList.querySelector("[data-ingredient-id]")) {
            return;
        }

        ingredientList.replaceChildren();

        const row = document.createElement("tr");
        row.id = "recipe-ingredients-empty";

        const cell = document.createElement("td");
        cell.colSpan = 3;
        cell.className = "recipe-empty-state";
        cell.textContent = "Todavía no has agregado ingredientes.";

        row.appendChild(cell);
        ingredientList.appendChild(row);
    }

    function clearIngredientRows() {
        if (ingredientList) {
            ingredientList.replaceChildren();
            showEmptyIngredientRow();
        }

        form.querySelectorAll('input[data-recipe-ingredient="true"]').forEach(
            function (input) {
                input.remove();
            },
        );
        ingredientInputIndex = 0;

        if (ingredientName) {
            ingredientName.value = "";
        }

        if (ingredientAmount) {
            ingredientAmount.value = "";
            ingredientAmount.setCustomValidity("");
        }

        if (ingredientMeasure) {
            ingredientMeasure.value = "";
        }

        if (confirmIngredientButton) {
            confirmIngredientButton.textContent = "Agregar";
        }

        if (ingredientAmount) {
            ingredientAmount.value = "";
            ingredientAmount.setCustomValidity("");
        }

        if (ingredientMeasure) {
            ingredientMeasure.value = "oz";
        }

        if (confirmIngredientButton) {
            confirmIngredientButton.textContent = "Agregar";
        }

        if (ingredientSearchResults) {
            ingredientSearchResults.replaceChildren();
            ingredientSearchResults.hidden = true;
        }

        selectedIngredient = null;
    }

    // =====================================================
    // AGREGAR INGREDIENTE A LA TABLA Y AL FORMULARIO
    // =====================================================

    function addIngredientToForm(ingrediente, peso, medidaReceta) {
        if (!ingredientList) return false;

        const id = getIngredientId(ingrediente);
        const nombre = ingrediente?.nombre;

        if (id === null || !nombre) {
            console.warn("Ingrediente inválido:", ingrediente);
            return false;
        }

        const existingRow = Array.from(
            ingredientList.querySelectorAll("[data-ingredient-id]"),
        ).find(function (row) {
            return row.dataset.ingredientId === id;
        });

        if (existingRow) {
            const existingAmount = existingRow.querySelector("[data-ingredient-weight]");
            const existingMeasure = existingRow.querySelector("[data-ingredient-measure]");
            const amountDisplay = existingRow.querySelector(".ingredient-amount-display");

            if (existingAmount) existingAmount.value = peso;
            if (existingMeasure) existingMeasure.value = medidaReceta;
            if (amountDisplay) {
                amountDisplay.textContent = peso
                    ? `${formatearCantidadIngrediente(peso)} ${medidaReceta}`
                    : "Sin cantidad";
            }

            return true;
        }

        document.getElementById("recipe-ingredients-empty")?.remove();

        const row = document.createElement("tr");
        row.dataset.ingredientId = id;

        const nameCell = document.createElement("td");
        nameCell.textContent = nombre;

        const weightCell = document.createElement("td");
        const ingredientIndex = ingredientInputIndex++;
        const medidaInventario = ingrediente?.medida;
        const ingredientWeight = peso ?? ingrediente?.peso ?? ingrediente?.pivot?.peso ?? "";
        const measure = normalizarMedidaIngrediente(
            medidaReceta ?? ingrediente?.medida_receta ?? medidaInventario,
        );

        const amountDisplay = document.createElement("span");
        amountDisplay.className = "ingredient-amount-display";
        amountDisplay.textContent = ingredientWeight
            ? `${formatearCantidadIngrediente(ingredientWeight)} ${measure}`
            : "Sin cantidad";
        weightCell.appendChild(amountDisplay);

        const weightInput = document.createElement("input");
        weightInput.type = "hidden";
        weightInput.name = `ingredientes[${ingredientIndex}][peso]`;
        weightInput.value = ingredientWeight;
        weightInput.dataset.ingredientWeight = "true";
        weightInput.dataset.recipeIngredient = "true";

        const measureInput = document.createElement("input");
        measureInput.type = "hidden";
        measureInput.name = `ingredientes[${ingredientIndex}][medida]`;
        measureInput.value = measure;
        measureInput.dataset.ingredientMeasure = "true";
        measureInput.dataset.recipeIngredient = "true";

        const actionsCell = document.createElement("td");
        const editButton = document.createElement("button");
        const removeButton = document.createElement("button");

        editButton.type = "button";
        editButton.className = "recipe-secondary-button";
        editButton.textContent = "Editar";
        editButton.setAttribute("aria-label", "Editar " + nombre);

        removeButton.type = "button";
        removeButton.className = "recipe-secondary-button";
        removeButton.textContent = "Eliminar";
        removeButton.setAttribute("aria-label", "Eliminar " + nombre);

        const hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = `ingredientes[${ingredientIndex}][id]`;
        hiddenInput.value = id;
        hiddenInput.dataset.recipeIngredient = "true";
        hiddenInput.setAttribute("form", form.id);
        weightInput.setAttribute("form", form.id);
        measureInput.setAttribute("form", form.id);

        editButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            selectedIngredient = {
                id: id,
                nombre: nombre,
                medida: medidaInventario,
                medida_receta: measureInput.value,
                stock: ingrediente?.stock ?? 0,
            };

            ingredientName.value = nombre;
            ingredientAmount.value = formatearCantidadIngrediente(weightInput.value);
            ingredientMeasure.value = measureInput.value;
            confirmIngredientButton.textContent = "Actualizar";
            openIngredientModal();
            ingredientAmount.focus();
        });

        editButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            selectedIngredient = {
                id: id,
                nombre: nombre,
                medida: ingredientMeasure,
                medida_receta: measureInput.value,
                stock: ingrediente?.stock ?? 0,
            };

            ingredientName.value = nombre;
            ingredientAmount.value = weightInput.value;
            ingredientMeasure.value = measureInput.value;
            confirmIngredientButton.textContent = "Actualizar";
            ingredientAmount.focus();
        });

        removeButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            row.remove();
            hiddenInput.remove();
            weightInput.remove();
            measureInput.remove();

            if (selectedIngredient?.id === id) {
                selectedIngredient = null;
                ingredientName.value = "";
                ingredientAmount.value = "";
                confirmIngredientButton.textContent = "Agregar";
            }

            showEmptyIngredientRow();
        });

        actionsCell.append(editButton, removeButton);
        weightCell.append(hiddenInput, weightInput, measureInput);
        row.append(nameCell, weightCell, actionsCell);

        ingredientList.appendChild(row);

        return true;
    }

    // =====================================================
    // REINICIAR FORMULARIO DE RECETA
    // =====================================================

    function resetRecipeForm() {
        form.reset();

        submittingForm = false;
        form.dataset.submitting = "false";

        clearIngredientRows();

        const idInput = form.elements.namedItem("idreceta");
        const methodInput = form.elements.namedItem("_method");

        if (idInput) {
            idInput.value = "";
        }

        if (methodInput) {
            methodInput.value = "PUT";
            methodInput.disabled = true;
        }

        if (form.dataset.createUrl) {
            form.action = form.dataset.createUrl;
        }

        const title = document.getElementById("recipe-modal-title");

        if (title) {
            title.textContent = "Nueva receta";
        }

        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = "Guardar receta";
        }

        const loadingOverlay = document.getElementById("recipe-saving-overlay");

        if (loadingOverlay) {
            loadingOverlay.hidden = true;
            loadingOverlay.classList.remove("is-visible");
        }

        updateIngredientButtonText();
    }

    function setRecipeUpdateMode(recipeId) {
        if (!recipeId) return;

        const idInput = form.elements.namedItem("idreceta");
        const methodInput = form.elements.namedItem("_method");

        if (idInput) {
            idInput.value = recipeId;
        }

        if (methodInput) {
            methodInput.value = "PUT";
            methodInput.disabled = false;
        }

        if (form.dataset.updateUrl) {
            form.action = form.dataset.updateUrl.replace(
                "__ID__",
                encodeURIComponent(recipeId),
            );
        }

        const title = document.getElementById("recipe-modal-title");

        if (title) {
            title.textContent = "Editar receta";
        }

        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = "Actualizar";
        }

        updateIngredientButtonText();
    }

    // =====================================================
    // ABRIR RECETA NUEVA
    // =====================================================

    function openNewRecipeModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        hideModal(ingredientModal);
        hideModal(detailsModal);
        hideModal(processModal);

        resetRecipeForm();
        showModal(recipeModal);
    }

    // =====================================================
    // EDITAR RECETA Y RECUPERAR SUS INGREDIENTES
    // =====================================================

    function openEditRecipeModal(row, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!row) return;

        hideModal(ingredientModal);
        hideModal(detailsModal);
        hideModal(processModal);

        resetRecipeForm();

        const nameInput = form.elements.namedItem("nombre");
        const descriptionInput = form.elements.namedItem("descripcion");
        const instructivoInput = form.elements.namedItem("instructivo");
        if (nameInput) {
            nameInput.value = row.dataset.recipeName || "";
        }

        if (descriptionInput) {
            descriptionInput.value = row.dataset.recipeDescription || "";
        }

        if (instructivoInput) {
            instructivoInput.value = row.dataset.recipeInstructivo || "";
        }
        const recipeId = row.dataset.recipeId || "";

        if (!recipeId) {
            console.error("No se encontró el ID de la receta.", row);
            return;
        }

        setRecipeUpdateMode(recipeId);

        try {
            const ingredientes = JSON.parse(
                row.dataset.recipeIngredients || "[]",
            );

            if (Array.isArray(ingredientes)) {
                ingredientes.forEach(function (ingrediente) {
                    addIngredientToForm(ingrediente);
                });
            }
        } catch (error) {
            console.error(
                "No se pudieron cargar los ingredientes de la receta:",
                error,
            );
        }

        showModal(recipeModal);
    }

    // =====================================================
    // VER DETALLES DE RECETA
    // =====================================================

    function openRecipeDetails(row, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (!row) return;

        hideModal(ingredientModal);
        hideModal(recipeModal);
        hideModal(processModal);

        const name = document.getElementById("view-recipe-name");
        const ingredientsList = document.getElementById(
            "view-recipe-ingredients",
        );
        const instructivo = document.getElementById("view-recipe-instructivo");

        if (name) {
            name.textContent = row.dataset.recipeName || "";
        }

        if (ingredientsList) {
            ingredientsList.replaceChildren();

            try {
                const ingredients = JSON.parse(
                    row.dataset.recipeIngredients || "[]",
                );

                if (!Array.isArray(ingredients)) {
                    throw new TypeError("El formato de ingredientes no es válido.");
                }

                if (ingredients.length === 0) {
                    const emptyItem = document.createElement("li");
                    emptyItem.textContent = "No hay ingredientes registrados.";
                    ingredientsList.appendChild(emptyItem);
                } else {
                    ingredients.forEach(function (ingredient) {
                        const item = document.createElement("li");

                        if (!ingredient || typeof ingredient.nombre !== "string") {
                            console.error(
                                "No se pudo mostrar un ingrediente de la receta.",
                                ingredient,
                            );
                            return;
                        }

                        const peso = ingredient.peso ?? ingredient.pivot?.peso;
                        const pesoMostrado = formatearCantidadIngrediente(peso);
                        const medidaReceta = ingredient.medida_receta ?? ingredient.pivot?.medida;

                        item.textContent = peso !== null && peso !== undefined && peso !== ""
                            ? `${ingredient.nombre}: ${pesoMostrado} ${medidaReceta || "oz"}`
                            : ingredient.medida
                                ? `${ingredient.nombre} (${ingredient.medida})`
                                : ingredient.nombre;
                        ingredientsList.appendChild(item);
                    });
                }
            } catch (error) {
                console.error(
                    "No se pudieron cargar los ingredientes de la receta:",
                    error,
                );
            }
        }

        if (instructivo) {
            instructivo.textContent = row.dataset.recipeInstructivo || "";
        }

        showModal(detailsModal);
    }

    function closeRecipeModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        hideModal(ingredientModal);
        hideModal(recipeModal);
    }

    function closeRecipeDetailsModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        hideModal(detailsModal);
    }

    // =====================================================
    // PROCESAR RECETA
    // =====================================================

    function getLocalDate() {
        const now = new Date();

        return [
            now.getFullYear(),
            String(now.getMonth() + 1).padStart(2, "0"),
            String(now.getDate()).padStart(2, "0"),
        ].join("-");
    }

    
function openProcessRecipeModal(row, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    if (!row || !processModal || !processForm) {
        console.error("No se encontró el modal de procesamiento.");
        return;
    }

    hideModal(recipeModal);
    hideModal(detailsModal);
    hideModal(ingredientModal);

    // Eliminar mensajes flotantes anteriores
    document.querySelectorAll(".process-stock-message").forEach(function (message) {
        message.remove();
    });

    const recetaId = row.dataset.recipeId || "";
    const recetaNombre = row.dataset.recipeName || "";
    if (!recetaId) {
        alert("No se encontró el ID de la receta.");
        return;
    }

    let ingredientes = [];

    try {
        ingredientes = JSON.parse(row.dataset.recipeIngredients || "[]");
    } catch (error) {
        console.error("Error al leer los ingredientes:", error);
        alert("No se pudieron cargar los ingredientes de la receta.");
        return;
    }

    if (!Array.isArray(ingredientes) || ingredientes.length === 0) {
        alert("Esta receta no tiene ingredientes registrados.");
        return;
    }

    if (ingredientes.some((ingrediente) => !Number(ingrediente.peso ?? ingrediente.pivot?.peso))) {
        alert("Completa la cantidad de todos los ingredientes en la receta antes de procesarla.");
        return;
    }

    processForm.reset();

    submittingProcess = false;
    processForm.dataset.submitting = "false";

    processRecipeId.value = recetaId;
    processRecipeName.textContent = recetaNombre;
    processFlourInput.value = "1";
    const harina = ingredientes.filter((ingrediente) =>
        String(ingrediente.nombre || "").toLowerCase().includes("harina"),
    );

    if (harina.length !== 1) {
        alert("La receta debe tener exactamente un ingrediente de harina para calcular las cantidades.");
        return;
    }
    const harinaId = String(harina[0].id ?? harina[0].idingrediente);
    const harinaPorPan = Number(harina[0].peso ?? harina[0].pivot?.peso);
    const medidaHarina = harina[0].medida_receta ?? harina[0].pivot?.medida;

    if (harinaPorPan <= 0 || !medidaHarina) {
        alert("Completa la cantidad y unidad de la harina en la receta antes de procesarla.");
        return;
    }

    const fecha = getLocalDate();
    processDate.value = fecha;

    const fechaTexto = document.getElementById("process-fecha-texto");

    if (fechaTexto) {
        fechaTexto.textContent = formatDateDisplay(fecha);
    }

    processIngredientsList.replaceChildren();

    ingredientes.forEach(function (ingrediente, index) {
        const id = ingrediente.id ?? ingrediente.idingrediente;
        const nombre = ingrediente.nombre;

        if (id === undefined || id === null || !nombre) {
            console.warn("Ingrediente inválido:", ingrediente);
            return;
        }

        const rowIngredient = document.createElement("tr");

        const nameCell = document.createElement("td");
        nameCell.textContent = nombre;

        const medidaReceta = ingrediente.medida_receta ?? ingrediente.pivot?.medida;
        const cantidadPorPan = Number(ingrediente.peso ?? ingrediente.pivot?.peso);
        const measureCell = document.createElement("td");
        measureCell.textContent = `${cantidadPorPan} ${medidaReceta}`;

        const quantityCell = document.createElement("td");
        const quantityInput = document.createElement("input");
        quantityInput.type = "text";
        quantityInput.readOnly = true;
        quantityInput.value = cantidadPorPan;
        quantityInput.className = "process-quantity-input";
        quantityInput.removeAttribute("name");
        quantityInput.dataset.perPan = String(cantidadPorPan);
        quantityInput.dataset.measure = medidaReceta;

        quantityCell.appendChild(quantityInput);

        const stockCell = document.createElement("td");
        const stockStatus = document.createElement("span");
        const stockDisponible = Number(ingrediente.stock ?? 0);
        const medidaInventario = ingrediente.medida || "u";
        const esPeso = ["mg", "g", "gr", "gramo", "gramos", "kg", "kilo", "kilos", "kilogramo", "kilogramos", "oz", "onza", "onzas", "lb", "lbs", "libra", "libras"].includes(String(medidaInventario).toLowerCase());
        const unidadStock = esPeso ? "oz" : medidaInventario;

        stockStatus.className = "process-stock-status";
        stockStatus.dataset.stock = String(stockDisponible);
        stockStatus.dataset.stockUnit = unidadStock;
        stockStatus.textContent = `Disponible: ${stockDisponible} ${unidadStock}`;
        stockCell.appendChild(stockStatus);

        rowIngredient.dataset.processIngredient = "true";
        rowIngredient.dataset.isFlour = String(id) === harinaId ? "true" : "false";
        rowIngredient.dataset.inventoryUnit = unidadStock;
        rowIngredient.hidden = rowIngredient.dataset.isFlour === "true";
        rowIngredient.append(nameCell, measureCell, quantityCell, stockCell);
        processIngredientsList.appendChild(rowIngredient);
    });

    function convertirConsumoAStock(cantidad, unidad, unidadStock) {
        const normalizar = (value) => String(value || "").trim().toLowerCase();
        const factores = {
            mg: ["peso", 1 / 28349.523125], g: ["peso", 1 / 28.349523125],
            gr: ["peso", 1 / 28.349523125], gramo: ["peso", 1 / 28.349523125],
            gramos: ["peso", 1 / 28.349523125], kg: ["peso", 1000 / 28.349523125],
            kilo: ["peso", 1000 / 28.349523125], kilos: ["peso", 1000 / 28.349523125],
            kilogramo: ["peso", 1000 / 28.349523125], kilogramos: ["peso", 1000 / 28.349523125],
            oz: ["peso", 1], onza: ["peso", 1], onzas: ["peso", 1],
            lb: ["peso", 16], lbs: ["peso", 16], libra: ["peso", 16], libras: ["peso", 16],
            ml: ["volumen", 1], mililitro: ["volumen", 1], mililitros: ["volumen", 1],
            l: ["volumen", 1000], litro: ["volumen", 1000], litros: ["volumen", 1000],
            u: ["conteo", 1], un: ["conteo", 1], unidad: ["conteo", 1], unidades: ["conteo", 1],
            pieza: ["conteo", 1], piezas: ["conteo", 1], huevo: ["conteo", 1], huevos: ["conteo", 1],
            lata: ["conteo", 1], latas: ["conteo", 1], barra: ["conteo", 1], barras: ["conteo", 1],
        };
        const source = factores[normalizar(unidad)];
        const target = factores[normalizar(unidadStock)];

        if (!source || !target || source[0] !== target[0]) return null;
        if (source[0] === "conteo" && !Number.isInteger(cantidad)) return null;
        return (cantidad * source[1]) / target[1];
    }

    function actualizarConsumoEstimado() {
        const librasHarina = Number(processFlourInput.value) || 0;
        const harinaTotalOnzas = convertirConsumoAStock(librasHarina, "lb", "oz");
        const harinaPorPanOnzas = convertirConsumoAStock(harinaPorPan, medidaHarina, "oz");
        const factorProduccion = harinaTotalOnzas !== null && harinaPorPanOnzas > 0
            ? harinaTotalOnzas / harinaPorPanOnzas
            : 0;
        let hayStockSuficiente = factorProduccion >= 1;

        if (processFlourStockMessage) {
            processFlourStockMessage.hidden = true;
            processFlourStockMessage.textContent = "";
        }

        processIngredientsList.querySelectorAll("[data-process-ingredient]").forEach((row) => {
            const quantityInput = row.querySelector(".process-quantity-input");
            const esHarina = row.dataset.isFlour === "true";
            const cantidadTotal = esHarina
                ? librasHarina
                : Number(quantityInput.dataset.perPan) * factorProduccion;
            const medidaReceta = esHarina ? "lb" : quantityInput.dataset.measure;
            const unidadStock = row.dataset.inventoryUnit;
            const stockStatus = row.querySelector(".process-stock-status");
            const stockDisponible = Number(stockStatus.dataset.stock);
            const cantidadStock = convertirConsumoAStock(cantidadTotal, medidaReceta, unidadStock);
            const formato = (value) => Number(value.toFixed(3)).toString();

            quantityInput.value = `${formato(cantidadTotal)} ${medidaReceta}`;

            if (cantidadStock === null) {
                stockStatus.textContent = `Unidad incompatible con ${unidadStock}`;
                stockStatus.classList.add("stock-error");
                hayStockSuficiente = false;
            } else if (cantidadStock > stockDisponible) {
                stockStatus.textContent = `Faltan ${formato(cantidadStock - stockDisponible)} ${unidadStock}`;
                stockStatus.classList.add("stock-error");
                hayStockSuficiente = false;
                if (esHarina && processFlourStockMessage) {
                    processFlourStockMessage.textContent = "No hay suficiente stock";
                    processFlourStockMessage.hidden = false;
                }
            } else {
                stockStatus.textContent = `Disponible: ${formato(stockDisponible - cantidadStock)} ${unidadStock}`;
                stockStatus.classList.remove("stock-error");
            }
        });

        if (saveProcessButton) saveProcessButton.disabled = !hayStockSuficiente;
    }

    if (processIngredientsList.children.length === 0) {
        document.querySelectorAll(".process-stock-message").forEach(function (message) {
            message.remove();
        });

        alert("No se pudieron cargar los ingredientes.");
        return;
    }

    actualizarConsumoEstimado();

    if (saveProcessButton) {
        saveProcessButton.textContent = "Enviar a Horneado";
    }

    showModal(processModal);

    processFlourInput.oninput = actualizarConsumoEstimado;
    processFlourInput.focus({ preventScroll: true });
}







    function closeProcessModal(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        hideModal(processModal);
    }

    // =====================================================
    // BUSCAR INGREDIENTES EXISTENTES
    // =====================================================

    if (ingredientSearchResults) {
        ingredientSearchResults.hidden = true;
    }

    if (ingredientName && ingredientSearchResults) {
        ingredientName.addEventListener("input", function () {
            const nombre = this.value.trim();

            selectedIngredient = null;
            ingredientName.setCustomValidity("");
            ingredientAmount.value = "";
            ingredientAmount.setCustomValidity("");
            confirmIngredientButton.textContent = "Agregar";

            clearTimeout(ingredientSearchTimeout);

            if (nombre.length < 2) {
                ingredientSearchRequest++;
                ingredientSearchResults.replaceChildren();
                ingredientSearchResults.hidden = true;
                return;
            }

            ingredientSearchTimeout = setTimeout(function () {
                buscarIngredientes(nombre);
            }, 300);
        });

        ingredientName.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();

                if (selectedIngredient) {
                    confirmarIngredienteSeleccionado();
                }
            }
        });
    }

    async function buscarIngredientes(nombre) {
        if (!ingredientSearchResults) return;

        const requestId = ++ingredientSearchRequest;

        ingredientSearchResults.replaceChildren();
        ingredientSearchResults.hidden = false;
        ingredientSearchResults.textContent = "Buscando...";

        try {
            const url = new URL(
                "/api/proporcionesreceta/buscar-ingredientes",
                window.location.origin,
            );

            url.searchParams.set("nombre", nombre);

            const response = await fetch(url, {
                method: "GET",
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "same-origin",
            });

            if (!response.ok) {
                throw new Error("Error HTTP " + response.status);
            }

            const ingredientes = await response.json();

            if (requestId !== ingredientSearchRequest) return;

            ingredientSearchResults.replaceChildren();

            if (!Array.isArray(ingredientes) || ingredientes.length === 0) {
                ingredientSearchResults.textContent =
                    "No se encontraron ingredientes.";
                ingredientSearchResults.hidden = false;
                return;
            }

            ingredientes.forEach(function (ingrediente) {
                const id = getIngredientId(ingrediente);

                if (id === null || !ingrediente.nombre) {
                    console.warn("Ingrediente sin ID válido:", ingrediente);
                    return;
                }

                const option = document.createElement("button");

                option.type = "button";
                option.className = "ingredient-search-option";
                option.textContent = ingrediente.nombre;
                option.setAttribute("role", "option");

                option.addEventListener("click", function () {
                    const existingRow = Array.from(
                        ingredientList.querySelectorAll("[data-ingredient-id]"),
                    ).find((row) => row.dataset.ingredientId === id);
                    const existingAmount = existingRow?.querySelector("[data-ingredient-weight]");
                    const existingMeasure = existingRow?.querySelector("[data-ingredient-measure]");

                    selectedIngredient = {
                        id: id,
                        nombre: ingrediente.nombre,
                        medida: ingrediente.medida,
                        peso: existingAmount?.value ?? "",
                        medida_receta: normalizarMedidaIngrediente(ingrediente.medida),
                        stock: ingrediente.stock,
                    };

                    ingredientName.value = ingrediente.nombre;
                    ingredientName.setCustomValidity("");
                    ingredientAmount.value = formatearCantidadIngrediente(selectedIngredient.peso);
                    ingredientAmount.setCustomValidity("");
                    ingredientMeasure.value = selectedIngredient.medida_receta;
                    confirmIngredientButton.textContent = existingRow
                        ? "Actualizar"
                        : "Agregar";

                    ingredientSearchResults.replaceChildren();
                    ingredientSearchResults.hidden = true;
                    ingredientAmount.focus();
                });

                ingredientSearchResults.appendChild(option);
            });

            if (ingredientSearchResults.children.length === 0) {
                ingredientSearchResults.textContent =
                    "No hay resultados con IDs válidos.";
            }

            ingredientSearchResults.hidden = false;
        } catch (error) {
            if (requestId !== ingredientSearchRequest) return;

            console.error("Error al buscar ingredientes:", error);

            ingredientSearchResults.replaceChildren();
            ingredientSearchResults.textContent =
                "No se pudieron cargar los ingredientes.";
            ingredientSearchResults.hidden = false;
        }
    }

    // =====================================================
    // CONFIRMAR INGREDIENTE SELECCIONADO
    // =====================================================

    function confirmarIngredienteSeleccionado() {
        if (!selectedIngredient) {
            alert("Primero selecciona un ingrediente de la lista.");
            return;
        }

        const cantidad = normalizarCantidadIngrediente(ingredientAmount.value);
        if (cantidad === null) {
            ingredientAmount.setCustomValidity(
                "Ingresa una cantidad mayor que cero, con punto o coma decimal y hasta tres decimales.",
            );
            ingredientAmount.reportValidity();
            return;
        }
        ingredientAmount.setCustomValidity("");

        if (!ingredientMeasure.checkValidity()) {
            ingredientMeasure.reportValidity();
            return;
        }

        const agregado = addIngredientToForm(
            selectedIngredient,
            cantidad,
            ingredientMeasure.value,
        );

        if (!agregado) {
            alert("No se pudo actualizar este ingrediente.");
            return;
        }

        selectedIngredient = null;

        if (ingredientName) {
            ingredientName.value = "";
            ingredientName.setCustomValidity("");
        }

        ingredientAmount.value = "";
        ingredientAmount.setCustomValidity("");
        ingredientMeasure.value = "";
        confirmIngredientButton.textContent = "Agregar";

        if (ingredientSearchResults) {
            ingredientSearchResults.replaceChildren();
            ingredientSearchResults.hidden = true;
        }

        ingredientName.focus();
    }

    // =====================================================
    // BUSCAR RECETAS EN LA TABLA
    // =====================================================

    function filterRecipeRows(search) {
        const term = (search || "").trim().toLocaleLowerCase();
        const rows = document.querySelectorAll("[data-recipe-row]");
        const noResults = document.getElementById("recipe-no-results");

        let visibleRows = 0;

        rows.forEach(function (row) {
            const name = (row.dataset.recipeName || "").toLocaleLowerCase();
            const description = (
                row.dataset.recipeDescription || ""
            ).toLocaleLowerCase();

            const matches = name.includes(term) || description.includes(term);

            row.hidden = !matches;

            if (matches) {
                visibleRows++;
            }
        });

        if (noResults) {
            noResults.hidden = term === "" || visibleRows > 0;
        }
    }

    // =====================================================
    // EVENTOS DE LOS BOTONES
    // =====================================================

    newButton?.addEventListener("click", openNewRecipeModal);

    cancelRecipeButton?.addEventListener("click", closeRecipeModal);

    cancelDetailsButton?.addEventListener("click", closeRecipeDetailsModal);

    cancelProcessButton?.addEventListener("click", closeProcessModal);

    document.querySelectorAll("[data-edit-recipe]").forEach(function (button) {
        button.addEventListener("click", function (event) {
            openEditRecipeModal(button.closest("[data-recipe-row]"), event);
        });
    });

    document.querySelectorAll("[data-view-recipe]").forEach(function (button) {
        button.addEventListener("click", function (event) {
            openRecipeDetails(button.closest("[data-recipe-row]"), event);
        });
    });

    document
        .querySelectorAll("[data-process-recipe]")
        .forEach(function (button) {
            button.addEventListener("click", function (event) {
                openProcessRecipeModal(
                    button.closest("[data-recipe-row]"),
                    event,
                );
            });
        });

    openIngredientButton?.addEventListener("click", openIngredientModal);

    cancelIngredientButton?.addEventListener("click", closeIngredientModal);

    confirmIngredientButton?.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();

        confirmarIngredienteSeleccionado();
    });

    // =====================================================
    // BUSCADOR DE RECETAS
    // =====================================================

    if (searchBar) {
        const input = searchBar.querySelector(".input-search");
        const button = searchBar.querySelector(".search-bar-btn");

        input?.addEventListener("input", function () {
            filterRecipeRows(input.value);
        });

        input?.addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                filterRecipeRows(input.value);
            }
        });

        button?.addEventListener("click", function (event) {
            event.preventDefault();

            if (input) {
                filterRecipeRows(input.value);
            }
        });
    }

    // =====================================================
    // CERRAR MODALES CON ESCAPE
    // =====================================================

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;

        if (isModalOpen(ingredientModal)) {
            event.preventDefault();
            closeIngredientModal();
            return;
        }

        if (isModalOpen(processModal)) {
            event.preventDefault();
            closeProcessModal();
            return;
        }

        if (isModalOpen(recipeModal)) {
            event.preventDefault();
            closeRecipeModal();
            return;
        }

        if (isModalOpen(detailsModal)) {
            event.preventDefault();
            closeRecipeDetailsModal();
        }
    });

    // =====================================================
    // GUARDAR O ACTUALIZAR RECETA
    // =====================================================

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            if (submittingForm || form.dataset.submitting === "true") {
                return;
            }

            const invalidIngredientField = ingredientList?.querySelector(":invalid");
            if (invalidIngredientField) {
                openIngredientModal();
                invalidIngredientField.focus();
                invalidIngredientField.reportValidity();
                return;
            }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            submittingForm = true;
            form.dataset.submitting = "true";

            const loadingOverlay = document.getElementById("recipe-saving-overlay");

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = "Procesando...";
            }

            if (loadingOverlay) {
                loadingOverlay.hidden = false;
                loadingOverlay.classList.add("is-visible");
            }

            HTMLFormElement.prototype.submit.call(form);
        });
    }

    // =====================================================
    // GUARDAR PROCESAMIENTO
    // =====================================================

    if (processForm) {
        processForm.addEventListener("submit", function (event) {
            event.preventDefault();

            if (
                submittingProcess ||
                processForm.dataset.submitting === "true"
            ) {
                return;
            }

            if (!processForm.checkValidity()) {
                processForm.reportValidity();
                return;
            }

            if (!processIngredientsList?.querySelector("tr")) {
                alert("La receta no tiene ingredientes para procesar.");
                return;
            }

            submittingProcess = true;
            processForm.dataset.submitting = "true";

            if (saveProcessButton) {
                saveProcessButton.disabled = true;
                saveProcessButton.textContent = "Guardando...";
            }

            HTMLFormElement.prototype.submit.call(processForm);
        });
    }

    // =====================================================
    // INICIALIZACIÓN
    // =====================================================

    if (form) {
        const methodInput = form.elements.namedItem("_method");

        if (methodInput && !form.elements.namedItem("idreceta")?.value) {
            methodInput.disabled = true;
        }

        updateIngredientButtonText();

        if (recipeModal?.dataset.openOnLoad === "true") {
            showModal(recipeModal);
        }
    }
});
