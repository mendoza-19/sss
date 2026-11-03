@extends('inicio.principal')

@section('content')

<div class="container py-0">

    <div class="card border-0 shadow-sm">

        <div class="card-header text-white py-3" style="background-color:#7A9E7E;">

            <div class="d-flex align-items-center justify-content-center">
                <i class="bi bi-person-badge me-2 fs-5"></i>
                <span class="fw-bold">LISTA DE EMPLEADOS</span>
            </div>

        </div>

        <div class="card-body p-4">

            <div class="d-flex justify-content-end mb-3">

                <a href="{{ route('empleados.create') }}"
                   class="btn text-white px-4"
                   style="background-color:#7A9E7E;">

                    <i class="bi bi-person-plus me-1"></i>
                    Nuevo empleado

                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>

                        <tr>
                            <th class="text-center">#</th>
                            <th>Nombre completo</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Inicio de contrato</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($empleados as $empleado)

                            <tr>

                                <td class="text-center fw-semibold">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        {{ $empleado->nombreCompleto ?? 'No se ha registrado' }}
                                    </span>
                                </td>

                                <td>
                                    {{ $empleado->usuario->name ?? 'No se ha registrado' }}
                                </td>

                                <td>
                                    {{ $empleado->usuario->email ?? 'No se ha registrado' }}
                                </td>

                                <td>
                                    {{ $empleado->iniciocontrato
                                        ? $empleado->iniciocontrato->format('d/m/Y')
                                        : 'No se ha registrado'
                                    }}
                                </td>

                                <td>

                                    @if(strtolower($empleado->estado ?? '') === 'activo')

                                        <span class="text-success fw-semibold">
                                            Activo
                                        </span>

                                    @else

                                        <span class="text-danger fw-semibold">
                                            Inactivo
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <div class="d-flex justify-content-center gap-1">

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEmpleado{{ $empleado->idempleado }}"
                                            title="Ver empleado">

                                            <i class="bi bi-eye"></i>

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="text-center py-5">

                                    <div class="text-muted">

                                        <i class="bi bi-people fs-1 d-block mb-2"></i>

                                        <span>
                                            No hay empleados registrados.
                                        </span>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@foreach($empleados as $empleado)

    <div class="modal fade"
         id="modalEmpleado{{ $empleado->idempleado }}"
         tabindex="-1"
         aria-labelledby="modalEmpleadoLabel{{ $empleado->idempleado }}"
         aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered">

            <div class="modal-content border-0 shadow">

                <div class="modal-header text-white"
                     style="background-color:#7A9E7E;">

                    <h5 class="modal-title"
                        id="modalEmpleadoLabel{{ $empleado->idempleado }}">

                        <i class="bi bi-person-badge me-2"></i>
                        Información del empleado

                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row">

                        <div class="col-md-4 text-center">

                            <div class="mb-3">

                                @if($empleado->foto)

                                    <img
                                        src="data:image/jpeg;base64,{{ base64_encode($empleado->foto) }}"
                                        alt="Foto del empleado"
                                        class="rounded-circle"
                                        style="width:100px;height:100px;object-fit:cover;">

                                @else

                                    <div
                                        class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto"
                                        style="width:100px;height:100px;">

                                        <i class="bi bi-person fs-1 text-secondary"></i>

                                    </div>

                                @endif

                            </div>

                            <h5 class="fw-bold mb-1">
                                {{ $empleado->nombreCompleto ?? 'No se ha registrado' }}
                            </h5>

                            <div class="text-muted">
                                {{ $empleado->rol->descripcion ?? 'No se ha registrado' }}
                            </div>

                        </div>

                        <div class="col-md-8">

                            <div class="mb-4">

                                <h6 class="fw-bold border-bottom pb-2">

                                    <i class="bi bi-person me-2"></i>
                                    Datos personales

                                </h6>

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            DUI
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->duiempleado ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Teléfono
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->telefono ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Usuario
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->usuario->name ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Correo
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->usuario->email ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div>

                                <h6 class="fw-bold border-bottom pb-2">

                                    <i class="bi bi-geo-alt me-2"></i>
                                    Ubicación

                                </h6>

                                <div class="row">

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Departamento
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->distrito->municipio->departamento->nombre ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Municipio
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->distrito->municipio->nombre ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Distrito
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->distrito->nombre ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                    <div class="col-md-6 mb-3">

                                        <div class="text-muted small">
                                            Residencia
                                        </div>

                                        <div class="fw-semibold">
                                            {{ $empleado->Residencia ?? 'No se ha registrado' }}
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cerrar

                    </button>

                </div>

            </div>

        </div>

    </div>

@endforeach

@if(session('success'))

    <div
        class="modal fade"
        id="modalExito"
        tabindex="-1"
        aria-labelledby="modalExitoLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 shadow rounded-3">

                <div class="modal-body text-center py-4">

                    <div
                        class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center"
                        style="width:60px;height:60px;background-color:#d1e7dd;">

                        <i class="bi bi-check-circle-fill text-success fs-2"></i>

                    </div>

                    <h6
                        class="fw-bold mb-1"
                        id="modalExitoLabel">

                        Correcto

                    </h6>

                    <div class="text-muted">
                        {{ session('success') }}
                    </div>

                </div>

            </div>

        </div>

    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const modalElement = document.getElementById('modalExito');

            if (modalElement) {

                const modal = new bootstrap.Modal(modalElement);

                modal.show();

                setTimeout(function () {
                    modal.hide();
                }, 2000);

            }

        });

    </script>

@endif

@endsection