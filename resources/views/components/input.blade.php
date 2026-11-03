@props([
'type' => 'text',
'name' => '',
'value' => '',
'placeholder' => '',
'clickEvent' => '',
'classInput' => '',
'idInput' => '',
'step' => '1', //Solo para input type="number" e indica cuandos decimales se permiten y su valores pueden ser 0.00, 1.5, 1, 0.0, 100, 200.00, etc, en caso que se quiera permitir todos los números se puede poner any
])
@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/input.css') }}">
@endpush

@push('js')
<script src="{{ asset('/js/components/input.js') }}"></script>
@endpush
@endonce
<label for="{{ $idInput }}" class="input-label-file" style="{{ $type != 'file' ? 'display: none;' : 'display: flex;' }}">
    <img src="{{ asset('assets/global/icon-uploaded.svg') }}" alt="upload">
    <img class="input-img-hidden" src="{{ asset('assets/global/icon-file.svg') }}" alt="file">
    <h2>UPLOAD FILE</h2>
    <p></p>
</label>
<input type="{{ $type }}" step="{{ $step }}" class="input-{{ $type }} input {{ $classInput }}" name="{{ $name }}" value="{{ $value }}" onClick="{{ $clickEvent }}" id="{{ $idInput }}" placeholder="{{ $placeholder }}">