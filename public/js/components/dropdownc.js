(function () {
    let dropdownCloseDiscard = false;
    document.addEventListener('click', function (event) {
        if (!dropdownCloseDiscard) {
            document.querySelectorAll('.dropdown-body').forEach(function (element) {
                element.classList.add('hidden');
            });
        } else {
            dropdownCloseDiscard = false;
        }
    });
    document.querySelectorAll('.dropdown-container').forEach(function (element) {
        const header = element.querySelector('.dropdown-header');
        const body = element.querySelector('.dropdown-body');
        header.addEventListener('click', function () {
            body.classList.toggle('hidden');
            dropdownCloseDiscard = true;
        });
    });
})();