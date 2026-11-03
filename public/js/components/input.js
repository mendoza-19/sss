document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('input').forEach(element => {
        const tipeInput = element.className.split(' ').find(elen => elen.includes('input-'));

        switch (tipeInput) {
            case 'input-text':
                element.classList.add('input-text-focus');
                element.addEventListener('blur', errorInput);
                break;
            case 'input-number':
                element.classList.add('input-text-focus');
                element.addEventListener('blur', errorInput);
                break;
            case 'input-email':
                element.classList.add('input-text-focus');
                element.addEventListener('blur', errorInput);
                break;
            case 'input-password':
                element.classList.add('input-text-focus');
                element.addEventListener('blur', errorInput);
                break;
            case 'input-button':
                element.classList.add('input-button');
                break;
            case 'input-submit':
                element.classList.add('input-button');
                break;
            case 'input-file':
                element.addEventListener('change', fillInputFile);
                break;
            default:
                console.log('No se reconoce el tipo de input');
                console.log(tipeInput);
                break;
        }
    });
});

function errorInput(event) {
    if (!event.target.checkValidity()) {
        event.target.classList.add('input-text-error');
        event.target.classList.remove('input-text-focus');
    } else if (event.target.classList.contains('input-text-error')) {
        event.target.classList.remove('input-text-error');
        event.target.classList.add('input-text-focus');
    }
}

function fillInputFile(event) {
    const ide = event.target.attributes.id.textContent;
    if (event.target.value != '') {
        const label = document.querySelector(`label[for="${ide}"]`);
        label.querySelectorAll('img').forEach(img => {
            if (img.attributes.alt.textContent != 'upload') {
                img.classList.remove('input-img-hidden');
            } else {
                img.classList.add('input-img-hidden');
            }
        });
        label.querySelector('p').textContent = event.target.files[0].name;
    }
}