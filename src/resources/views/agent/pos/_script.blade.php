@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-pos-root]');
    if (!root) return;

    const timer = root.querySelector('[data-pos-timer]');
    if (timer) {
        const signedInAt = new Date(timer.dataset.signedInAt).getTime();
        const tick = () => {
            const elapsed = Math.max(0, Date.now() - signedInAt);
            const seconds = Math.floor(elapsed / 1000);
            const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
            const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
            const remainder = String(seconds % 60).padStart(2, '0');
            timer.textContent = hours + ':' + minutes + ':' + remainder;
        };
        tick();
        window.setInterval(tick, 1000);
    }

    const form = root.querySelector('[data-pos-sale-form]');
    if (!form) return;

    const items = form.querySelector('[data-pos-items]');
    const template = document.querySelector('[data-pos-item-template]');
    const salesAgentInput = form.querySelector('[data-pos-sales-agent-input]');
    const salesAgentGrid = form.querySelector('[data-pos-sales-agent-grid]');
    const summaryCard = form.querySelector('[data-pos-summary-card]');
    const submitWrap = form.querySelector('[data-pos-submit-wrap]');
    const submitButton = form.querySelector('[data-pos-submit]');
    const modal = document.querySelector('[data-pos-discount-modal]');
    const discountInput = modal.querySelector('[data-discount-value-input]');
    const imageModal = document.querySelector('[data-pos-image-modal]');
    const imageModalSrc = imageModal?.querySelector('[data-pos-image-modal-src]');
    const money = new Intl.NumberFormat('en-MY', { style: 'currency', currency: 'MYR' });
    let activeDiscountRow = null;
    let grandTotal = 0;
    let hasSeenSummary = false;

    const updateSubmitState = () => {
        if (!submitButton || !submitWrap) return;
        submitWrap.classList.toggle('hidden', !hasSeenSummary);
        submitButton.disabled = grandTotal <= 0;
    };

    const setAgentChoiceState = (button, selected) => {
        button.classList.toggle('border-orange-300', selected);
        button.classList.toggle('bg-orange-50', selected);
        button.classList.toggle('text-[#d95419]', selected);
        button.classList.toggle('border-slate-200', !selected);
        button.classList.toggle('bg-white', !selected);
        button.classList.toggle('text-slate-700', !selected);

        const icon = button.querySelector('[data-pos-agent-icon]');
        if (icon) {
            icon.classList.toggle('bg-[#d95419]', selected);
            icon.classList.toggle('text-white', selected);
            icon.classList.toggle('bg-slate-100', !selected);
            icon.classList.toggle('text-slate-700', !selected);
        }

        const photo = button.querySelector('[data-pos-agent-photo]');
        if (photo) {
            photo.classList.toggle('ring-orange-300', selected);
            photo.classList.toggle('ring-slate-200', !selected);
        }
    };

    const syncSalesAgentChoice = () => {
        if (!salesAgentGrid || !salesAgentInput) return;
        const selected = salesAgentInput.value;
        salesAgentGrid.querySelectorAll('[data-pos-sales-agent-choice]').forEach((button) => {
            setAgentChoiceState(button, button.dataset.agentId === selected);
        });
    };

    const clampDiscount = (value, maxValue = Number.POSITIVE_INFINITY) => Math.min(maxValue, Math.max(0, Number(value) || 0));
    const rowPrice = (row) => Number(row.querySelector('[data-pos-product]').selectedOptions[0]?.dataset.price || 0);
    const rowQuantity = (row) => Number(row.querySelector('[data-pos-quantity]').value || 0);
    const rowGross = (row) => rowPrice(row) * rowQuantity(row);
    const renderProductList = (row, keyword = '') => {
        const searchInput = row.querySelector('[data-pos-product-search]');
        const select = row.querySelector('[data-pos-product]');
        const list = row.querySelector('[data-pos-product-list]');
        if (!searchInput || !select || !list) return;

        const normalized = keyword.trim().toLowerCase();
        const options = [...select.options].filter((option) => option.value !== '');
        const filtered = normalized === ''
            ? options
            : options.filter((option) => option.textContent.toLowerCase().includes(normalized));

        if (filtered.length === 0) {
            list.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">No matching product</div>';
            return;
        }

        list.innerHTML = filtered.map((option) => {
            const isSelected = option.value === select.value;
            return '<button type="button" class="block w-full px-3 py-2 text-left text-sm ' + (isSelected ? 'bg-orange-50 text-[#d95419] font-semibold' : 'text-slate-700 hover:bg-slate-50') + '" data-pos-product-option data-value="' + option.value + '" data-label="' + option.textContent.replace(/"/g, '&quot;') + '">' + option.textContent + '</button>';
        }).join('');
    };

    const openProductList = (row) => {
        const list = row.querySelector('[data-pos-product-list]');
        if (!list) return;
        list.classList.remove('hidden');
    };

    const closeProductList = (row) => {
        const list = row.querySelector('[data-pos-product-list]');
        if (!list) return;
        list.classList.add('hidden');
    };

    const syncSearchWithSelectedProduct = (row, fallbackToEmpty = false) => {
        const searchInput = row.querySelector('[data-pos-product-search]');
        const select = row.querySelector('[data-pos-product]');
        if (!searchInput || !select) return;

        const selectedLabel = select.selectedOptions[0]?.textContent || '';
        searchInput.value = select.value
            ? selectedLabel
            : (fallbackToEmpty ? '' : searchInput.value);
    };

    const applySelectedProduct = (row, value, label) => {
        const select = row.querySelector('[data-pos-product]');
        const searchInput = row.querySelector('[data-pos-product-search]');
        if (!select || !searchInput) return;

        select.value = value;
        searchInput.value = label;
        closeProductList(row);
    };
    const setRowDefaultDiscount = (row, force = false) => {
        const discount = row.querySelector('[data-pos-discount]');
        if (force || discount.value === '') {
            discount.value = '0.00';
            discount.dataset.customDiscount = 'false';
        }
    };

    const reindex = () => {
        items.querySelectorAll('[data-pos-item]').forEach((row, index) => {
            row.querySelector('[data-pos-product]').name = 'items[' + index + '][product_id]';
            row.querySelector('[data-pos-quantity]').name = 'items[' + index + '][quantity]';
            row.querySelector('[data-pos-discount]').name = 'items[' + index + '][discount_amount]';
        });
    };

    const calculate = () => {
        let grossTotal = 0;
        let discountTotal = 0;

        items.querySelectorAll('[data-pos-item]').forEach((row) => {
            const gross = rowGross(row);
            const discountInputValue = row.querySelector('[data-pos-discount]');
            const discount = clampDiscount(discountInputValue.value, gross);
            const net = gross - discount;

            if (discount !== Number(discountInputValue.value || 0)) {
                discountInputValue.value = discount.toFixed(2);
            }

            grossTotal += gross;
            discountTotal += discount;
            row.querySelector('[data-pos-line-total]').textContent = money.format(net);
            row.querySelector('[data-pos-discount-label]').textContent = 'Discount ' + money.format(discount);
        });

        form.querySelector('[data-pos-gross-total]').textContent = money.format(grossTotal);
        form.querySelector('[data-pos-total-discount]').textContent = '- ' + money.format(discountTotal);
        grandTotal = Math.max(0, grossTotal - discountTotal);
        form.querySelector('[data-pos-grand-total]').textContent = money.format(grandTotal);
        updateSubmitState();
    };

    const updateModalPreview = () => {
        if (!activeDiscountRow) return;
        const gross = rowGross(activeDiscountRow);
        const discount = clampDiscount(discountInput.value, gross);
        modal.querySelector('[data-discount-gross]').textContent = money.format(gross);
        modal.querySelector('[data-discount-preview-amount]').textContent = '- ' + money.format(discount);
        modal.querySelector('[data-discount-net]').textContent = money.format(gross - discount);
    };

    const openDiscountModal = (row) => {
        activeDiscountRow = row;
        setRowDefaultDiscount(row);
        const discount = clampDiscount(row.querySelector('[data-pos-discount]').value, rowGross(row));
        discountInput.value = discount.toFixed(2);
        updateModalPreview();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => {
            discountInput.focus();
            discountInput.select();
        }, 50);
    };

    const closeDiscountModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        activeDiscountRow = null;
    };

    const closeImageModal = () => {
        if (!imageModal || !imageModalSrc) return;
        imageModal.classList.add('hidden');
        imageModal.classList.remove('flex');
        imageModalSrc.src = '';
        document.body.classList.remove('overflow-hidden');
    };

    const openImageModal = (url) => {
        if (!imageModal || !imageModalSrc || !url) return;
        imageModalSrc.src = url;
        imageModal.classList.remove('hidden');
        imageModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    };

    form.querySelector('[data-add-pos-item]')?.addEventListener('click', () => {
        items.appendChild(template.content.cloneNode(true));
        const row = items.lastElementChild;
        setRowDefaultDiscount(row, true);
        syncSearchWithSelectedProduct(row, true);
        reindex();
        calculate();
    });

    salesAgentGrid?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pos-sales-agent-choice]');
        if (!button || !salesAgentInput) return;

        salesAgentInput.value = button.dataset.agentId || '';
        syncSalesAgentChoice();
    });

    items.addEventListener('click', (event) => {
        const productOption = event.target.closest('[data-pos-product-option]');
        if (productOption) {
            const row = productOption.closest('[data-pos-item]');
            applySelectedProduct(row, productOption.dataset.value || '', productOption.dataset.label || '');
            calculate();
            return;
        }

        const discountButton = event.target.closest('[data-open-pos-discount]');
        if (discountButton) {
            openDiscountModal(discountButton.closest('[data-pos-item]'));
            return;
        }

        const qtyPlusButton = event.target.closest('[data-pos-qty-plus]');
        if (qtyPlusButton) {
            const row = qtyPlusButton.closest('[data-pos-item]');
            const quantityInput = row?.querySelector('[data-pos-quantity]');
            if (!quantityInput) return;
            const max = Number(quantityInput.max || 9999);
            const value = Number(quantityInput.value || 0);
            quantityInput.value = String(Math.min(max, value + 1));
            calculate();
            return;
        }

        const qtyMinusButton = event.target.closest('[data-pos-qty-minus]');
        if (qtyMinusButton) {
            const row = qtyMinusButton.closest('[data-pos-item]');
            const quantityInput = row?.querySelector('[data-pos-quantity]');
            if (!quantityInput) return;
            const min = Number(quantityInput.min || 1);
            const value = Number(quantityInput.value || 0);
            quantityInput.value = String(Math.max(min, value - 1));
            calculate();
            return;
        }

        const removeButton = event.target.closest('[data-remove-pos-item]');
        if (!removeButton) return;

        if (items.querySelectorAll('[data-pos-item]').length === 1) {
            const row = removeButton.closest('[data-pos-item]');
            row.querySelector('[data-pos-product]').value = '';
            row.querySelector('[data-pos-quantity]').value = 1;
            setRowDefaultDiscount(row, true);
        } else {
            removeButton.closest('[data-pos-item]').remove();
        }

        reindex();
        calculate();
    });

    items.addEventListener('change', (event) => {
        const row = event.target.closest('[data-pos-item]');
        if (row && event.target.matches('[data-pos-product]')) {
            syncSearchWithSelectedProduct(row);
        }
        calculate();
    });
    items.addEventListener('input', (event) => {
        const row = event.target.closest('[data-pos-item]');
        if (row && event.target.matches('[data-pos-product-search]')) {
            const keyword = event.target.value;
            const select = row.querySelector('[data-pos-product]');
            if (select && !select.selectedOptions[0]?.textContent.toLowerCase().includes(keyword.trim().toLowerCase())) {
                select.value = '';
            }
            renderProductList(row, keyword);
            openProductList(row);
        }
        calculate();
    });
    items.addEventListener('focusin', (event) => {
        const row = event.target.closest('[data-pos-item]');
        if (!row || !event.target.matches('[data-pos-product-search]')) return;

        renderProductList(row, event.target.value || '');
        openProductList(row);
    });
    items.addEventListener('focusout', (event) => {
        const row = event.target.closest('[data-pos-item]');
        if (!row || !event.target.matches('[data-pos-product-search]')) return;

        window.setTimeout(() => closeProductList(row), 120);
    });

    discountInput.addEventListener('input', updateModalPreview);
    modal.querySelector('[data-apply-pos-discount]').addEventListener('click', () => {
        if (!activeDiscountRow) return;
        const discount = activeDiscountRow.querySelector('[data-pos-discount]');
        discount.value = clampDiscount(discountInput.value, rowGross(activeDiscountRow)).toFixed(2);
        discount.dataset.customDiscount = 'true';
        calculate();
        closeDiscountModal();
    });
    modal.querySelector('[data-reset-pos-discount]').addEventListener('click', () => {
        if (!activeDiscountRow) return;
        const discount = activeDiscountRow.querySelector('[data-pos-discount]');
        discount.value = '0.00';
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
        if (event.key === 'Escape' && imageModal && !imageModal.classList.contains('hidden')) closeImageModal();
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-pos-product-search]') || event.target.closest('[data-pos-product-list]')) {
            return;
        }

        items.querySelectorAll('[data-pos-item]').forEach((row) => closeProductList(row));
    });

    reindex();
    syncSalesAgentChoice();
    items.querySelectorAll('[data-pos-item]').forEach((row) => {
        setRowDefaultDiscount(row);
        syncSearchWithSelectedProduct(row, true);
        closeProductList(row);
    });

    if (summaryCard && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0]?.isIntersecting) {
                hasSeenSummary = true;
                updateSubmitState();
            }
        }, { threshold: 0.35 });

        observer.observe(summaryCard);
    } else {
        hasSeenSummary = true;
    }

    calculate();

    form.querySelectorAll('[data-pos-picture]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = form.querySelector('[data-picture-name="' + input.id + '"]');
            if (!label) return;
            const maxFiles = Number(input.dataset.maxFiles || 5);

            if (input.files && input.files.length > maxFiles) {
                input.value = '';
                label.textContent = 'Maximum ' + maxFiles + ' files allowed';
                return;
            }

            if (!input.files || input.files.length === 0) {
                label.textContent = input.id === 'payment_proofs' ? 'No proof selected' : 'No picture selected';
                return;
            }

            const totalSizeMb = [...input.files].reduce((total, file) => total + file.size, 0) / 1048576;
            label.textContent = input.files.length === 1
                ? input.files[0].name + ' · ' + totalSizeMb.toFixed(2) + ' MB'
                : input.files.length + ' files selected · ' + totalSizeMb.toFixed(2) + ' MB';
        });
    });

    form.querySelectorAll('[data-pos-image-thumb]').forEach((button) => {
        button.addEventListener('click', () => {
            openImageModal(button.dataset.previewSrc || '');
        });
    });

    imageModal?.querySelectorAll('[data-pos-image-close]').forEach((button) => {
        button.addEventListener('click', closeImageModal);
    });

    imageModal?.addEventListener('click', (event) => {
        if (event.target === imageModal) closeImageModal();
    });

    form.addEventListener('submit', async (event) => {
        if (form.dataset.prepared === 'true') return;
        if (submitButton?.disabled) {
            event.preventDefault();
            return;
        }

        const missingProductRow = [...items.querySelectorAll('[data-pos-item]')]
            .find((row) => !row.querySelector('[data-pos-product]')?.value);

        if (missingProductRow) {
            event.preventDefault();
            const searchInput = missingProductRow.querySelector('[data-pos-product-search]');
            if (searchInput) {
                searchInput.focus();
                searchInput.classList.add('border-red-300', 'ring-2', 'ring-red-100');
                window.setTimeout(() => {
                    searchInput.classList.remove('border-red-300', 'ring-2', 'ring-red-100');
                }, 1600);
            }
            return;
        }

        const submit = submitButton;
        submit.disabled = true;
        submit.textContent = 'Saving sale...';
    });
})();
</script>
@endpush
