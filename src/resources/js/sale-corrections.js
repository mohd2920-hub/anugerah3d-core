document.querySelectorAll('[data-sale-correction]').forEach((form) => {
    const items = form.querySelector('[data-correction-items]');
    let nextIndex = Math.max(-1, ...Array.from(items.querySelectorAll('select')).map((select) => Number(select.name.match(/items\[(\d+)\]/)?.[1] ?? 0))) + 1;
    form.querySelector('[data-add-correction-item]').addEventListener('click', () => {
        if (items.children.length >= 50) return;
        items.insertAdjacentHTML('beforeend', form.querySelector('[data-correction-template]').innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
    });
    items.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-correction-item]');
        if (button && items.children.length > 1) button.closest('[data-correction-item]').remove();
    });
});
