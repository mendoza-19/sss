@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/searchbar.css') }}">
@endpush
@endonce

@props([
'id' => '',
'classList' => ''
])
<div class="search-bar-component-container {{ $classList }}" id="{{ $id }}">
    <div class="search-bar-component">
        <input type="text" class="input-search" id="{{ $id }}" placeholder="Buscar">
        <button class="search-bar-btn">
            <img src="{{ asset('assets/global/lupa.png') }}" alt="buscar">
        </button>
    </div>
</div>