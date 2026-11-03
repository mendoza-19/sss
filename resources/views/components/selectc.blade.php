@props([
'name' => '',
'id' => '',
'classList' => '',
])

@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/selectc.css') }}">
@endpush
@endonce
<select name="{{ $name }}" id="{{ $id }}" class="select-container {{ $classList }}">
    {{ $slot }}
</select>