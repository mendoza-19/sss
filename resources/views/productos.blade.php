@once
    @push('styless')
        <link rel="stylesheet" href="{{ asset('css/recetas/style.css') }}?v={{ md5_file(public_path('css/recetas/style.css')) }}">
        <link rel="stylesheet" href="{{ asset('css/productos/style.css') }}">
    @endpush
    @push('js')
        @include('principal.sidebar-menu')
        <script src="{{ asset('js/productos/script.js') }}"></script>
    @endpush
@endonce

<x-canvas>
    @php($puedeEditarProductos = auth()->user()->empleado?->rol?->descripcion === 'Administrador')

    <x-slot name="header"><title>Productos - El Buen Sabor</title></x-slot>
    <main>
        <x-sidebar></x-sidebar>
        <div class="body-content">
            <x-navegation></x-navegation>
            <div class="page-content">
                <section class="recipe-page">
                    <header class="recipe-page-heading">
                        <div><h1>Productos</h1></div>
                    </header>

                    @if (session('error'))<div class="recipe-feedback recipe-feedback-error" role="alert">{{ session('error') }}</div>@endif
                    @if ($errors->any())
                        <div class="recipe-feedback recipe-feedback-error" role="alert">
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div class="recipe-table-tools"><x-searchbar id="search-bar-product" classList="search-bar-recipe"></x-searchbar></div>
                    <div class="recipe-table-container">
                        <x-tablec tableClass="recipe-table">
                            <x-slot name="colgroup"><col style="width:30%"><col style="width:16%"><col style="width:16%"><col style="width:18%"><col style="width:20%"></x-slot>
                            <x-slot name="thead"><tr><th>Producto</th><th>Costo</th><th>Precio</th><th>Existencias</th><th>Acciones</th></tr></x-slot>
                            <x-slot name="tbody">
                                @forelse ($productos as $producto)
                                    <tr data-product-row data-id="{{ $producto->idproducto }}" data-name="{{ $producto->nombre }}"
                                        data-description="{{ $producto->descripcion }}" data-price="{{ $producto->precio }}"
                                        data-cost="{{ $producto->costo }}" data-stock="{{ $producto->stock }}"
                                        data-status="{{ $producto->estado }}"
                                        data-photo="{{ $producto->foto }}">
                                        <td>{{ $producto->nombre }}</td>
                                        <td>${{ number_format($producto->costo, 2) }}</td>
                                        <td>${{ number_format($producto->precio, 2) }}</td>
                                        <td>{{ $producto->stock }}</td>
                                        <td><div class="recipe-actions">
                                            <button type="button" class="recipe-action-button" data-view-product-photo aria-label="Ver foto de {{ $producto->nombre }}" title="Ver foto"><img src="{{ asset('assets/global/vista.svg') }}" alt=""></button>
                                            @if ($puedeEditarProductos)
                                                <button type="button" class="recipe-action-button" data-edit-product aria-label="Editar {{ $producto->nombre }}" title="Editar"><img src="{{ asset('assets/global/editar_black_color.svg') }}" alt=""></button>
                                            @endif
                                        </div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="recipe-empty-state">Los productos aparecerán al procesar sus recetas.</td></tr>
                                @endforelse
                                <tr id="product-no-results" hidden><td colspan="5" class="recipe-empty-state">No se encontraron productos.</td></tr>
                            </x-slot>
                        </x-tablec>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <x-toast></x-toast>
    <span id="product-success-message" hidden>{{ session('success') }}</span>

    @if ($puedeEditarProductos)
    <div class="recipe-modal-window" id="product-modal" aria-hidden="true" data-open-on-load="{{ $errors->any() ? 'true' : 'false' }}">
        <section class="recipe-modal-card product-modal-card" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
            <div class="recipe-modal-heading"><h2 id="product-modal-title">Editar producto</h2></div>
            <form id="product-form" method="POST" action="{{ route('productos.update', ['producto' => '__ID__']) }}" enctype="multipart/form-data"
                data-update-url="{{ route('productos.update', ['producto' => '__ID__']) }}">
                @csrf
                <input type="hidden" name="_method" id="product-method" value="PUT">
                <input type="hidden" name="product_id" id="product-id" value="{{ old('product_id') }}">
                <div class="recipe-form-grid">
                    <div class="recipe-form-group"><label for="product-name">Nombre</label><input name="productName" id="product-name" maxlength="60" required value="{{ old('productName') }}"></div>
                    <div class="recipe-form-group"><label for="product-price">Precio de venta</label><input type="number" name="productPrice" id="product-price" min="0" step="0.01" required value="{{ old('productPrice', 0) }}"></div>
                    <div class="recipe-form-group"><label for="product-cost">Costo calculado</label><input type="number" id="product-cost" min="0" step="0.01" readonly required><small class="product-help-text">Costo unitario calculado con la receta actual del producto.</small></div>
                    <div class="recipe-form-group"><label for="product-stock">Existencias</label><input type="number" name="productQuantity" id="product-stock" min="0" step="1" required value="{{ old('productQuantity') }}"></div>
                    <div class="recipe-form-group"><label for="product-status">Estado</label><select name="status" id="product-status" required><option value="available">Disponible</option><option value="unavailable">Agotado</option></select></div>
                    <div class="recipe-form-group recipe-instructivo-group"><label for="product-description">Descripción</label><textarea name="productDescription" id="product-description" rows="3" required>{{ old('productDescription') }}</textarea></div>
                    <div class="recipe-form-group recipe-instructivo-group"><label for="product-picture">Imagen</label><input type="file" name="productPicture" id="product-picture" accept="image/*"><small class="product-help-text">Opcional. Máximo 4 MB.</small></div>
                </div>
                <div class="recipe-modal-actions"><button type="button" class="recipe-secondary-button" data-close-product>Cancelar</button><button type="submit" class="recipe-primary-button" id="product-submit">Guardar producto</button></div>
            </form>
        </section>
    </div>
    @endif

    <div class="recipe-modal-window product-photo-modal" id="product-photo-modal" aria-hidden="true">
        <section class="recipe-modal-card product-photo-card" role="dialog" aria-modal="true" aria-labelledby="product-photo-title">
            <div class="recipe-modal-heading"><h2 id="product-photo-title">Foto del producto</h2></div>
            <div class="product-photo-frame">
                <img id="product-photo-image" alt="Foto del producto" hidden>
                <p id="product-photo-empty" class="recipe-empty-state" hidden>Este producto no tiene foto.</p>
            </div>
            <div class="recipe-modal-actions"><button type="button" class="recipe-secondary-button" data-close-product-photo>Cerrar</button></div>
        </section>
    </div>
</x-canvas>