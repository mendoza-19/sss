@php
use App\Http\Controllers\profileUserController;
@endphp

@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/components/navegation.css') }}">
    @endpush

    @push('js')
        <script src="{{ asset('js/components/navegation.js') }}"></script>
    @endpush
@endonce

@props([
    'isSearchBar' => true,
])

<header class="site-header">
    <div class="logo">El Buen Sabor</div>

    @if($isSearchBar)
        <div class="search-bar">
            <input type="text" id="navSearchInput" placeholder="Buscar pan, postres...">
            <button type="button" id="navSearchBtn" aria-label="Buscar producto">
                <img src="{{ asset('assets/global/lupa.png') }}" alt="Buscar">
            </button>
        </div>
    @endif

    @auth
        <div class="user-dropdown-container" id="userDropdownContainer">
            <button type="button" class="user-dropdown-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>

                <span>{{ Auth::user()->name }}</span>
                <span class="arrow-icon">&#9660;</span>
            </button>

            <div class="user-dropdown-menu" id="userDropdownMenu">
                <div class="user-dropdown-header">
                    <div class="user-dropdown-name">
                        {{ Auth::user()->name }}
                    </div>

                    <div class="user-dropdown-email">
                        {{ Auth::user()->email }}
                    </div>
                </div>

                <a href="{{ route('perfil') }}" class="user-dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>

                    Mi perfil
                </a>

                <a href="{{ route('logout') }}" class="user-dropdown-item logout-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>

                    Cerrar sesión
                </a>
            </div>

            <img
                src="{{ profileUserController::getProfilePicture() }}"
                alt="Foto de perfil"
                class="profile-picture"
                onerror="this.onerror=null; this.src='{{ asset('assets/global/profile-picture.png') }}';"
            >
        </div>
    @else
        <button
            type="button"
            class="login-btn"
            onclick="window.location.href='{{ route('login') }}'"
        >
            Iniciar sesión
        </button>
    @endauth
</header>
