
@php
    $esMiPerfil = auth()->user()?->empleado?->idempleado == $empleado->idempleado;
    $fotoSrc = null;

    if ($empleado->foto) {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($empleado->foto);

        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            $fotoSrc = 'data:' . $mimeType . ';base64,' . base64_encode($empleado->foto);
        }
    }

    $distritoActual = $empleado->distrito;
    $municipioActual = $distritoActual?->municipio;
    $departamentoActual = $municipioActual?->departamento;
@endphp

@push('styless')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/empleados/style.css') }}">
@endpush

@push('js')
    @include('principal.sidebar-menu')
@endpush

<x-canvas>
    <x-slot name="header">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Perfil</title>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    </x-slot>

    <main>
        <x-sidebar></x-sidebar>

        <div class="body-content">
            <x-navegation></x-navegation>

            <div class="page-content">
                <div class="employee-profile-page">

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="employee-profile-card">

                        <div class="employee-profile-body">

                            <form
                                action="{{ $esMiPerfil ? route('actualizarPerfilEmpleado') : route('empleados.actualizar') }}"
                                method="POST"
                                enctype="multipart/form-data"
                                class="employee-profile-form"
                            >
                                @csrf

                                @if (!$esMiPerfil)
                                    @method('PUT')
                                    <input type="hidden" name="idempleado" value="{{ $empleado->idempleado }}">
                                @endif

                                <div class="row g-4">

                                    <div class="col-md-4 employee-profile-photo-wrap">
                                        <div class="employee-profile-photo-column h-100">

                                            <h4 class="employee-profile-column-title">Perfil</h4>

                                            <img
                                                src="{{ $fotoSrc ?: asset('assets/global/profile-picture.png') }}"
                                                alt="Foto del empleado"
                                                class="employee-profile-photo mb-3"
                                            >

                                            <label for="foto" class="form-label fw-bold text-center">
                                                Fotografía del empleado
                                            </label>

                                            <input
                                                type="file"
                                                name="foto"
                                                id="foto"
                                                class="form-control"
                                                accept=".jpg,.jpeg,.png,.webp"
                                            >

                                            <small class="text-muted text-center mt-2">
                                                Formatos permitidos: JPG, PNG y WEBP.
                                            </small>

                                            @error('foto')
                                                <small class="text-danger mt-1">{{ $message }}</small>
                                            @enderror

                                            <div class="employee-profile-photo-meta">
                                                <div class="employee-profile-meta-item">
                                                    <span class="employee-profile-role">
                                                        {{ $empleado->rol->descripcion ?? 'No registrado' }}
                                                    </span>
                                                </div>
                                                <div class="employee-profile-meta-item">
                                                    <label for="fechaContrato" class="form-label">
                                                        Fecha de contrato
                                                    </label>
                                                    <input
                                                        type="date"
                                                        id="fechaContrato"
                                                        class="form-control"
                                                        value="{{ $empleado->iniciocontrato ? \Carbon\Carbon::parse($empleado->iniciocontrato)->format('Y-m-d') : '' }}"
                                                        disabled
                                                    >
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-md-8 employee-profile-details">
                                        <div class="row g-3">

                                            <div class="col-12">
                                                <label for="nombreCompleto" class="form-label">
                                                    Nombre completo
                                                </label>

                                                <input
                                                    type="text"
                                                    name="nombreCompleto"
                                                    id="nombreCompleto"
                                                    class="form-control"
                                                    value="{{ old('nombreCompleto', $empleado->nombreCompleto) }}"
                                                    maxlength="60"
                                                    pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+( [A-Za-zÁÉÍÓÚáéíóúÑñÜü]+)*"
                                                    title="Ingrese un nombre completo válido"
                                                    required
                                                >

                                                <div class="invalid-feedback">
                                                    Complete correctamente el nombre completo.
                                                </div>

                                                @error('nombreCompleto')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="duiempleado" class="form-label">
                                                    DUI
                                                </label>

                                                <input
                                                    type="text"
                                                    name="duiempleado"
                                                    id="duiempleado"
                                                    class="form-control"
                                                    value="{{ preg_replace('/\D+/', '', old('duiempleado', $empleado->duiempleado)) }}"
                                                    inputmode="numeric"
                                                    maxlength="10"
                                                    minlength="10"
                                                    pattern="0[0-9]{7}-[0-9]"
                                                    required
                                                >

                                                <div class="invalid-feedback">
                                                    Complete correctamente el DUI.
                                                </div>

                                                @error('duiempleado')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label for="telefono" class="form-label">
                                                    Teléfono
                                                </label>

                                                <input
                                                    type="text"
                                                    name="telefono"
                                                    id="telefono"
                                                    class="form-control"
                                                    value="{{ preg_replace('/\D+/', '', old('telefono', $empleado->telefono)) }}"
                                                    inputmode="numeric"
                                                    maxlength="9"
                                                    minlength="9"
                                                    pattern="[567][0-9]{3}-[0-9]{4}"
                                                    required
                                                >

                                                <div class="invalid-feedback">
                                                    Complete correctamente el teléfono.
                                                </div>

                                                @error('telefono')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-12">
                                                <label for="direccionMostrada" class="form-label">
                                                    Residencia
                                                </label>

                                                <div class="input-group">
                                                    <input
                                                        type="text"
                                                        id="direccionMostrada"
                                                        class="form-control"
                                                        value="{{ collect([
                                                            $empleado->Residencia,
                                                            $distritoActual?->nombre,
                                                            $municipioActual?->nombre,
                                                            $departamentoActual?->nombre,
                                                        ])->filter()->implode(', ') }}"
                                                        readonly
                                                    >

                                                    <button
                                                        type="button"
                                                        class="btn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalUbicacion"
                                                    >
                                                        <i class="bi bi-pencil me-1"></i>
                                                        Modificar
                                                    </button>
                                                </div>

                                                <div
                                                    id="errorResidenciaPrincipal"
                                                    class="text-danger small mt-1 d-none"
                                                >
                                                    <i class="bi bi-exclamation-circle"></i>
                                                    Complete la dirección.
                                                </div>
                                            </div>

                                            <div class="col-12 employee-profile-submit">
                                                <button type="submit" class="btn employee-profile-save">
                                                    <i class="bi bi-save me-1"></i>
                                                    Actualizar información
                                                </button>
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <input
                                    type="hidden"
                                    name="iddepartamento"
                                    id="iddepartamento"
                                    value="{{ old('iddepartamento', $departamentoActual?->iddepartamento ?? '') }}"
                                >

                                <input
                                    type="hidden"
                                    name="idmunicipio"
                                    id="idmunicipio"
                                    value="{{ old('idmunicipio', $municipioActual?->idmunicipio ?? '') }}"
                                >

                                <input
                                    type="hidden"
                                    name="iddistrito"
                                    id="iddistrito"
                                    value="{{ old('iddistrito', $empleado->iddistrito ?? '') }}"
                                >

                                <input
                                    type="hidden"
                                    name="residencia"
                                    id="residencia"
                                    value="{{ old('residencia', $empleado->Residencia ?? '') }}"
                                >

                                @error('iddistrito')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                                @error('residencia')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @if (session('success'))
        <div
            class="modal fade"
            id="employeeSuccessModal"
            tabindex="-1"
            aria-labelledby="employeeSuccessModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content employee-success-modal-content">
                    <div class="modal-body">
                        <span class="employee-success-icon">
                            <i class="bi bi-check-lg"></i>
                        </span>
                        <h2 id="employeeSuccessModalLabel">¡Actualización exitosa!</h2>
                        <p>{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div
        class="modal fade"
        id="modalUbicacion"
        tabindex="-1"
        aria-labelledby="modalUbicacionLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalUbicacionLabel">
                        <i class="bi bi-geo-alt me-2"></i>
                        Modificar dirección
                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"
                    ></button>
                </div>

                <div class="modal-body">
                    <div id="errorUbicacion" class="alert alert-danger d-none" role="alert"></div>

                    <label for="modalDepartamento" class="form-label">Departamento</label>
                    <select id="modalDepartamento" class="form-select mb-3">
                        <option value="">Seleccione departamento</option>

                        @foreach ($departamentos as $departamento)
                            <option value="{{ $departamento->iddepartamento }}">
                                {{ $departamento->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <label for="modalMunicipio" class="form-label">Municipio</label>
                    <select id="modalMunicipio" class="form-select mb-3" disabled>
                        <option value="">Seleccione municipio</option>
                    </select>

                    <label for="modalDistrito" class="form-label">Distrito</label>
                    <select id="modalDistrito" class="form-select mb-3" disabled>
                        <option value="">Seleccione distrito</option>
                    </select>

                    <label for="modalResidencia" class="form-label">Residencia</label>
                    <input
                        type="text"
                        id="modalResidencia"
                        class="form-control"
                        maxlength="255"
                        value="{{ old('residencia', $empleado->Residencia ?? '') }}"
                        required
                    >
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="btn btn-save-location"
                        id="guardarUbicacion"
                    >
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar dirección
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const successModalElement = document.getElementById('employeeSuccessModal');
            const photoInput = document.getElementById('foto');

            photoInput?.addEventListener('change', () => {
                const file = photoInput.files?.[0];
                if (!file) return;

                const currentPreview = document.querySelector(
                    '.employee-profile-photo, .employee-profile-placeholder'
                );
                if (!currentPreview) return;

                const preview = document.createElement('img');
                preview.src = URL.createObjectURL(file);
                preview.alt = 'Foto del empleado';
                preview.className = 'employee-profile-photo mb-3';
                currentPreview.replaceWith(preview);
            });

            if (successModalElement) {
                const successModal = new bootstrap.Modal(successModalElement);
                successModal.show();
                window.setTimeout(() => successModal.hide(), 3000);
            }

            const formatearNumero = (id, digitosMaximos, posicionGuion, primerDigitoPermitido) => {
                const campo = document.getElementById(id);

                const aplicarFormato = () => {
                    const digitos = campo.value.replace(/\D/g, '').slice(0, digitosMaximos);

                    if (digitos && !primerDigitoPermitido.test(digitos[0])) {
                        campo.value = '';
                        return;
                    }

                    campo.value = digitos.length > posicionGuion
                        ? `${digitos.slice(0, posicionGuion)}-${digitos.slice(posicionGuion)}`
                        : digitos;
                };

                campo.addEventListener('input', aplicarFormato);
                aplicarFormato();
            };

            formatearNumero('duiempleado', 9, 8, /^0$/);
            formatearNumero('telefono', 8, 4, /^[567]$/);

            const departamento = document.getElementById('modalDepartamento');
            const municipio = document.getElementById('modalMunicipio');
            const distrito = document.getElementById('modalDistrito');
            const residencia = document.getElementById('modalResidencia');
            const error = document.getElementById('errorUbicacion');

            const idDepartamento = document.getElementById('iddepartamento');
            const idMunicipio = document.getElementById('idmunicipio');
            const idDistrito = document.getElementById('iddistrito');
            const residenciaHidden = document.getElementById('residencia');

            const cargarOpciones = async (
                select,
                url,
                placeholder,
                seleccionado = '',
                claveId = ''
            ) => {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('No se pudieron cargar las opciones de ubicación.');
                }

                const opciones = await response.json();

                select.replaceChildren(new Option(placeholder, ''));

                opciones.forEach(opcion => {
                    const id = claveId
                        ? opcion[claveId]
                        : (opcion.idmunicipio ?? opcion.iddistrito);

                    select.add(new Option(opcion.nombre, id));
                });

                select.disabled = opciones.length === 0;

                if (seleccionado) {
                    select.value = String(seleccionado);
                }
            };

            const mostrarError = mensaje => {
                error.textContent = mensaje;
                error.classList.remove('d-none');
            };

            const cargarMunicipios = async (id, seleccionado = '') => {
                municipio.disabled = true;
                municipio.replaceChildren(new Option('Seleccione municipio', ''));

                distrito.replaceChildren(new Option('Seleccione distrito', ''));
                distrito.disabled = true;

                if (id) {
                    await cargarOpciones(
                        municipio,
                        `/municipios/${id}`,
                        'Seleccione municipio',
                        seleccionado,
                        'idmunicipio'
                    );
                }
            };

            const cargarDistritos = async (id, seleccionado = '') => {
                distrito.disabled = true;
                distrito.replaceChildren(new Option('Seleccione distrito', ''));

                if (id) {
                    await cargarOpciones(
                        distrito,
                        `/distritos/${id}`,
                        'Seleccione distrito',
                        seleccionado,
                        'iddistrito'
                    );
                }
            };

            departamento.addEventListener('change', async () => {
                error.classList.add('d-none');

                idDepartamento.value = departamento.value;
                idMunicipio.value = '';
                idDistrito.value = '';

                try {
                    await cargarMunicipios(departamento.value);
                } catch (exception) {
                    mostrarError(exception.message);
                }
            });

            municipio.addEventListener('change', async () => {
                error.classList.add('d-none');

                idMunicipio.value = municipio.value;
                idDistrito.value = '';

                try {
                    await cargarDistritos(municipio.value);
                } catch (exception) {
                    mostrarError(exception.message);
                }
            });

            distrito.addEventListener('change', () => {
                idDistrito.value = distrito.value;
            });

            document.getElementById('guardarUbicacion').addEventListener('click', () => {
                error.classList.add('d-none');

                if (
                    !departamento.value ||
                    !municipio.value ||
                    !distrito.value ||
                    !residencia.value.trim()
                ) {
                    mostrarError(
                        'Selecciona el departamento, municipio y distrito, e ingresa tu residencia.'
                    );
                    return;
                }

                idDepartamento.value = departamento.value;
                idMunicipio.value = municipio.value;
                idDistrito.value = distrito.value;
                residenciaHidden.value = residencia.value.trim();

                document.getElementById('direccionMostrada').value = [
                    residencia.value.trim(),
                    distrito.selectedOptions[0].text,
                    municipio.selectedOptions[0].text,
                    departamento.selectedOptions[0].text
                ].join(', ');

                const modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalUbicacion')
                );

                modal?.hide();
            });

            const ubicacionActual = async () => {
                if (!idDepartamento.value) {
                    return;
                }

                try {
                    departamento.value = idDepartamento.value;

                    await cargarMunicipios(
                        idDepartamento.value,
                        idMunicipio.value
                    );

                    await cargarDistritos(
                        idMunicipio.value,
                        idDistrito.value
                    );
                } catch (exception) {
                    mostrarError(exception.message);
                }
            };

            document.getElementById('modalUbicacion').addEventListener('show.bs.modal', () => {
                residencia.value = residenciaHidden.value;
                error.classList.add('d-none');
            });

            ubicacionActual();
        });
    </script>
</x-canvas>
