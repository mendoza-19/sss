<section class="tray-breakdown" data-tray-breakdown>
    <header class="tray-breakdown-heading">
        <h2>Producción por latas</h2>
    </header>
    <div class="tray-breakdown-groups" data-tray-groups>
        <div class="tray-breakdown-row" data-tray-row>
            <div class="recipe-form-group">
                <label for="latas-cantidad-0" data-tray-indexed-id="cantidad">Cantidad de latas</label>
                <input type="number" id="latas-cantidad-0" name="latas[0][cantidad]" data-tray-count
                    min="0" step="1" value="{{ old('latas.0.cantidad') }}" required>
            </div>
            <div class="recipe-form-group">
                <label for="latas-panes-0" data-tray-indexed-id="panes">Panes por lata</label>
                <input type="number" id="latas-panes-0" name="latas[0][panes_por_lata]" data-tray-capacity
                    min="1" step="1" value="{{ old('latas.0.panes_por_lata') }}" required>
            </div>
            <button type="button" class="recipe-secondary-button tray-remove-button" data-remove-tray
                aria-label="Quitar grupo de latas">Quitar</button>
        </div>
    </div>
    <footer class="tray-breakdown-footer">
        <button type="button" class="recipe-secondary-button" data-add-tray>Agregar tamaño de lata</button>
        <p class="tray-breakdown-total" aria-live="polite">
            Total horneado <strong><span data-tray-total>0</span> panes</strong>
        </p>
    </footer>
</section>

<script>
    (() => {
        document.querySelectorAll('[data-tray-breakdown]').forEach((container) => {
            const groups = container.querySelector('[data-tray-groups]');
            const totalOutput = container.querySelector('[data-tray-total]');

            const update = () => {
                const rows = [...groups.querySelectorAll('[data-tray-row]')];
                let total = 0;

                rows.forEach((row, index) => {
                    const countInput = row.querySelector('[data-tray-count]');
                    const capacityInput = row.querySelector('[data-tray-capacity]');
                    countInput.name = `latas[${index}][cantidad]`;
                    capacityInput.name = `latas[${index}][panes_por_lata]`;
                    row.querySelectorAll('[data-tray-indexed-id]').forEach((element) => {
                        const id = `latas-${element.dataset.trayIndexedId}-${index}`;
                        element.id = id;
                        if (element.tagName === 'LABEL') element.htmlFor = id;
                    });
                    row.querySelector('[data-remove-tray]').disabled = rows.length === 1;
                    total += Number(countInput.value || 0) * Number(capacityInput.value || 0);
                });

                totalOutput.textContent = total;
                container.dispatchEvent(new CustomEvent('tray-breakdown:change', { bubbles: true }));
            };

            const reset = () => {
                const rows = [...groups.querySelectorAll('[data-tray-row]')];
                rows.slice(1).forEach((row) => row.remove());
                rows[0].querySelector('[data-tray-count]').value = '';
                rows[0].querySelector('[data-tray-capacity]').value = '';
                update();
            };

            container.addEventListener('input', update);
            container.addEventListener('reset-trays', reset);
            container.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-tray]');
                const removeButton = event.target.closest('[data-remove-tray]');

                if (addButton) {
                    const row = groups.querySelector('[data-tray-row]').cloneNode(true);
                    row.querySelector('[data-tray-count]').value = '';
                    row.querySelector('[data-tray-capacity]').value = '';
                    groups.appendChild(row);
                } else if (removeButton && groups.querySelectorAll('[data-tray-row]').length > 1) {
                    removeButton.closest('[data-tray-row]').remove();
                } else {
                    return;
                }

                update();
            });

            update();
        });
    })();
</script>