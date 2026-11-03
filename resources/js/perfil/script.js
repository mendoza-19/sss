window.switchTab = function (tabName) {
    const targetTab = document.getElementById(tabName);
    if (!targetTab) return;

    // Ocultar todos los contenidos
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });

    // Activar tab seleccionado
    targetTab.classList.add('active');

    // Actualizar botones activos
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('value') === tabName || (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(tabName))) {
            btn.classList.add('active');
        }
    });
};

// Mostrar u ocultar contraseñas
const togglePasswordIcons = document.querySelectorAll('.show-password');
togglePasswordIcons.forEach(icon => {
    icon.addEventListener('click', function () {
        const container = this.parentElement;
        const passwordInput = container.querySelector('input');
        const isPassword = passwordInput.type === 'password';

        // Alternar tipo de input
        passwordInput.type = isPassword ? 'text' : 'password';

        // Alternar imagen
        const hideImg = container.querySelector('img[alt="ocultar contraseña"]');
        const showImg = container.querySelector('img[alt="ver contraseña"]');

        if (isPassword) {
            hideImg.classList.add('password_img_inactive');
            showImg.classList.remove('password_img_inactive');
        } else {
            hideImg.classList.remove('password_img_inactive');
            showImg.classList.add('password_img_inactive');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            switchTab(this.value);
        });
    });

    // Si hay un tab activo por clase (por ejemplo debido a errores de validación), sincronizar botón
    const activeTab = document.querySelector('.tab-content.active');
    if (activeTab) {
        switchTab(activeTab.id);
    }

    const successMessage = document.querySelector('[data-auto-dismiss]');
    if (successMessage) {
        window.setTimeout(() => {
            successMessage.classList.add('is-hidden');
            window.setTimeout(() => successMessage.remove(), 250);
        }, 4000);
    }
});

const photoInput = document.querySelector('input[name="fotografia"]');
photoInput?.addEventListener('change', change => {
    const file = change.target.files[0];
    if (!file) return;

    const container = document.querySelector('.photo-upload');
    let avatar = container.querySelector('img[alt="Avatar"]');
    if (!avatar) {
        avatar = document.createElement('img');
        avatar.alt = 'Avatar';
        avatar.className = 'avart-img';
        container.prepend(avatar);
    }

    const objectUrl = URL.createObjectURL(file);
    avatar.src = objectUrl;
});