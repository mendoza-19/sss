// Control del desplegable de usuario
const container = document.getElementById('userDropdownContainer');
const button = document.querySelector('.user-dropdown-btn');
const menu = document.getElementById('userDropdownMenu');

function closeUserDropdown() {
    if (container) {
        container.classList.remove('active');
    }
}

function toggleUserDropdown(event) {
    if (event) {
        event.stopPropagation();
    }

    if (container) {
        container.classList.toggle('active');
    }
}

if (button) {
    button.addEventListener('click', toggleUserDropdown);
}

if (container) {
    container.addEventListener('mouseleave', closeUserDropdown);
}

document.addEventListener('click', function (event) {
    if (container && !container.contains(event.target)) {
        closeUserDropdown();
    }
});

if (container) {
    closeUserDropdown();
}
