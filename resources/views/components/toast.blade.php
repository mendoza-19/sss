@once
@push('styless')
<link rel="stylesheet" href="{{ asset('css/components/toast.css') }}">
@endpush
@push('js')
<script src="{{ asset('js/components/toast.js') }}"></script>
@endpush
@endonce

<!-- Toast Notificación -->
<div id="toast-container" class="toast-container"></div>