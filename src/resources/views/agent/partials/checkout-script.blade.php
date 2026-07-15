    document.querySelector('[data-back-to-cart]').addEventListener('click', () => {
        close(checkoutModal);
        renderReview();
        cartModal.classList.remove('hidden');
        cartModal.classList.add('flex');
    });
    document.querySelectorAll('[data-fulfilment]').forEach((button) => button.addEventListener('click', () => {
        const isDelivery = button.dataset.fulfilment === 'delivery';
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
    document.querySelector('[data-checkout-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        renderCheckout();
        successModal.classList.remove('hidden');
        successModal.classList.add('flex');
    });
    document.querySelector('[data-close-success]').addEventListener('click', () => {
        close(successModal);
        close(checkoutModal);
    });
