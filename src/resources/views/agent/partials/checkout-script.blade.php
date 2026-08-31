    const checkoutForm = document.querySelector('[data-checkout-form]');
    const checkoutError = document.querySelector('[data-checkout-error]');
    const submitOrderButton = document.querySelector('[data-submit-order]');
    const paymentProofSection = document.querySelector('[data-payment-proof-section]');
    const paymentProofUploadInput = document.querySelector('[data-payment-proof-upload]');
    const paymentProofCameraInput = document.querySelector('[data-payment-proof-camera]');
    const paymentProofStatus = document.querySelector('[data-payment-proof-status]');
    const paymentProofPreview = document.querySelector('[data-payment-proof-preview]');
    const paymentProofClearButton = document.querySelector('[data-payment-proof-clear]');
    const checkoutProofModal = document.querySelector('[data-checkout-proof-modal]');
    const checkoutProofModalSrc = document.querySelector('[data-checkout-proof-modal-src]');
    let checkoutToken = crypto.randomUUID();

    const selectedPaymentMethod = () => document.querySelector('[data-payment-option] input:checked')?.value || 'bank_transfer';
    const selectedPaymentProofFiles = () => [
        ...(paymentProofUploadInput?.files ? Array.from(paymentProofUploadInput.files) : []),
        ...(paymentProofCameraInput?.files ? Array.from(paymentProofCameraInput.files) : []),
    ].slice(0, 5);

    const renderPaymentProofPreview = () => {
        if (!paymentProofPreview) return;

        const files = selectedPaymentProofFiles();
        paymentProofPreview.innerHTML = '';

        if (files.length === 0) {
            paymentProofPreview.classList.add('hidden');
            paymentProofClearButton?.classList.add('hidden');
            return;
        }

        files.forEach((file) => {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.className = 'h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white';

            const img = document.createElement('img');
            img.className = 'h-full w-full object-cover';
            img.alt = file.name;

            const url = URL.createObjectURL(file);
            img.src = url;

            thumb.addEventListener('click', (event) => {
                event.preventDefault();
                if (!checkoutProofModal || !checkoutProofModalSrc) return;
                checkoutProofModalSrc.src = url;
                checkoutProofModal.classList.remove('hidden');
                checkoutProofModal.classList.add('flex');
            });

            thumb.appendChild(img);
            paymentProofPreview.appendChild(thumb);
        });

        paymentProofPreview.classList.remove('hidden');
        paymentProofClearButton?.classList.remove('hidden');
    };

    const clearPaymentProofSelection = () => {
        if (paymentProofUploadInput) paymentProofUploadInput.value = '';
        if (paymentProofCameraInput) paymentProofCameraInput.value = '';
        if (checkoutProofModalSrc) checkoutProofModalSrc.src = '';
        checkoutProofModal?.classList.add('hidden');
        checkoutProofModal?.classList.remove('flex');
        updatePaymentProofStatus();
        renderPaymentProofPreview();
    };

    const updatePaymentProofStatus = () => {
        if (!paymentProofStatus) return;
        const files = selectedPaymentProofFiles();

        if (files.length === 0) {
            paymentProofStatus.textContent = 'No proof selected (max 5 images)';
            paymentProofStatus.classList.remove('text-emerald-700');
            paymentProofStatus.classList.add('text-slate-500');
            return;
        }

        paymentProofStatus.textContent = `${files.length} proof image(s) selected`;
        paymentProofStatus.classList.remove('text-slate-500');
        paymentProofStatus.classList.add('text-emerald-700');
    };

    const updatePaymentProofVisibility = () => {
        if (!paymentProofSection) return;
        const bankTransfer = selectedPaymentMethod() === 'bank_transfer';
        paymentProofSection.classList.toggle('hidden', !bankTransfer);
    };

    document.querySelector('[data-payment-proof-source="browse"]')?.addEventListener('click', () => {
        paymentProofUploadInput?.click();
    });

    document.querySelector('[data-payment-proof-source="camera"]')?.addEventListener('click', () => {
        paymentProofCameraInput?.click();
    });

    paymentProofUploadInput?.addEventListener('change', updatePaymentProofStatus);
    paymentProofCameraInput?.addEventListener('change', updatePaymentProofStatus);
    paymentProofUploadInput?.addEventListener('change', renderPaymentProofPreview);
    paymentProofCameraInput?.addEventListener('change', renderPaymentProofPreview);
    paymentProofClearButton?.addEventListener('click', clearPaymentProofSelection);
    document.querySelectorAll('[data-checkout-proof-close]').forEach((button) => {
        button.addEventListener('click', () => {
            checkoutProofModal?.classList.add('hidden');
            checkoutProofModal?.classList.remove('flex');
            if (checkoutProofModalSrc) checkoutProofModalSrc.src = '';
        });
    });

    document.querySelector('[data-back-to-cart]').addEventListener('click', () => {
        close(checkoutModal);
        renderReview();
        cartModal.classList.remove('hidden');
        cartModal.classList.add('flex');
    });

    document.querySelectorAll('[data-fulfilment]').forEach((button) => button.addEventListener('click', () => {
        const isDelivery = button.dataset.fulfilment === 'delivery';
        checkoutForm.elements.fulfilment_method.value = button.dataset.fulfilment;
        document.querySelectorAll('[data-fulfilment]').forEach((option) => {
            const active = option === button;
            option.classList.toggle('bg-white', active);
            option.classList.toggle('text-[#17324d]', active);
            option.classList.toggle('shadow-sm', active);
            option.classList.toggle('text-slate-500', !active);
        });
        document.querySelector('[data-delivery-address]').classList.toggle('hidden', !isDelivery);
        document.querySelector('[name="delivery_address"]').required = isDelivery;
        renderCheckout();
    }));

    document.querySelectorAll('[data-payment-option] input').forEach((input) => input.addEventListener('change', () => {
        document.querySelectorAll('[data-payment-option]').forEach((option) => {
            const active = option.contains(input);
            option.classList.toggle('border-[#17324d]', active);
            option.classList.toggle('bg-blue-50/50', active);
            option.classList.toggle('border-slate-200', !active);
            option.classList.toggle('bg-white', !active);
        });

        updatePaymentProofVisibility();
    }));

    updatePaymentProofStatus();
    renderPaymentProofPreview();
    updatePaymentProofVisibility();

    checkoutForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        checkoutError.classList.add('hidden');
        submitOrderButton.disabled = true;

        const formData = new FormData(checkoutForm);
        const paymentMethod = String(formData.get('payment_method') || 'bank_transfer');
        const proofFiles = selectedPaymentProofFiles();

        if (paymentMethod === 'bank_transfer' && proofFiles.length === 0) {
            checkoutError.textContent = 'Please upload or take at least one payment proof for bank transfer.';
            checkoutError.classList.remove('hidden');
            submitOrderButton.disabled = false;
            return;
        }

        const payload = new FormData();
        payload.append('_token', String(formData.get('_token') || ''));
        payload.append('idempotency_key', checkoutToken);
        payload.append('fulfilment_method', String(formData.get('fulfilment_method') || 'delivery'));
        payload.append('recipient_name', String(formData.get('recipient_name') || ''));
        payload.append('phone_number', String(formData.get('phone_number') || ''));
        payload.append('delivery_address', String(formData.get('delivery_address') || ''));
        payload.append('notes', String(formData.get('notes') || ''));
        payload.append('payment_method', paymentMethod);

        values().forEach((item, index) => {
            payload.append(`items[${index}][product_id]`, String(Number(item.id)));
            payload.append(`items[${index}][quantity]`, String(Number(item.quantity)));

            if (item.productType === 'clicker') {
                const characterCount = Number(item.clickerCharacterCount || 0);
                const characters = Array.isArray(item.clickerCharacters)
                    ? item.clickerCharacters
                        .map((character) => String(character || '').trim())
                        .filter((character) => character !== '')
                    : [];

                payload.append(`items[${index}][clicker_character_count]`, String(characterCount));
                payload.append(`items[${index}][clicker_casing_image_id]`, String(Number(item.clickerCasingSelection?.id || 0)));
                payload.append(`items[${index}][clicker_huruf_image_id]`, String(Number(item.clickerHurufSelection?.id || 0)));
                characters.slice(0, characterCount).forEach((character) => {
                    payload.append(`items[${index}][clicker_characters][]`, character);
                });
            }
        });

        proofFiles.forEach((file) => payload.append('payment_proofs[]', file));

        try {
            const response = await fetch(checkoutForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body: payload,
            });
            const contentType = response.headers.get('content-type') || '';
            const rawBody = await response.text();
            const isJsonResponse = contentType.includes('application/json');
            const result = isJsonResponse
                ? JSON.parse(rawBody || '{}')
                : { message: rawBody };

            if (!response.ok) {
                const validationMessage = result.errors ? Object.values(result.errors).flat()[0] : null;
                if (!isJsonResponse) {
                    throw new Error('Server returned an invalid response. Please refresh and try again.');
                }

                throw new Error(validationMessage || result.message || 'Unable to place the order. Please try again.');
            }

            document.querySelector('[data-success-order]').textContent = `Order ${result.order.number}`;
            document.querySelector('[data-success-total]').textContent = currency.format(Number(result.order.total));
            cart = {};
            localStorage.removeItem(cartKey);
            renderCart();
            clearPaymentProofSelection();
            checkoutToken = crypto.randomUUID();
            successModal.classList.remove('hidden');
            successModal.classList.add('flex');
        } catch (error) {
            checkoutError.textContent = error.message;
            checkoutError.classList.remove('hidden');
        } finally {
            submitOrderButton.disabled = false;
        }
    });

    document.querySelector('[data-close-success]').addEventListener('click', () => {
        window.location.assign(checkoutForm.dataset.historyUrl);
    });
