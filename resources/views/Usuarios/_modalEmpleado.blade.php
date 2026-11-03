<div class="modal fade"
     id="empleadoModal{{ $usuario->id }}"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0 shadow">

            <div class="modal-header text-white py-2"
                 style="background-color:#8B5E3C;">

                <h6 class="modal-title mb-0">
                    <i class="bi bi-person-vcard-fill me-2"></i>
                    Información del empleado
                </h6>

                <button type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body p-3"
                 style="background-color:#FFF9F0;">

                <div class="row g-3">

                    <div class="col-md-4">

                        <div class="text-center">

                            @if($empleado->foto)

                                <img src="data:image/jpeg;base64,{{ base64_encode($empleado->foto) }}"
                                     alt="Foto del empleado"
                                     class="rounded shadow-sm"
                                     style="width:140px; height:170px; object-fit:cover;">

                            @else

                                <div class="d-flex align-items-center justify-content-center rounded mx-auto"
                                     style="width:140px; height:170px; background-color:#F7E7CE;">

                                    <i class="bi bi-person-fill"
                                       style="font-size:60px; color:#8B5E3C;">
                                    </i>

                                </div>

                            @endif

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Nombre completo
                            </div>

                            <div class="small">
                                {{ $empleado->nombreCompleto ?: 'No registrado' }}
                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                DUI
                            </div>

                            <div class="small">
                                {{ $empleado->duiempleado ?: 'No registrado' }}
                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Teléfono
                            </div>

                            <div class="small">
                                {{ $empleado->telefono ?: 'No registrado' }}
                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Fecha de inicio
                            </div>

                            <div class="small">

                                @if($empleado->iniciocontrato)

                                    {{ date('d/m/Y', strtotime($empleado->iniciocontrato)) }}

                                @else

                                    No registrado

                                @endif

                            </div>

                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Rol
                            </div>

                            <div class="small">

                                @if($empleado->rol)

                                    {{ $empleado->rol->descripcion }}

                                @else

                                    No registrado

                                @endif

                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Departamento
                            </div>

                            <div class="small">

                                @if(
                                    $empleado->distrito &&
                                    $empleado->distrito->municipio &&
                                    $empleado->distrito->municipio->departamento
                                )

                                    {{ $empleado->distrito->municipio->departamento->nombre }}

                                @else

                                    No registrado

                                @endif

                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Municipio
                            </div>

                            <div class="small">

                                @if(
                                    $empleado->distrito &&
                                    $empleado->distrito->municipio
                                )

                                    {{ $empleado->distrito->municipio->nombre }}

                                @else

                                    No registrado

                                @endif

                            </div>

                        </div>

                        <div class="mb-2">

                            <div class="fw-semibold small"
                                 style="color:#8B5E3C;">
                                Distrito
                            </div>

                            <div class="small">

                                @if($empleado->distrito)

                                    {{ $empleado->distrito->nombre }}

                                @else

                                    No registrado

                                @endif

                            </div>

                        </div>

                    </div>

                    <div class="col-md-8">

                        <div class="fw-semibold small"
                             style="color:#8B5E3C;">
                            Residencia
                        </div>

                        <div class="small mt-1">
                            {{ $empleado->Residencia ?: 'No registrado' }}
                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="fw-semibold small"
                             style="color:#8B5E3C;">
                            Estado
                        </div>

                        <div class="small mt-1">

                            @if($empleado->estado)

                                <span class="badge bg-success">
                                    Activo
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Inactivo
                                </span>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

            <div class="modal-footer py-2"
                 style="background-color:#F7E7CE;">

                <button type="button"
                        class="btn btn-sm btn-secondary"
                        data-bs-dismiss="modal">

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>