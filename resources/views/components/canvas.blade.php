<!DOCTYPE html>
<html lang="es-SV">

<head>
    <link rel="icon" type="image/png" href="{{ asset('assets/global/logo-panaderia-icon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @isset($header)
    {{ $header }}
    @endisset
    @stack('header')

    @isset($metas)
    {{ $metas }}
    @endisset
    @stack('styless')
</head>

<body>
    {{ $slot }}
    <script>
        let URL_APP = "{{ request()->getHost() ?: parse_url(config('app.url'), PHP_URL_HOST) ?: '' }}";
        let csrf_token = "{{ csrf_token() }}";
    </script>
    <script src="{{ asset('js/components/form-formats.js') }}" defer></script>
    @stack('js')
</body>

@isset($footer)
{{ $footer }}
@endisset

</html>