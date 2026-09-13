const fieldClass = 'mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm';

document.querySelectorAll('[data-pos-clicker-catalog]').forEach((source) => {
    const catalog = JSON.parse(source.textContent || '{}');
    const form = source.closest('form');
    if (!form) return;
    const blocks = () => [...form.querySelectorAll('[data-pos-clicker]')];
    const productSelect = (block) => block.closest('[data-pos-item], [data-correction-item]').querySelector('[name$="[product_id]"]');
    const notify = () => form.dispatchEvent(new CustomEvent('pos:configuration-changed'));
    const update = (block) => {
        const select = productSelect(block);
        const data = catalog[select.value];
        if (!data) return;
        const casingId = block.querySelector('[data-choice="casing"]')?.value;
        const hurufId = block.querySelector('[data-choice="huruf"]')?.value;
        const count = Number(block.querySelector('[data-choice="count"]')?.value || 0);
        block.dataset.unitPrice = data.prices[count] ?? 0;
        const casing = data.images.find((image) => String(image.id) === casingId);
        const result = data.results.find((image) => String(image.casing) === casingId && String(image.huruf) === hurufId);
        const preview = block.querySelector('[data-result-image]');
        preview.hidden = !result;
        if (result) { preview.src = result.src; preview.alt = result.name || 'Selected combination'; }
        else preview.removeAttribute('src');
        block.querySelector('[data-clicker-price]').textContent = 'Harga: RM ' + Number(data.prices[count] || 0).toFixed(2);
        const stock = casing?.stock === null || casing?.stock === undefined ? null : Number(casing.stock[count] || 0);
        block.querySelector('[data-clicker-stock]').textContent = !casingId || !count ? 'Pilih casing dan bilangan huruf.'
            : stock === null ? 'Stok mengikut saiz belum diaktifkan.' : `Baki casing ${count} huruf: ${stock} unit`;
        block.querySelectorAll('[data-image-choice]').forEach((button) => {
            const active = button.dataset.imageChoice === (button.dataset.type === 'casing' ? casingId : hurufId);
            button.setAttribute('aria-pressed', String(active));
            button.classList.toggle('ring-2', active);
            button.classList.toggle('ring-blue-500', active);
        });
        notify();
    };
    const mount = (block, force = false) => {
        const select = productSelect(block);
        const id = select.value;
        if (!force && block.dataset.product === id) return;
        const initial = !block.dataset.product ? JSON.parse(block.dataset.initial || '{}') : {};
        block.dataset.product = id;
        block.replaceChildren();
        delete block.dataset.unitPrice;
        const data = catalog[id];
        block.classList.toggle('hidden', !data);
        if (!data) { notify(); return; }
        const prefix = select.name.replace('[product_id]', '');
        const addSelect = (title, key, name, choices, value) => {
            const label = document.createElement('label');
            label.className = 'block text-xs font-semibold text-slate-600';
            label.textContent = title;
            const input = document.createElement('select');
            input.className = fieldClass; input.required = true; input.dataset.choice = key; input.name = `${prefix}[${name}]`;
            input.add(new Option('Pilih ' + title, ''));
            choices.forEach(([key, text]) => input.add(new Option(text, key)));
            input.value = String(value || '');
            label.append(input); block.append(label);
            return input;
        };
        ['casing', 'huruf'].forEach((type) => {
            const images = data.images.filter((image) => image.type === type);
            const input = addSelect(type === 'casing' ? 'Casing' : 'Huruf', type, `clicker_${type}_image_id`, images.map((image) => [image.id, image.name || type]), initial[`clicker_${type}_image_id`]);
            const gallery = document.createElement('div'); gallery.className = 'flex gap-2 overflow-x-auto py-2';
            images.forEach((image) => {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'w-16 shrink-0 rounded-lg border border-slate-200 bg-white p-1';
                button.dataset.imageChoice = String(image.id); button.dataset.type = type; button.setAttribute('aria-label', image.name || type);
                const picture = document.createElement('img'); picture.src = image.src; picture.alt = image.name || type; picture.className = 'h-14 w-full rounded object-contain';
                const caption = document.createElement('span'); caption.textContent = image.name || type; caption.className = 'block truncate text-[10px]';
                button.append(picture, caption); button.addEventListener('click', () => { input.value = String(image.id); update(block); }); gallery.append(button);
            });
            block.append(gallery); input.addEventListener('change', () => update(block));
        });
        const count = addSelect('Bilangan huruf', 'count', 'clicker_character_count', Array.from({length: 8}, (_, index) => [index + 1, `${index + 1} huruf`]), initial.clicker_character_count);
        const characters = document.createElement('div'); characters.className = 'grid grid-cols-4 gap-2 lg:grid-cols-8'; block.append(characters);
        const drawCharacters = (values = []) => {
            characters.replaceChildren();
            for (let index = 0; index < Number(count.value); index++) {
                const input = document.createElement('input'); input.type = 'text'; input.maxLength = 1; input.required = true; input.className = fieldClass + ' text-center uppercase';
                input.name = `${productSelect(block).name.replace('[product_id]', '')}[clicker_characters][${index}]`; input.value = values[index] || ''; input.setAttribute('aria-label', `Huruf ${index + 1}`); characters.append(input);
            }
        };
        drawCharacters(initial.clicker_characters || []);
        count.addEventListener('change', () => { const values = [...characters.querySelectorAll('input')].map((input) => input.value); drawCharacters(values); update(block); });
        const preview = document.createElement('img'); preview.dataset.resultImage = ''; preview.className = 'mt-2 max-h-56 w-full rounded-xl bg-slate-50 object-contain'; preview.hidden = true;
        const price = document.createElement('p'); price.dataset.clickerPrice = ''; price.className = 'text-sm font-semibold text-blue-700';
        const stock = document.createElement('p'); stock.dataset.clickerStock = ''; stock.className = 'text-xs text-slate-600'; block.append(preview, price, stock);
        update(block);
    };
    blocks().forEach((block) => mount(block));
    form.addEventListener('change', (event) => { if (event.target.matches('[name$="[product_id]"]')) blocks().forEach((block) => mount(block)); });
    form.addEventListener('pos:product-changed', () => blocks().forEach((block) => mount(block)));
    new MutationObserver(() => blocks().forEach((block) => mount(block))).observe(form, {childList: true, subtree: true});
});
