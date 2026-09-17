document.querySelectorAll('[data-salary-amounts]').forEach((form) => {
    const sync = () => {
        let totalCents = 0;
        form.querySelectorAll('tbody tr').forEach((row) => {
            const present = row.querySelector('[name="staff_ids[]"]').checked;
            const amount = row.querySelector('[data-salary-amount]');
            amount.required = present;
            amount.disabled = !present;
            if (present && Number.isFinite(Number(amount.value))) {
                totalCents += Math.round(Number(amount.value) * 100);
            }
        });
        const option = form.elements.namedItem('operation_id').selectedOptions[0];
        const netCents = Number(option?.dataset.netCents || 0);
        form.querySelector('[data-salary-total]').textContent = `RM ${(totalCents / 100).toFixed(2)}`;
        form.querySelector('[data-salary-rate]').textContent = netCents > 0
            ? `${(Math.round(totalCents * 10000 / netCents) / 100).toFixed(2)}%` : '—';
    };
    form.addEventListener('input', sync);
    form.addEventListener('change', sync);
    sync();
});
