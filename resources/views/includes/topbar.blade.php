@php 
    $usuarioActual = auth()->user(); 
    $empleadoActual = $usuarioActual?->empleado; 
@endphp 
 
<div class="container-fluid d-flex align-items-center justify-content-between text-white" 
     style="background-color:#4A2E1B; height:40px; min-height:60px; flex-shrink:0;"> 
 
    <div class="d-flex align-items-center gap-3"> 
 
        <span class="navbar-brand fw-bold mb-0" 
              style="margin-left:80px; font-size:28px;"> 
 
            Panaderia El buen sabor 
 
        </span> 
 
    </div> 
 
    <div class="dropdown me-3"> 
 
        <button 
            class="btn dropdown-toggle d-flex align-items-center gap-2 text-white" 
            type="button" 
            data-bs-toggle="dropdown" 
            style="background-color:#8B5E3C; border:none;"> 
 
            @if($empleadoActual?->foto) 
 
                <img 
                    src="data:image/jpeg;base64,{{ base64_encode($empleadoActual->foto) }}" 
                    alt="Foto" 
                    style="width:32px; height:32px; object-fit:cover;" 
                    class="rounded-circle"> 
 
            @else 
 
                <i class="bi bi-person-circle fs-4"></i> 
 
            @endif 
 
            <span> 
                {{ $usuarioActual?->name ?? 'Usuario' }} 
            </span> 
 
        </button> 
 
        <ul class="dropdown-menu dropdown-menu-end"> 
 
            <li> 
 
                @if($empleadoActual)

                    <form method="POST"
                          action="{{ route('empleados.seleccionar') }}"
                          class="m-0">

                        @csrf

                        <input 
                            type="hidden" 
                            name="idempleado" 
                            value="{{ $empleadoActual->idempleado }}">

                        <button 
                            type="submit"
                            class="dropdown-item">

                            <i class="bi bi-person me-2"></i>
                            Perfil

                        </button>

                    </form>

                @else

                    <a 
                        class="dropdown-item" 
                        href="#">

                        <i class="bi bi-person me-2"></i>
                        Perfil

                    </a>

                @endif
 
            </li> 
 
            <li> 
 
                <hr class="dropdown-divider"> 
 
            </li> 
 
            <li> 
 
                <a 
                    class="dropdown-item text-danger" 
                    href="{{ route('logout') }}"> 
 
                    <i class="bi bi-box-arrow-right me-2"></i> 
                    Salir 
 
                </a> 
 
            </li> 
 
        </ul> 
 
    </div> 
 
</div>