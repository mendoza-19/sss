document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-registro');
    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmar = document.getElementById('confirmar');
    const errorNombre = document.getElementById('error-nombre');
    const errorApellido = document.getElementById('error-apellido');
    const errorEmail = document.getElementById('error-email');
    const errorPassword = document.getElementById('error-password');
    const errorConfirmar = document.getElementById('error-confirmar');
    const nombreGroup = document.getElementById('group-nombre');
    const apellidoGroup = document.getElementById('group-apellido');
    const emailGroup = document.getElementById('group-email');
    const passwordGroup = document.getElementById('group-password');
    const confirmarGroup = document.getElementById('group-confirmar');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Función para mostrar errores
    function showError(group, errorSpan, message) {
        if (group) group.classList.add("error");
        if (errorSpan) errorSpan.textContent = message;
    }

    // Función para limpiar errores
    function clearError(group, errorSpan) {
        if (group) group.classList.remove("error");
        if (errorSpan) errorSpan.textContent = "";
    }

    // Validar Correo
    function validateEmail() {
        const value = email.value.trim();
        if (value === "") {
            showError(emailGroup, errorEmail, "El correo electrónico es obligatorio.");
            return false;
        } else if (!emailRegex.test(value)) {
            showError(emailGroup, errorEmail, "Ingresa un correo electrónico válido (ej. usuario@dominio.com).");
            return false;
        } else {
            clearError(emailGroup, errorEmail);
            return true;
        }
    }

    function validationRequirements(value) {
        let requirements = {
            lowerCase: false,
            upperCase: false,
            number: false,
            specialChar: false,
            minLength: false,
            maxLength: false,
        };
        requirements.lowerCase = /[a-z]/.test(value);
        requirements.upperCase = /[A-Z]/.test(value);
        requirements.number = /[0-9]/.test(value);
        requirements.specialChar = /[!@#$%^&*]/.test(value);
        requirements.minLength = value.length >= 8;
        requirements.maxLength = value.length <= 16;
        return requirements;
    }

    // Validar Contraseña
    function validatePassword() {
        const value = password.value;
        if (value === "") {
            showError(passwordGroup, errorPassword, "La contraseña es obligatoria.");
            return false;
        } else if (!validationRequirements(value)) {
            showError(passwordGroup, errorPassword, "Debe tener mayúscula, minúscula, número y carácter especial (8-16 caracteres).");
            return false;
        } else {
            clearError(passwordGroup, errorPassword);
            return true;
        }
    }

    function validateNombre() {
        const value = nombre.value;
        if (value === "") {
            showError(nombreGroup, errorNombre, "El nombre es obligatorio.");
            return false;
        } else if (value.length < 3) {
            showError(nombreGroup, errorNombre, "El nombre debe tener al menos 3 caracteres.");
            return false;
        } else {
            clearError(nombreGroup, errorNombre);
            return true;
        }
    }

    function validateApellido() {
        const value = apellido.value;
        if (value === "") {
            showError(apellidoGroup, errorApellido, "El apellido es obligatorio.");
            return false;
        } else if (value.length < 3) {
            showError(apellidoGroup, errorApellido, "El apellido debe tener al menos 3 caracteres.");
            return false;
        } else {
            clearError(apellidoGroup, errorApellido);
            return true;
        }
    }

    // Validar Confirmar Contraseña
    function validateConfirmar() {
        const value = confirmar.value;
        if (value === "") {
            showError(confirmarGroup, errorConfirmar, "La confirmación de la contraseña es obligatoria.");
            return false;
        } else if (value !== password.value) {
            showError(confirmarGroup, errorConfirmar, "Las contraseñas no coinciden.");
            return false;
        } else if (nombre.length < 3) {
            showError(nombreGroup, errorNombre, "El nombre debe tener al menos 3 caracteres.");
            return false;
        } else if (apellido.length < 3) {
            showError(apellidoGroup, errorApellido, "El apellido debe tener al menos 3 caracteres.");
            return false;
        } else {
            clearError(confirmarGroup, errorConfirmar);
            return true;
        }
    }

    // Validar en tiempo real (evento blur e input cuando ya hay un error activo)
    nombre.addEventListener("blur", validateNombre);
    nombre.addEventListener("input", function () {
        if (nombreGroup && nombreGroup.classList.contains("error")) {
            validateNombre();
        }
    });

    apellido.addEventListener("blur", validateApellido);
    apellido.addEventListener("input", function () {
        if (apellidoGroup && apellidoGroup.classList.contains("error")) {
            validateApellido();
        }
    });

    email.addEventListener("blur", validateEmail);
    email.addEventListener("input", function () {
        if (emailGroup && emailGroup.classList.contains("error")) {
            validateEmail();
        }
    });

    password.addEventListener("blur", validatePassword);
    password.addEventListener("input", function () {
        if (passwordGroup && passwordGroup.classList.contains("error")) {
            validatePassword();
        }
    });

    confirmar.addEventListener("blur", validateConfirmar);
    confirmar.addEventListener("input", function () {
        if (confirmarGroup && confirmarGroup.classList.contains("error")) {
            validateConfirmar();
        }
    });

    form.addEventListener('submit', function (event) {
        const isEmailValid = validateEmail();
        const isPasswordValid = validatePassword();
        const isConfirmarValid = validateConfirmar();
        const isNombreValid = validateNombre();
        const isApellidoValid = validateApellido();

        if (!isEmailValid || !isPasswordValid || !isConfirmarValid || !isNombreValid || !isApellidoValid) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
});