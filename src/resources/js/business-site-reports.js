document.querySelectorAll('[data-site-report-filter]').forEach((form) => {
    const period = form.querySelector('[data-site-period]');
    const updateDateFields = () => {
        form.querySelectorAll('[data-site-date-field]').forEach((field) => {
            const active = field.dataset.siteDateField === period.value;
            field.hidden = !active;
            field.querySelectorAll('input').forEach((input) => {
                input.disabled = !active;
                input.required = active;
            });
        });
    };
    period.addEventListener('change', updateDateFields);
    updateDateFields();

    const selectAll = form.querySelector('[data-select-all-sites]');
    if (selectAll) {
        const locations = [...form.querySelectorAll('input[name="site_ids[]"]')];
        const updateSelectionButton = () => {
            selectAll.disabled = locations.length === 0;
            selectAll.textContent = locations.length > 0 && locations.every((input) => input.checked)
                ? 'Nyahpilih Semua' : 'Pilih Semua';
        };
        selectAll.hidden = false;
        selectAll.addEventListener('click', () => {
            const checked = !locations.every((input) => input.checked);
            locations.forEach((input) => { input.checked = checked; });
            updateSelectionButton();
        });
        locations.forEach((input) => input.addEventListener('change', updateSelectionButton));
        updateSelectionButton();
    }
});
