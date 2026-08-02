const modal = document.querySelector('[data-weekly-payment-modal]');

if (modal) {
    const form = modal.querySelector('[data-weekly-payment-form]');
    const triggers = [...document.querySelectorAll('[data-open-weekly-payment]')];
    const closeButtons = modal.querySelectorAll('[data-close-weekly-payment]');
    const summaryInput = modal.querySelector('[data-payment-summary-id]');
    const existingAttachment = modal.querySelector('[data-payment-existing-attachment]');
    const notifyCheckbox = modal.querySelector('input[type="checkbox"][name="notify_agent"]');
    const submitButton = form?.querySelector('button[type="submit"]');
    let previousFocus = null;

    const setText = (selector, value) => {
        const element = modal.querySelector(selector);

        if (element) {
            element.textContent = value || '-';
        }
    };

    const updateSubmitLabel = () => {
        if (submitButton) {
            submitButton.textContent = notifyCheckbox?.checked
                ? 'Confirm payment & send email'
                : 'Confirm payment';
        }
    };

    const openModal = (trigger, preserveOldValues = false) => {
        if (!form || !trigger || !summaryInput || !existingAttachment) {
            return;
        }

        previousFocus = document.activeElement;
        form.action = trigger.dataset.action || '';
        summaryInput.value = trigger.dataset.summaryId || '';

        setText('[data-payment-agent-name]', trigger.dataset.agentName);
        setText('[data-payment-agent-email]', trigger.dataset.agentEmail);
        setText('[data-payment-notify-email]', trigger.dataset.agentEmail);
        setText('[data-payment-bank-name]', trigger.dataset.bankName);
        setText('[data-payment-bank-account-name]', trigger.dataset.bankAccountName);
        setText('[data-payment-bank-account-number]', trigger.dataset.bankAccountNumber);
        setText('[data-payment-total-bonus]', trigger.dataset.totalBonus || '0.00');

        if (!preserveOldValues) {
            form.elements.payment_receipt_datetime_text.value = trigger.dataset.receiptDatetime || '';
            form.elements.payment_reference.value = trigger.dataset.reference || '';
            form.elements.payment_notes.value = trigger.dataset.notes || '';

            if (notifyCheckbox) {
                notifyCheckbox.checked = true;
            }
        }

        updateSubmitLabel();

        const attachmentUrl = trigger.dataset.attachmentUrl || '';

        if (attachmentUrl) {
            existingAttachment.href = attachmentUrl;
            existingAttachment.classList.remove('hidden');
        } else {
            existingAttachment.removeAttribute('href');
            existingAttachment.classList.add('hidden');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        modal.querySelector('[data-close-weekly-payment]:not(.absolute)')?.focus();
    };

    const closeModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        if (previousFocus instanceof HTMLElement) {
            previousFocus.focus();
        }
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openModal(trigger));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    notifyCheckbox?.addEventListener('change', updateSubmitLabel);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    form?.addEventListener('submit', () => {
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Processing payment...';
        }
    });

    const openSummaryId = modal.dataset.openSummaryId;

    if (openSummaryId) {
        const trigger = triggers.find((candidate) => candidate.dataset.summaryId === openSummaryId && candidate.offsetParent !== null)
            || triggers.find((candidate) => candidate.dataset.summaryId === openSummaryId);

        openModal(trigger, modal.dataset.preserveOldValues === 'true');
    }
}
