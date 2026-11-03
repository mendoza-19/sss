@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/gastos/style.css') }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header"><title>Gastos del mes - El Buen Sabor</title></x-slot>

    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <section class="recipe-page monthly-expenses-page">
                    <header class="recipe-page-heading">
                        <div>
                            <h1>Gastos del mes</h1>
                            <p>Servicios y otros gastos operativos, separados de compras de ingredientes.</p>
                        </div>
                    </header>

                    @if (session('success'))
                        <p class="recipe-feedback" role="status">{{ session('success') }}</p>
                    @endif
                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <p class="expenses-form-feedback" id="expense-page-feedback" role="status" aria-live="polite" hidden></p>

                    <div class="expenses-period-bar">
                        <form method="GET" action="{{ route('gastos.index') }}" class="expenses-month-filter">
                            <label for="expenses-month">Período</label>
                            <input type="month" id="expenses-month" name="mes" value="{{ $mes }}" required>
                            <button type="submit" class="recipe-secondary-button">Consultar</button>
                        </form>
                        <div class="expenses-total" aria-live="polite">
                            <span>Total de {{ \Illuminate\Support\Carbon::parse($periodo)->format('m/Y') }}</span>
                            <strong id="expense-month-total" data-total="{{ number_format((float) $total, 2, '.', '') }}">${{ number_format((float) $total, 2) }}</strong>
                        </div>
                    </div>

                    <dl class="expenses-category-summary" aria-label="Totales por categoría">
                        @foreach ($categorias as $clave => $nombre)
                            <div>
                                <dt>{{ $nombre }}</dt>
                                <dd data-expense-category-total="{{ $clave }}" data-total="{{ number_format((float) $totalesPorCategoria[$clave], 2, '.', '') }}">${{ number_format((float) $totalesPorCategoria[$clave], 2) }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <section class="expenses-entry-section" aria-labelledby="expense-entry-title">
                        <h2 id="expense-entry-title">Registrar gasto</h2>
                        <form method="POST" action="{{ route('gastos.store') }}" class="expenses-entry-form" data-expense-form>
                            @csrf
                            <div class="recipe-form-group">
                                <label for="expense-category">Categoría</label>
                                <select id="expense-category" name="categoria" required>
                                    @foreach ($categorias as $clave => $nombre)
                                        <option value="{{ $clave }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="recipe-form-group">
                                <label for="expense-date">Fecha de compra</label>
                                <input type="date" id="expense-date" name="fecha" value="{{ $fechaNueva }}"
                                    min="{{ $fechaMinima }}" max="{{ $fechaMaxima }}" required>
                            </div>
                            <div class="recipe-form-group">
                                <label for="expense-description">Descripción</label>
                                <input type="text" id="expense-description" name="descripcion" maxlength="255"
                                    placeholder="Ej. recarga de gas">
                            </div>
                            <div class="recipe-form-group">
                                <label for="expense-amount">Monto ($)</label>
                                <input type="number" id="expense-amount" name="monto" min="0.01" max="99999999.99"
                                    step="0.01" required>
                            </div>
                            <button type="submit" class="recipe-primary-button">Guardar gasto</button>
                        </form>
                        <p class="expenses-form-feedback" id="expense-form-feedback" role="status" aria-live="polite" hidden></p>
                    </section>

                    <section class="expenses-list-section" aria-labelledby="expenses-list-title">
                        <h2 id="expenses-list-title">Movimientos de {{ \Illuminate\Support\Carbon::parse($periodo)->format('m/Y') }}</h2>
                        <div class="recipe-table-container">
                            <table class="recipe-table">
                                <thead>
                                    <tr><th>Fecha</th><th>Categoría</th><th>Descripción</th><th>Monto</th><th>Acciones</th></tr>
                                </thead>
                                <tbody id="expenses-list-body">
                                    @forelse ($gastos as $gasto)
                                        <tr data-expense-id="{{ $gasto->id }}" data-expense-category="{{ $gasto->categoria }}" data-expense-amount="{{ number_format((float) $gasto->monto, 2, '.', '') }}">
                                            <td>{{ $gasto->fecha->format('d/m/Y') }}</td>
                                            <td>{{ $categorias[$gasto->categoria] }}</td>
                                            <td>{{ $gasto->descripcion ?: '—' }}</td>
                                            <td>${{ number_format((float) $gasto->monto, 2) }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('gastos.destroy', $gasto) }}" data-expense-delete>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="recipe-secondary-button">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr data-expense-empty><td colspan="5" class="recipe-empty-state">No hay gastos registrados para este mes.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </section>
            </div>
        </div>
    </main>

    <script>
        const expenseForm = document.querySelector('[data-expense-form]');
        const expenseList = document.getElementById('expenses-list-body');
        const expenseFeedback = document.getElementById('expense-form-feedback');
        const expenseMonthTotal = document.getElementById('expense-month-total');
        const expensePageFeedback = document.getElementById('expense-page-feedback');
        const expenseCurrency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });

        function updateExpenseTotal(element, amount) {
            if (!element) return;
            const current = Math.round(Number(element.dataset.total || 0) * 100);
            const added = Math.round(Number(amount || 0) * 100);
            const next = (current + added) / 100;
            element.dataset.total = next.toFixed(2);
            element.textContent = expenseCurrency.format(next);
        }

        function appendExpenseRow(expense) {
            expenseList.querySelector('[data-expense-empty]')?.remove();

            const row = document.createElement('tr');
            row.dataset.expenseId = expense.id;
            row.dataset.expenseCategory = expense.categoria;
            row.dataset.expenseAmount = Number(expense.monto).toFixed(2);
            [expense.fecha_label, expense.categoria_nombre, expense.descripcion || '—', expenseCurrency.format(expense.monto)].forEach(function (value) {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');
            const deleteForm = document.createElement('form');
            deleteForm.method = 'POST';
            deleteForm.action = expense.delete_url;
            deleteForm.dataset.expenseDelete = '';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = expenseForm.querySelector('[name="_token"]').value;

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';

            const deleteButton = document.createElement('button');
            deleteButton.type = 'submit';
            deleteButton.className = 'recipe-secondary-button';
            deleteButton.textContent = 'Eliminar';
            deleteForm.append(csrfInput, methodInput, deleteButton);
            actionCell.appendChild(deleteForm);
            row.appendChild(actionCell);
            expenseList.prepend(row);
        }

        expenseForm?.addEventListener('submit', async function (event) {
            event.preventDefault();
            expenseFeedback.hidden = true;
            const submitButton = expenseForm.querySelector('button[type="submit"]');
            const categoryInput = expenseForm.querySelector('[name="categoria"]');
            const selectedCategory = categoryInput.value;
            submitButton.disabled = true;

            try {
                const response = await fetch(expenseForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(expenseForm),
                });
                const result = await response.json();

                if (!response.ok) {
                    const validationMessage = Object.values(result.errors || {}).flat()[0];
                    throw new Error(validationMessage || result.message || 'No se pudo guardar el gasto.');
                }

                appendExpenseRow(result.gasto);
                updateExpenseTotal(expenseMonthTotal, result.gasto.monto);
                updateExpenseTotal(
                    document.querySelector('[data-expense-category-total="' + CSS.escape(result.gasto.categoria) + '"]'),
                    result.gasto.monto
                );
                expenseForm.reset();
                expenseForm.querySelector('[name="fecha"]').value = '{{ $fechaNueva }}';
                categoryInput.value = selectedCategory;
                expenseFeedback.classList.remove('is-error');
                expensePageFeedback.classList.remove('is-error');
                expensePageFeedback.textContent = result.message;
                expensePageFeedback.hidden = false;
            } catch (error) {
                expenseFeedback.classList.add('is-error');
                expenseFeedback.textContent = error.message || 'Ocurrió un error al guardar el gasto.';
                expenseFeedback.hidden = false;
            } finally {
                submitButton.disabled = false;
            }
        });

        document.addEventListener('submit', async function (event) {
            const deleteForm = event.target.closest('[data-expense-delete]');
            if (!deleteForm) return;

            event.preventDefault();

            const row = deleteForm.closest('[data-expense-id]');
            const submitButton = deleteForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                const response = await fetch(deleteForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(deleteForm),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'No se pudo eliminar el gasto.');

                updateExpenseTotal(expenseMonthTotal, -Number(row.dataset.expenseAmount));
                updateExpenseTotal(
                    document.querySelector('[data-expense-category-total="' + CSS.escape(row.dataset.expenseCategory) + '"]'),
                    -Number(row.dataset.expenseAmount)
                );
                row.remove();

                if (!expenseList.querySelector('[data-expense-id]')) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.dataset.expenseEmpty = '';
                    emptyRow.innerHTML = '<td colspan="5" class="recipe-empty-state">No hay gastos registrados para este mes.</td>';
                    expenseList.appendChild(emptyRow);
                }

                expensePageFeedback.classList.remove('is-error');
                expensePageFeedback.textContent = result.message;
                expensePageFeedback.hidden = false;
            } catch (error) {
                expensePageFeedback.classList.add('is-error');
                expensePageFeedback.textContent = error.message || 'No se pudo eliminar el gasto.';
                expensePageFeedback.hidden = false;
                submitButton.disabled = false;
            }
        });
    </script>
</x-canvas>