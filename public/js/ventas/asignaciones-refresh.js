document.addEventListener('DOMContentLoaded', function () {
    const settings = document.getElementById('seller-assignment-settings');
    const table = document.getElementById('seller-assignments-table');
    const tbody = table?.querySelector('tbody');
    const assignmentTabs = Array.from(document.querySelectorAll('[data-assignment-tab]'));

    if (!settings || !tbody) return;

    const view = settings.dataset.view;
    const csrfToken = settings.dataset.csrfToken;
    let activeAssignmentTab = document.querySelector('[data-assignment-tab].active')?.dataset.assignmentTab || 'nuevas';
    let lastSnapshot = null;
    let requestInProgress = false;

    function getDrafts() {
        return new Map(Array.from(tbody.querySelectorAll('tr[data-assignment-id]')).map(function (row) {
            const form = row.querySelector('form');
            const products = {};

            form?.querySelectorAll('[data-closeout-product-id]').forEach(function (productRow) {
                products[productRow.dataset.closeoutProductId] = {
                    returned: productRow.querySelector('[data-closeout-returned]')?.value ?? '0',
                    lost: productRow.querySelector('[data-closeout-lost]')?.value ?? '0',
                };
            });

            return [row.dataset.assignmentId, {
                returned: form?.elements.namedItem('unidadesdevueltas')?.value ?? '0',
                lost: form?.elements.namedItem('unidadesperdidas')?.value ?? '0',
                products: products,
                incident: form?.elements.namedItem('incidencia')?.value ?? '',
                severity: form?.elements.namedItem('gravedad')?.value ?? 'Media',
            }];
        }));
    }

    function makeCell(text) {
        const cell = document.createElement('td');
        cell.textContent = text ?? '';
        return cell;
    }

    function appendIncidents(cell, incidents) {
        if (incidents.length === 0) {
            cell.textContent = '—';
            return;
        }

        incidents.forEach(function (incident) {
            const paragraph = document.createElement('p');
            paragraph.append(document.createTextNode(incident.detalle || 'Incidencia'));

            const severity = document.createElement('small');
            severity.textContent = ' (' + (incident.gravedad || 'Media') + ')';
            paragraph.appendChild(severity);
            cell.appendChild(paragraph);
        });
    }

    function makeIncidentCell(incidents) {
        const cell = document.createElement('td');
        appendIncidents(cell, incidents);
        return cell;
    }

    function makeSellerForm(assignment, draft) {
        const form = document.createElement('form');
        form.className = 'seller-closeout-form';
        form.method = 'POST';
        form.action = '/ventas/asignaciones/' + encodeURIComponent(assignment.id) + '/cerrar';

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken;
        form.appendChild(token);

        const productList = document.createElement('div');
        productList.className = 'seller-product-closeout-list';
        const products = Array.isArray(assignment.productos) && assignment.productos.length > 0
            ? assignment.productos
            : [{ nombre: 'Asignación general', asignadas: assignment.emitidas, id: null }];

        products.forEach(function (product) {
            const productRow = document.createElement('div');
            productRow.className = 'seller-product-closeout-row';
            if (product.id !== null && product.id !== undefined) {
                productRow.dataset.closeoutProductId = String(product.id);
            } else {
                productRow.classList.add('seller-product-closeout-legacy');
            }

            const meta = document.createElement('div');
            meta.className = 'seller-product-line-meta';
            const name = document.createElement('strong');
            name.textContent = product.nombre || 'Producto';
            const details = document.createElement('small');
            details.textContent = Number(product.asignadas || 0).toLocaleString() + ' asignadas'
                + (product.subtotal !== undefined
                    ? ' · $' + (Number(product.asignadas) > 0 ? Number(product.subtotal) / Number(product.asignadas) : 0).toFixed(2) + ' c/u'
                    : '');
            meta.append(name, details);
            productRow.appendChild(meta);

            const productDraft = product.id !== null && product.id !== undefined
                ? (draft.products?.[String(product.id)] || {})
                : draft;

            [
                ['Devueltas', 'unidadesdevueltas', 'returned', 'data-closeout-returned'],
                ['Perdidas', 'unidadesperdidas', 'lost', 'data-closeout-lost'],
            ].forEach(function ([labelText, name, draftKey, dataAttribute]) {
                const group = document.createElement('div');
                group.className = 'recipe-form-group seller-closeout-line-input';

                const inputId = name + '-' + assignment.id + '-' + (product.id ?? 'legacy');
                const label = document.createElement('label');
                label.htmlFor = inputId;
                label.textContent = labelText;

                const input = document.createElement('input');
                input.type = 'number';
                input.id = inputId;
                input.name = product.id !== null && product.id !== undefined
                    ? 'productos[' + product.id + '][' + name + ']'
                    : name;
                input.min = '0';
                input.max = String(product.asignadas ?? assignment.emitidas);
                input.value = productDraft[draftKey] ?? '0';
                input.required = true;
                input.setAttribute(dataAttribute, '');

                group.append(label, input);
                productRow.appendChild(group);
            });
            productList.appendChild(productRow);
        });

        const incidentGroup = document.createElement('div');
        incidentGroup.className = 'recipe-form-group seller-incident-group';

        const incidentId = 'incidencia-' + assignment.id;
        const incidentLabel = document.createElement('label');
        incidentLabel.htmlFor = incidentId;
        incidentLabel.textContent = 'Incidencia (opcional)';

        const incidentInput = document.createElement('textarea');
        incidentInput.id = incidentId;
        incidentInput.name = 'incidencia';
        incidentInput.rows = 2;
        incidentInput.maxLength = 1000;
        incidentInput.value = draft.incident;

        incidentGroup.append(incidentLabel, incidentInput);

        const severityGroup = document.createElement('div');
        severityGroup.className = 'recipe-form-group';
        const severityId = 'gravedad-' + assignment.id;
        const severityLabel = document.createElement('label');
        severityLabel.htmlFor = severityId;
        severityLabel.textContent = 'Gravedad';

        const severitySelect = document.createElement('select');
        severitySelect.id = severityId;
        severitySelect.name = 'gravedad';
        ['Baja', 'Media', 'Alta'].forEach(function (severity) {
            const option = document.createElement('option');
            option.value = severity;
            option.textContent = severity;
            option.selected = severity === draft.severity;
            severitySelect.appendChild(option);
        });
        severityGroup.append(severityLabel, severitySelect);

        const submit = document.createElement('button');
        submit.type = 'submit';
        submit.className = 'recipe-primary-button';
        submit.textContent = 'Registrar cierre';

        const footer = document.createElement('div');
        footer.className = 'seller-closeout-footer';
        footer.append(incidentGroup, severityGroup, submit);
        form.append(productList, footer);
        return form;
    }

    function makeRow(assignment, draft) {
        const row = document.createElement('tr');
        row.className = 'seller-assignment-row';
        row.dataset.assignmentId = String(assignment.id);
        row.dataset.assignmentState = assignment.cerrada ? 'realizadas' : 'nuevas';

        if (view === 'admin') {
            row.append(
                makeCell(assignment.vendedor),
                makeCell(assignment.fecha),
                makeCell(Number(assignment.emitidas).toLocaleString()),
                makeCell(assignment.cerrada ? Number(assignment.vendidas).toLocaleString() : 'Pendiente'),
                makeCell(assignment.cerrada ? Number(assignment.devueltas).toLocaleString() : '—'),
                makeCell(assignment.cerrada ? Number(assignment.perdidas).toLocaleString() : '—'),
                makeIncidentCell(assignment.incidencias || []),
            );
            return row;
        }

        const dateCell = makeCell(assignment.fecha);
        dateCell.dataset.label = 'Fecha';
        const date = document.createElement('time');
        date.className = 'seller-assignment-date';
        date.textContent = assignment.fecha || '—';
        dateCell.replaceChildren(date);

        const unitsCell = document.createElement('td');
        unitsCell.dataset.label = 'Panes asignados';
        const units = document.createElement('strong');
        units.className = 'seller-assigned-units';
        units.textContent = Number(assignment.emitidas).toLocaleString();
        const caption = document.createElement('small');
        caption.className = 'seller-units-caption';
        caption.textContent = 'panes asignados';
        unitsCell.append(units, caption);
        row.append(dateCell, unitsCell);

        const result = document.createElement('td');
        result.dataset.label = 'Cierre e incidencia';
        if (assignment.cerrada) {
            if (Array.isArray(assignment.productos) && assignment.productos.length > 0) {
                const summary = document.createElement('div');
                summary.className = 'seller-product-close-summary';
                assignment.productos.forEach(function (product) {
                    const item = document.createElement('p');
                    const name = document.createElement('strong');
                    name.textContent = product.nombre || 'Producto';
                    const sold = document.createElement('span');
                    sold.textContent = 'Vendidas ' + Number(product.vendidas || 0).toLocaleString();
                    const returned = document.createElement('span');
                    returned.textContent = 'Devueltas ' + Number(product.devueltas || 0).toLocaleString();
                    const lost = document.createElement('span');
                    lost.textContent = 'Perdidas ' + Number(product.perdidas || 0).toLocaleString();
                    item.append(name, sold, returned, lost);
                    summary.appendChild(item);
                });
                result.appendChild(summary);
            } else {
                const summary = document.createElement('div');
                summary.className = 'seller-close-summary';
                [
                    ['Vendidas ', assignment.vendidas],
                    ['Devueltas ', assignment.devueltas],
                    ['Perdidas ', assignment.perdidas],
                ].forEach(function ([label, value]) {
                    const item = document.createElement('p');
                    item.append(document.createTextNode(label));
                    const strong = document.createElement('strong');
                    strong.textContent = Number(value || 0).toLocaleString();
                    item.appendChild(strong);
                    summary.appendChild(item);
                });
                result.appendChild(summary);
            }

            (assignment.incidencias || []).forEach(function (incident) {
                const paragraph = document.createElement('p');
                paragraph.className = 'seller-incident';
                paragraph.textContent = 'Incidencia: ' + (incident.detalle || '') + ' (' + (incident.gravedad || 'Media') + ')';
                result.appendChild(paragraph);
            });
            const status = document.createElement('span');
            status.className = 'seller-assignment-status is-closed seller-closeout-complete';
            status.textContent = 'Cierre realizado';
            result.appendChild(status);
        } else {
            result.appendChild(makeSellerForm(assignment, draft));
        }

        row.appendChild(result);
        return row;
    }

    function renderAssignments(assignments) {
        const drafts = getDrafts();
        const activeElement = document.activeElement;
        const activeRow = activeElement?.closest('tr[data-assignment-id]');
        const activeAssignmentId = activeRow?.dataset.assignmentId;
        const activeFieldName = activeElement?.name;
        const activeSelection = activeElement instanceof HTMLTextAreaElement
            ? activeElement.selectionStart
            : null;

        const rows = assignments.map(function (assignment) {
            const draft = drafts.get(String(assignment.id)) || {
                returned: '0',
                lost: '0',
                incident: '',
                severity: 'Media',
            };
            return makeRow(assignment, draft);
        });

        if (view === 'admin' && rows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 7;
            cell.className = 'recipe-empty-state';
            cell.textContent = 'Todavía no hay asignaciones de vendedores.';
            row.appendChild(cell);
            rows.push(row);
        }

        if (view === 'seller') {
            const emptyRows = ['nuevas', 'realizadas'].map(function (state) {
                const row = document.createElement('tr');
                row.dataset.assignmentEmpty = state;
                row.hidden = true;

                const cell = document.createElement('td');
                cell.colSpan = 3;
                cell.className = 'recipe-empty-state';
                cell.textContent = state === 'nuevas'
                    ? 'No tienes nuevas asignaciones.'
                    : 'Todavía no has realizado asignaciones.';
                row.appendChild(cell);

                return row;
            });
            tbody.replaceChildren(...rows, ...emptyRows);
            updateAssignmentTabs(assignments);
            applyAssignmentTab();
        } else {
            tbody.replaceChildren(...rows);
        }

        if (activeAssignmentId && activeFieldName) {
            const selector = 'tr[data-assignment-id="' + CSS.escape(activeAssignmentId) + '"] [name="' + CSS.escape(activeFieldName) + '"]';
            const restoredField = tbody.querySelector(selector);
            restoredField?.focus();
            if (activeSelection !== null && restoredField instanceof HTMLTextAreaElement) {
                restoredField.setSelectionRange(activeSelection, activeSelection);
            }
        }
    }

    function updateAssignmentTabs(assignments) {
        const counts = {
            nuevas: assignments.filter((assignment) => !assignment.cerrada).length,
            realizadas: assignments.filter((assignment) => assignment.cerrada).length,
        };

        assignmentTabs.forEach(function (tab) {
            const active = tab.dataset.assignmentTab === activeAssignmentTab;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
            const count = tab.querySelector('[data-assignment-count]');
            if (count) count.textContent = counts[tab.dataset.assignmentTab] ?? 0;
        });
    }

    function applyAssignmentTab() {
        const rows = Array.from(tbody.querySelectorAll('tr[data-assignment-id]'));
        const matchingRows = rows.filter((row) => row.dataset.assignmentState === activeAssignmentTab);

        rows.forEach(function (row) {
            row.hidden = row.dataset.assignmentState !== activeAssignmentTab;
        });

        tbody.querySelectorAll('tr[data-assignment-empty]').forEach(function (row) {
            row.hidden = row.dataset.assignmentEmpty !== activeAssignmentTab || matchingRows.length > 0;
        });
    }

    assignmentTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activeAssignmentTab = tab.dataset.assignmentTab;
            assignmentTabs.forEach(function (item) {
                const active = item === tab;
                item.classList.toggle('active', active);
                item.setAttribute('aria-selected', String(active));
            });
            applyAssignmentTab();
        });
    });

    if (view === 'seller') applyAssignmentTab();

    async function refreshAssignments() {
        if (requestInProgress) return;
        requestInProgress = true;

        try {
            const response = await fetch('/api/ventas/asignaciones', {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) return;

            const data = await response.json();
            const assignments = data.asignaciones || [];
            const snapshot = JSON.stringify(assignments);

            if (snapshot !== lastSnapshot) {
                renderAssignments(assignments);
                lastSnapshot = snapshot;
            }
        } catch (error) {
            console.error('No se pudieron actualizar las asignaciones:', error);
        } finally {
            requestInProgress = false;
        }
    }

    refreshAssignments();
    window.setInterval(function () {
        if (!document.hidden) refreshAssignments();
    }, 2000);
});