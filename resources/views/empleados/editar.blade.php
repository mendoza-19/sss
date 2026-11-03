@php
    $esMiPerfil = auth()->user()?->empleado?->idempleado == $empleado->idempleado;
    $fotoSrc = $empleado->foto;

    if ($fotoSrc) {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($fotoSrc);

        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            $fotoSrc = 'data:' . $mimeType . ';base64,' . base64_encode($fotoSrc);
        }
    }
@endphp

@push('styless')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/empleados/style.css') }}">
@endpush

<x-canvas>
    <x-slot name="header">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $esMiPerfil ? 'Mi perfil' : 'Editar empleado' }}</title>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">

    <div class="container py-0">

        <div class="card border-0 shadow-sm">

            <div class="card-header text-white py-3" style="background-color:#8B5E3C;">

                <div class="d-flex align-items-center">
                    <i class="bi bi-person-vcard-fill me-2 fs-5"></i>

                    <span class="fw-bold">
                        ACTUALIZAR DATOS
                    </span>
                </div>

            </div>

            <div class="card-body p-4">

                <form method="POST"
                    action="{{ $esMiPerfil ? route('actualizarPerfilEmpleado') : route('empleados.actualizar') }}"
                    enctype="multipart/form-data">

                    @csrf

                    @if (!$esMiPerfil)
                        @method('PUT')
                        <input type="hidden" name="idempleado" value="{{ $empleado->idempleado }}">
                    @endif

                    <div class="row">

                        <div class="col-md-6">

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    DUI del empleado
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text text-white" style="background-color:#8B5E3C;">
                                        <i class="bi bi-card-text"></i>
                                    </span>

                                    <input type="text" name="duiempleado" id="duiempleado" class="form-control"
                                        value="{{ old('duiempleado', $empleado->duiempleado) }}" inputmode="numeric"
                                        maxlength="10" minlength="10" pattern="^0[0-9]{7}-[0-9]$"
                                        title="El DUI debe iniciar con 0 y tener el formato 01234567-8" required>

                                </div>

                                @error('duiempleado')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Teléfono
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text text-white" style="background-color:#8B5E3C;">
                                        <i class="bi bi-telephone-fill"></i>
                                    </span>

                                    <input type="text" name="telefono" id="telefono" class="form-control"
                                        value="{{ old('telefono', $empleado->telefono) }}" inputmode="numeric"
                                        maxlength="9" minlength="9" pattern="[567][0-9]{3}-[0-9]{4}"
                                        title="El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000" required>

                                </div>

                                @error('telefono')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Inicio de contrato
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text text-white" style="background-color:#8B5E3C;">
                                        <i class="bi bi-calendar-event"></i>
                                    </span>

                                    <input type="date" name="iniciocontrato" class="form-control"
                                        value="{{ old('iniciocontrato', $empleado->iniciocontrato ? \Carbon\Carbon::parse($empleado->iniciocontrato)->format('Y-m-d') : '') }}"
                                        required>

                                </div>

                                @error('iniciocontrato')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Residencia
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text text-white" style="background-color:#8B5E3C;">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </span>

                                    <input type="text" id="direccionMostrada" class="form-control"
                                        value="{{ old('residencia', $empleado->Residencia) }}" readonly>

                                    <button type="button" class="btn text-white" style="background-color:#8B5E3C;"
                                        data-bs-toggle="modal" data-bs-target="#modalUbicacion">

                                        <i class="bi bi-pencil-square"></i>

                                    </button>

                                </div>

                                <input type="hidden" name="iddepartamento" id="iddepartamento"
                                    value="{{ old('iddepartamento', $empleado->distrito?->municipio?->departamento?->iddepartamento) }}">

                                <input type="hidden" name="idmunicipio" id="idmunicipio"
                                    value="{{ old('idmunicipio', $empleado->distrito?->municipio?->idmunicipio) }}">

                                <input type="hidden" name="iddistrito" id="iddistrito"
                                    value="{{ old('iddistrito', $empleado->iddistrito) }}">

                                <input type="hidden" name="residencia" id="residencia"
                                    value="{{ old('residencia', $empleado->Residencia) }}">

                                @error('iddistrito')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                                @error('residencia')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Nombre completo
                                </label>

                                <div class="input-group">

                                    <span class="input-group-text text-white" style="background-color:#8B5E3C;">
                                        <i class="bi bi-person-fill"></i>
                                    </span>

                                    <input type="text" name="nombreCompleto" id="nombreCompleto" class="form-control"
                                        value="{{ old('nombreCompleto', $empleado->nombreCompleto) }}" maxlength="60"
                                        pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+( [A-Za-zÁÉÍÓÚáéíóúÑñÜü]+)*"
                                        title="Ingrese un nombre completo válido." required>

                                </div>

                                @error('nombreCompleto')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                            <div class="mb-4">

                                <label class="form-label fw-semibold">
                                    Foto del empleado
                                </label>

                                <div
                                    style="display:flex; flex-direction:row; align-items:center; gap:15px; flex-wrap:nowrap;">

                                    <label for="foto"
                                        class="btn text-white d-flex align-items-center justify-content-center"
                                        style="background-color:#8B5E3C; width:48px; height:48px; min-width:48px;"
                                        title="Tomar o seleccionar foto">

                                        <i class="bi bi-camera-fill fs-5"></i>

                                    </label>

                                    <input type="file" name="foto" id="foto" class="d-none"
                                        accept="image/jpeg,image/png,image/jpg,image/webp" capture="environment">

                                    @if ($fotoSrc)
                                        <img id="vistaPrevia"
                                            src="{{ $fotoSrc }}"
                                            alt="Foto del empleado"
                                            style="width:180px; height:210px; object-fit:cover; display:block;"
                                            class="rounded shadow-sm">
                                    @else
                                        <div id="contenedorFoto"
                                            class="d-flex align-items-center justify-content-center rounded shadow-sm"
                                            style="width:180px; height:210px; min-width:180px; background-color:#F7E7CE;">

                                            <i class="bi bi-person-fill" style="font-size:80px; color:#8B5E3C;">
                                            </i>

                                        </div>

                                        <img id="vistaPrevia" src="" alt="Vista previa"
                                            style="width:180px; height:210px; object-fit:cover; display:none;"
                                            class="rounded shadow-sm">
                                    @endif

                                </div>

                                <small class="text-muted">
                                    JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                                </small>

                                @error('foto')
                                    <small class="text-danger d-block">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="border-top pt-4 mt-2 d-flex justify-content-end gap-2">

                        <a href="{{ $esMiPerfil ? route('menu') : route('empleados.index') }}"
                            class="btn btn-outline-secondary px-4">

                            <i class="bi bi-arrow-left me-1"></i>
                            Regresar

                        </a>

                        <button type="submit" class="btn text-white px-4" style="background-color:#8B5E3C;">

                            <i class="bi bi-check-circle me-1"></i>
                            Actualizar

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div class="modal fade" id="modalUbicacion" tabindex="-1" aria-labelledby="modalUbicacionLabel"
        aria-hidden="true">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header text-white" style="background-color:#8B5E3C;">

                    <h5 class="modal-title" id="modalUbicacionLabel">
                        <i class="bi bi-geo-alt-fill me-2"></i>
                        Seleccionar ubicación
                    </h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Departamento
                        </label>

                        <select id="modalDepartamento" class="form-select">

                            <option value="">
                                Seleccione departamento
                            </option>

                            @foreach ($departamentos as $departamento)
                                <option value="{{ $departamento->iddepartamento }}">
                                    {{ $departamento->nombre }}
                                </option>
                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Municipio
                        </label>

                        <select id="modalMunicipio" class="form-select" disabled>

                            <option value="">
                                Seleccione municipio
                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Distrito
                        </label>

                        <select id="modalDistrito" class="form-select" disabled>

                            <option value="">
                                Seleccione distrito
                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Residencia
                        </label>

                        <input type="text" id="modalResidencia" class="form-control" maxlength="255"
                            value="{{ old('residencia', $empleado->Residencia) }}"
                            placeholder="Ej. Candelaria o Cantón El Pinal">

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button type="button" class="btn text-white" id="guardarUbicacion"
                        style="background-color:#8B5E3C;">

                        <i class="bi bi-check-circle me-1"></i>
                        Guardar ubicación

                    </button>

                </div>

            </div>

        </div>

    </div>

    @if (session('success'))
        <div class="modal fade" id="modalExito" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content border-0 shadow">

                    <div class="modal-body text-center py-4">

                        <i class="bi bi-check-circle-fill text-success fs-1"></i>

                        <div class="fw-bold mt-2">
                            Información actualizada
                        </div>

                        <div class="text-muted small">
                            {{ session('success') }}
                        </div>

                    </div>

                </div>

            </div>

        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const formulario = document.querySelector('form');
            const dui = document.getElementById('duiempleado');
            const telefono = document.getElementById('telefono');
            const nombreCompleto = document.getElementById('nombreCompleto');
            const foto = document.getElementById('foto');
            const vistaPrevia = document.getElementById('vistaPrevia');
            const contenedorFoto = document.getElementById('contenedorFoto');

            const departamentoSelect = document.getElementById('modalDepartamento');
            const municipioSelect = document.getElementById('modalMunicipio');
            const distritoSelect = document.getElementById('modalDistrito');
            const residenciaInput = document.getElementById('modalResidencia');

            const iddepartamento = document.getElementById('iddepartamento');
            const idmunicipio = document.getElementById('idmunicipio');
            const iddistrito = document.getElementById('iddistrito');
            const residencia = document.getElementById('residencia');

            dui.addEventListener('input', function() {

                let numeros = this.value.replace(/\D/g, '');

                if (numeros.length > 0 && numeros.charAt(0) !== '0') {
                    numeros = '';
                }

                numeros = numeros.slice(0, 9);

                if (numeros.length > 8) {
                    numeros = numeros.slice(0, 8) + '-' + numeros.slice(8);
                }

                this.value = numeros;
            });

            telefono.addEventListener('input', function() {

                let numeros = this.value.replace(/\D/g, '');

                if (
                    numeros.length > 0 &&
                    !['5', '6', '7'].includes(numeros.charAt(0))
                ) {
                    numeros = '';
                }

                numeros = numeros.slice(0, 8);

                if (numeros.length > 4) {
                    numeros = numeros.slice(0, 4) + '-' + numeros.slice(4);
                }

                this.value = numeros;
            });

            nombreCompleto.addEventListener('input', function() {

                let valor = this.value;

                valor = valor.replace(
                    /[^A-Za-zÁÉÍÓÚáéíóúÑñÜü ]/g,
                    ''
                );

                valor = valor.replace(/\s+/g, ' ');
                valor = valor.slice(0, 60);

                valor = valor
                    .toLowerCase()
                    .replace(
                        /(^|\s)([a-záéíóúñü])/g,
                        function(match, espacio, letra) {
                            return espacio + letra.toUpperCase();
                        }
                    );

                this.value = valor;
            });

            if (foto) {

                foto.addEventListener('change', function() {

                    const archivo = this.files[0];

                    if (!archivo) {
                        return;
                    }

                    const lector = new FileReader();

                    lector.onload = function(evento) {

                        if (vistaPrevia) {
                            vistaPrevia.src = evento.target.result;
                            vistaPrevia.style.display = 'block';
                        }

                        if (contenedorFoto) {
                            contenedorFoto.style.display = 'none';
                        }

                    };

                    lector.readAsDataURL(archivo);
                });

            }

            const departamentoActual = @json(old('iddepartamento', $empleado->distrito?->municipio?->departamento?->iddepartamento));

            const municipioActual = @json(old('idmunicipio', $empleado->distrito?->municipio?->idmunicipio));

            const distritoActual = @json(old('iddistrito', $empleado->iddistrito));

            function cargarMunicipios(id, seleccionar = null) {

                municipioSelect.innerHTML = '<option value="">Seleccione municipio</option>';
                distritoSelect.innerHTML = '<option value="">Seleccione distrito</option>';

                municipioSelect.disabled = true;
                distritoSelect.disabled = true;

                if (!id) {
                    return;
                }

                fetch(`/municipios/${id}`)
                    .then(response => {

                        if (!response.ok) {
                            throw new Error('No se pudieron cargar los municipios');
                        }

                        return response.json();

                    })
                    .then(municipios => {

                        municipioSelect.disabled = false;

                        municipios.forEach(municipio => {

                            const option = document.createElement('option');

                            option.value = municipio.idmunicipio;
                            option.textContent = municipio.nombre;

                            if (
                                seleccionar !== null &&
                                String(seleccionar) === String(municipio.idmunicipio)
                            ) {
                                option.selected = true;
                            }

                            municipioSelect.appendChild(option);

                        });

                        if (seleccionar) {
                            cargarDistritos(seleccionar, distritoActual);
                        }

                    })
                    .catch(error => {
                        console.error(error);
                    });
            }

            function cargarDistritos(id, seleccionar = null) {

                distritoSelect.innerHTML = '<option value="">Seleccione distrito</option>';
                distritoSelect.disabled = true;

                if (!id) {
                    return;
                }

                fetch(`/distritos/${id}`)
                    .then(response => {

                        if (!response.ok) {
                            throw new Error('No se pudieron cargar los distritos');
                        }

                        return response.json();

                    })
                    .then(distritos => {

                        distritoSelect.disabled = false;

                        distritos.forEach(distrito => {

                            const option = document.createElement('option');

                            option.value = distrito.iddistrito;
                            option.textContent = distrito.nombre;

                            if (
                                seleccionar !== null &&
                                String(seleccionar) === String(distrito.iddistrito)
                            ) {
                                option.selected = true;
                            }

                            distritoSelect.appendChild(option);

                        });

                    })
                    .catch(error => {
                        console.error(error);
                    });
            }

            departamentoSelect.addEventListener('change', function() {

                iddepartamento.value = this.value;
                idmunicipio.value = '';
                iddistrito.value = '';

                cargarMunicipios(this.value);
            });

            municipioSelect.addEventListener('change', function() {

                idmunicipio.value = this.value;
                iddistrito.value = '';

                cargarDistritos(this.value);
            });

            distritoSelect.addEventListener('change', function() {
                iddistrito.value = this.value;
            });

            document.getElementById('guardarUbicacion').addEventListener('click', function() {

                if (
                    departamentoSelect.value === '' ||
                    municipioSelect.value === '' ||
                    distritoSelect.value === '' ||
                    residenciaInput.value.trim() === ''
                ) {
                    return;
                }

                iddepartamento.value = departamentoSelect.value;
                idmunicipio.value = municipioSelect.value;
                iddistrito.value = distritoSelect.value;
                residencia.value = residenciaInput.value.trim();

                document.getElementById('direccionMostrada').value =
                    residenciaInput.value.trim();

                const modalElement = document.getElementById('modalUbicacion');
                const modal = bootstrap.Modal.getInstance(modalElement);

                if (modal) {
                    modal.hide();
                }
            });

            formulario.addEventListener('submit', function(event) {

                let valido = true;

                const campos = [
                    dui,
                    telefono,
                    nombreCompleto
                ];

                campos.forEach(function(campo) {

                    if (campo.value.trim() === '' || !campo.checkValidity()) {

                        campo.classList.add('is-invalid');
                        valido = false;

                    } else {

                        campo.classList.remove('is-invalid');

                    }

                });

                const inicioContrato = document.querySelector('[name="iniciocontrato"]');

                if (
                    inicioContrato.value.trim() === '' ||
                    !inicioContrato.checkValidity()
                ) {

                    inicioContrato.classList.add('is-invalid');
                    valido = false;

                } else {

                    inicioContrato.classList.remove('is-invalid');

                }

                if (residencia.value.trim() === '') {
                    valido = false;
                }

                if (!valido) {
                    event.preventDefault();
                }
            });

            if (departamentoActual) {

                departamentoSelect.value = departamentoActual;

                cargarMunicipios(
                    departamentoActual,
                    municipioActual
                );
            }

            @if (session('success'))

                const modalExito = document.getElementById('modalExito');

                if (modalExito) {

                    const modal = new bootstrap.Modal(modalExito);

                    modal.show();

                    setTimeout(function() {
                        modal.hide();
                    }, 2000);

                }
            @endif

        });
    </script>
            </div>
        </div>
    </main>
</x-canvas>
