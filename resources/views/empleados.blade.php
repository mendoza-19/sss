@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/empleados/style.css') }}">
    @endpush

    @push('js')
        <script>
            window.employeeEndpoints = {
                pagination: "{{ route('api.empleados.getEmpleadosPagination') }}",
                search: "{{ route('api.empleados.searchEmpleado') }}",
                options: "{{ route('api.empleados.opcionesFormulario') }}",
                details: "{{ route('api.empleados.show', ['idempleado' => '__ID__']) }}",
                create: "{{ route('api.empleados.create') }}",
                update: "{{ route('api.empleados.update') }}",
                resendPassword: "{{ route('api.empleados.password.resend', ['idempleado' => '__ID__']) }}"
            };

            window.empleadoAssets = {
                edit: "{{ asset('assets/global/editar_black_color.svg') }}",
                view: "{{ asset('assets/global/vista.svg') }}"
            };
        </script>

        <script src="{{ asset('js/tools/modalManager.js') }}"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.modalManager = new ModalManager();

                modalManager.addModal('modal-employee-form');
                modalManager.addModal('modal-employee-details');
                modalManager.addModal('base-modal-loading');
            });
        </script>

        <script src="{{ asset('js/empleados/script.js') }}"></script>

        @include('principal.sidebar-menu')
    @endpush
@endonce

<x-canvas>
    <x-slot name="header">
        <title>Empleados</title>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>

        <div class="body-content">
            <x-navegation></x-navegation>

            <div class="page-content">
                <div class="employee-page-heading">
                    <div>
                        <h1>Empleados</h1>
                        <p></p>
                    </div>

                    <button type="button" id="btn-nuevo" class="employee-primary-button" title="Nuevo empleado">
                        <span aria-hidden="true">+</span>
                        Nuevo
                    </button>
                </div>

                <div class="employee-table-tools">
                    <x-searchbar id="search-bar-employee" classList="search-bar-employee"></x-searchbar>
                </div>

                <div class="employee-table-container">
                    <x-tablec tableClass="employee-table">
                        <x-slot name="colgroup">
                            <col style="width: 40%;">
                            <col style="width: 40%;">
                            <col style="width: 20%;">
                        </x-slot>

                        <x-slot name="thead">
                            <tr>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Acciones</th>
                            </tr>
                        </x-slot>

                        <x-slot name="tbody">
                            <tr class="employee-loading-row">
                                <td colspan="3">
                                    <div class="load-animation-container">
                                        <div class="load-animation"></div>
                                    </div>
                                </td>
                            </tr>
                        </x-slot>
                    </x-tablec>
                </div>
            </div>
        </div>
    </main>

    <div class="modal-new-employee" id="modal-employee-form">
        <section class="employee-modal-content" role="dialog" aria-modal="true" aria-labelledby="employee-modal-title">
            <div class="employee-modal-heading">
                <div>
                    <h2 id="employee-modal-title">Nuevo empleado</h2>
                </div>

                <button type="button" class="employee-modal-close" aria-label="Cerrar" onclick="closeEmployeeModal()">
                    &times;
                </button>
            </div>

            <form id="form-employee" enctype="multipart/form-data">
                <input type="hidden" name="idempleado">
                <input type="hidden" name="password" value="Empleado2026@">

                <div class="employee-form-grid">
                    <label>
                        Usuario
                        <input type="text" name="name" maxlength="60" autocomplete="username" required>
                    </label>

                    <label>
                        Correo
                        <input type="email" name="email" maxlength="255" autocomplete="email" required>
                    </label>

                    <label>
                        Rol
                        <select name="idrol" required>
                            <option value="">Seleccione rol</option>
                        </select>
                    </label>

                    <label id="employee-status-field" hidden>
                        Estado
                        <select name="estado" id="employee-status">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </label>

                    <div id="employee-password-field" hidden></div>
                </div>

                <div class="employee-modal-actions">
                    <button type="button" class="employee-secondary-button" id="btn-resend-employee-password" hidden>
                        Reenviar enlace
                    </button>

                    <button type="button" class="employee-secondary-button" onclick="closeEmployeeModal()">
                        Cancelar
                    </button>

                    <button type="submit" class="employee-primary-button" id="btn-save-employee">
                        Guardar
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="modal-new-employee" id="modal-employee-details">
        <section class="employee-modal-content employee-details-content" role="dialog" aria-modal="true"
            aria-labelledby="employee-details-title">

            <div class="employee-modal-heading">
                <div>
                    <h2 id="employee-details-title">Datos del empleado</h2>
                </div>

                <button type="button" class="employee-modal-close" aria-label="Cerrar"
                    onclick="closeEmployeeDetails()">
                    &times;
                </button>
            </div>

            <div id="employee-details-incomplete" class="employee-details-incomplete" hidden>
                <div class="employee-details-incomplete-icon">!</div>
                <p>Datos incompletos de empleado</p>
            </div>

            <div id="employee-details-information" hidden>
                <div class="employee-details-layout">
                    <img id="employee-details-photo" class="employee-details-photo"
                        alt="Fotografía del empleado" hidden>

                    <dl class="employee-details-grid">
                        <div>
                            <dt>Nombre</dt>
                            <dd id="employee-details-name"></dd>
                        </div>

                        <div>
                            <dt>Rol</dt>
                            <dd id="employee-details-role"></dd>
                        </div>

                        <div>
                            <dt>DUI</dt>
                            <dd id="employee-details-dui"></dd>
                        </div>

                        <div>
                            <dt>Teléfono</dt>
                            <dd id="employee-details-phone"></dd>
                        </div>

                        <div>
                            <dt>Inicio de contrato</dt>
                            <dd id="employee-details-contract"></dd>
                        </div>

                        <div>
                            <dt>Estado</dt>
                            <dd id="employee-details-status"></dd>
                        </div>

                        <div>
                            <dt>Departamento</dt>
                            <dd id="employee-details-department"></dd>
                        </div>

                        <div>
                            <dt>Municipio</dt>
                            <dd id="employee-details-municipality"></dd>
                        </div>

                        <div>
                            <dt>Distrito</dt>
                            <dd id="employee-details-district"></dd>
                        </div>

                        <div>
                            <dt>Residencia</dt>
                            <dd id="employee-details-address"></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="employee-modal-actions employee-details-footer">
                <button type="button" class="employee-secondary-button" onclick="closeEmployeeDetails()">
                    Cerrar
                </button>
            </div>
        </section>
    </div>
    <x-toast></x-toast>

    <div class="base-modal-loading" id="base-modal-loading">
        <div class="modal-loading" id="modal-loading">
            <div class="load-animation"></div>
            <h3>Procesando...</h3>
            <p>Esto puede tomar algunos segundos...</p>
        </div>
    </div>

    
    
</x-canvas>
