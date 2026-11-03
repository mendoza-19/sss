@extends('inicio.principal')

@section('content')

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">

    <h2 class="fw-bold">
        Nueva receta
    </h2>

    <a href="{{ route('receta.index') }}"
       class="btn btn-secondary">

        <i class="bi bi-arrow-left me-1"></i>
        Regresar

    </a>

</div>

<div class="card shadow-sm border-0">

    <div class="card-body">

        <form method="POST"
              action="{{ route('receta.guardar') }}">

            @csrf

            <div class="mb-3">

                <label for="nombre"
                       class="form-label fw-bold">

                    Nombre

                </label>

                <input type="text"
                       class="form-control"
                       id="nombre"
                       name="nombre"
                       value="{{ old('nombre') }}"
                       required>

                @error('nombre')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="mb-3">

                <label for="descripcion"
                       class="form-label fw-bold">

                    Descripción

                </label>

                <textarea class="form-control"
                          id="descripcion"
                          name="descripcion"
                          rows="4"
                          required>{{ old('descripcion') }}</textarea>

                @error('descripcion')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="mb-3">

                <label for="instrucciones"
                       class="form-label fw-bold">

                    Instructivo

                </label>

                <textarea class="form-control"
                          id="instrucciones"
                          name="instrucciones"
                          rows="6"
                          required>{{ old('instrucciones') }}</textarea>

                @error('instrucciones')
                    <div class="text-danger">
                        {{ $message }}
                    </div>
                @enderror

            </div>

            <div class="d-flex justify-content-end gap-2">

                <a href="{{ route('receta.index') }}"
                   class="btn btn-secondary">

                    Cancelar

                </a>

                <button type="submit"
                        class="btn text-white"
                        style="background-color:#7A9E7E;">

                    <i class="bi bi-save me-1"></i>
                    Guardar receta

                </button>

            </div>

        </form>

    </div>

</div>

</div>

@endsection