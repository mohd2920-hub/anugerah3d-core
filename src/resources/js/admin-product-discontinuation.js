const dialog = document.getElementById('product-discontinuation-dialog');

if (dialog) {
    const form = dialog.querySelector('form');
    const submit = dialog.querySelector('[data-discontinuation-submit]');
    let trigger;
    let previousOverflow;

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-discontinuation-open]');
        if (!button) return;
        trigger = button;
        const reactivate = button.dataset.discontinued === '1';
        form.reset();
        form.action = button.dataset.action;
        form.elements.discontinued.value = reactivate ? '0' : '1';
        form.elements.reason.disabled = reactivate;
        dialog.querySelector('[data-discontinuation-reason]').hidden = reactivate;
        dialog.querySelector('#discontinuation-title').textContent = reactivate ? 'Aktifkan Semula Produk' : 'Hentikan Produk';
        dialog.querySelector('[data-discontinuation-name]').textContent = button.dataset.name;
        dialog.querySelector('[data-discontinuation-code]').textContent = button.dataset.code;
        dialog.querySelector('[data-discontinuation-balance]').textContent = new Intl.NumberFormat('ms-MY').format(Number(button.dataset.balance));
        dialog.querySelector('#discontinuation-description').textContent = reactivate
            ? 'Produk akan menerima jualan dan pre-order semula. Tetapan paparan katalog dikekalkan.'
            : 'Baki stok masih boleh dijual sehingga habis. Pre-order disekat dan produk disembunyikan daripada katalog apabila stok habis. Sejarah transaksi dikekalkan.';
        submit.textContent = reactivate ? 'Sahkan Aktifkan Semula' : 'Sahkan Hentikan Produk';
        submit.disabled = false;
        document.dispatchEvent(new Event('admin:close-product-actions'));
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        dialog.showModal();
        dialog.querySelector('[data-discontinuation-close]').focus({ preventScroll: true });
    });
    dialog.querySelectorAll('[data-discontinuation-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => {
        document.body.style.overflow = previousOverflow;
        const menuButton = trigger?.closest('[data-action-menu]')?.querySelector('[data-action-menu-button]');
        (menuButton || trigger)?.focus({ preventScroll: true });
    });
    form.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Menyimpan…';
    });
}
