@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/tablec.css') }}">
@endpush
@endonce

@props([
'tableClass' => '',
'id' => ''
])

<table class="table-component {{ $tableClass }}" id="{{ $id }}">
    @isset($colgroup)
    <colgroup>
        {{ $colgroup }}
    </colgroup>
    @endisset
    @isset($thead)
    <thead>
        {{ $thead }}
    </thead>
    @endisset
    @isset($tbody)
    <tbody>
        {{ $tbody }}
    </tbody>
    @endisset
</table>