document.addEventListener('DOMContentLoaded', function () {
    const successMessage = document.getElementById('product-success-message')?.textContent.trim();
    if (successMessage && typeof showToast === 'function') showToast(successMessage, 'success');

    const modal = document.getElementById('product-modal');
    const photoModal = document.getElementById('product-photo-modal');
    const photoImage = document.getElementById('product-photo-image');
    const photoEmpty = document.getElementById('product-photo-empty');
    const photoTitle = document.getElementById('product-photo-title');
    const form = document.getElementById('product-form');
    const title = document.getElementById('product-modal-title');
    const method = document.getElementById('product-method');
    const costInput = document.getElementById('product-cost');
    const search = document.querySelector('#search-bar-product input') || document.getElementById('search-bar-product');

    if (!photoModal) return;

    function openModal() {
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.getElementById('product-name').focus();
    }

    function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function editProduct(row, restoreOldValues = false) {
        if (!row) return;

        const fields = {
            name: document.getElementById('product-name'),
            description: document.getElementById('product-description'),
            price: document.getElementById('product-price'),
            stock: document.getElementById('product-stock'),
            status: document.getElementById('product-status'),
        };
        const oldValues = restoreOldValues ? {
            name: fields.name.value,
            description: fields.description.value,
            price: fields.price.value,
            stock: fields.stock.value,
            status: fields.status.value,
        } : null;

        form.action = form.dataset.updateUrl.replace('__ID__', row.dataset.id);
        method.value = 'PUT';
        method.disabled = false;
        document.getElementById('product-id').value = row.dataset.id;
        fields.name.value = row.dataset.name;
        fields.description.value = row.dataset.description;
        fields.price.value = row.dataset.price;
        fields.stock.value = row.dataset.stock;
        fields.status.value = row.dataset.status;
        costInput.value = row.dataset.cost;
        document.getElementById('product-picture').value = '';

        if (oldValues) {
            Object.entries(oldValues).forEach(function ([key, value]) {
                if (value !== '') fields[key].value = value;
            });
        }

        title.textContent = 'Editar producto';
        document.getElementById('product-submit').textContent = 'Guardar cambios';
        openModal();
    }

    document.querySelectorAll('[data-edit-product]').forEach(function (button) {
        button.addEventListener('click', function () {
            editProduct(button.closest('[data-product-row]'));
        });
    });

    document.querySelectorAll('[data-view-product-photo]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = button.closest('[data-product-row]');
            const photo = row.dataset.photo.trim();
            photoTitle.textContent = 'Foto de ' + row.dataset.name;
            photoImage.hidden = !photo;
            photoEmpty.hidden = Boolean(photo);
            if (photo) photoImage.src = photo;
            photoModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    document.querySelectorAll('[data-close-product]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-close-product-photo]').forEach(function (button) {
        button.addEventListener('click', function () {
            photoModal.setAttribute('aria-hidden', 'true');
            photoImage.removeAttribute('src');
            document.body.style.overflow = '';
        });
    });

    modal?.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    photoModal.addEventListener('click', function (event) {
        if (event.target === photoModal) {
            photoModal.setAttribute('aria-hidden', 'true');
            photoImage.removeAttribute('src');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal?.getAttribute('aria-hidden') === 'false') closeModal();
        if (event.key === 'Escape' && photoModal.getAttribute('aria-hidden') === 'false') {
            photoModal.setAttribute('aria-hidden', 'true');
            photoImage.removeAttribute('src');
            document.body.style.overflow = '';
        }
    });

    search?.addEventListener('input', function () {
        const query = search.value.trim().toLocaleLowerCase();
        let visible = 0;
        document.querySelectorAll('[data-product-row]').forEach(function (row) {
            const matches = row.textContent.toLocaleLowerCase().includes(query);
            row.hidden = !matches;
            visible += matches ? 1 : 0;
        });
        const noResults = document.getElementById('product-no-results');
        if (noResults) noResults.hidden = visible > 0;
    });

    if (modal?.dataset.openOnLoad === 'true' && form) {
        const productId = document.getElementById('product-id').value;
        const row = Array.from(document.querySelectorAll('[data-product-row]'))
            .find(function (productRow) { return productRow.dataset.id === productId; });
        editProduct(row, true);
    }
});