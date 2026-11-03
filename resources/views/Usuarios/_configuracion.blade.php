<div class="modal fade"
     id="modalConfiguracion{{ $usuario->id }}"
     tabindex="-1"
     aria-hidden="true">

<div class="modal-dialog modal-sm modal-dialog-centered">

    <div class="modal-content border-0 shadow">

        <div class="modal-header text-white"
             style="background-color:#6F4E37;">

            <h6 class="modal-title">

                <i class="bi bi-gear-fill me-2"></i>
                Configuración

            </h6>

            <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal">
            </button>

        </div>

        <form action="{{ route('usuarios.actualizarConfiguracion', $usuario->id) }}"
              method="POST">    

            @csrf
            @method('PUT')

            <div class="modal-body">

                <div class="mb-3">

                    <div class="text-muted small">
                        Usuario
                    </div>

                    <div class="fw-semibold">
                        {{ $usuario->name }}
                    </div>

                </div>

                <div class="mb-3">

                    <div class="text-muted small">
                        Correo
                    </div>

                    <div class="fw-semibold">
                        {{ $usuario->email }}
                    </div>

                </div>

                @if($usuario->empleado)

                    <div class="mb-3">

                        <label class="text-muted small">
                            Estado
                        </label>

                        <select name="estado"
                                class="form-select">

                            <option value="1"
                                {{ $usuario->empleado->estado ? 'selected' : '' }}>

                                Activo

                            </option>

                            <option value="0"
                                {{ !$usuario->empleado->estado ? 'selected' : '' }}>

                                Inactivo

                            </option>

                        </select>

                    </div>

                @endif

            </div>

            <div class="modal-footer py-2">

                <button type="button"
                        class="btn btn-sm btn-secondary"
                        data-bs-dismiss="modal">

                    Cerrar

                </button>

                <button type="submit"
                        class="btn btn-sm text-white"
                        style="background-color:#8B5E3C;">

                    <i class="bi bi-check-circle me-1"></i>
                    Guardar

                </button>

            </div>

        </form>

    </div>

</div>

</div>
