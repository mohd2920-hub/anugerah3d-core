@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-pos-root]');
    if (!root) return;

    const timer = root.querySelector('[data-pos-timer]');
    if (timer) {
        const expiresAt = new Date(timer.dataset.expiresAt).getTime();
        const tick = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const seconds = Math.floor(remaining / 1000);
            const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const remainder = String(seconds % 60).padStart(2, '0');
            timer.textContent = hours + ':' + minutes + ':' + remainder;
            if (remaining === 0) window.location.reload();
        };
        tick();
        window.setInterval(tick, 1000);
    }

    const form = root.querySelector('[data-pos-sale-form]');
    if (!form) return;

    const items = form.querySelector('[data-pos-items]');
    const template = document.querySelector('[data-pos-item-template]');
    const salesAgent = form.querySelector('[data-pos-sales-agent]');
    const modal = document.querySelector('[data-pos-discount-modal]');
    const discountInput = modal.querySelector('[data-discount-percentage]');
    const money = new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' });
    let activeDiscountRow = null;

    const clampDiscount = (value) => Math.min(100, Math.max(0, Number(value) || 0));
    const rowPrice = (row) => Number(row.querySelector('[data-pos-product]').selectedOptions[0]?.dataset.price || 0);
    const rowQuantity = (row) => Number(row.querySelector('[data-pos-quantity]').value || 0);
    const rowGross = (row) => rowPrice(row) * rowQuantity(row);
    const rowBaseline = (row) => {
        const agentDiscount = Number(salesAgent.selectedOptions[0]?.dataset.discount || 0);
        const productDiscount = Number(row.querySelector('[data-pos-product]').selectedOptions[0]?.dataset.defaultDiscount || 0);
        return clampDiscount(agentDiscount > 0 ? agentDiscount : productDiscount);
    };

    const setRowBaseline = (row, force = false) => {
        const discount = row.querySelector('[data-pos-discount]');
        if (force || discount.dataset.customDiscount !== 'true' || discount.value === '') {
            discount.value = rowBaseline(row).toFixed(2);
            discount.dataset.customDiscount = 'false';
        }
    };

    const reindex = () => {
        items.querySelectorAll('[data-pos-item]').forEach((row, index) => {
            row.querySelector('[data-pos-product]').name = 'items[' + index + '][product_id]';
            row.querySelector('[data-pos-quantity]').name = 'items[' + index + '][quantity]';
            row.querySelector('[data-pos-discount]').name = 'items[' + index + '][discount_percentage]';
        });
    };

    const calculate = () => {
        let grossTotal = 0;
        let discountTotal = 0;

        items.querySelectorAll('[data-pos-item]').forEach((row) => {
            setRowBaseline(row);
            const gross = rowGross(row);
            const percentage = clampDiscount(row.querySelector('[data-pos-discount]').value);
            const discount = gross * percentage / 100;
            const net = gross - discount;

            grossTotal += gross;
            discountTotal += discount;
            row.querySelector('[data-pos-line-total]').textContent = money.format(net);
            row.querySelector('[data-pos-discount-label]').textContent = 'Discount ' + percentage.toFixed(2).replace(/\.00$/, '') + '% · ' + money.format(discount);
        });

        form.querySelector('[data-pos-gross-total]').textContent = money.format(grossTotal);
        form.querySelector('[data-pos-total-discount]').textContent = '- ' + money.format(discountTotal);
        form.querySelector('[data-pos-grand-total]').textContent = money.format(grossTotal - discountTotal);
    };

    const updateModalPreview = () => {
        if (!activeDiscountRow) return;
        const gross = rowGross(activeDiscountRow);
        const percentage = clampDiscount(discountInput.value);
        const discount = gross * percentage / 100;
        modal.querySelector('[data-discount-gross]').textContent = money.format(gross);
        modal.querySelector('[data-discount-amount]').textContent = '- ' + money.format(discount);
        modal.querySelector('[data-discount-net]').textContent = money.format(gross - discount);
    };

    const openDiscountModal = (row) => {
        activeDiscountRow = row;
        setRowBaseline(row);
        const percentage = clampDiscount(row.querySelector('[data-pos-discount]').value);
        modal.querySelector('[data-discount-baseline]').textContent = rowBaseline(row).toFixed(2).replace(/\.00$/, '') + '%';
        discountInput.value = percentage.toFixed(2);
        updateModalPreview();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => discountInput.focus(), 50);
    };

    const closeDiscountModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        activeDiscountRow = null;
    };

    form.querySelector('[data-add-pos-item]')?.addEventListener('click', () => {
        items.appendChild(template.content.cloneNode(true));
        const row = items.lastElementChild;
        setRowBaseline(row, true);
        reindex();
        calculate();
    });

    salesAgent.addEventListener('change', () => {
        items.querySelectorAll('[data-pos-item]').forEach((row) => setRowBaseline(row, true));
        calculate();
    });

    items.addEventListener('click', (event) => {
        const discountButton = event.target.closest('[data-open-pos-discount]');
        if (discountButton) {
            openDiscountModal(discountButton.closest('[data-pos-item]'));
            return;
        }

        const removeButton = event.target.closest('[data-remove-pos-item]');
        if (!removeButton) return;

        if (items.querySelectorAll('[data-pos-item]').length === 1) {
            const row = removeButton.closest('[data-pos-item]');
            row.querySelector('[data-pos-product]').value = '';
            row.querySelector('[data-pos-quantity]').value = 1;
            setRowBaseline(row, true);
        } else {
            removeButton.closest('[data-pos-item]').remove();
        }

        reindex();
        calculate();
    });

    items.addEventListener('change', (event) => {
        const row = event.target.closest('[data-pos-item]');
        if (row && event.target.matches('[data-pos-product]')) setRowBaseline(row, true);
        calculate();
    });
    items.addEventListener('input', calculate);

    discountInput.addEventListener('input', updateModalPreview);
    modal.querySelector('[data-apply-pos-discount]').addEventListener('click', () => {
        if (!activeDiscountRow) return;
        const discount = activeDiscountRow.querySelector('[data-pos-discount]');
        discount.value = clampDiscount(discountInput.value).toFixed(2);
        discount.dataset.customDiscount = 'true';
        calculate();
        closeDiscountModal();
    });
    modal.querySelector('[data-reset-pos-discount]').addEventListener('click', () => {
        if (!activeDiscountRow) return;
        const discount = activeDiscountRow.querySelector('[data-pos-discount]');
        discount.value = rowBaseline(activeDiscountRow).toFixed(2);
        discount.dataset.customDiscount = 'false';
        discountInput.value = discount.value;
        updateModalPreview();
    });
    modal.querySelectorAll('[data-close-pos-discount]').forEach((button) => button.addEventListener('click', closeDiscountModal));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeDiscountModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeDiscountModal();
    });

    reindex();
    items.querySelectorAll('[data-pos-item]').forEach((row) => setRowBaseline(row));
    calculate();

    document.querySelectorAll('[data-picture-input]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.pictureInput);
            if (button.dataset.pictureMode === 'camera') input.setAttribute('capture', 'environment');
            else input.removeAttribute('capture');
            input.click();
        });
    });
    form.querySelectorAll('[data-pos-picture]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = form.querySelector('[data-picture-name="' + input.id + '"]');
            if (label && input.files[0]) label.textContent = input.files[0].name + ' · ' + (input.files[0].size / 1048576).toFixed(2) + ' MB';
        });
    });

    const compressPicture = (input) => new Promise((resolve) => {
        const file = input.files[0];
        if (!file || !file.type.startsWith('image/') || file.size < 700000) {
            resolve();
            return;
        }

        const image = new Image();
        const url = URL.createObjectURL(file);
        image.onload = () => {
            URL.revokeObjectURL(url);
            const scale = Math.min(1, 1600 / Math.max(image.naturalWidth, image.naturalHeight));
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(image.naturalWidth * scale);
            canvas.height = Math.round(image.naturalHeight * scale);
            canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (blob && window.DataTransfer) {
                    const transfer = new DataTransfer();
                    transfer.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
                    input.files = transfer.files;
                }
                resolve();
            }, 'image/jpeg', 0.82);
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            resolve();
        };
        image.src = url;
    });

    form.addEventListener('submit', async (event) => {
        if (form.dataset.prepared === 'true') return;
        event.preventDefault();
        const submit = form.querySelector('[data-pos-submit]');
        submit.disabled = true;
        submit.textContent = 'Preparing pictures...';
        await Promise.all([...form.querySelectorAll('[data-pos-picture]')].map(compressPicture));
        form.dataset.prepared = 'true';
        submit.textContent = 'Saving sale...';
        form.submit();
    });
})();
</script>
@endpush
