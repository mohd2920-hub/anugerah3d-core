document.querySelectorAll('[data-discount-cancel]').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('form').reset();
        button.closest('[data-discount-editor]').open = false;
    });
});
