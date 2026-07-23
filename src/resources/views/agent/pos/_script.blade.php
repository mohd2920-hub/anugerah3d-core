@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-pos-root]');
    if (!root) return;

    const deleteModal = root.querySelector('[data-pos-delete-modal]');
    const deleteForm = deleteModal?.querySelector('[data-pos-delete-form]');
    const deletePassword = deleteModal?.querySelector('[name="delete_password"]');

    const openDeleteModal = (action, saleNumber, preservePasswordError = false) => {
        if (!deleteModal || !deleteForm) return;

        deleteForm.action = action;
        deleteForm.querySelector('[data-pos-delete-action]').value = action;
        deleteForm.querySelector('[data-pos-delete-sale-number]').value = saleNumber;
        deleteModal.querySelector('[data-pos-delete-sale-label]').textContent = saleNumber;

        if (!preservePasswordError && deletePassword) deletePassword.value = '';

        deleteModal.classList.remove('hidden');
        deleteModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => deletePassword?.focus(), 50);
    };

    const closeDeleteModal = () => {
        if (!deleteModal) return;
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    };

    root.querySelectorAll('[data-open-pos-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            openDeleteModal(button.dataset.action || '', button.dataset.saleNumber || '');
        });
    });

    deleteModal?.querySelectorAll('[data-close-pos-delete]').forEach((button) => {
        button.addEventListener('click', closeDeleteModal);
    });

    deleteForm?.addEventListener('submit', () => {
        const submit = deleteForm.querySelector('[type="submit"]');
        if (!submit) return;
        submit.disabled = true;
        submit.textContent = 'Deleting sale...';
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && deleteModal && !deleteModal.classList.contains('hidden')) {
            closeDeleteModal();
        }
    });

    if (deleteModal?.dataset.openOnLoad === 'true') {
        openDeleteModal(deleteModal.dataset.action || '', deleteModal.dataset.saleNumber || '', true);
    }

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
    const quickProducts = form.querySelector('[data-pos-quick-products]');
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
    let quickProductToastTimer = null;

    const showQuickProductToast = (button) => {
        form.querySelector('[data-pos-quick-toast]')?.remove();
        if (quickProductToastTimer) window.clearTimeout(quickProductToastTimer);

        const toast = document.createElement('span');
        toast.dataset.posQuickToast = '';
        toast.className = 'pointer-events-none absolute z-40 bg-[#17324d] font-bold text-white shadow-lg';
        toast.style.left = '50%';
        toast.style.bottom = 'calc(100% + 8px)';
        toast.style.transform = 'translateX(-50%)';
        toast.style.width = 'max-content';
        toast.style.minWidth = '260px';
        toast.style.maxWidth = 'min(360px, calc(100vw - 32px))';
        toast.style.overflow = 'hidden';
        toast.style.padding = '10px 16px';
        toast.style.borderRadius = '9999px';
        toast.style.fontSize = '12px';
        toast.style.lineHeight = '18px';
        toast.style.textAlign = 'center';
        toast.style.textOverflow = 'ellipsis';
        toast.style.whiteSpace = 'nowrap';
        toast.textContent = '✓ 1 item added  ·  ' + (button.dataset.name || '');
        button.appendChild(toast);

        quickProductToastTimer = window.setTimeout(() => toast.remove(), 1600);
    };

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
        const filtered = (normalized === ''
            ? options
            : options.filter((option) => (option.dataset.search || '').includes(normalized)))
            .slice(0, 20);

        if (filtered.length === 0) {
            list.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">No matching product</div>';
            return;
        }

        list.replaceChildren();
        const fragment = document.createDocumentFragment();

        filtered.forEach((option) => {
            const isSelected = option.value === select.value;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'flex w-full items-center gap-3 px-3 py-2 text-left ' + (isSelected ? 'bg-orange-50 text-[#d95419]' : 'text-slate-700 hover:bg-slate-50');
            button.dataset.posProductOption = '';
            button.dataset.value = option.value;
            button.dataset.label = option.textContent;

            if (option.dataset.image) {
                const image = document.createElement('img');
                image.src = option.dataset.image;
                image.alt = '';
                image.loading = 'lazy';
                image.className = 'h-10 w-10 shrink-0 rounded-lg bg-slate-100 object-cover';
                button.appendChild(image);
            } else {
                const placeholder = document.createElement('span');
                placeholder.className = 'grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-sm text-slate-400';
                placeholder.textContent = '▧';
                button.appendChild(placeholder);
            }

            const details = document.createElement('span');
            details.className = 'min-w-0 flex-1';

            const code = document.createElement('span');
            code.className = 'block truncate text-[10px] font-bold uppercase tracking-wider text-slate-400';
            code.textContent = option.dataset.code || '';

            const name = document.createElement('span');
            name.className = 'mt-0.5 block truncate text-sm font-semibold';
            name.textContent = option.dataset.name || option.textContent;

            const price = document.createElement('span');
            price.className = 'mt-0.5 block text-[11px] font-bold text-[#d95419]';
            price.textContent = money.format(Number(option.dataset.price || 0));

            details.append(code, name, price);
            button.appendChild(details);
            fragment.appendChild(button);
        });

        list.appendChild(fragment);
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

    quickProducts?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pos-quick-product]');
        if (!button) return;
        showQuickProductToast(button);

        const productId = button.dataset.value || '';
        const existingRow = [...items.querySelectorAll('[data-pos-item]')]
            .find((row) => row.querySelector('[data-pos-product]')?.value === productId);

        if (existingRow) {
            const quantityInput = existingRow.querySelector('[data-pos-quantity]');
            if (!quantityInput) return;
            const max = Number(quantityInput.max || 9999);
            quantityInput.value = String(Math.min(max, Number(quantityInput.value || 0) + 1));
            calculate();
            return;
        }

        let targetRow = [...items.querySelectorAll('[data-pos-item]')]
            .find((row) => !row.querySelector('[data-pos-product]')?.value);

        if (!targetRow) {
            items.appendChild(template.content.cloneNode(true));
            targetRow = items.lastElementChild;
            reindex();
        }

        setRowDefaultDiscount(targetRow, true);
        applySelectedProduct(targetRow, productId, button.dataset.label || '');
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

        const select = row.querySelector('[data-pos-product]');
        const selectedLabel = select?.selectedOptions[0]?.textContent || '';
        const keyword = select?.value && event.target.value === selectedLabel ? '' : event.target.value;
        renderProductList(row, keyword || '');
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
