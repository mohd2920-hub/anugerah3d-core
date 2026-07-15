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
    let previewUrls = [];

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
        count.textContent = `${activeExistingCards().length + input.files.length} of 5`;
    };

    const clearPreviewUrls = () => {
        previewUrls.forEach((url) => URL.revokeObjectURL(url));
        previewUrls = [];
    };

    const renderPreviews = () => {
        clearPreviewUrls();
        previews.innerHTML = '';
        const files = [...input.files];
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
            radio.addEventListener('change', updateMainStyles);

            label.append(radio, document.createTextNode(' Main picture'));
            frame.append(image, badge);
            card.append(frame, label);
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

            if (!checkbox.checked && activeExistingCards().length + input.files.length > 5) {
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
        const availableSlots = 5 - activeExistingCards().length;

        if (input.files.length > availableSlots) {
            input.value = '';
            error.textContent = `You can add only ${availableSlots} more picture${availableSlots === 1 ? '' : 's'}.`;
            error.classList.remove('hidden');
        } else {
            error.textContent = '';
            error.classList.add('hidden');
        }

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
