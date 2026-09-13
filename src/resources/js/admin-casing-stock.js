document.querySelectorAll('[data-clicker-product-builder][data-casing-stock]').forEach((container) => {
    const update = () => {
        let total = 0;
        const totals = {};
        container.querySelectorAll('[data-casing-stock-row]').forEach((row) => {
            row.querySelectorAll('[data-casing-size]').forEach((input) => {
                const quantity = Math.max(0, Number(input.value || 0));
                total += quantity;
                const count = input.dataset.casingSize;
                totals[count] = (totals[count] || 0) + quantity;
                container.querySelectorAll(`[data-combination-stock="${row.dataset.casingStockRow}:${count}"]`)
                    .forEach((output) => { output.textContent = String(quantity); });
            });
        });
        container.querySelectorAll('[data-casing-stock-total]').forEach((output) => { output.textContent = String(total); });
        container.querySelectorAll('[data-size-stock-total]').forEach((output) => { output.textContent = String(totals[output.dataset.sizeStockTotal] || 0); });
    };
    container.addEventListener('input', update);
    update();
});
