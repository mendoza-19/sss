@php
    $usuarioActual = auth()->user();
    $empleadoActual = $usuarioActual?->empleado;
    $idrol = $empleadoActual?->idrol;
@endphp

<div id="sidebar" class="text-white p-3" style="background-color:#5c4033;">

    <div class="d-flex align-items-center mb-4">

        <button class="btn text-white p-0 me-2" id="btnMenu">
            <i class="bi bi-list fs-2"></i>
        </button>

        <h5 class="mb-0 texto-menu">
            Panadería
        </h5>

    </div>

    <ul class="nav flex-column">

        <li class="nav-item mb-2">

            <a href="/dashboard" class="nav-link text-white">

                <i class="bi bi-house-fill"></i>

                <span class="texto-menu">
                    Dashboard
                </span>

            </a>

        </li>

        @if ($idrol == 1)
            <li class="nav-item mb-2">

                <a href="{{ route('usuarios.index') }}" class="nav-link text-white">

                    <i class="bi bi-people-fill"></i>

                    <span class="texto-menu">
                        Usuarios
                    </span>

                </a>

            </li>
            <li class="nav-item mb-2">

                <a href="{{ route('receta.index') }}" class="nav-link text-white">

                    <i class="bi bi-journal-text"></i>

                    <span class="texto-menu">
                        Recetas
                    </span>

                </a>

            </li>

            </li>
        @elseif($idrol == 2)
            <li class="nav-item mb-2">

                <a href="{{ url('/ventas') }}" class="nav-link text-white">

                    <i class="bi bi-basket-fill"></i>

                    <span class="texto-menu">
                        Ventas
                    </span>

                </a>

            </li>

            <li class="nav-item mb-2">

                <a href="{{ url('/productos') }}" class="nav-link text-white">

                    <i class="bi bi-box-seam me-1"></i>

                    <span class="texto-menu">
                        Productos
                    </span>

                </a>

            </li>
        @elseif($idrol == 3)
            <li class="nav-item mb-2">

                <a href="{{ url('/ventas') }}" class="nav-link text-white">

                    <i class="bi bi-basket-fill"></i>

                    <span class="texto-menu">
                        Ventas
                    </span>

                </a>

            </li>
        @endif

    </ul>

</div>
