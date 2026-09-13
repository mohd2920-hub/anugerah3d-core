const balanceDialog = document.getElementById('product-balance-dialog');
if (balanceDialog) {
    const form = balanceDialog.querySelector('form');
    const submit = balanceDialog.querySelector('[data-balance-submit]');
    const error = balanceDialog.querySelector('[data-balance-error]');
    const variation = balanceDialog.querySelector('[data-balance-variation]');
    const close = balanceDialog.querySelector('[data-balance-close]');
    let snapshot, trigger, previousOverflow, controller, saving = false;
    const selected = () => snapshot.casing_stock ? snapshot.variations[Number(variation.value)] : null;
    const updateQuantity = () => {
        const quantity = selected()?.quantity ?? snapshot.balance;
        form.elements.quantity.value = quantity;
        balanceDialog.querySelector('[data-balance-current]').textContent = new Intl.NumberFormat('ms-MY').format(quantity);
    };
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-balance-open]');
        if (!button) return;
        trigger = button;
        controller?.abort(); controller = new AbortController();
        form.reset(); snapshot = null; submit.disabled = true;
        error.textContent = '';
        balanceDialog.querySelector('[data-balance-name]').textContent = 'Memuatkan baki terkini…';
        balanceDialog.querySelector('[data-balance-fields]').hidden = true;
        previousOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden';
        balanceDialog.showModal(); close.focus();
        try {
            const response = await fetch(button.dataset.url, {headers: {Accept: 'application/json'}, signal: controller.signal, cache: 'no-store'});
            if (!response.ok) throw new Error('Baki tidak dapat dimuatkan. Tutup dialog dan cuba semula.');
            snapshot = await response.json();
            balanceDialog.querySelector('[data-balance-name]').textContent = `${snapshot.name} · ${snapshot.code}`;
            variation.replaceChildren();
            snapshot.variations.forEach((item, index) => variation.add(new Option(item.label, index)));
            const match = snapshot.variations.findIndex(item => String(item.casing_id) === button.dataset.casing && String(item.character_count) === button.dataset.count);
            if (match >= 0) variation.value = String(match);
            balanceDialog.querySelector('[data-balance-variation-label]').hidden = !snapshot.casing_stock;
            if (snapshot.casing_stock && !snapshot.variations.length) throw new Error('Urus tetapan casing produk terlebih dahulu.');
            updateQuantity();
            balanceDialog.querySelector('[data-balance-fields]').hidden = false;
            submit.disabled = false;
        } catch (exception) { if (exception.name !== 'AbortError') error.textContent = exception.message; }
    });
    variation.addEventListener('change', updateQuantity);
    close.addEventListener('click', () => { if (!saving) balanceDialog.close(); });
    balanceDialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); });
    balanceDialog.addEventListener('close', () => { controller?.abort(); document.body.style.overflow = previousOverflow; trigger?.focus({preventScroll: true}); });
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); if (!snapshot || saving) return;
        saving = true; submit.disabled = true; close.disabled = true; error.textContent = '';
        submit.textContent = 'Menyimpan…';
        const item = selected();
        try {
            const response = await fetch(trigger.dataset.url, {method: 'PATCH', headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.elements._token.value}, body: JSON.stringify({expected_balance: snapshot.balance, expected_quantity: item?.quantity ?? snapshot.balance, quantity: form.elements.quantity.value, reason: form.elements.reason.value, casing_id: item?.casing_id ?? null, character_count: item?.character_count ?? null})});
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Simpanan gagal.');
            try { sessionStorage.setItem('product-balance-scroll', JSON.stringify({url: location.href, y: window.scrollY})); } catch {}
            location.reload();
        } catch (exception) { error.textContent = exception.message; saving = false; submit.disabled = false; close.disabled = false; submit.textContent = 'Simpan Stok'; }
    });
    try {
        const position = JSON.parse(sessionStorage.getItem('product-balance-scroll'));
        sessionStorage.removeItem('product-balance-scroll');
        if (position?.url === location.href) {
            const notice = document.createElement('p');
            notice.className = 'rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800';
            notice.setAttribute('role', 'status');
            notice.textContent = 'Baki stok berjaya dikemas kini.';
            document.querySelector('[data-product-index]')?.prepend(notice);
            window.addEventListener('load', () => window.scrollTo(0, position.y), {once: true});
        }
    } catch { /* Storage may be unavailable in private browsing. */ }
}
