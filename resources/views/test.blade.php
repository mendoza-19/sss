@push('styless')
<link rel="stylesheet" href="{{ asset('css/tools/modalManager.css') }}">
<link rel="stylesheet" href="{{ asset('css/test/test.css') }}">
@endpush
@push('js')
<script>
    var modalManager;
    document.addEventListener('DOMContentLoaded', function() {
        addOptionSidebar({
            tag: 'Productos',
            urlIcon: '{{ asset("assets/global/editar_black_color.svg") }}',
            href: '{{ route("productos") }}'
        });
        modalManager = new ModalManager();
        modalManager.addModal('modal-test');
        modalManager.addModal('modal-test-2');
        document.getElementById('modal-btn-open').addEventListener('click', function() {
            modalManager.showModalId('modal-test');
        });
        document.getElementById('modal-btn-open-2').addEventListener('click', function() {
            modalManager.showModalId('modal-test-2');
        });
        document.getElementById('modal-btn-close').addEventListener('click', (event) => modalManager.eventClickCloseModal(event));
        document.getElementById('modal-btn-close-2').addEventListener('click', (event) => modalManager.eventClickCloseModal(event));
    });
</script>
<script src="{{ asset('js/tools/modalManager.js') }}"></script>
@endpush
<x-canvas>
    <x-slot name="header">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
    </x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div style="width: 300px; margin-left: 60px;">
                <x-dropdownc title="Productos" classList="dropdown-test">
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                    <p>test</p>
                </x-dropdownc>
            </div>
            <div style="width: 300px; margin-left: 60px;">
                <x-selectc name="selectc-test" id="selectc-test">
                    <option value="">design option</option>
                    <option value="">design option</option>
                    <option value="">design option</option>
                    <option value="">design option</option>
                    <option value="">design option</option>
                    <option value="">design option</option>
                </x-selectc>
            </div>
            <p>{{ $_SERVER['HTTP_HOST'] }}</p>
            <div style="width: 100px; height: 100px;">
                <x-input type="file" name="test" value="test" placeholder="test" idInput="tecal"></x-input>
            </div>
            <div style="width: 400px; height: 45px;">
                <x-input type="text" name="test" value="test" placeholder="test" idInput="text"></x-input>
            </div>
            <div style="width: 400px; height: 45px;">
                <x-input type="email" name="test" value="test" placeholder="test" idInput="text"></x-input>
            </div>
            <div style="width: 400px; height: 45px;">
                <x-input type="password" name="test" value="test" placeholder="test" idInput="text"></x-input>
            </div>
            <div style="width: 400px; height: 45px;">
                <x-input type="number" step="any" name="test" value="test" placeholder="test" idInput="text"></x-input>
            </div>
            <div style="width: 400px; height: 45px;">
                <x-input type="button" name="test" value="test" placeholder="test" idInput="text"></x-input>
            </div>
            <button onclick="showToast('Producto agregado al carrito 🛒')" style="width: 60px; height: 20px">Toast</button>
            <button id="modal-btn-open" style="width: 60px; height: 20px">Modal</button>
            <button id="modal-btn-open-2" style="width: 80px; height: 20px">Modal 2</button>
        </div>
    </main>
    <x-toast></x-toast>
    <div id="modal-test" class="modal-test" style="display: none; width: 100px; height: 100px; background-color: red;">
        <button id="modal-btn-close" style="width: 120px; height: 20px">Cerrar Modal</button>
    </div>
    <div id="modal-test-2" class="modal-test" style="display: none; width: 100px; height: 100px; background-color: blue; top: 2%;">
        <button id="modal-btn-close-2" style="width: 120px; height: 20px">Cerrar Modal</button>
    </div>
</x-canvas>