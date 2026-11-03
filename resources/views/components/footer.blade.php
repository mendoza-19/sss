@once
@push('styless')
<link href="{{ asset('css/components/footer.css') }}" rel="stylesheet">
@endpush

@push('js')
<script src="{{ asset('js/components/footer.js') }}"></script>
@endpush
@endonce

<!-- Pie de Página (Footer Dinámico) -->
<footer id="site-footer" class="site-footer">
    <div class="footer-container">
        <!-- Sección 1: Atención al Cliente -->
        <div class="footer-section">
            <h4 class="footer-title">Pedidos y atención</h4>
            <div class="footer-info-item">
                <svg viewBox="0 0 24 24">
                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z" />
                </svg>
                <span>+503 7373-6546</span>
            </div>
            <div class="footer-info-item">
                <svg viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
                <span>panadaeriaelbuensabor@gmail.com</span>
            </div>
            <div class="footer-info-item">
                <svg viewBox="0 0 24 24">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                </svg>
                <span>Todos los días: 7:00 a. m. - 8:00 p. m.</span>
            </div>
            <button class="footer-link openModal" value="modal-atencion" style="margin-top: 5px; font-weight: bold; color: #f3d4a0;">Ayuda con mi pedido ❯</button>
        </div>

        <!-- Sección 2: Contacto -->
        <div class="footer-section">
            <h4 class="footer-title">Visítanos y contáctanos</h4>
            <div class="footer-info-item">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
                <span>Apastepeque, El Salvador</span>
            </div>
            <button class="footer-link openModal" value="modal-contacto">Escribe a la panadería</button>
            <button class="footer-link openModal" value="modal-contacto">Cómo llegar a la tienda</button>
            <button class="footer-link openModal" value="modal-contacto">Dudas sobre nuestros productos</button>
        </div>

        <!-- Sección 3: Información Legal & Políticas -->
        <div class="footer-section">
            <h4 class="footer-title">Compra y privacidad</h4>
            <ul class="footer-list">
                <li><button class="footer-link openModal" value="modal-aviso-legal">Aviso Legal</button></li>
                <li><button class="footer-link openModal" value="modal-cookies">Política de Cookies</button></li>
                <li><button class="footer-link openModal" value="modal-privacidad">Política de Privacidad</button></li>
            </ul>
        </div>

        <!-- Sección 4: Canales de Información (Redes Sociales) -->
        <div class="footer-section">
            <h4 class="footer-title">Redes Sociales</h4>
            <p style="font-size: 12.5px; color: #d7ccc8; margin-bottom: 10px;">Sigue nuestros canales para promociones y recetas exclusivas.</p>
            <div class="social-channels">
                <a href="https://facebook.com" target="_blank" class="social-btn" title="Facebook">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H7.5v-3H10V9.69c0-2.47 1.47-3.83 3.72-3.83 1.08 0 2.21.19 2.21.19v2.43h-1.25c-1.23 0-1.61.76-1.61 1.54V12h2.74l-.44 3H13v6.8c4.56-.93 8-4.96 8-9.8z" />
                    </svg>
                </a>
                <a href="https://instagram.com" target="_blank" class="social-btn" title="Instagram">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                    </svg>
                </a>
                <a href="https://x.com" target="_blank" class="social-btn" title="X / Twitter">
                    <svg viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                </a>
                <a href="https://whatsapp.com" target="_blank" class="social-btn" title="WhatsApp">
                    <svg viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; 2026 Panadería El Buen Sabor. Todos los derechos reservados.
    </div>
</footer>

<!-- Ventanas Modales Informativas -->
<!-- Modal Aviso Legal -->
<div id="modal-aviso-legal" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close closeModal" value="modal-aviso-legal">&times;</button>
        <h3 class="modal-title">Aviso Legal</h3>
        <div class="modal-body">
            <p>En cumplimiento con la legislación vigente, el presente sitio web es propiedad de <strong>Panadería El Buen Sabor S.A.S.</strong></p>
            <h4>1. Términos de Uso</h4>
            <p>El acceso y navegación por este sitio web atribuye la condición de usuario e implica la aceptación de todas las condiciones aquí expuestas.</p>
            <h4>2. Propiedad Intelectual</h4>
            <p>Todos los contenidos, marcas, logos, textos e imágenes de productos presentes en esta plataforma están protegidos por derechos de autor y propiedad industrial.</p>
        </div>
    </div>
</div>

<!-- Modal Política de Cookies -->
<div id="modal-cookies" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close closeModal" value="modal-cookies">&times;</button>
        <h3 class="modal-title">Política de Cookies</h3>
        <div class="modal-body">
            <p>Utilizamos cookies propias y de terceros para mejorar la experiencia de navegación y ofrecer contenido personalizado.</p>
            <h4>¿Qué tipos de cookies utilizamos?</h4>
            <p><strong>Cookies esenciales:</strong> Necesarias para el funcionamiento del carrito de compras y la sesión.</p>
            <p><strong>Cookies de análisis:</strong> Nos permiten medir la interacción con la carta digital para mejorar nuestros servicios.</p>
        </div>
    </div>
</div>

<!-- Modal Política de Privacidad -->
<div id="modal-privacidad" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close closeModal" value="modal-privacidad">&times;</button>
        <h3 class="modal-title">Política de Privacidad</h3>
        <div class="modal-body">
            <p>En El Buen Sabor nos comprometemos a proteger sus datos personales conforme al Reglamento General de Protección de Datos.</p>
            <h4>Uso de la Información</h4>
            <p>Sus datos solo serán utilizados para el procesamiento de pedidos, atención al cliente y notificaciones sobre el estado de sus compras.</p>
        </div>
    </div>
</div>

<!-- Modal Contacto -->
<div id="modal-contacto" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close closeModal" value="modal-contacto">&times;</button>
        <h3 class="modal-title">Contacto y Ubicación</h3>
        <div class="modal-body">
            <p><strong>Dirección Principal:</strong> Apastepeque, El Salvador</p>
            <p><strong>Teléfono:</strong> +503 7373-6546</p>
            <p><strong>Correo electrónico:</strong> panadaeriaelbuensabor@gmail.com</p>
            <h4>Envíanos un mensaje</h4>
            <form onsubmit="event.preventDefault(); alert('¡Gracias por contactarnos! Te responderemos muy pronto.'); closeModal('modal-contacto');">
                <input type="text" placeholder="Tu Nombre" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #d7ccc8; border-radius: 6px;">
                <input type="email" placeholder="Tu Correo" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #d7ccc8; border-radius: 6px;">
                <textarea placeholder="Mensaje..." rows="3" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #d7ccc8; border-radius: 6px;"></textarea>
                <button type="submit" class="login-btn" style="width: 100%;">Enviar Mensaje</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Atención al Cliente -->
<div id="modal-atencion" class="modal-overlay">
    <div class="modal-card">
        <button class="modal-close closeModal" value="modal-atencion">&times;</button>
        <h3 class="modal-title">Atención al Cliente</h3>
        <div class="modal-body">
            <p>Estamos para atenderte en todo momento con la mayor amabilidad.</p>
            <h4>Líneas de Atención Directa</h4>
            <p><strong>Teléfono:</strong> +503 7373-6546</p>
            <p><strong>WhatsApp Soporte:</strong> +57 300 123 4567</p>
            <h4>Horarios de Servicio</h4>
            <p>Lunes a Sábado: 6:30 AM - 8:30 PM<br>Domingos y Festivos: 7:00 AM - 7:00 PM</p>
        </div>
    </div>
</div>