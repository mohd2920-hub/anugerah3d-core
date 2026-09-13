const initializeProductImageManager = () => {
    const manager = document.querySelector('[data-product-image-manager]');

    if (!manager) {
        return;
    }

    const input = manager.querySelector('[data-product-image-input]');
    const previews = manager.querySelector('[data-new-image-previews]');
    const previewSection = manager.querySelector('[data-new-images-section]');
    const count = manager.querySelector('[data-image-count]');
    const error = manager.querySelector('[data-image-error]');
    const maxImages = Number(manager.dataset.maxImages);
    let selectedFiles = [...input.files];
    let previewUrls = [];

    const syncFiles = () => {
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
    };

    const activeExistingCards = () => [...manager.querySelectorAll('[data-existing-image-card]')]
        .filter((card) => !card.querySelector('[data-remove-existing]').checked);

    const updateMainStyles = () => {
        const selected = manager.querySelector('[data-main-image]:checked');

        manager.querySelectorAll('[data-existing-image-card], [data-new-image-card]').forEach((card) => {
            const radio = card.querySelector('[data-main-image]');
            const isMain = selected && radio === selected && !radio.disabled;
            card.classList.toggle('border-[#1a73e8]', Boolean(isMain));
            card.classList.toggle('ring-2', Boolean(isMain));
            card.classList.toggle('ring-blue-100', Boolean(isMain));
            card.classList.toggle('border-slate-200', !isMain);
            card.querySelector('[data-main-badge]')?.classList.toggle('hidden', !isMain);
        });
    };

    const chooseFirstAvailableMain = () => {
        const selected = manager.querySelector('[data-main-image]:checked');

        if (selected && !selected.disabled) {
            return;
        }

        const firstAvailable = [...manager.querySelectorAll('[data-main-image]')]
            .find((radio) => !radio.disabled);

        if (firstAvailable) {
            firstAvailable.checked = true;
        }
    };

    const updateCount = () => {
        count.textContent = `${activeExistingCards().length + selectedFiles.length} of ${maxImages}`;
    };

    const clearPreviewUrls = () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
    };

    const renderPreviews = (mainFile = null) => {
        const selected = manager.querySelector('[data-main-image]:checked');
        const mainValue = selected?.value;
        clearPreviewUrls();
        previews.innerHTML = '';
        const files = selectedFiles;
        previewSection.classList.toggle('hidden', files.length === 0);

        files.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            previewUrls.push(url);

            const card = document.createElement('article');
            card.dataset.newImageCard = '';
            card.className = 'relative overflow-hidden rounded-xl border border-slate-200 bg-white p-2 transition';

            const frame = document.createElement('div');
            frame.className = 'relative aspect-square overflow-hidden rounded-lg bg-slate-100';

            const image = document.createElement('img');
            image.src = url;
            image.alt = file.name;
            image.className = 'h-full w-full object-cover';

            const badge = document.createElement('span');
            badge.dataset.mainBadge = '';
            badge.className = 'absolute left-2 top-2 hidden rounded-full bg-[#1a73e8] px-2 py-1 text-[10px] font-bold text-white shadow';
            badge.textContent = 'Main';

            const label = document.createElement('label');
            label.className = 'mt-2 flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'main_image';
            radio.value = `new-${index}`;
            radio.dataset.mainImage = '';
            radio.className = 'h-4 w-4 border-slate-300 text-[#1a73e8] focus:ring-[#1a73e8]';
            radio.checked = mainFile ? file === mainFile : radio.value === mainValue;
            radio.addEventListener('change', updateMainStyles);

            label.append(radio, document.createTextNode(' Main picture'));
            frame.append(image, badge);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'mt-2 inline-flex text-xs font-semibold text-red-600';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.addEventListener('click', () => {
                const selected = manager.querySelector('[data-main-image]:checked');
                const selectedIndex = selected?.value.startsWith('new-') ? Number(selected.value.slice(4)) : -1;
                const mainFile = selectedFiles[selectedIndex];
                if (selectedIndex === index) {
                    selected.checked = false;
                }
                selectedFiles.splice(index, 1);
                syncFiles();
                error.classList.add('hidden');
                renderPreviews(mainFile);
            });
            card.append(frame, label, remove);
            previews.append(card);
        });

        chooseFirstAvailableMain();
        updateMainStyles();
        updateCount();
    };

    manager.querySelectorAll('[data-remove-existing]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const card = checkbox.closest('[data-existing-image-card]');
            const radio = card.querySelector('[data-main-image]');
            const label = card.querySelector('[data-remove-label]');

            if (!checkbox.checked && activeExistingCards().length + selectedFiles.length > maxImages) {
                checkbox.checked = true;
                error.textContent = 'Remove another picture before restoring this one.';
                error.classList.remove('hidden');

                return;
            }

            error.textContent = '';
            error.classList.add('hidden');
            card.classList.toggle('opacity-40', checkbox.checked);
            radio.disabled = checkbox.checked;
            label.textContent = checkbox.checked ? 'Undo removal' : 'Remove';

            chooseFirstAvailableMain();
            updateMainStyles();
            updateCount();
        });
    });

    manager.querySelectorAll('[data-main-image]').forEach((radio) => {
        radio.addEventListener('change', updateMainStyles);
    });

    input.addEventListener('change', () => {
        const availableSlots = maxImages - activeExistingCards().length - selectedFiles.length;

        if (input.files.length > availableSlots) {
            error.textContent = `You can add only ${availableSlots} more picture${availableSlots === 1 ? '' : 's'}.`;
            error.classList.remove('hidden');
        } else {
            selectedFiles.push(...input.files);
            error.textContent = '';
            error.classList.add('hidden');
        }

        syncFiles();
        renderPreviews();
    });

    chooseFirstAvailableMain();
    updateMainStyles();
    updateCount();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProductImageManager);
} else {
    initializeProductImageManager();
}
document.querySelectorAll('[data-clicker-slot]').forEach((slot) => {
    const name = slot.querySelector('input[type="text"]');
    const file = slot.querySelector('input[type="file"]');
    const remove = slot.querySelector('[data-slot-remove]');
    const clear = slot.querySelector('[data-slot-clear]');
    const undo = slot.querySelector('[data-slot-undo]');
    const preview = slot.querySelector('[data-slot-preview]');
    const empty = slot.querySelector('[data-slot-empty]');
    const status = slot.querySelector('[data-slot-status]');
    const stocks = [...slot.querySelectorAll('[data-casing-size]')];
    let snapshot;

    const markRemoved = (restoring = false) => {
        if (!restoring && stocks.some(input => Number(input.value) !== 0)) {
            status.textContent = 'Casing masih mempunyai stok. Selaraskan stok dan simpan dahulu.';
            return;
        }
        snapshot = {name: restoring ? slot.dataset.savedName : name.value, required: name.required, stocks: stocks.map(input => input.value)};
        name.value = '';
        name.required = false;
        name.readOnly = true;
        file.disabled = true;
        stocks.forEach(input => { input.value = '0'; input.readOnly = true; });
        remove.value = preview ? '1' : '0';
        if (preview) preview.hidden = true;
        empty.hidden = false;
        clear.hidden = true;
        undo.hidden = false;
        status.textContent = 'Slot dikosongkan. Belum disimpan.';
        slot.dispatchEvent(new Event('input', {bubbles: true}));
    };
    clear.addEventListener('click', () => markRemoved());
    undo.addEventListener('click', () => {
        if (!snapshot) return;
        name.value = snapshot.name;
        name.required = snapshot.required;
        name.readOnly = false;
        file.disabled = false;
        stocks.forEach((input, index) => { input.value = snapshot.stocks[index]; input.readOnly = false; });
        remove.value = '0';
        if (preview) preview.hidden = false;
        empty.hidden = Boolean(preview);
        clear.hidden = false;
        undo.hidden = true;
        status.textContent = '';
        slot.dispatchEvent(new Event('input', {bubbles: true}));
    });
    if (remove.value === '1') markRemoved(true);
});
