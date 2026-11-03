document.addEventListener('DOMContentLoaded', function () {
    const successMessage = document.getElementById('sales-success-message')?.textContent.trim();
    if (successMessage && typeof showToast === 'function') showToast(successMessage, 'success');

    const saleModal = document.getElementById('sale-modal');
    const detailsModal = document.getElementById('sale-details-modal');
    const sellerSalesModal = document.getElementById('seller-sales-modal');
    const clientOrderHistoryModal = document.getElementById('client-order-history-modal');
    const processModal = document.getElementById('process-order-modal');
    const localOrderClientModal = document.getElementById('local-order-client-modal');
    const localOrderRegisterClientModal = document.getElementById('local-order-register-client-modal');
    const localOrderModal = document.getElementById('local-order-modal');
    const localOrderDepositModal = document.getElementById('local-order-deposit-modal');
    const localOrderDepositAmount = document.getElementById('local-order-deposit-amount');
    const localOrderDepositValue = document.getElementById('local-order-deposit-value');
    const salesSearchModal = document.getElementById('sales-search-modal');
    const salesSearchInput = document.getElementById('sales-search-input');
    const salesClientCombobox = document.getElementById('sales-client-combobox');
    const salesClientOptions = document.getElementById('sales-client-options');
    const registerClientFromSearchButton = document.getElementById('btn-register-client-from-search');
    const openSalesSearchButton = document.getElementById('btn-open-sales-search');
    const localOrderForm = document.getElementById('local-order-form');
    const localRegisterClientForm = document.getElementById('local-register-client-form');
    const localOrderItemsTbody = document.getElementById('local-order-items');
    const localOrderItems = new Map();
    const localOrderProductSelect = document.getElementById('local-order-product');
    const localOrderProductSearch = document.getElementById('local-order-product-search');
    const localOrderProductOptions = document.getElementById('local-order-product-options');
    const localOrderQuantityInput = document.getElementById('local-order-quantity');
    const localOrderMoneyInput = document.getElementById('local-order-money');
    const localOrderMoneyGroup = document.getElementById('local-order-money-group');
    let localOrderCalculationMode = 'units';
    const localClientSelect = document.getElementById('local-order-client-select');
    const localClientCombobox = document.getElementById('local-order-client-combobox');
    const localClientOptions = document.getElementById('local-order-client-options');
    const localClientIdInput = document.getElementById('local-order-client-id');
    const localClientContinueButton = document.getElementById('btn-continue-local-order');
    const localClientRegisterButton = document.getElementById('btn-register-local-client');
    const form = document.getElementById('sale-form');
    const saleItems = document.getElementById('sale-items');
    const selectedItems = new Map();
    let registerClientReturnModal = localOrderClientModal;

    const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    const sellerHistorySettings = document.getElementById('seller-history-refresh-settings');
    let activeSellerSalesId = null;
    let sellerHistoryRequestInProgress = false;

    function fechaLocalParaInput() {
        const hoy = new Date();
        const mes = String(hoy.getMonth() + 1).padStart(2, '0');
        const dia = String(hoy.getDate()).padStart(2, '0');

        return `${hoy.getFullYear()}-${mes}-${dia}`;
    }

    function showModal(modal) {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function hideModal(modal) {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        const anyOpen = (saleModal?.getAttribute('aria-hidden') === 'false') ||
                        (detailsModal?.getAttribute('aria-hidden') === 'false') ||
                        (sellerSalesModal?.getAttribute('aria-hidden') === 'false') ||
                        (clientOrderHistoryModal?.getAttribute('aria-hidden') === 'false') ||
                        (processModal?.getAttribute('aria-hidden') === 'false') ||
                        (localOrderClientModal?.getAttribute('aria-hidden') === 'false') ||
                        (localOrderRegisterClientModal?.getAttribute('aria-hidden') === 'false') ||
                        (localOrderModal?.getAttribute('aria-hidden') === 'false') ||
                        (localOrderDepositModal?.getAttribute('aria-hidden') === 'false') ||
                        (salesSearchModal?.getAttribute('aria-hidden') === 'false');
        document.body.style.overflow = anyOpen ? 'hidden' : '';
    }

    /* -------------------------------------------------------------
     * GESTIÓN DE PESTAÑAS (TABS)
     * ------------------------------------------------------------- */
    const tabButtons = document.querySelectorAll('.sales-tab-btn');
    const tabContents = document.querySelectorAll('.sales-tab-content');

    function switchTab(tabName) {
        tabButtons.forEach(function (btn) {
            const isActive = btn.dataset.salesTab === tabName;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        tabContents.forEach(function (content) {
            content.classList.toggle('active', content.id === 'tab-content-' + tabName);
        });
        updateSalesSearchLabel(tabName);
        applySalesSearch();
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchTab(btn.dataset.salesTab);
        });
    });

    function getActiveSalesTab() {
        return document.querySelector('.sales-tab-btn.active')?.dataset.salesTab || 'ventas';
    }

    function updateSalesSearchLabel(tabName) {
        const labels = {
            ventas: 'Buscar en venta local',
            pedidos: 'Buscar pedidos pendientes',
            'historial-pedidos': 'Buscar pedidos',
            vendedores: 'Buscar en venta ambulante',
        };
        const label = labels[tabName] || labels.ventas;
        const title = document.getElementById('sales-search-title');
        const fieldLabel = document.querySelector('label[for="sales-search-input"]');
        if (title) title.textContent = label;
        if (fieldLabel) fieldLabel.textContent = label;
        if (salesSearchInput) salesSearchInput.placeholder = label;
    }

    function setClientOptionsVisible(visible) {
        if (!salesClientOptions || !salesClientCombobox) return;
        salesClientOptions.hidden = !visible;
        salesClientCombobox.setAttribute('aria-expanded', visible ? 'true' : 'false');
    }

    function getClientOptionElements() {
        return Array.from(salesClientOptions?.querySelectorAll('[data-client-option]') || []);
    }

    function setLocalClientOptionsVisible(visible) {
        if (!localClientOptions || !localClientCombobox) return;
        localClientOptions.hidden = !visible;
        localClientCombobox.setAttribute('aria-expanded', visible ? 'true' : 'false');
    }

    function getLocalClientOptionElements() {
        return Array.from(localClientOptions?.querySelectorAll('[data-client-option]') || []);
    }

    function getLocalOrderProductOptionElements() {
        return Array.from(localOrderProductOptions?.querySelectorAll('[data-local-order-product-option]') || []);
    }

    function setLocalOrderProductOptionsVisible(visible) {
        if (!localOrderProductOptions || !localOrderProductSearch) return;
        localOrderProductOptions.hidden = !visible;
        localOrderProductSearch.setAttribute('aria-expanded', visible ? 'true' : 'false');
    }

    function filterLocalOrderProductOptions(query) {
        const normalizedQuery = query.trim().toLocaleLowerCase();
        const options = getLocalOrderProductOptionElements();
        let visibleCount = 0;
        options.forEach(function (option) {
            const matches = option.dataset.productName.toLocaleLowerCase().includes(normalizedQuery);
            option.hidden = !matches;
            if (matches) visibleCount += 1;
        });
        document.getElementById('local-order-product-no-results')?.toggleAttribute('hidden', visibleCount > 0);
        setLocalOrderProductOptionsVisible(true);
    }

    function updateLocalOrderBudget(mode) {
        const option = localOrderProductSelect?.options[localOrderProductSelect.selectedIndex];
        if (!option?.value || !localOrderQuantityInput || !localOrderMoneyInput) return;

        const priceCents = Math.round(Number(option.dataset.price) * 100);
        if (priceCents <= 0) return;

        if (mode === 'money') {
            const amountCents = Math.round(Number(localOrderMoneyInput.value) * 100);
            if (!Number.isFinite(amountCents) || amountCents <= 0) {
                localOrderQuantityInput.value = '';
                return;
            }
            const quantity = Math.floor(amountCents / priceCents);
            localOrderQuantityInput.value = quantity > 0 ? String(quantity) : '';
            return;
        }

        const quantity = Number(localOrderQuantityInput.value);
        localOrderMoneyInput.value = Number.isInteger(quantity) && quantity > 0
            ? ((quantity * priceCents) / 100).toFixed(2)
            : '';
    }

    function applySalesSearch() {
        const tabName = getActiveSalesTab();
        const selectors = {
            ventas: '[data-sale-row]',
            pedidos: '#tab-content-pedidos [data-pedido-row]',
            'historial-pedidos': '#tab-content-historial-pedidos [data-order-history-row]',
            vendedores: '#tab-content-vendedores [data-search-row]',
        };
        const emptyRows = {
            ventas: 'sales-no-results',
            pedidos: 'pedidos-no-results',
            'historial-pedidos': 'historial-pedidos-no-results',
            vendedores: 'vendedores-no-results',
        };
        const query = salesSearchInput?.value.trim().toLocaleLowerCase() || '';
        const clientQuery = salesClientCombobox?.value.trim().toLocaleLowerCase() || '';
        const rows = document.querySelectorAll(selectors[tabName] || selectors.ventas);
        let visible = 0;
        rows.forEach(function (row) {
            const matchesText = row.textContent.toLocaleLowerCase().includes(query);
            const rowClient = (row.dataset.saleClient || row.dataset.pedidoClient || row.textContent).toLocaleLowerCase();
            const matchesClient = !clientQuery || rowClient.includes(clientQuery);
            const matches = matchesText && matchesClient;
            row.hidden = !matches;
            visible += matches ? 1 : 0;
        });
        const noResults = document.getElementById(emptyRows[tabName]);
        if (noResults) noResults.hidden = visible > 0 || !query;
    }

    openSalesSearchButton?.addEventListener('click', function () {
        updateSalesSearchLabel(getActiveSalesTab());
        showModal(salesSearchModal);
        salesSearchInput?.focus();
    });
    salesSearchInput?.addEventListener('input', applySalesSearch);
    salesClientCombobox?.addEventListener('focus', function () {
        getClientOptionElements().forEach(option => { option.hidden = false; });
        setClientOptionsVisible(true);
    });
    salesClientCombobox?.addEventListener('input', function () {
        const query = salesClientCombobox.value.trim().toLocaleLowerCase();
        const options = getClientOptionElements();
        const hasMatch = options.some(function (option) {
            return option.dataset.clientName.toLocaleLowerCase() === query;
        });
        options.forEach(function (option) {
            option.hidden = Boolean(query) && !option.dataset.clientName.toLocaleLowerCase().includes(query);
        });
        setClientOptionsVisible(true);
        if (registerClientFromSearchButton) registerClientFromSearchButton.hidden = !query || hasMatch;
        document.getElementById('sales-client-not-found')?.toggleAttribute('hidden', !query || hasMatch);
        applySalesSearch();
    });
    salesClientOptions?.addEventListener('click', function (event) {
        const option = event.target.closest('[data-client-option]');
        if (!option) return;
        salesClientCombobox.value = option.dataset.clientName;
        setClientOptionsVisible(false);
        salesClientCombobox.dispatchEvent(new Event('input'));
        setClientOptionsVisible(false);
    });
    document.addEventListener('click', function (event) {
        if (salesClientOptions && !salesClientOptions.parentElement.contains(event.target)) setClientOptionsVisible(false);
        if (localClientOptions && !localClientOptions.parentElement.contains(event.target)) setLocalClientOptionsVisible(false);
        if (saleProductOptions && !saleProductOptions.parentElement.contains(event.target)) setSaleProductOptionsVisible(false);
        if (localOrderProductOptions && !localOrderProductOptions.parentElement.contains(event.target)) setLocalOrderProductOptionsVisible(false);
    });
    registerClientFromSearchButton?.addEventListener('click', function () {
        registerClientReturnModal = salesSearchModal;
        localRegisterClientForm?.reset();
        const name = document.getElementById('local-register-client-name');
        if (name && salesClientCombobox) name.value = salesClientCombobox.value.trim();
        document.getElementById('local-register-client-error')?.setAttribute('hidden', '');
        hideModal(salesSearchModal);
        showModal(localOrderRegisterClientModal);
        name?.focus();
    });
    document.getElementById('btn-apply-sales-search')?.addEventListener('click', function () {
        applySalesSearch();
        hideModal(salesSearchModal);
    });
    document.querySelectorAll('[data-close-sales-search]').forEach(function (button) {
        button.addEventListener('click', function () {
            hideModal(salesSearchModal);
        });
    });

    /* -------------------------------------------------------------
     * MODAL 1: REGISTRO DE VENTA LOCAL EN MOSTRADOR
     * ------------------------------------------------------------- */
    function getSubtotal() {
        return Array.from(selectedItems.values()).reduce(function (total, item) {
            return total + item.price * item.quantity;
        }, 0);
    }

    function renderItems() {
        if (!saleItems) return;
        saleItems.replaceChildren();
        const items = Array.from(selectedItems.values());

        if (items.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'sales-empty';
            cell.textContent = 'Agrega productos a la venta.';
            row.appendChild(cell);
            saleItems.appendChild(row);
        }

        items.forEach(function (item, index) {
            const row = document.createElement('tr');
            const nameCell = document.createElement('td');
            const quantityCell = document.createElement('td');
            const subtotalCell = document.createElement('td');
            const actionCell = document.createElement('td');
            const removeButton = document.createElement('button');
            const idInput = document.createElement('input');
            const quantityInput = document.createElement('input');

            nameCell.textContent = item.name;
            quantityCell.textContent = item.quantity;
            subtotalCell.textContent = currency.format(item.price * item.quantity);
            removeButton.type = 'button';
            removeButton.className = 'sales-remove-item';
            removeButton.textContent = 'Quitar';
            removeButton.setAttribute('aria-label', 'Quitar ' + item.name);
            removeButton.addEventListener('click', function () {
                selectedItems.delete(item.id);
                renderItems();
            });
            idInput.type = 'hidden';
            idInput.name = 'items[' + index + '][producto_id]';
            idInput.value = item.id;
            quantityInput.type = 'hidden';
            quantityInput.name = 'items[' + index + '][cantidad]';
            quantityInput.value = item.quantity;
            actionCell.append(removeButton, idInput, quantityInput);
            row.append(nameCell, quantityCell, subtotalCell, actionCell);
            saleItems.appendChild(row);
        });

        const subtotal = getSubtotal();
        const discountPercent = Number(document.getElementById('sale-discount')?.value) || 0;
        const discountAmount = Math.round(subtotal * discountPercent / 100 * 100) / 100;
        const discountAmountEl = document.getElementById('sale-discount-amount');
        const saleTotalEl = document.getElementById('sale-total');
        if (discountAmountEl) discountAmountEl.textContent = currency.format(discountAmount);
        if (saleTotalEl) saleTotalEl.textContent = currency.format(Math.max(0, subtotal - discountAmount));
        const errorEl = document.getElementById('sale-form-error');
        if (errorEl) errorEl.hidden = true;
    }

    function renderLocalOrderItems() {
        if (!localOrderItemsTbody) return;
        localOrderItemsTbody.replaceChildren();

        if (localOrderItems.size === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.className = 'sales-empty';
            cell.textContent = 'Agrega productos al encargo.';
            row.appendChild(cell);
            localOrderItemsTbody.appendChild(row);
        }

        let total = 0;
        Array.from(localOrderItems.values()).forEach(function (item, index) {
            const row = document.createElement('tr');
            const lineTotal = item.price * item.quantity;
            total += lineTotal;

            [item.name, currency.format(item.price), item.quantity, currency.format(lineTotal)].forEach(function (value) {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');
            const removeButton = document.createElement('button');
            const idInput = document.createElement('input');
            const quantityInput = document.createElement('input');
            removeButton.type = 'button';
            removeButton.className = 'sales-remove-item';
            removeButton.textContent = 'Quitar';
            removeButton.setAttribute('aria-label', 'Quitar ' + item.name + ' del encargo');
            removeButton.addEventListener('click', function () {
                localOrderItems.delete(item.id);
                renderLocalOrderItems();
            });
            idInput.type = 'hidden';
            idInput.name = 'items[' + index + '][id]';
            idInput.value = item.id;
            quantityInput.type = 'hidden';
            quantityInput.name = 'items[' + index + '][quantity]';
            quantityInput.value = item.quantity;
            actionCell.append(removeButton, idInput, quantityInput);
            row.appendChild(actionCell);
            localOrderItemsTbody.appendChild(row);
        });

        const totalElement = document.getElementById('local-order-total');
        if (totalElement) totalElement.textContent = currency.format(total);
        updateLocalOrderDepositSummary();
    }

    function getLocalOrderTotal() {
        return Array.from(localOrderItems.values())
            .reduce((total, item) => total + item.price * item.quantity, 0);
    }

    function updateLocalOrderDepositSummary() {
        const total = getLocalOrderTotal();
        const appliedAmount = Number(localOrderDepositValue?.value) || 0;
        const enteredAmount = Number(localOrderDepositAmount?.value) || 0;
        const totalLabel = document.getElementById('local-order-deposit-total');
        const balanceLabel = document.getElementById('local-order-deposit-balance');
        const summary = document.getElementById('local-order-deposit-summary');

        if (totalLabel) totalLabel.textContent = currency.format(total);
        if (balanceLabel) balanceLabel.textContent = currency.format(Math.max(0, total - enteredAmount));
        if (summary) {
            summary.textContent = appliedAmount > 0
                ? currency.format(appliedAmount) + ' recibido · saldo al recoger ' + currency.format(Math.max(0, total - appliedAmount))
                : 'Sin adelanto registrado.';
        }
    }

    function restoreLocalOrderItems() {
        try {
            const oldItems = JSON.parse(localOrderForm?.dataset.oldItems || '[]');
            oldItems.forEach(function (item) {
                const option = Array.from(document.getElementById('local-order-product')?.options || [])
                    .find(function (candidate) { return candidate.value === String(item.id); });
                if (!option) return;
                localOrderItems.set(option.value, {
                    id: option.value,
                    name: option.textContent.trim(),
                    price: Number(option.dataset.price),
                    quantity: Number(item.quantity),
                });
            });
        } catch (error) {
            localOrderItems.clear();
        }
        renderLocalOrderItems();
    }

    function seleccionarClienteLocal(client) {
        if (!client || !localClientIdInput || !localClientSelect) return;

        const clientId = String(client.id);
        let option = Array.from(localClientSelect.options).find(candidate => candidate.value === clientId);
        if (!option) {
            option = document.createElement('option');
            option.value = clientId;
            localClientSelect.appendChild(option);
        }

        option.dataset.name = client.nombre;
        option.dataset.phone = client.telefono || '';
        option.textContent = client.telefono ? `${client.nombre} · ${client.telefono}` : client.nombre;
        localClientSelect.value = clientId;
        const customOption = getLocalClientOptionElements().find(candidate => candidate.dataset.clientId === clientId);
        if (!customOption && localClientOptions) {
            const newCustomOption = document.createElement('button');
            newCustomOption.type = 'button';
            newCustomOption.className = 'sales-client-option';
            newCustomOption.setAttribute('role', 'option');
            newCustomOption.dataset.clientOption = '';
            newCustomOption.dataset.clientId = clientId;
            newCustomOption.dataset.clientName = client.nombre;
            newCustomOption.textContent = client.telefono ? `${client.nombre} · ${client.telefono}` : client.nombre;
            localClientOptions.appendChild(newCustomOption);
        }
        if (localClientCombobox) localClientCombobox.value = client.nombre;
        document.getElementById('local-order-client-not-found')?.setAttribute('hidden', '');
        setLocalClientOptionsVisible(false);
        localClientIdInput.value = clientId;
        localClientContinueButton.disabled = false;

        const selected = document.getElementById('local-order-selected-client');
        if (selected) {
            selected.textContent = client.telefono ? `${client.nombre} · ${client.telefono}` : client.nombre;
            selected.hidden = false;
        }
    }

    document.getElementById('btn-new-local-encargo')?.addEventListener('click', function () {
        localOrderForm?.reset();
        localRegisterClientForm?.reset();
        localOrderItems.clear();
        renderLocalOrderItems();
        if (localClientIdInput) localClientIdInput.value = '';
        if (localClientContinueButton) localClientContinueButton.disabled = true;
        if (localClientSelect) localClientSelect.value = '';
        if (localClientCombobox) localClientCombobox.value = '';
        getLocalClientOptionElements().forEach(option => { option.hidden = false; });
        document.getElementById('local-order-client-not-found')?.setAttribute('hidden', '');
        setLocalClientOptionsVisible(false);
        document.getElementById('local-order-selected-client')?.setAttribute('hidden', '');
        showModal(localOrderClientModal);
    });

    localClientSelect?.addEventListener('change', function () {
        const option = localClientSelect.options[localClientSelect.selectedIndex];
        if (!option?.value) {
            if (localClientIdInput) localClientIdInput.value = '';
            if (localClientContinueButton) localClientContinueButton.disabled = true;
            document.getElementById('local-order-selected-client')?.setAttribute('hidden', '');
            return;
        }

        seleccionarClienteLocal({
            id: option.value,
            nombre: option.dataset.name || option.textContent.trim(),
            telefono: option.dataset.phone || '',
        });
    });

    localClientCombobox?.addEventListener('focus', function () {
        getLocalClientOptionElements().forEach(option => { option.hidden = false; });
        setLocalClientOptionsVisible(true);
    });
    function filterLocalClients() {
        if (!localClientCombobox) return;
        const query = localClientCombobox.value.trim().toLocaleLowerCase();
        const options = getLocalClientOptionElements();
        const visibleOptions = options.filter(function (option) {
            const clientName = (option.dataset.clientName || option.textContent).trim().toLocaleLowerCase();
            option.hidden = Boolean(query) && !clientName.includes(query);
            return !option.hidden;
        });
        if (localClientIdInput) localClientIdInput.value = '';
        if (localClientContinueButton) localClientContinueButton.disabled = true;
        document.getElementById('local-order-selected-client')?.setAttribute('hidden', '');
        document.getElementById('local-order-client-not-found')?.toggleAttribute('hidden', !query || visibleOptions.length > 0);
        setLocalClientOptionsVisible(true);
    }
    localClientCombobox?.addEventListener('input', filterLocalClients);
    localClientCombobox?.addEventListener('keyup', filterLocalClients);
    localClientOptions?.addEventListener('click', function (event) {
        const option = event.target.closest('[data-client-option]');
        if (!option || !localClientSelect) return;
        localClientSelect.value = option.dataset.clientId;
        localClientSelect.dispatchEvent(new Event('change'));
        setLocalClientOptionsVisible(false);
    });

    localClientRegisterButton?.addEventListener('click', function () {
        localRegisterClientForm?.reset();
        const error = document.getElementById('local-register-client-error');
        if (error) error.hidden = true;
        hideModal(localOrderClientModal);
        showModal(localOrderRegisterClientModal);
        document.getElementById('local-register-client-name')?.focus();
    });

    document.querySelectorAll('[data-close-register-client]').forEach(function (button) {
        button.addEventListener('click', function () {
            hideModal(localOrderRegisterClientModal);
            showModal(registerClientReturnModal);
        });
    });

    localRegisterClientForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const submitButton = document.getElementById('local-register-client-submit');
        const error = document.getElementById('local-register-client-error');
        submitButton.disabled = true;

        try {
            const response = await fetch(localRegisterClientForm.dataset.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': localRegisterClientForm.elements._token.value,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    nombre: localRegisterClientForm.elements.nombre.value.trim(),
                    apellido: localRegisterClientForm.elements.apellido.value.trim() || null,
                    telefono: localRegisterClientForm.elements.telefono.value.trim() || null,
                }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'No se pudo registrar el cliente.');
            }

            seleccionarClienteLocal(data.cliente);
            hideModal(localOrderRegisterClientModal);
            if (salesClientCombobox && registerClientReturnModal === salesSearchModal) {
                salesClientCombobox.value = data.cliente.nombre;
                salesClientCombobox.dispatchEvent(new Event('input'));
                setClientOptionsVisible(false);
            }
            showModal(registerClientReturnModal);
        } catch (registerError) {
            if (error) {
                error.textContent = registerError.message || 'No se pudo registrar el cliente.';
                error.hidden = false;
            }
        } finally {
            submitButton.disabled = false;
        }
    });

    document.getElementById('btn-continue-local-order')?.addEventListener('click', function () {
        if (!localClientIdInput?.value) return;
        const summary = document.getElementById('local-order-customer-summary');
        const selected = document.getElementById('local-order-selected-client');
        if (summary && selected) summary.textContent = selected.textContent;
        hideModal(localOrderClientModal);
        showModal(localOrderModal);
    });

    document.querySelectorAll('[data-close-local-order]').forEach(function (button) {
        button.addEventListener('click', function () {
            hideModal(localOrderModal);
            hideModal(localOrderClientModal);
        });
    });

    document.getElementById('btn-open-local-order-deposit')?.addEventListener('click', function () {
        const error = document.getElementById('local-order-deposit-error');
        if (error) error.hidden = true;
        if (localOrderDepositAmount) {
            localOrderDepositAmount.value = localOrderDepositValue?.value || '0.00';
            localOrderDepositAmount.max = getLocalOrderTotal().toFixed(2);
        }
        updateLocalOrderDepositSummary();
        showModal(localOrderDepositModal);
    });

    localOrderDepositAmount?.addEventListener('input', updateLocalOrderDepositSummary);

    document.querySelectorAll('[data-close-local-order-deposit]').forEach(function (button) {
        button.addEventListener('click', function () {
            hideModal(localOrderDepositModal);
        });
    });

    document.getElementById('btn-apply-local-order-deposit')?.addEventListener('click', function () {
        const total = getLocalOrderTotal();
        const amount = Number(localOrderDepositAmount?.value);
        const error = document.getElementById('local-order-deposit-error');

        if (!Number.isFinite(amount) || amount < 0 || amount > total) {
            if (error) {
                error.textContent = 'El adelanto debe estar entre $0.00 y el total estimado.';
                error.hidden = false;
            }
            return;
        }

        if (localOrderDepositValue) localOrderDepositValue.value = amount.toFixed(2);
        updateLocalOrderDepositSummary();
        hideModal(localOrderDepositModal);
    });

    localOrderProductSearch?.addEventListener('focus', function () {
        filterLocalOrderProductOptions('');
    });
    localOrderProductSearch?.addEventListener('input', function () {
        if (localOrderProductSelect?.value) {
            localOrderProductSelect.value = '';
            localOrderProductSelect.dispatchEvent(new Event('change'));
        }
        filterLocalOrderProductOptions(localOrderProductSearch.value);
    });
    localOrderProductSearch?.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setLocalOrderProductOptionsVisible(false);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            getLocalOrderProductOptionElements().find(option => !option.hidden)?.focus();
        } else if (event.key === 'Enter' && localOrderProductOptions && !localOrderProductOptions.hidden) {
            event.preventDefault();
            getLocalOrderProductOptionElements().find(option => !option.hidden)?.click();
        }
    });
    localOrderProductOptions?.addEventListener('click', function (event) {
        const option = event.target.closest('[data-local-order-product-option]');
        if (!option || !localOrderProductSelect || !localOrderProductSearch) return;
        localOrderProductSelect.value = option.dataset.productId;
        localOrderProductSearch.value = option.dataset.productName;
        localOrderProductSelect.dispatchEvent(new Event('change'));
        localOrderProductSearch.focus();
        setLocalOrderProductOptionsVisible(false);
    });
    localOrderProductSelect?.addEventListener('change', function () {
        const hasProduct = Boolean(localOrderProductSelect.value);
        if (localOrderQuantityInput) {
            localOrderQuantityInput.disabled = !hasProduct;
            localOrderQuantityInput.value = hasProduct ? '1' : '';
        }
        if (localOrderMoneyInput) {
            localOrderMoneyInput.disabled = !hasProduct;
            localOrderMoneyInput.value = '';
        }
        if (localOrderMoneyGroup) localOrderMoneyGroup.hidden = !hasProduct;
        localOrderCalculationMode = 'units';
        updateLocalOrderBudget(localOrderCalculationMode);
    });
    localOrderQuantityInput?.addEventListener('input', function () {
        localOrderCalculationMode = 'units';
        updateLocalOrderBudget(localOrderCalculationMode);
    });
    localOrderMoneyInput?.addEventListener('input', function () {
        localOrderCalculationMode = 'money';
        updateLocalOrderBudget(localOrderCalculationMode);
    });

    document.getElementById('btn-add-local-order-item')?.addEventListener('click', function () {
        const select = localOrderProductSelect;
        const quantity = Number(localOrderQuantityInput?.value);
        const option = select?.options[select.selectedIndex];
        const error = document.getElementById('local-order-form-error');

        if (!option?.value || !Number.isInteger(quantity) || quantity < 1) {
            if (error) {
                error.textContent = 'Selecciona un producto e ingresa unidades o un monto suficiente.';
                error.hidden = false;
            }
            return;
        }

        const current = localOrderItems.get(option.value);
        if (current) {
            if (error) {
                error.textContent = `${option.textContent.trim()} ya fue agregado al encargo.`;
                error.hidden = false;
            }
            return;
        }

        localOrderItems.set(option.value, {
            id: option.value,
            name: option.textContent.trim(),
            price: Number(option.dataset.price),
            quantity: quantity,
        });
        select.value = '';
        if (localOrderProductSearch) localOrderProductSearch.value = '';
        setLocalOrderProductOptionsVisible(false);
        select.dispatchEvent(new Event('change'));
        renderLocalOrderItems();
        if (error) error.hidden = true;
    });

    localOrderForm?.addEventListener('submit', function (event) {
        if (!localClientIdInput?.value) {
            event.preventDefault();
            hideModal(localOrderModal);
            showModal(localOrderClientModal);
            return;
        }

        if (localOrderItems.size === 0) {
            event.preventDefault();
            const error = document.getElementById('local-order-form-error');
            if (error) {
                error.textContent = 'Agrega al menos un producto al encargo.';
                error.hidden = false;
            }
        }
    });

    document.getElementById('btn-new-sale')?.addEventListener('click', function () {
        if (!form) return;
        form.reset();
        const dateInput = document.getElementById('sale-date');
        if (dateInput) dateInput.value = fechaLocalParaInput();
        selectedItems.clear();
        toggleSaleSellerBlock();
        renderItems();
        showModal(saleModal);
    });

    document.getElementById('btn-add-sale-item')?.addEventListener('click', function () {
        const select = document.getElementById('sale-product');
        if (!select) return;
        const option = select.options[select.selectedIndex];
        const quantity = Number(document.getElementById('sale-quantity')?.value);
        const error = document.getElementById('sale-form-error');

        if (!option?.value || !Number.isInteger(quantity) || quantity < 1) {
            if (error) {
                error.textContent = 'Selecciona un producto y una cantidad válida.';
                error.hidden = false;
            }
            return;
        }

        const current = selectedItems.get(option.value);
        if (current) {
            if (error) {
                error.textContent = `${option.textContent.trim()} ya fue agregado a la venta.`;
                error.hidden = false;
            }
            return;
        }

        const newQuantity = quantity;
        if (newQuantity > Number(option.dataset.stock)) {
            if (error) {
                error.textContent = 'La cantidad supera las existencias disponibles.';
                error.hidden = false;
            }
            return;
        }

        selectedItems.set(option.value, {
            id: option.value,
            name: option.textContent.trim(),
            price: getSaleUnitPrice(option),
            stock: Number(option.dataset.stock),
            quantity: newQuantity,
        });
        select.value = '';
        if (saleProductSearch) saleProductSearch.value = '';
        setSaleProductOptionsVisible(false);
        select.dispatchEvent(new Event('change'));
        renderItems();
    });

    const saleProductSelect = document.getElementById('sale-product');
    const saleProductSearch = document.getElementById('sale-product-search');
    const saleProductOptions = document.getElementById('sale-product-options');
    const saleSellerCheckbox = document.getElementById('sale-is-vendedor');
    const saleSellerSelect = document.getElementById('sale-seller');
    const saleQuantityInput = document.getElementById('sale-quantity');
    const saleMoneyInput = document.getElementById('sale-money');
    const saleMoneyGroup = document.getElementById('sale-money-group');
    const saleDiscountInput = document.getElementById('sale-discount');
    const saleDiscountZeroInput = document.getElementById('sale-discount-zero');
    let saleCalculationMode = 'units';
    const getSaleUnitPrice = function (option) {
        return saleSellerCheckbox?.checked
            ? Number(option.dataset.sellerPrice || option.dataset.price)
            : Number(option.dataset.price);
    };
    const getSaleTotalCents = function (quantity, unitPriceCents) {
        const subtotalCents = quantity * unitPriceCents;
        const discountPercent = saleSellerCheckbox?.checked
            ? 0
            : Number(saleDiscountInput?.value) || 0;
        return subtotalCents - Math.round(subtotalCents * discountPercent / 100);
    };
    const getSaleProductOptionElements = function () {
        return Array.from(saleProductOptions?.querySelectorAll('[data-sale-product-option]') || []);
    };
    const setSaleProductOptionsVisible = function (visible) {
        if (!saleProductOptions || !saleProductSearch) return;
        saleProductOptions.hidden = !visible;
        saleProductSearch.setAttribute('aria-expanded', visible ? 'true' : 'false');
    };
    const filterSaleProductOptions = function (query) {
        const normalizedQuery = query.trim().toLocaleLowerCase();
        const options = getSaleProductOptionElements();
        let visibleCount = 0;
        options.forEach(function (option) {
            const matches = option.dataset.productName.toLocaleLowerCase().includes(normalizedQuery);
            option.hidden = !matches;
            if (matches) visibleCount += 1;
        });
        document.getElementById('sale-product-no-results')?.toggleAttribute('hidden', visibleCount > 0);
        setSaleProductOptionsVisible(true);
    };
    const updateSaleBudget = function (mode) {
        const option = saleProductSelect?.options[saleProductSelect.selectedIndex];
        if (!option?.value || !saleQuantityInput || !saleMoneyInput) return;

        const unitPriceCents = Math.round(getSaleUnitPrice(option) * 100);
        if (unitPriceCents <= 0) return;

        if (mode === 'money') {
            const budgetCents = Math.round(Number(saleMoneyInput.value) * 100);
            if (!Number.isFinite(budgetCents) || budgetCents <= 0) {
                saleQuantityInput.value = '';
                return;
            }

            const stock = Number(option.dataset.stock) || 0;
            let low = 0;
            let high = stock + 1;
            while (low + 1 < high) {
                const middle = Math.floor((low + high) / 2);
                if (getSaleTotalCents(middle, unitPriceCents) <= budgetCents) low = middle;
                else high = middle;
            }
            const quantity = low;
            if (quantity < 1) {
                saleQuantityInput.value = '';
                return;
            }

            saleQuantityInput.value = String(quantity);
            return;
        }

        const quantity = Number(saleQuantityInput.value);
        if (!Number.isInteger(quantity) || quantity < 1) {
            saleMoneyInput.value = '';
            return;
        }

        const totalCents = getSaleTotalCents(quantity, unitPriceCents);
        saleMoneyInput.value = (totalCents / 100).toFixed(2);
    };
    const constrainDiscount = function (input) {
        if (!input || input.value.trim() === '') return;
        const value = Number(input.value);
        if (!Number.isFinite(value)) return;
        if (value > 100) input.value = '100';
        else if (value < 0) input.value = '0';
    };
    const toggleSaleSellerBlock = function () {
        const checked = saleSellerCheckbox?.checked;
        if (saleDiscountInput) {
            if (checked) saleDiscountInput.value = '0';
            saleDiscountInput.disabled = !!checked;
        }
        if (saleDiscountZeroInput) saleDiscountZeroInput.disabled = !checked;
        if (saleSellerSelect) {
            saleSellerSelect.disabled = !checked;
            saleSellerSelect.required = !!checked;
        }
        const hasProduct = Boolean(saleProductSelect?.value);
        if (saleMoneyGroup) saleMoneyGroup.hidden = !hasProduct;
        if (saleQuantityInput) saleQuantityInput.disabled = !hasProduct;
        if (saleMoneyInput) saleMoneyInput.disabled = !hasProduct;
        if (hasProduct) updateSaleBudget(saleCalculationMode);
        renderItems();
    };
    saleProductSelect?.addEventListener('change', function () {
        const hasProduct = Boolean(saleProductSelect.value);
        saleQuantityInput.disabled = !hasProduct;
        saleMoneyInput.disabled = !hasProduct;
        saleMoneyGroup.hidden = !hasProduct;
        saleCalculationMode = 'units';
        saleQuantityInput.value = hasProduct ? '1' : '';
        saleMoneyInput.value = '';
        updateSaleBudget(saleCalculationMode);
    });
    saleProductSearch?.addEventListener('focus', function () {
        filterSaleProductOptions('');
    });
    saleProductSearch?.addEventListener('input', function () {
        if (saleProductSelect?.value) {
            saleProductSelect.value = '';
            saleProductSelect.dispatchEvent(new Event('change'));
        }
        filterSaleProductOptions(saleProductSearch.value);
    });
    saleProductSearch?.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            setSaleProductOptionsVisible(false);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            getSaleProductOptionElements().find(option => !option.hidden)?.focus();
        } else if (event.key === 'Enter' && saleProductOptions && !saleProductOptions.hidden) {
            event.preventDefault();
            getSaleProductOptionElements().find(option => !option.hidden)?.click();
        }
    });
    saleProductOptions?.addEventListener('click', function (event) {
        const option = event.target.closest('[data-sale-product-option]');
        if (!option || !saleProductSelect || !saleProductSearch) return;
        saleProductSelect.value = option.dataset.productId;
        saleProductSearch.value = option.dataset.productName;
        saleProductSelect.dispatchEvent(new Event('change'));
        saleProductSearch.focus();
        setSaleProductOptionsVisible(false);
    });
    saleQuantityInput?.addEventListener('input', function () {
        saleCalculationMode = 'units';
        updateSaleBudget(saleCalculationMode);
    });
    saleMoneyInput?.addEventListener('input', function () {
        saleCalculationMode = 'money';
        updateSaleBudget(saleCalculationMode);
    });
    saleDiscountInput?.addEventListener('input', function () {
        constrainDiscount(saleDiscountInput);
        updateSaleBudget(saleCalculationMode);
        renderItems();
    });
    saleSellerCheckbox?.addEventListener('change', toggleSaleSellerBlock);
    toggleSaleSellerBlock();

    form?.addEventListener('submit', function (event) {
        if (selectedItems.size === 0) {
            event.preventDefault();
            const error = document.getElementById('sale-form-error');
            if (error) {
                error.textContent = 'Agrega al menos un producto para registrar la venta.';
                error.hidden = false;
            }
            return;
        }

        const discountPercent = Number(document.getElementById('sale-discount')?.value);
        if (!Number.isInteger(discountPercent) || discountPercent < 0 || discountPercent > 100) {
            event.preventDefault();
            const error = document.getElementById('sale-form-error');
            if (error) {
                error.textContent = 'El descuento debe ser un porcentaje entero entre 0 y 100.';
                error.hidden = false;
            }
            return;
        }

        if (saleSellerCheckbox?.checked && !saleSellerSelect?.value) {
            event.preventDefault();
            const error = document.getElementById('sale-form-error');
            if (error) {
                error.textContent = 'Selecciona el vendedor para la venta a vendedores.';
                error.hidden = false;
            }
        }
    });

    document.querySelectorAll('[data-close-sale]').forEach(function (button) {
        button.addEventListener('click', function () { hideModal(saleModal); });
    });

    /* -------------------------------------------------------------
     * MODAL 2: DETALLE DE VENTA
     * ------------------------------------------------------------- */
    document.querySelectorAll('[data-close-details]').forEach(function (button) {
        button.addEventListener('click', function () { hideModal(detailsModal); });
    });

    document.querySelectorAll('[data-close-seller-sales]').forEach(function (button) {
        button.addEventListener('click', function () { hideModal(sellerSalesModal); });
    });

    document.querySelectorAll('[data-close-client-history]').forEach(function (button) {
        button.addEventListener('click', function () { hideModal(clientOrderHistoryModal); });
    });

    document.querySelectorAll('[data-view-client-history]').forEach(function (button) {
        button.addEventListener('click', function () {
            const title = document.getElementById('client-order-history-title');
            const tbody = document.getElementById('client-order-history-items');
            let orders = [];

            try {
                orders = JSON.parse(button.dataset.clientHistory || '[]');
            } catch (error) {
                orders = [];
            }

            if (title) title.textContent = 'Historial de ' + (button.dataset.clientName || 'cliente');
            if (tbody) {
                tbody.replaceChildren();
                orders.forEach(function (order) {
                    const row = document.createElement('tr');
                    const dateCell = document.createElement('td');
                    const productsCell = document.createElement('td');
                    const totalCell = document.createElement('td');
                    const products = order.productos || [];
                    dateCell.textContent = formatDateDisplay(order.fecha) || '—';
                    totalCell.textContent = currency.format(Number(order.total || 0));

                    if (products.length === 0) {
                        productsCell.textContent = '—';
                    } else {
                        const productList = document.createElement('div');
                        products.forEach(function (product) {
                            const item = document.createElement('div');
                            item.textContent = (product.nombre || 'Producto') + ' × ' + product.cantidad;
                            productList.appendChild(item);
                        });
                        productsCell.appendChild(productList);
                    }

                    row.append(dateCell, productsCell, totalCell);

                    tbody.appendChild(row);
                });
            }

            showModal(clientOrderHistoryModal);
        });
    });

    function renderSellerSales(sales, sellerName) {
        const title = document.getElementById('seller-sales-title');
        const tbody = document.getElementById('seller-sales-items');
        if (title) title.textContent = 'Ventas de ' + (sellerName || 'vendedor');
        if (!tbody) return;

        tbody.replaceChildren();
        sales.forEach(function (sale) {
            const row = document.createElement('tr');
            const details = document.createElement('td');
            details.dataset.label = 'Detalle e incidencias';
            const detailList = document.createElement('ul');
            detailList.className = 'seller-history-detail-list';

            (sale.productos || []).forEach(function (product) {
                const item = document.createElement('li');
                item.textContent = (product.nombre || 'Producto') + ' × ' + product.cantidad;
                detailList.appendChild(item);
            });

            (sale.incidencias || []).forEach(function (incident) {
                const item = document.createElement('li');
                item.className = 'seller-history-incident';
                const heading = document.createElement('div');
                heading.className = 'seller-history-incident-heading';
                heading.textContent = 'Incidencia';
                const severity = document.createElement('span');
                severity.textContent = incident.gravedad || 'Media';
                heading.appendChild(severity);
                const detail = document.createElement('p');
                detail.textContent = incident.detalle || '';
                item.append(heading, detail);
                detailList.appendChild(item);
            });

            if (detailList.childElementCount === 0) {
                details.textContent = '—';
            } else {
                details.appendChild(detailList);
            }

            [
                ['Fecha', formatDateDisplay(sale.fecha) || '—'],
                ['Asignadas', Number(sale.unidades_asignadas || 0).toLocaleString()],
                ['Vendidas', sale.unidades_vendidas === null ? 'Pendiente' : Number(sale.unidades_vendidas).toLocaleString()],
                ['Devueltas', sale.unidades_devueltas === null ? '—' : Number(sale.unidades_devueltas).toLocaleString()],
                ['Perdidas', sale.unidades_perdidas === null ? '—' : Number(sale.unidades_perdidas).toLocaleString()],
                ['Total', currency.format(Number(sale.total || 0))],
            ].forEach(function ([label, value]) {
                const cell = document.createElement('td');
                cell.dataset.label = label;
                cell.textContent = value;
                row.appendChild(cell);
            });

            row.appendChild(details);
            tbody.appendChild(row);
        });
    }

    async function refreshSellerHistory() {
        if (!sellerHistorySettings || sellerHistoryRequestInProgress) return;
        sellerHistoryRequestInProgress = true;

        try {
            const response = await fetch(sellerHistorySettings.dataset.endpoint, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return;

            const data = await response.json();
            (data.vendedores || []).forEach(function (seller) {
                const sales = seller.registros || [];
                const sellerTotal = sales.reduce(function (total, sale) {
                    return total + Number(sale.total || 0);
                }, 0);
                const totalCell = document.querySelector('[data-seller-total-id="' + CSS.escape(String(seller.id)) + '"]');
                if (totalCell) {
                    const totalValue = totalCell.querySelector('strong') || totalCell;
                    totalValue.textContent = currency.format(sellerTotal);
                }

                const button = document.querySelector('[data-view-seller-sales][data-seller-id="' + CSS.escape(String(seller.id)) + '"]');
                if (button) {
                    button.dataset.sellerSales = JSON.stringify(sales);
                    button.dataset.sellerName = seller.nombre || button.dataset.sellerName;
                }

                if (String(seller.id) === String(activeSellerSalesId)) {
                    renderSellerSales(sales, seller.nombre);
                }
            });
        } catch (error) {
            console.error('No se pudo actualizar el historial del vendedor:', error);
        } finally {
            sellerHistoryRequestInProgress = false;
        }
    }

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-view-seller-sales]');
        if (!button) return;

        activeSellerSalesId = button.dataset.sellerId;
        let sales = [];
        try {
            sales = JSON.parse(button.dataset.sellerSales || '[]');
        } catch (error) {
            sales = [];
        }
        renderSellerSales(sales, button.dataset.sellerName);
        showModal(sellerSalesModal);
        await refreshSellerHistory();
    });

    function bindCobrarButtons() {
        document.querySelectorAll('[data-open-cobrar-modal]').forEach(function (button) {
            if (button.dataset.cobrarBound === 'true') return;
            button.dataset.cobrarBound = 'true';
            button.addEventListener('click', function () {
                const pedidoId = button.dataset.pedidoId;
                if (processSelect) {
                    processSelect.value = pedidoId;
                    const option = processSelect.options[processSelect.selectedIndex];
                    renderPedidoDetails(option);
                }
                showModal(processModal);
            });
        });
    }

    const productionStages = [
        { value: 'pendiente', label: 'Pendiente' },
        { value: 'en_preparacion', label: 'En preparación' },
        { value: 'horneado', label: 'Horneado' },
        { value: 'terminado', label: 'Terminado' },
    ];

    function renderProductionOptions(select, currentStatus) {
        const currentIndex = productionStages.findIndex(function (stage) { return stage.value === currentStatus; });
        const safeIndex = currentIndex < 0 ? 0 : currentIndex;
        const options = productionStages.slice(safeIndex, safeIndex + 2).map(function (stage) {
            return new Option(stage.label, stage.value, false, stage.value === currentStatus);
        });
        if (currentStatus !== 'pendiente') options.push(new Option('Cancelar', 'pendiente'));
        select.replaceChildren(...options);
    }

    document.addEventListener('change', async function (event) {
        const select = event.target.closest('[data-production-status]');
        if (!select) return;

        const previousStatus = select.dataset.currentStatus || 'pendiente';
        const nextStatus = select.value;
        const csrfToken = document.querySelector('#process-order-form input[name="_token"]')?.value;
        select.disabled = true;

        try {
            const response = await fetch(select.dataset.endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    idpedido: select.dataset.pedidoId,
                    estado_produccion: nextStatus,
                }),
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'No se pudo actualizar la etapa de producción.');
            }

            select.dataset.currentStatus = result.estado_produccion;
            renderProductionOptions(select, result.estado_produccion);
            const cobrarButton = select.closest('tr')?.querySelector('[data-open-cobrar-modal]');
            if (cobrarButton) {
                const produccionTerminada = result.estado_produccion === 'terminado';
                cobrarButton.disabled = !produccionTerminada;
                cobrarButton.dataset.productionReady = produccionTerminada ? 'true' : 'false';
                cobrarButton.title = produccionTerminada
                    ? `Procesar y cobrar pedido #${select.dataset.pedidoId}`
                    : 'La producción debe estar terminada antes de cobrar.';
            }
            if (typeof showToast === 'function') showToast('Etapa de producción actualizada.', 'success');
        } catch (error) {
            renderProductionOptions(select, previousStatus);
            if (typeof showToast === 'function') showToast(error.message || 'Error al actualizar la producción.', 'error');
        } finally {
            select.disabled = false;
        }
    });

    bindCobrarButtons();

    refreshSellerHistory();
    window.setInterval(function () {
        if (!document.hidden) refreshSellerHistory();
    }, 2000);

    document.querySelectorAll('[data-view-sale]').forEach(function (button) {
        button.addEventListener('click', function () {
            const details = JSON.parse(button.dataset.saleItems || '[]');
            const tbody = document.getElementById('sale-details-items');
            if (tbody) tbody.replaceChildren();

            details.forEach(function (item) {
                const row = document.createElement('tr');
                [item.nombre, item.cantidad, currency.format(Number(item.subtotal))].forEach(function (value) {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.appendChild(cell);
                });
                tbody?.appendChild(row);
            });

            const subtotal = Number(button.dataset.saleSubtotal) || 0;
            const discountAmount = Number(button.dataset.saleDiscountAmount) || 0;

            const titleEl = document.getElementById('sale-details-title');
            const originEl = document.getElementById('sale-details-origin-text');
            const subtotalEl = document.getElementById('sale-details-subtotal');
            const discountLabelEl = document.getElementById('sale-details-discount-label');
            const discountAmountEl = document.getElementById('sale-details-discount-amount');
            const totalEl = document.getElementById('sale-details-total');

            if (titleEl) titleEl.textContent = 'Detalle de venta #' + button.dataset.saleId;
            if (originEl) originEl.textContent = button.dataset.saleOrigin || 'Venta local en tienda';
            if (subtotalEl) subtotalEl.textContent = currency.format(subtotal);
            if (discountLabelEl) discountLabelEl.textContent = 'Descuento';
            if (discountAmountEl) discountAmountEl.textContent = currency.format(discountAmount);
            if (totalEl) totalEl.textContent = currency.format(Number(button.dataset.saleTotal));

            showModal(detailsModal);
        });
    });

    /* -------------------------------------------------------------
     * MODAL 3: PROCESAR PEDIDO COMO VENTA
     * ------------------------------------------------------------- */
    const processSelect = document.getElementById('process-order-select');
    const processHiddenId = document.getElementById('process-selected-pedido-id');
    const processInfoCard = document.getElementById('process-order-info-card');
    const processClientText = document.getElementById('process-order-client-text');
    const processPhoneText = document.getElementById('process-order-phone-text');
    const processAddressText = document.getElementById('process-order-address-text');
    const processItemsTbody = document.getElementById('process-order-items');
    const processSubtotalEl = document.getElementById('process-order-subtotal-amount');
    const processOfferDiscountEl = document.getElementById('process-order-offer-discount-amount');
    const processDiscountInput = document.getElementById('process-order-discount');
    const processDiscountEl = document.getElementById('process-order-discount-amount');
    const processAdvanceEl = document.getElementById('process-order-advance-amount');
    const processTotalEl = document.getElementById('process-order-total-amount');
    const processTotalLabel = document.getElementById('process-order-total-label');
    const processPaymentDate = document.getElementById('process-order-date');
    const processPaymentDateGroup = document.getElementById('process-order-payment-date-group');
    const btnSubmitProcess = document.getElementById('btn-submit-process');
    const processFormError = document.getElementById('process-order-form-error');
    const processSellerCheckbox = document.getElementById('process-order-is-vendedor');
    const processSellerBlock = document.getElementById('process-order-seller-block');
    const processSellerSelect = document.getElementById('process-order-seller');

    const toggleProcessSellerBlock = function () {
        const checked = processSellerCheckbox?.checked;
        if (processSellerBlock) processSellerBlock.style.display = checked ? 'block' : 'none';
        if (processSellerSelect) processSellerSelect.required = !!checked;
    };

    processSellerCheckbox?.addEventListener('change', toggleProcessSellerBlock);
    toggleProcessSellerBlock();

    if (processPaymentDate) {
        processPaymentDate.required = true;
        processPaymentDate.disabled = false;
    }
    if (processPaymentDateGroup) processPaymentDateGroup.hidden = false;
    if (processTotalLabel) processTotalLabel.textContent = 'Total a cobrar';
    if (btnSubmitProcess) btnSubmitProcess.textContent = 'Registrar venta y cobrar';

    let currentOrderSubtotal = 0;
    let currentOrderOfferDiscount = 0;
    let currentOrderDeposit = 0;

    function recalculateProcessFinancials() {
        const discountPercent = Number(processDiscountInput?.value) || 0;
        const subtotalAfterOffers = Math.max(0, currentOrderSubtotal - currentOrderOfferDiscount);
        const discountAmount = Math.round(subtotalAfterOffers * discountPercent / 100 * 100) / 100;
        const finalTotal = Math.max(0, subtotalAfterOffers - discountAmount);
        const balanceDue = Math.max(0, finalTotal - currentOrderDeposit);
        const advanceExceedsTotal = currentOrderDeposit > finalTotal;

        if (processSubtotalEl) processSubtotalEl.textContent = currency.format(currentOrderSubtotal);
        if (processOfferDiscountEl) processOfferDiscountEl.textContent = currency.format(currentOrderOfferDiscount);
        if (processDiscountEl) processDiscountEl.textContent = currency.format(discountAmount);
        if (processAdvanceEl) processAdvanceEl.textContent = currency.format(currentOrderDeposit);
        if (processTotalEl) processTotalEl.textContent = currency.format(balanceDue);
        if (processTotalLabel) processTotalLabel.textContent = currentOrderDeposit > 0 ? 'Saldo pendiente' : 'Total a cobrar';
        if (btnSubmitProcess) {
            btnSubmitProcess.disabled = advanceExceedsTotal;
            btnSubmitProcess.textContent = currentOrderDeposit > 0 ? 'Cobrar saldo y cerrar encargo' : 'Registrar venta y cobrar';
        }
        if (processFormError) {
            processFormError.textContent = advanceExceedsTotal
                ? 'El total final no puede ser menor que el adelanto recibido.'
                : '';
            processFormError.hidden = !advanceExceedsTotal;
        }
    }

    function renderPedidoDetails(option) {
        if (!option || !option.value) {
            currentOrderSubtotal = 0;
            currentOrderOfferDiscount = 0;
            currentOrderDeposit = 0;
            if (processHiddenId) processHiddenId.value = '';
            if (processInfoCard) processInfoCard.hidden = true;
            if (btnSubmitProcess) btnSubmitProcess.disabled = true;
            if (processItemsTbody) {
                processItemsTbody.innerHTML = '<tr><td colspan="5" class="recipe-empty-state">Selecciona un pedido para visualizar sus productos.</td></tr>';
            }
            recalculateProcessFinancials();
            return;
        }

        if (processHiddenId) processHiddenId.value = option.value;
        if (processClientText) processClientText.textContent = option.dataset.client || '-';
        if (processPhoneText) processPhoneText.textContent = option.dataset.phone || 'No registrado';
        if (processAddressText) processAddressText.textContent = option.dataset.address || '-';
        if (processInfoCard) processInfoCard.hidden = false;
        currentOrderDeposit = Number(option.dataset.abono) || 0;

        let items = [];
        try {
            items = JSON.parse(option.dataset.items || '[]');
        } catch (e) {
            items = [];
        }

        if (processItemsTbody) {
            processItemsTbody.replaceChildren();
            if (items.length === 0) {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="5" class="recipe-empty-state">El pedido no tiene productos registrados.</td>';
                processItemsTbody.appendChild(tr);
            } else {
                items.forEach(function (item) {
                    const tr = document.createElement('tr');
                    const tdName = document.createElement('td');
                    const tdQty = document.createElement('td');
                    const tdPrice = document.createElement('td');
                    const tdOffer = document.createElement('td');
                    const tdSubtotal = document.createElement('td');
                    const lineSubtotal = Number(item.precio || 0) * Number(item.cantidad || 0);
                    const offerPercent = Number(item.descuento_oferta) || 0;
                    const lineOfferDiscount = Math.round(lineSubtotal * offerPercent / 100 * 100) / 100;

                    tdName.textContent = item.nombre;
                    tdQty.textContent = item.cantidad;
                    tdPrice.textContent = currency.format(Number(item.precio || 0));
                    tdOffer.textContent = offerPercent > 0 ? offerPercent + '%' : '—';
                    tdSubtotal.textContent = currency.format(Math.max(0, lineSubtotal - lineOfferDiscount));

                    tr.append(tdName, tdQty, tdPrice, tdOffer, tdSubtotal);
                    processItemsTbody.appendChild(tr);
                });
            }
        }

        currentOrderSubtotal = items.reduce(function (total, item) {
            return total + Number(item.precio || 0) * Number(item.cantidad || 0);
        }, 0);
        currentOrderOfferDiscount = items.reduce(function (total, item) {
            const lineSubtotal = Number(item.precio || 0) * Number(item.cantidad || 0);
            return total + Math.round(lineSubtotal * (Number(item.descuento_oferta) || 0) / 100 * 100) / 100;
        }, 0);
        if (btnSubmitProcess) btnSubmitProcess.disabled = items.length === 0;
        if (processFormError) processFormError.hidden = true;

        recalculateProcessFinancials();
    }

    processSelect?.addEventListener('change', function () {
        const option = processSelect.options[processSelect.selectedIndex];
        renderPedidoDetails(option);
    });

    processDiscountInput?.addEventListener('input', function () {
        constrainDiscount(processDiscountInput);
        recalculateProcessFinancials();
    });

    // Botón superior "Procesar pedido"
    document.getElementById('btn-process-order')?.addEventListener('click', function () {
        if (!processModal) return;
        if (processSelect && processSelect.options.length > 1 && !processSelect.value) {
            processSelect.selectedIndex = 1;
            renderPedidoDetails(processSelect.options[1]);
        }
        showModal(processModal);
    });

    // Botón "Cobrar pedido" dentro de cada fila de la tabla de pedidos
    document.querySelectorAll('[data-close-process]').forEach(function (button) {
        button.addEventListener('click', function () { hideModal(processModal); });
    });

    document.getElementById('process-order-form')?.addEventListener('submit', function (event) {
        if (!processHiddenId?.value) {
            event.preventDefault();
            if (processFormError) {
                processFormError.textContent = 'Debes seleccionar un pedido para procesar.';
                processFormError.hidden = false;
            }
            return;
        }

        const discountPercent = Number(processDiscountInput?.value);
        if (!Number.isInteger(discountPercent) || discountPercent < 0 || discountPercent > 100) {
            event.preventDefault();
            if (processFormError) {
                processFormError.textContent = 'El descuento debe ser un porcentaje entero entre 0 y 100.';
                processFormError.hidden = false;
            }
            return;
        }

        if (!processPaymentDate?.value) {
            event.preventDefault();
            if (processFormError) {
                processFormError.textContent = 'Indica la fecha de pago para registrar la venta y cobrarla.';
                processFormError.hidden = false;
            }
        }
    });

    /* -------------------------------------------------------------
     * CIERRE GLOBAL DE MODALES (CLICK EXTERNO / TECLA ESC)
     * ------------------------------------------------------------- */
    [saleModal, detailsModal, sellerSalesModal, clientOrderHistoryModal, processModal, localOrderClientModal, localOrderRegisterClientModal, localOrderModal, salesSearchModal].forEach(function (modal) {
        modal?.addEventListener('click', function (event) {
            if (event.target === modal) hideModal(modal);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (detailsModal?.getAttribute('aria-hidden') === 'false') hideModal(detailsModal);
            else if (sellerSalesModal?.getAttribute('aria-hidden') === 'false') hideModal(sellerSalesModal);
            else if (clientOrderHistoryModal?.getAttribute('aria-hidden') === 'false') hideModal(clientOrderHistoryModal);
            else if (saleModal?.getAttribute('aria-hidden') === 'false') hideModal(saleModal);
            else if (processModal?.getAttribute('aria-hidden') === 'false') hideModal(processModal);
            else if (localOrderRegisterClientModal?.getAttribute('aria-hidden') === 'false') hideModal(localOrderRegisterClientModal);
            else if (localOrderClientModal?.getAttribute('aria-hidden') === 'false') hideModal(localOrderClientModal);
            else if (localOrderModal?.getAttribute('aria-hidden') === 'false') hideModal(localOrderModal);
            else if (salesSearchModal?.getAttribute('aria-hidden') === 'false') hideModal(salesSearchModal);
        }
    });

    /* -------------------------------------------------------------
     * INICIALIZACIÓN POR CARGA PREVIA CON ERRORES
     * ------------------------------------------------------------- */
    if (saleModal?.dataset.openOnLoad === 'true') {
        try {
            const oldItems = JSON.parse(form?.dataset.oldItems || '[]');
            oldItems.forEach(function (oldItem) {
                const option = Array.from(document.getElementById('sale-product')?.options || [])
                    .find(function (candidate) { return candidate.value === String(oldItem.producto_id); });
                if (!option) return;
                selectedItems.set(option.value, {
                    id: option.value,
                    name: option.textContent.split(' · ')[0],
                    price: Number(option.dataset.price),
                    stock: Number(option.dataset.stock),
                    quantity: Number(oldItem.cantidad),
                });
            });
        } catch (error) {
            selectedItems.clear();
        }
        renderItems();
        showModal(saleModal);
    } else {
        renderItems();
    }

    if (processModal?.dataset.openOnLoad === 'true') {
        const selectedOption = processSelect?.options[processSelect.selectedIndex];
        if (selectedOption) renderPedidoDetails(selectedOption);
        showModal(processModal);
    }

    restoreLocalOrderItems();
    if (localOrderModal?.dataset.openOnLoad === 'true') showModal(localOrderModal);

    const salesModals = [saleModal, detailsModal, sellerSalesModal, clientOrderHistoryModal, processModal, localOrderClientModal, localOrderRegisterClientModal, localOrderModal, salesSearchModal];
    let salesRefreshInProgress = false;

    async function refreshSalesContent() {
        if (salesRefreshInProgress) return;
        salesRefreshInProgress = true;

        try {
            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) return;

            const html = await response.text();
            const refreshedDocument = new DOMParser().parseFromString(html, 'text/html');
            let contentChanged = false;
            const normalizeContent = function (content) {
                return content
                    .replace(/\sdata-cobrar-bound="true"/g, '')
                    .replace(/\sdata-seller-sales="[^"]*"/g, '')
                    .replace(/\sdata-seller-name="[^"]*"/g, '');
            };

            refreshedDocument.querySelectorAll('.sales-tab-content').forEach(function (freshTab) {
                const currentTabElement = document.getElementById(freshTab.id);
                const currentHtml = currentTabElement && normalizeContent(currentTabElement.innerHTML);
                const freshHtml = normalizeContent(freshTab.innerHTML);
                if (currentTabElement && currentHtml !== freshHtml) {
                    currentTabElement.innerHTML = freshTab.innerHTML;
                    contentChanged = true;
                }
            });

            refreshedDocument.querySelectorAll('[data-sales-tab]').forEach(function (freshButton) {
                const currentButton = document.querySelector('[data-sales-tab="' + freshButton.dataset.salesTab + '"]');
                if (currentButton && currentButton.innerHTML !== freshButton.innerHTML) {
                    currentButton.innerHTML = freshButton.innerHTML;
                    contentChanged = true;
                }
            });

            if (contentChanged) {
                bindCobrarButtons();
                applySalesSearch();
            }
        } catch (error) {
            console.error('No se pudo actualizar el contenido de ventas:', error);
        } finally {
            salesRefreshInProgress = false;
        }
    }

    window.setInterval(function () {
        const modalOpen = salesModals.some(function (modal) {
            return modal?.getAttribute('aria-hidden') === 'false';
        });
        if (!document.hidden && !modalOpen) refreshSalesContent();
    }, 2000);
});