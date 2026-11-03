 <x-canvas>
     <x-slot name="header">
         <title>El Buen Sabor</title>
         @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
         @vite(['resources/css/menu/style.css'])
         @else
         <style>
         </style>
         @endif
     </x-slot>
     <x-slot name="metas">
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
     </x-slot>
     <x-slot name="footer">
         <!-- Lógica de Scroll y Control de Modales -->
         @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
         @vite('resources/js/menu/script.js')
         @else
         <script></script>
         @endif
     </x-slot>
     <x-navegation></x-navegation>

     <main>
         <section class="encargo-intro">
             <div>
                 <h2>¿Necesitas algo para otro día?</h2>
                 <p>Encarga productos para la fecha que elijas.</p>
             </div>
             <button type="button" class="encargo-open-btn" id="openEncargoBtn">Hacer un encargo</button>
         </section>

         <!-- Sección de Más Populares -->
         <section class="populares-section">
             <h2 class="section-title">Más Populares</h2>

             <div class="carousel-container">
                 @forelse ($populares as $producto)
                     <div class="card-small" data-product-id="{{ $producto->idproducto }}">
                         <img src="{{ $producto->foto ?: asset('assets/menu/pan-icono.png') }}" alt="{{ $producto->nombre }}" class="product-img">
                         <div class="product-name">{{ $producto->nombre }}</div>
                         <div class="product-price">${{ number_format($producto->precio, 2) }}</div>
                         <button type="button" class="btn-add">Agregar</button>
                     </div>
                 @empty
                     <p>No hay productos populares disponibles.</p>
                 @endforelse
             </div>

             <button class="carousel-arrow">❯</button>
         </section>

         <!-- Grilla de productos con efecto de aparición -->
         <section class="products-grid">
             @forelse ($productos as $producto)
                 <div class="card-large" data-product-id="{{ $producto->idproducto }}">
                     <img src="{{ $producto->foto ?: asset('assets/menu/pan-icono.png') }}" alt="{{ $producto->nombre }}" class="product-img">
                     <div class="product-name">{{ $producto->nombre }}</div>
                     <div class="product-price">${{ number_format($producto->precio, 2) }}</div>
                     <p class="product-desc">{{ $producto->descripcion }}</p>
                     <button type="button" class="btn-add">Agregar</button>
                 </div>
             @empty
                 <p>No hay productos disponibles en este momento.</p>
             @endforelse
         </section>
     </main>

     <x-footer> </x-footer>

     <div class="encargo-overlay" id="encargoModal" hidden>
         <section class="encargo-dialog" role="dialog" aria-modal="true" aria-labelledby="encargo-title">
             <button type="button" class="encargo-close-btn" id="closeEncargoBtn" aria-label="Cerrar">&times;</button>
             <h2 id="encargo-title">Hacer un encargo</h2>
             <p class="encargo-description">Elige los productos y la fecha en que los necesitas.</p>

             @auth
                 <form id="encargoForm">
                     <div class="encargo-fields">
                         <label>
                             Fecha de entrega
                             <input type="date" name="fecha_entrega" min="{{ now()->addDay()->toDateString() }}" required>
                         </label>
                         <label>
                             Dirección de entrega
                             <input type="text" name="direccion" minlength="5" maxlength="255" required>
                         </label>
                         <label>
                             Teléfono (opcional)
                             <input type="tel" name="telefono" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7" placeholder="7000-0000">
                         </label>
                         <label>
                             Nota (opcional)
                             <input type="text" name="notas" maxlength="255" placeholder="Indicaciones para el encargo">
                         </label>
                     </div>

                     <h3>Productos para encargar</h3>
                     <div class="encargo-product-picker">
                         <button type="button" class="encargo-add-product-btn" id="encargo-add-product-btn" @disabled($productosParaEncargo->isEmpty())>
                             Abrir catálogo de productos
                         </button>
                     </div>
                     <div class="encargo-order-table-wrap">
                         <table class="encargo-order-table">
                             <colgroup>
                                 <col style="width: 40%">
                                 <col style="width: 20%">
                                 <col style="width: 14%">
                                 <col style="width: 16%">
                                 <col style="width: 10%">
                             </colgroup>
                             <thead>
                                 <tr><th>Producto</th><th>Precio unitario</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
                             </thead>
                             <tbody id="encargo-product-list">
                                 <tr class="encargo-empty-products"><td colspan="5">Aún no has agregado productos.</td></tr>
                             </tbody>
                             <tfoot>
                                 <tr><th colspan="3">Total del encargo</th><td id="encargo-grand-total">$0.00</td><td></td></tr>
                             </tfoot>
                         </table>
                     </div>

                     <button type="submit" class="encargo-submit-btn" @disabled($productosParaEncargo->isEmpty())>Enviar encargo</button>
                 </form>
             @else
                 <p>Inicia sesión para registrar un encargo.</p>
                 <a class="encargo-submit-btn" href="{{ route('login') }}">Iniciar sesión</a>
             @endauth
         </section>
     </div>

     <div class="encargo-catalog-modal" id="encargo-catalog-modal" hidden>
         <section class="encargo-catalog-dialog" role="dialog" aria-modal="true" aria-labelledby="encargo-catalog-title">
             <button type="button" class="encargo-close-btn" id="close-encargo-catalog-btn" aria-label="Cerrar">&times;</button>
             <h2 id="encargo-catalog-title">Catálogo de productos</h2>
             <input type="search" class="encargo-catalog-search" id="encargo-catalog-search" placeholder="Buscar producto" aria-label="Buscar producto">
             <div class="encargo-catalog-grid" id="encargo-catalog-grid">
                 @foreach ($productosParaEncargo as $producto)
                     <article class="encargo-catalog-card" data-calculation-mode="money">
                         <img src="{{ $producto->foto ?: asset('assets/menu/pan-icono.png') }}" alt="{{ $producto->nombre }}">
                         <div class="encargo-catalog-info">
                             <h3>{{ $producto->nombre }}</h3>
                             <p>${{ number_format($producto->precio, 2) }}</p>
                         </div>
                         <div class="encargo-catalog-budget">
                             <div class="encargo-budget-field">
                                 <label for="encargo-budget-{{ $producto->idproducto }}">Monto ($)</label>
                                 <input type="number" id="encargo-budget-{{ $producto->idproducto }}"
                                     class="encargo-money-input" min="0.01" step="0.01" placeholder="5.00"
                                     aria-label="Monto a encargar para {{ $producto->nombre }}">
                             </div>
                             <div class="encargo-budget-field">
                                 <label for="encargo-units-{{ $producto->idproducto }}">Unidades</label>
                                 <input type="number" id="encargo-units-{{ $producto->idproducto }}"
                                     class="encargo-units-input" min="1" step="1" placeholder="Cantidad"
                                     aria-label="Unidades a encargar de {{ $producto->nombre }}">
                             </div>
                             <p class="encargo-budget-result" aria-live="polite">Ingresa un monto para calcular unidades y vuelto.</p>
                         </div>
                         <button type="button" class="encargo-catalog-add-btn" disabled
                             data-product-id="{{ $producto->idproducto }}"
                             data-name="{{ $producto->nombre }}"
                             data-price="{{ $producto->precio }}"
                             data-photo="{{ $producto->foto ?: asset('assets/menu/pan-icono.png') }}">
                             Agregar
                         </button>
                     </article>
                 @endforeach
                 <p class="encargo-catalog-empty" id="encargo-catalog-empty" @if ($productosParaEncargo->isNotEmpty()) hidden @endif>
                     Producto no disponible
                 </p>
             </div>
         </section>
     </div>

     <!-- Botón Flotante del Carrito -->
     <button id="floatingCartBtn" class="floating-cart-btn openModal" value="modal-carrito" aria-label="Ver Carrito de Compras" title="Ver Carrito de Compras">
         <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
             <circle cx="9" cy="21" r="1"></circle>
             <circle cx="20" cy="21" r="1"></circle>
             <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
         </svg>
         <span class="cart-badge" id="cartBadge">0</span>
     </button>

     <!-- Modal Carrito de Compras -->
     <div id="modal-carrito" class="modal-overlay">
         <div class="modal-card cart-modal-card">
             <button class="modal-close closeModal" value="modal-carrito">&times;</button>
             <h3 class="modal-title">Tu Carrito de Compras 🛒</h3>
             <div class="modal-body cart-layout">
                 <div class="cart-order-panel">
                     @auth
                         <div class="cart-delivery-field">
                             <label for="cart-delivery-address">Dirección de entrega</label>
                             <input type="text" id="cart-delivery-address" minlength="5" maxlength="255" placeholder="Escribe tu dirección" required>
                         </div>
                         <div class="cart-delivery-field">
                             <label for="cart-delivery-phone">Teléfono</label>
                             <input type="tel" id="cart-delivery-phone" data-format="sv-phone" inputmode="numeric" maxlength="9" pattern="[567][0-9]{3}-[0-9]{4}" title="El teléfono debe iniciar con 5, 6 o 7" placeholder="7000-0000">
                         </div>
                         <div class="cart-delivery-field">
                             <label for="cart-delivery-notes">Nota</label>
                             <input type="text" id="cart-delivery-notes" maxlength="255" placeholder="Indicaciones para la entrega">
                         </div>
                     @endauth
                     <div class="cart-summary">
                         <div class="cart-total-row">
                             <span>Total:</span>
                             <span id="cartTotal">$0.00</span>
                         </div>
                         <button class="login-btn cart-checkout-btn" id="cartCheckoutBtn">Finalizar Compra</button>
                     </div>
                 </div>
                 <div class="cart-products-panel">
                     <div id="cartItemsList" class="cart-items-list">
                         <p class="empty-cart-msg">Tu carrito está vacío. ¡Agrega deliciosos productos!</p>
                     </div>
                 </div>
             </div>
         </div>
     </div>

     <x-toast></x-toast>
 </x-canvas>