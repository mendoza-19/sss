document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-login");
    const userInput = document.getElementById("username");
    const passInput = document.getElementById("password");

    const userGroup = document.getElementById("group-username") || userInput.closest(".input-group");
    const passGroup = document.getElementById("group-password") || passInput.closest(".input-group");

    const userError = document.getElementById("error-username");
    const passError = document.getElementById("error-password");

    // Expresión regular estándar para validación de correo electrónico
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
    function validateUser() {
        const value = userInput.value.trim();
        if (value === "") {
            showError(userGroup, userError, "El correo electrónico es obligatorio.");
            return false;
        } else if (!emailRegex.test(value)) {
            showError(userGroup, userError, "Ingresa un correo electrónico válido (ej. usuario@dominio.com).");
            return false;
        } else {
            clearError(userGroup, userError);
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
        return requirements.lowerCase
            && requirements.upperCase
            && requirements.number
            && requirements.specialChar
            && requirements.minLength
            && requirements.maxLength
            ? true : false;
    }

    // Validar Contraseña
    function validatePassword() {
        const value = passInput.value;
        if (value === "") {
            showError(passGroup, passError, "La contraseña es obligatoria.");
            return false;
        } else if (!validationRequirements(value)) {
            showError(passGroup, passError, "Debe tener mayúscula, minúscula, número y carácter especial (8-16 caracteres).");
            return false;
        } else {
            clearError(passGroup, passError);
            return true;
        }
    }

    // Validar en tiempo real (evento blur e input cuando ya hay un error activo)
    userInput.addEventListener("blur", validateUser);
    userInput.addEventListener("input", function () {
        if (userGroup && userGroup.classList.contains("error")) {
            validateUser();
        }
    });

    passInput.addEventListener("blur", validatePassword);
    passInput.addEventListener("input", function () {
        if (passGroup && passGroup.classList.contains("error")) {
            validatePassword();
        }
    });

    // Validar al enviar el formulario
    form.addEventListener("submit", function (event) {
        const isUserValid = validateUser();
        const isPassValid = validatePassword();

        if (!isUserValid || !isPassValid) {
            event.preventDefault(); // Detener el envío si hay campos inválidos
        }
    });
});