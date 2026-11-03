//migrar
(function () {
    let lastScrollTop = 0;
    const footer = document.getElementById('site-footer');
    const delta = 5;

    window.addEventListener('scroll', function () {
        if (!footer) return;
        let st = window.pageYOffset || document.documentElement.scrollTop;

        // Evitar parpadeos en movimientos pequeños
        if (Math.abs(lastScrollTop - st) <= delta) return;

        // Si el usuario se desplaza hacia abajo, ocultar pie de página
        if (st > lastScrollTop && st > 50) {
            footer.classList.add('footer-hidden');
        } else {
            // Si el usuario se desplaza hacia arriba, mostrar pie de página
            footer.classList.remove('footer-hidden');
        }

        lastScrollTop = st <= 0 ? 0 : st;
    });
})();

//migrar
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

//migrar
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

//migrar
document.querySelectorAll('.openModal').forEach(btn => {
    btn.addEventListener('click', function () {
        openModal(this.value);
    });
});

//migrar
document.querySelectorAll('.closeModal').forEach(btn => {
    btn.addEventListener('click', function () {
        closeModal(this.value);
    });
});

//migrar
// Cerrar modales al hacer clic fuera del contenido
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});