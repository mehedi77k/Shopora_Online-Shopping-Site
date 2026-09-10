document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');

    if (menuToggle && menu) {
        menuToggle.addEventListener('click', () => menu.classList.toggle('is-open'));
    }

    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (event) => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-qty]').forEach((group) => {
        const input = group.querySelector('input[type="number"]');
        const minus = group.querySelector('[data-minus]');
        const plus = group.querySelector('[data-plus]');
        if (!input) return;
        minus?.addEventListener('click', () => {
            input.value = Math.max(Number(input.min || 1), Number(input.value || 1) - 1);
        });
        plus?.addEventListener('click', () => {
            const max = Number(input.max || 9999);
            input.value = Math.min(max, Number(input.value || 1) + 1);
        });
    });

    const year = document.querySelector('[data-current-year]');
    if (year) year.textContent = new Date().getFullYear();
});

// Live preview for product and category image uploads in the admin panel.
document.querySelectorAll('[data-image-manager]').forEach((manager) => {
    const input = manager.querySelector('[data-image-input]');
    const preview = manager.querySelector('[data-image-preview]');
    const fileName = manager.querySelector('[data-image-file-name]');
    const remove = manager.querySelector('[data-image-remove]');

    if (!input || !preview) return;

    const originalSrc = preview.src;
    const placeholder = preview.dataset.placeholder || originalSrc;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            preview.src = remove?.checked ? placeholder : originalSrc;
            if (fileName) fileName.textContent = 'No new image selected';
            return;
        }

        if (fileName) fileName.textContent = file.name;
        if (remove) remove.checked = false;

        const objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.onload = () => URL.revokeObjectURL(objectUrl);
    });

    remove?.addEventListener('change', () => {
        if (remove.checked) {
            input.value = '';
            preview.src = placeholder;
            if (fileName) fileName.textContent = 'Current image will be removed';
        } else {
            preview.src = originalSrc;
            if (fileName) fileName.textContent = 'No new image selected';
        }
    });
});
