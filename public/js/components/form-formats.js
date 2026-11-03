window.formatDateDisplay = function (value) {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    return match ? `${match[3]}/${match[2]}/${match[1]}` : (value || '');
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[data-format="sv-dui"]').forEach(input => {
        const formatDui = () => {
            let digits = input.value.replace(/\D/g, '').slice(0, 9);

            if (digits && digits[0] !== '0') {
                digits = '';
            }

            input.value = digits.length > 8
                ? `${digits.slice(0, 8)}-${digits.slice(8)}`
                : digits;
        };

        formatDui();
        input.addEventListener('input', formatDui);
    });

    document.querySelectorAll('input[data-format="sv-phone"]').forEach(input => {
        const formatPhone = event => {
            const rawValue = input.value.trim();
            const digits = rawValue.replace(/\D/g, '');
            const hasCountryPrefix = /^\+?503(?:\s|-|$)/.test(rawValue);

            if (hasCountryPrefix || (digits && !['5', '6', '7'].includes(digits[0]))) {
                input.setCustomValidity('El teléfono debe iniciar con 5, 6 o 7.');
                if (event) input.value = '';
                return;
            }

            input.setCustomValidity('');
            const localDigits = digits.slice(0, 8);
            const formattedLocal = localDigits.length > 4
                ? `${localDigits.slice(0, 4)}-${localDigits.slice(4)}`
                : localDigits;

            input.value = formattedLocal;
        };

        formatPhone();
        input.addEventListener('input', formatPhone);
    });
});