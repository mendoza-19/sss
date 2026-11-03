// --- Gestor del Carrito de Compras ---
let cartState = [];

function updateCartUI() {
    const cartBadge = document.getElementById('cartBadge');
    const cartItemsList = document.getElementById('cartItemsList');
    const cartTotal = document.getElementById('cartTotal');
    const floatBtn = document.getElementById('floatingCartBtn');

    const totalCount = cartState.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cartState.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    if (cartBadge) {
        cartBadge.textContent = totalCount;
    }

    if (floatBtn && totalCount > 0) {
        floatBtn.classList.add('badge-pulse');
        setTimeout(() => floatBtn.classList.remove('badge-pulse'), 300);
    }

    if (cartTotal) {
        cartTotal.textContent = `$${totalPrice.toFixed(2)}`;
    }

    if (cartItemsList) {
        if (cartState.length === 0) {
            cartItemsList.innerHTML = '<p class="empty-cart-msg">Tu carrito está vacío. ¡Agrega deliciosos productos!</p>';
        } else {
            cartItemsList.innerHTML = cartState.map((item, index) => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <span class="cart-item-name">${item.name}</span>
                        <span class="cart-item-price">$${(item.price * item.quantity).toFixed(2)} ($${item.price.toFixed(2)} c/u)</span>
                    </div>
                    <div class="cart-item-controls">
                        <button class="cart-qty-btn" onclick="changeCartQty(${index}, -1)">-</button>
                        <span class="cart-item-qty">${item.quantity}</span>
                        <button class="cart-qty-btn" onclick="changeCartQty(${index}, 1)">+</button>
                    </div>
                </div>
            `).join('');
        }
    }
}

function addToCart(name, price, id) {
    const normalizedId = Number(id);
    if (!Number.isFinite(normalizedId) || normalizedId <= 0) {
        showToast('No se pudo agregar este producto. Intenta otra vez.', 'error');
        return;
    }

    const existingIndex = cartState.findIndex(item => String(item.id) === String(normalizedId));
    if (existingIndex > -1) {
        cartState[existingIndex].quantity += 1;
    } else {
        cartState.push({ id: normalizedId, name: name, price: price, quantity: 1 });
    }
    updateCartUI();
    showToast(`"${name}" agregado al carrito 🛒`);
}

window.changeCartQty = function (index, delta) {
    if (index >= 0 && index < cartState.length) {
        cartState[index].quantity += delta;
        if (cartState[index].quantity <= 0) {
            cartState.splice(index, 1);
        }
        updateCartUI();
    }
};

const checkoutBtn = document.getElementById('cartCheckoutBtn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', async function () {
        if (cartState.length === 0) {
            alert('Tu carrito está vacío.');
            return;
        }

        const addressInput = document.getElementById('cart-delivery-address');
        const phoneInput = document.getElementById('cart-delivery-phone');
        const notesInput = document.getElementById('cart-delivery-notes');
        const address = addressInput?.value.trim() || '';
        if (address.length < 5) {
            addressInput?.focus();
            showToast('Escribe una dirección de entrega válida.', 'error');
            return;
        }

        checkoutBtn.disabled = true;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch('/api/pedidos/cliente/crear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify({
                    direccion: address,
                    telefono: phoneInput?.value.trim() || null,
                    notas: notesInput?.value.trim() || null,
                    items: cartState.map(item => ({ id: item.id, quantity: item.quantity })),
                }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo registrar el pedido.');

            showToast(data.message || 'Pedido registrado correctamente.', 'success');
            cartState = [];
            updateCartUI();
            if (addressInput) addressInput.value = '';
            if (phoneInput) phoneInput.value = '';
            if (notesInput) notesInput.value = '';
            closeModal('modal-carrito');
        } catch (error) {
            showToast(error.message || 'No se pudo registrar el pedido.', 'error');
        } finally {
            checkoutBtn.disabled = false;
        }
    });
}

function createProductCard(product, cardClass) {
    const card = document.createElement('div');
    card.className = cardClass;

    const productId = product.idproducto ?? product.id ?? product.producto_id;
    if (productId !== undefined && productId !== null && productId !== '') {
        card.dataset.productId = String(productId);
    }

    const image = document.createElement('img');
    image.src = product.foto || '/assets/menu/pan-icono.png';
    image.alt = product.nombre || 'Producto';
    image.className = 'product-img';

    const name = document.createElement('div');
    name.className = 'product-name';
    name.textContent = product.nombre || 'Producto';

    const price = document.createElement('div');
    price.className = 'product-price';
    price.textContent = '$' + Number(product.precio || 0).toFixed(2);

    card.append(image, name, price);

    if (cardClass === 'card-large') {
        const description = document.createElement('p');
        description.className = 'product-desc';
        description.textContent = product.descripcion || '';
        card.appendChild(description);
    }

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'btn-add';
    addButton.textContent = 'Agregar';
    addButton.addEventListener('click', eventAddToCart);
    card.appendChild(addButton);

    return card;
}

function renderProductCards(container, products, cardClass, emptyMessage) {
    if (!container) return;

    const cards = products.map(product => createProductCard(product, cardClass));

    if (cards.length === 0) {
        const message = document.createElement('p');
        message.textContent = emptyMessage;
        cards.push(message);
    }

    container.replaceChildren(...cards);
}

async function refreshMenuProducts() {
    try {
        const [productsResponse, popularResponse] = await Promise.all([
            fetch('/api/productos?skip=0&limit=1000', { cache: 'no-store', headers: { Accept: 'application/json' } }),
            fetch('/api/productos/populares?limit=6', { cache: 'no-store', headers: { Accept: 'application/json' } }),
        ]);

        if (!productsResponse.ok || !popularResponse.ok) return;

        const [products, popularProducts] = await Promise.all([
            productsResponse.json(),
            popularResponse.json(),
        ]);

        renderProductCards(
            document.querySelector('.products-grid'),
            products,
            'card-large',
            'No hay productos disponibles en este momento.',
        );
        renderProductCards(
            document.querySelector('.carousel-container'),
            popularProducts,
            'card-small',
            'No hay productos populares disponibles.',
        );
    } catch (error) {
        console.error('No se pudo actualizar la carta de productos:', error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const encargoModal = document.getElementById('encargoModal');
    const openEncargoBtn = document.getElementById('openEncargoBtn');
    const closeEncargoBtn = document.getElementById('closeEncargoBtn');
    const encargoForm = document.getElementById('encargoForm');
    const addEncargoProductBtn = document.getElementById('encargo-add-product-btn');
    const encargoCatalogModal = document.getElementById('encargo-catalog-modal');
    const closeEncargoCatalogBtn = document.getElementById('close-encargo-catalog-btn');
    const encargoCatalogSearch = document.getElementById('encargo-catalog-search');

    openEncargoBtn?.addEventListener('click', () => {
        if (encargoModal) encargoModal.hidden = false;
    });
    closeEncargoBtn?.addEventListener('click', () => {
        if (encargoModal) encargoModal.hidden = true;
    });
    encargoModal?.addEventListener('click', event => {
        if (event.target === encargoModal) encargoModal.hidden = true;
    });
    addEncargoProductBtn?.addEventListener('click', () => {
        if (encargoCatalogModal) encargoCatalogModal.hidden = false;
        encargoCatalogSearch?.focus();
    });
    closeEncargoCatalogBtn?.addEventListener('click', cerrarCatalogoEncargo);
    encargoCatalogModal?.addEventListener('click', event => {
        if (event.target === encargoCatalogModal) cerrarCatalogoEncargo();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && encargoCatalogModal && !encargoCatalogModal.hidden) {
            cerrarCatalogoEncargo();
        }
    });
    encargoCatalogSearch?.addEventListener('input', filtrarCatalogoEncargo);
    encargoCatalogModal?.addEventListener('input', event => {
        const input = event.target.closest('.encargo-money-input, .encargo-units-input');
        if (input) calcularPresupuestoEncargo(input);
    });
    encargoCatalogModal?.addEventListener('click', event => {
        const button = event.target.closest('.encargo-catalog-add-btn');
        if (!button) return;

        const productoId = Number(button.dataset.productId);
        const lista = document.getElementById('encargo-product-list');
        const yaAgregado = Array.from(lista?.querySelectorAll('.encargo-product-row') || [])
            .some(row => Number(row.dataset.productId) === productoId);

        if (yaAgregado) {
            eliminarProductoEncargo(productoId);
            return;
        }

        agregarProductoEncargo(button);
    });
    encargoForm?.addEventListener('submit', enviarEncargo);

    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('click', eventAddToCart);
    });

    refreshMenuProducts();
    window.setInterval(function () {
        if (!document.hidden) refreshMenuProducts();
    }, 5000);
});

function eventAddToCart(event) {
    const card = event.target.closest('.card-small, .card-large');
    if (card) {
        const nameEl = card.querySelector('.product-name');
        const priceEl = card.querySelector('.product-price');
        if (nameEl && priceEl) {
            const name = nameEl.textContent.trim();
            const priceText = priceEl.textContent.replace(/[^0-9.]/g, '');
            const price = parseFloat(priceText) || 0;
            addToCart(name, price, card.dataset.productId);
        }
    }
}

function cerrarCatalogoEncargo() {
    const modal = document.getElementById('encargo-catalog-modal');
    if (modal) modal.hidden = true;
    document.getElementById('encargo-add-product-btn')?.focus();
}

function filtrarCatalogoEncargo(event) {
    const query = event.currentTarget.value.trim().toLocaleLowerCase();
    const cards = document.querySelectorAll('.encargo-catalog-card');
    let visibleCards = 0;

    cards.forEach(card => {
        card.hidden = !card.textContent.toLocaleLowerCase().includes(query);
        if (!card.hidden) visibleCards += 1;
    });

    const emptyState = document.getElementById('encargo-catalog-empty');
    if (emptyState) emptyState.hidden = visibleCards > 0;
}

function actualizarEstadoCatalogoEncargo() {
    const lista = document.getElementById('encargo-product-list');
    if (!lista) return;

    document.querySelectorAll('.encargo-catalog-add-btn').forEach(button => {
        const fila = Array.from(lista.querySelectorAll('.encargo-product-row'))
            .find(row => Number(row.dataset.productId) === Number(button.dataset.productId));
        button.classList.toggle('is-selected', Boolean(fila));
        button.textContent = fila ? 'Eliminar' : 'Agregar';
        if (fila) button.disabled = false;
    });
}

function encargoProductoEstaAgregado(productId) {
    return Array.from(document.querySelectorAll('.encargo-product-row'))
        .some(row => Number(row.dataset.productId) === productId);
}

function eliminarProductoEncargo(productId) {
    const lista = document.getElementById('encargo-product-list');
    const fila = Array.from(lista?.querySelectorAll('.encargo-product-row') || [])
        .find(row => Number(row.dataset.productId) === productId);
    if (!lista || !fila) return;

    fila.remove();
    if (lista.querySelectorAll('.encargo-product-row').length === 0) {
        const mensajeVacio = document.createElement('tr');
        mensajeVacio.className = 'encargo-empty-products';
        const celda = document.createElement('td');
        celda.colSpan = 5;
        celda.textContent = 'Aún no has agregado productos.';
        mensajeVacio.appendChild(celda);
        lista.appendChild(mensajeVacio);
    }

    actualizarEstadoCatalogoEncargo();
    actualizarTotalEncargo();

    const button = Array.from(document.querySelectorAll('.encargo-catalog-add-btn'))
        .find(item => Number(item.dataset.productId) === productId);
    const card = button?.closest('.encargo-catalog-card');
    const input = card?.dataset.calculationMode === 'units'
        ? card.querySelector('.encargo-units-input')
        : card?.querySelector('.encargo-money-input');
    if (input) calcularPresupuestoEncargo(input);
}

function calcularPresupuestoEncargo(input) {
    const tarjeta = input.closest('.encargo-catalog-card');
    const button = tarjeta?.querySelector('.encargo-catalog-add-btn');
    const resultado = tarjeta?.querySelector('.encargo-budget-result');
    const moneyInput = tarjeta?.querySelector('.encargo-money-input');
    const unitsInput = tarjeta?.querySelector('.encargo-units-input');
    const precioCentavos = Math.round(Number(button?.dataset.price) * 100);

    if (!button || !resultado || !moneyInput || !unitsInput || precioCentavos <= 0) {
        if (resultado) resultado.textContent = 'Este producto no tiene un precio válido.';
        if (button) button.disabled = !encargoProductoEstaAgregado(Number(button.dataset.productId));
        return;
    }

    let cantidad;
    let presupuestoCentavos;

    if (input === moneyInput) {
        tarjeta.dataset.calculationMode = 'money';
        presupuestoCentavos = Math.round(Number(moneyInput.value) * 100);
        if (!Number.isFinite(presupuestoCentavos) || presupuestoCentavos <= 0) {
            unitsInput.value = '';
            resultado.textContent = 'Ingresa un monto para calcular unidades y vuelto.';
            button.disabled = !encargoProductoEstaAgregado(Number(button.dataset.productId));
            return;
        }

        cantidad = Math.floor(presupuestoCentavos / precioCentavos);
        if (cantidad < 1) {
            unitsInput.value = '';
            resultado.textContent = `No alcanza para una unidad. Faltan $${((precioCentavos - presupuestoCentavos) / 100).toFixed(2)}.`;
            button.disabled = !encargoProductoEstaAgregado(Number(button.dataset.productId));
            return;
        }
        unitsInput.value = String(cantidad);
    } else {
        tarjeta.dataset.calculationMode = 'units';
        cantidad = Number(unitsInput.value);
        if (!Number.isInteger(cantidad) || cantidad < 1) {
            moneyInput.value = '';
            resultado.textContent = 'Ingresa una cantidad entera de unidades.';
            button.disabled = !encargoProductoEstaAgregado(Number(button.dataset.productId));
            return;
        }

        presupuestoCentavos = cantidad * precioCentavos;
        moneyInput.value = (presupuestoCentavos / 100).toFixed(2);
    }

    const totalCentavos = cantidad * precioCentavos;
    const vueltoCentavos = presupuestoCentavos - totalCentavos;
    resultado.textContent = tarjeta.dataset.calculationMode === 'money'
        ? `${cantidad} ${cantidad === 1 ? 'unidad' : 'unidades'} · Total: $${(totalCentavos / 100).toFixed(2)} · Vuelto: $${(vueltoCentavos / 100).toFixed(2)}`
        : `${cantidad} ${cantidad === 1 ? 'unidad' : 'unidades'} · Total: $${(totalCentavos / 100).toFixed(2)}`;
    button.disabled = false;
}

function obtenerCalculoEncargo(tarjeta, precioCentavos) {
    const moneyInput = tarjeta.querySelector('.encargo-money-input');
    const unitsInput = tarjeta.querySelector('.encargo-units-input');

    if (tarjeta.dataset.calculationMode === 'units') {
        const cantidad = Number(unitsInput.value);
        if (!Number.isInteger(cantidad) || cantidad < 1) return null;
        return { cantidad, presupuestoCentavos: cantidad * precioCentavos };
    }

    const presupuestoCentavos = Math.round(Number(moneyInput.value) * 100);
    if (!Number.isFinite(presupuestoCentavos) || presupuestoCentavos <= 0) return null;
    return {
        cantidad: Math.floor(presupuestoCentavos / precioCentavos),
        presupuestoCentavos,
    };
}

function actualizarDetalleEncargo(fila) {
    const cantidad = Number(fila.dataset.quantity);
    const precioCentavos = Number(fila.dataset.priceCents);
    const subtotalCentavos = cantidad * precioCentavos;

    fila.querySelector('.encargo-order-quantity').textContent = String(cantidad);
    fila.querySelector('.encargo-order-subtotal').textContent = `$${(subtotalCentavos / 100).toFixed(2)}`;
}

function actualizarTotalEncargo() {
    const lista = document.getElementById('encargo-product-list');
    const total = Array.from(lista?.querySelectorAll('.encargo-product-row') || [])
        .reduce((sum, fila) => sum + Number(fila.dataset.quantity) * Number(fila.dataset.priceCents), 0);
    const totalElement = document.getElementById('encargo-grand-total');
    if (totalElement) totalElement.textContent = `$${(total / 100).toFixed(2)}`;
}

function limpiarProductosEncargo() {
    const lista = document.getElementById('encargo-product-list');
    if (!lista) return;

    const mensajeVacio = document.createElement('tr');
    mensajeVacio.className = 'encargo-empty-products';
    const celda = document.createElement('td');
    celda.colSpan = 5;
    celda.textContent = 'Aún no has agregado productos.';
    mensajeVacio.appendChild(celda);
    lista.replaceChildren(mensajeVacio);

    document.querySelectorAll('.encargo-catalog-add-btn').forEach(button => {
        button.disabled = true;
        button.classList.remove('is-selected');
        button.textContent = 'Agregar';

        const card = button.closest('.encargo-catalog-card');
        card.dataset.calculationMode = 'money';
        card.querySelector('.encargo-money-input').value = '';
        card.querySelector('.encargo-units-input').value = '';
        card.querySelector('.encargo-budget-result').textContent = 'Ingresa un monto para calcular unidades y vuelto.';
    });

    const search = document.getElementById('encargo-catalog-search');
    if (search) search.value = '';
    const cards = document.querySelectorAll('.encargo-catalog-card');
    cards.forEach(card => { card.hidden = false; });
    const emptyState = document.getElementById('encargo-catalog-empty');
    if (emptyState) emptyState.hidden = cards.length > 0;

    actualizarTotalEncargo();
}

function agregarProductoEncargo(button) {
    const lista = document.getElementById('encargo-product-list');
    const tarjeta = button.closest('.encargo-catalog-card');
    const productoId = Number(button.dataset.productId);
    const precioCentavos = Math.round(Number(button.dataset.price) * 100);
    const calculo = tarjeta && precioCentavos > 0 ? obtenerCalculoEncargo(tarjeta, precioCentavos) : null;

    if (!productoId || !lista || !calculo || calculo.cantidad < 1) return;
    const { cantidad: cantidadAgregar, presupuestoCentavos } = calculo;

    const filaExistente = Array.from(lista.querySelectorAll('.encargo-product-row'))
        .find(row => Number(row.dataset.productId) === productoId);

    if (filaExistente) {
        filaExistente.dataset.quantity = String(Number(filaExistente.dataset.quantity) + cantidadAgregar);
        filaExistente.dataset.budgetCents = String(Number(filaExistente.dataset.budgetCents) + presupuestoCentavos);
        actualizarDetalleEncargo(filaExistente);
        actualizarTotalEncargo();
        actualizarEstadoCatalogoEncargo();
        return;
    }

    lista.querySelector('.encargo-empty-products')?.remove();

    const fila = document.createElement('tr');
    fila.className = 'encargo-product-row';
    fila.dataset.productId = String(productoId);
    fila.dataset.quantity = String(cantidadAgregar);
    fila.dataset.budgetCents = String(presupuestoCentavos);
    fila.dataset.priceCents = String(precioCentavos);

    const productoCell = document.createElement('td');
    productoCell.className = 'encargo-order-product';
    const productoInfo = document.createElement('div');
    productoInfo.className = 'encargo-order-product-content';

    const image = document.createElement('img');
    image.className = 'encargo-product-image';
    image.src = button.dataset.photo;
    image.alt = `Foto de ${button.dataset.name}`;

    const nombre = document.createElement('strong');
    nombre.textContent = button.dataset.name;
    productoInfo.append(image, nombre);
    productoCell.appendChild(productoInfo);

    const precioCell = document.createElement('td');
    precioCell.textContent = `$${(precioCentavos / 100).toFixed(2)}`;

    const cantidadCell = document.createElement('td');
    cantidadCell.className = 'encargo-order-quantity';

    const subtotalCell = document.createElement('td');
    subtotalCell.className = 'encargo-order-subtotal';

    const actionCell = document.createElement('td');

    const quitar = document.createElement('button');
    quitar.type = 'button';
    quitar.className = 'encargo-remove-product-btn';
    quitar.textContent = 'Quitar';
    quitar.setAttribute('aria-label', `Quitar ${button.dataset.name} del encargo`);
    quitar.addEventListener('click', () => {
        eliminarProductoEncargo(productoId);
    });
    actionCell.appendChild(quitar);

    fila.append(productoCell, precioCell, cantidadCell, subtotalCell, actionCell);
    actualizarDetalleEncargo(fila);
    lista.appendChild(fila);
    actualizarTotalEncargo();
    actualizarEstadoCatalogoEncargo();
}

async function enviarEncargo(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const items = Array.from(form.querySelectorAll('.encargo-product-row'))
        .map(row => ({
            id: Number(row.dataset.productId),
            quantity: Number(row.dataset.quantity),
        }))
        .filter(item => item.quantity > 0);

    if (items.length === 0) {
        showToast('Selecciona al menos un producto para el encargo.');
        return;
    }

    const submitButton = form.querySelector('[type="submit"]');
    submitButton.disabled = true;

    try {
        const response = await fetch('/api/pedidos/cliente/encargo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf_token,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                fecha_entrega: form.elements.fecha_entrega.value,
                direccion: form.elements.direccion.value.trim(),
                telefono: form.elements.telefono.value.trim() || null,
                notas: form.elements.notas.value.trim() || null,
                items,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            const message = data.message || Object.values(data.errors || {}).flat()[0] || 'No se pudo registrar el encargo.';
            throw new Error(message);
        }

        showToast(`${data.message} Número: #${data.pedido_id}`);
        form.reset();
        limpiarProductosEncargo();
        const encargoModal = document.getElementById('encargoModal');
        if (encargoModal) encargoModal.hidden = true;
    } catch (error) {
        showToast(error.message || 'Error de conexión al registrar el encargo.');
    } finally {
        submitButton.disabled = false;
    }
}