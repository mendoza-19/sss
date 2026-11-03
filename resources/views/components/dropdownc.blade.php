@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/dropdownc.css') }}">
@endpush

@push('js')
<script src="{{ asset('js/components/dropdownc.js') }}"></script>
@endpush
@endonce

@props([
'title' => '',
'classList'=>''
])
<div class="dropdown-container {{ $classList }}">
    <div class="dropdown-header">
        <p>{{ $title }}</p>
        <span>▼</span>
    </div>
    <div class="dropdown-body hidden">
        {{ $slot }}
    </div>
</div>