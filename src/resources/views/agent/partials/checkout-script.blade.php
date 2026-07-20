    const checkoutForm = document.querySelector('[data-checkout-form]');
    const checkoutError = document.querySelector('[data-checkout-error]');
    const submitOrderButton = document.querySelector('[data-submit-order]');
    let checkoutToken = crypto.randomUUID();

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
    }));

    document.querySelectorAll('[data-payment-option] input').forEach((input) => input.addEventListener('change', () => {
        document.querySelectorAll('[data-payment-option]').forEach((option) => {
            const active = option.contains(input);
            option.classList.toggle('border-[#17324d]', active);
            option.classList.toggle('bg-blue-50/50', active);
            option.classList.toggle('border-slate-200', !active);
            option.classList.toggle('bg-white', !active);
        });
    }));

    checkoutForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        checkoutError.classList.add('hidden');
        submitOrderButton.disabled = true;

        const formData = new FormData(checkoutForm);
        const payload = {
            idempotency_key: checkoutToken,
            fulfilment_method: formData.get('fulfilment_method'),
            recipient_name: formData.get('recipient_name'),
            phone_number: formData.get('phone_number'),
            delivery_address: formData.get('delivery_address'),
            notes: formData.get('notes'),
            payment_method: formData.get('payment_method'),
            items: values().map((item) => ({
                product_id: Number(item.id),
                quantity: Number(item.quantity),
            })),
        };

        try {
            const response = await fetch(checkoutForm.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': formData.get('_token'),
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (!response.ok) {
                const validationMessage = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(validationMessage || result.message || 'Unable to place the order. Please try again.');
            }

            document.querySelector('[data-success-order]').textContent = `Order ${result.order.number}`;
            document.querySelector('[data-success-total]').textContent = currency.format(Number(result.order.total));
            cart = {};
            localStorage.removeItem(cartKey);
            renderCart();
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
