@extends('inicio.principal')

@section('content')
@php
    $usuarios = $usuarios->reject(fn ($usuario) => $usuario->id == auth()->id());
@endphp

<div class="container py-0">

    <div class="card border-0 shadow-sm">

        <div class="card-header text-white py-3"
             style="background-color:#8B5E3C;">

            <div class="d-flex align-items-center justify-content-between">

                <div class="d-flex align-items-center">

                    <i class="bi bi-people-fill me-2 fs-5"></i>

                    <span class="fw-bold">
                        USUARIOS
                    </span>

                </div>

                <a href="{{ route('usuarios.create') }}"
                   class="btn btn-light">

                    <i class="bi bi-person-plus-fill me-1"></i>
                    Nuevo usuario

                </a>

            </div>

        </div>

        <div class="card-body p-4">

            @if (session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif

            @if (session('error'))

                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>

            @endif

            @if ($usuarios->count())

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead style="background-color:#F7E7CE;">

                            <tr>

                                <th>#</th>

                                <th>Usuario</th>

                                <th>Correo</th>

                                <th class="text-center">
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($usuarios as $usuario)

                                <tr>

                                    <td>
                                        {{ $usuario->id }}
                                    </td>

                                    <td>
                                        {{ $usuario->name }}
                                    </td>

                                    <td>
                                        {{ $usuario->email }}
                                    </td>

                                    <td>

                                        <div class="d-flex justify-content-center gap-1">

                                            @if ($usuario->empleado)

                                                {{-- VER INFORMACIÓN --}}

                                                <button type="button"
                                                        class="btn btn-sm text-white"
                                                        style="background-color:#C97A40;"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#empleadoModal{{ $usuario->id }}"
                                                        title="Ver información">

                                                    <i class="bi bi-eye-fill"></i>

                                                </button>


                                                {{-- CONFIGURACIÓN --}}

                                                <button type="button"
                                                        class="btn btn-sm text-white"
                                                        style="background-color:#6F4E37;"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalConfiguracion{{ $usuario->id }}"
                                                        title="Configuración">

                                                    <i class="bi bi-gear-fill"></i>

                                                </button>


                                                {{-- EDITAR EMPLEADO --}}

                                                <form method="POST"
                                                      action="{{ route('empleados.seleccionar') }}"
                                                      style="display:inline;">

                                                    @csrf

                                                    <input type="hidden"
                                                           name="idempleado"
                                                           value="{{ $usuario->empleado->idempleado }}">

                                                    <button type="submit"
                                                            class="btn btn-sm text-white"
                                                            style="background-color:#D4A017;"
                                                            title="Editar empleado">

                                                        <i class="bi bi-pencil-square"></i>

                                                    </button>

                                                </form>

                                            @else

                                                <button type="button"
                                                        class="btn btn-sm btn-secondary"
                                                        disabled
                                                        title="No tiene empleado asociado">

                                                    <i class="bi bi-pencil-square"></i>

                                                </button>

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>


                {{-- MODALES --}}

                @foreach ($usuarios as $usuario)

                    @if ($usuario->empleado)

                        {{-- MODAL VER INFORMACIÓN --}}

                        @include('usuarios._modalEmpleado', [
                            'usuario' => $usuario,
                            'empleado' => $usuario->empleado,
                        ])


                        {{-- MODAL CONFIGURACIÓN --}}

                        @include('usuarios._configuracion', [
                            'usuario' => $usuario,
                        ])

                    @endif

                @endforeach

            @else

                <div class="alert alert-info text-center">
                    No hay usuarios registrados.
                </div>

            @endif

        </div>

    </div>

</div>

@endsection