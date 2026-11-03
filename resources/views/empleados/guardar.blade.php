@extends('inicio.principal')

@section('content')
<div class="container py-0">
    <div class="card border-0 shadow-sm">
        <div class="card-header text-white py-3" style="background-color:#7A9E7E;">
            <h1 class="h5 mb-0">REGISTRAR EMPLEADO</h1>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('empleados.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="idusuario" class="form-label">Cuenta de usuario</label>
                        <select id="idusuario" name="idusuario" class="form-select" required>
                            <option value="">Seleccione una cuenta</option>
                            @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" @selected(old('idusuario') == $usuario->id)>
                                    {{ $usuario->name }} ({{ $usuario->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('idusuario')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="idrol" class="form-label">Rol</label>
                        <select id="idrol" name="idrol" class="form-select" required>
                            <option value="">Seleccione un rol</option>
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->idrol }}" @selected(old('idrol') == $rol->idrol)>
                                    {{ $rol->descripcion }}
                                </option>
                            @endforeach
                        </select>
                        @error('idrol')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="nombreCompleto" class="form-label">Nombre completo</label>
                        <input id="nombreCompleto" name="nombreCompleto" class="form-control" maxlength="60" value="{{ old('nombreCompleto') }}" required>
                        @error('nombreCompleto')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="duiempleado" class="form-label">DUI</label>
                        <input id="duiempleado" name="duiempleado" class="form-control" data-format="sv-dui" inputmode="numeric" maxlength="10" pattern="0[0-9]{7}-[0-9]" value="{{ old('duiempleado') }}" required>
                        @error('duiempleado')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input id="telefono" name="telefono" class="form-control" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7 y tener el formato 7000-0000" value="{{ old('telefono') }}" required>
                        @error('telefono')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="iniciocontrato" class="form-label">Inicio de contrato</label>
                        <input id="iniciocontrato" type="date" name="iniciocontrato" class="form-control" value="{{ old('iniciocontrato') }}" required>
                        @error('iniciocontrato')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="iddistrito" class="form-label">Distrito</label>
                        <select id="iddistrito" name="iddistrito" class="form-select" required>
                            <option value="">Seleccione un distrito</option>
                            @foreach ($distritos as $distrito)
                                <option value="{{ $distrito->iddistrito }}" @selected(old('iddistrito') == $distrito->iddistrito)>
                                    {{ $distrito->nombre }} - {{ $distrito->municipio->nombre }} - {{ $distrito->municipio->departamento->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('iddistrito')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="residencia" class="form-label">Residencia</label>
                        <input id="residencia" name="residencia" class="form-control" value="{{ old('residencia') }}" required>
                        @error('residencia')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="foto" class="form-label">Fotografía</label>
                        <input id="foto" type="file" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
                        @error('foto')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('empleados.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn text-white" style="background-color:#7A9E7E;">Guardar empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection