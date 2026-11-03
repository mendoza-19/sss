@extends('inicio.principal')

@section('content')

    <div class="container py-0">

        <div class="card border-0 shadow-sm">

            <div class="card-header text-white py-3" style="background-color:#8B5E3C;">

                <div class="d-flex align-items-center justify-content-center">

                    <i class="bi bi-person-plus-fill me-2 fs-5"></i>

                    <span class="fw-bold">
                        REGISTRAR USUARIO
                    </span>

                </div>

            </div>

            <div class="card-body p-4">

                <form action="{{ route('usuarios.store') }}" method="POST">

                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Nombre
                            </label>

                            <input type="text" name="name" class="form-control" placeholder="Ingrese nombre"
                                value="{{ old('name') }}" required>

                            @error('name')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Correo
                            </label>

                            <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com"
                                value="{{ old('email') }}" required>

                            @error('email')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Contraseña
                            </label>

                            <input type="password" name="password" class="form-control" placeholder="Ingrese contraseña"
                                required>

                            @error('password')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label fw-semibold">
                                Rol
                            </label>

                            <select name="idrol" class="form-select" required>

                                <option value="" disabled {{ old('idrol') ? '' : 'selected' }}>
                                    Seleccione rol
                                </option>

                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->idrol }}" {{ old('idrol') == $rol->idrol ? 'selected' : '' }}>

                                        {{ $rol->descripcion }}

                                    </option>
                                @endforeach

                            </select>

                            @error('idrol')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror

                        </div>

                    </div>

                    <div class="d-flex justify-content-end mt-4">

                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary px-4 me-2">

                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar

                        </a>

                        <button type="submit" class="btn text-white px-4" style="background-color:#8B5E3C;">

                            <i class="bi bi-save me-1"></i>
                            Guardar usuario

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    @if ($errors->any())
        <div id="notificacionesErrores">

            @foreach ($errors->all() as $error)
                <div class="notificacion-error">

                    <div class="icono-error">
                        <i class="bi bi-exclamation-lg"></i>
                    </div>

                    <div class="contenido-error">

                        <div class="titulo-error">
                            Algo salió mal
                        </div>

                        <div class="texto-error">
                            {{ $error }}
                        </div>

                    </div>

                    <button type="button" class="cerrar-error" onclick="cerrarError(this)">

                        <i class="bi bi-x"></i>

                    </button>

                    <div class="progreso-error"></div>

                </div>
            @endforeach

        </div>

        <style>
            #notificacionesErrores {
                position: fixed;
                top: 25px;
                right: 25px;
                width: 380px;
                z-index: 9999;
            }

            .notificacion-error {
                position: relative;
                display: flex;
                align-items: center;
                min-height: 82px;
                margin-bottom: 14px;
                padding: 14px 42px 14px 14px;
                background: #ffffff;
                border-radius: 16px;
                border-left: 5px solid #dc3545;
                box-shadow: 0 10px 35px rgba(0, 0, 0, 0.15);
                overflow: hidden;
                animation: aparecerError 0.5s cubic-bezier(.21, 1.02, .73, 1) forwards;
            }

            .icono-error {
                width: 46px;
                height: 46px;
                min-width: 46px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 13px;
                border-radius: 50%;
                background: #fde8eb;
                color: #dc3545;
                font-size: 23px;
            }

            .contenido-error {
                min-width: 0;
            }

            .titulo-error {
                color: #252525;
                font-size: 15px;
                font-weight: 700;
                margin-bottom: 3px;
            }

            .texto-error {
                color: #707070;
                font-size: 13px;
                line-height: 1.4;
            }

            .cerrar-error {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 27px;
                height: 27px;
                border: 0;
                border-radius: 50%;
                background: transparent;
                color: #999;
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .cerrar-error:hover {
                background: #f1f1f1;
                color: #333;
            }

            .progreso-error {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                width: 100%;
                background: #dc3545;
                animation: progresoError 2s linear forwards;
            }

            @keyframes aparecerError {

                0% {
                    opacity: 0;
                    transform: translateX(100px) scale(0.92);
                }

                60% {
                    opacity: 1;
                    transform: translateX(-8px) scale(1.01);
                }

                100% {
                    opacity: 1;
                    transform: translateX(0) scale(1);
                }

            }

            @keyframes desaparecerError {

                0% {
                    opacity: 1;
                    transform: translateX(0) scale(1);
                }

                100% {
                    opacity: 0;
                    transform: translateX(100px) scale(0.92);
                }

            }

            @keyframes progresoError {

                from {
                    width: 100%;
                }

                to {
                    width: 0%;
                }

            }

            @media (max-width: 576px) {

                #notificacionesErrores {
                    left: 15px;
                    right: 15px;
                    width: auto;
                }

            }
        </style>

        <script>
            function cerrarError(boton) {

                const notificacion = boton.closest('.notificacion-error');

                notificacion.style.animation =
                    'desaparecerError 0.35s ease forwards';

                setTimeout(function() {
                    notificacion.remove();
                }, 350);

            }

            setTimeout(function() {

                document.querySelectorAll('.notificacion-error').forEach(function(notificacion) {

                    notificacion.style.animation =
                        'desaparecerError 0.35s ease forwards';

                    setTimeout(function() {
                        notificacion.remove();
                    }, 350);

                });

            }, 3000);
        </script>
    @endif

@endsection
