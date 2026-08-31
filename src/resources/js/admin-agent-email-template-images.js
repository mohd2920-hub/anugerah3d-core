document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-email-template-image-picker]').forEach((picker) => {
        const input = picker.querySelector('[data-email-template-image-input]');
        const preview = picker.querySelector('[data-email-template-image-preview]');
        const countLabel = picker.querySelector('[data-email-template-image-count]');
        const error = picker.querySelector('[data-email-template-image-error]');
        const removeInputs = Array.from(picker.querySelectorAll('[data-remove-template-image]'));
        const maximumImages = Number(picker.dataset.maxImages || 4);
        let previewUrls = [];

        if (!input || !preview || !countLabel || !error) {
            return;
        }

        const remainingExistingCount = () => removeInputs.filter((checkbox) => !checkbox.checked).length;
        const selectedFiles = () => Array.from(input.files || []);

        const updateCount = () => {
            const total = remainingExistingCount() + selectedFiles().length;
            countLabel.textContent = `${total} / ${maximumImages} images`;
            countLabel.classList.toggle('bg-red-50', total > maximumImages);
            countLabel.classList.toggle('text-red-700', total > maximumImages);
            countLabel.classList.toggle('bg-slate-100', total <= maximumImages);
            countLabel.classList.toggle('text-slate-600', total <= maximumImages);
        };

        const clearPreviewUrls = () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
        };

        const renderPreviews = () => {
            clearPreviewUrls();
            preview.innerHTML = '';

            selectedFiles().forEach((file) => {
                const url = URL.createObjectURL(file);
                const card = document.createElement('div');
                const image = document.createElement('img');
                const name = document.createElement('p');

                previewUrls.push(url);
                card.className = 'overflow-hidden rounded-xl border border-blue-200 bg-blue-50';
                image.src = url;
                image.alt = file.name;
                image.className = 'aspect-square w-full object-contain';
                name.className = 'truncate border-t border-blue-100 bg-white px-2 py-2 text-xs text-slate-600';
                name.textContent = file.name;
                card.append(image, name);
                preview.append(card);
            });

            preview.classList.toggle('hidden', selectedFiles().length === 0);
            preview.classList.toggle('grid', selectedFiles().length > 0);
            updateCount();
        };

        const clearError = () => {
            error.textContent = '';
            error.classList.add('hidden');
        };

        input.addEventListener('change', () => {
            clearError();

            if (remainingExistingCount() + selectedFiles().length > maximumImages) {
                const availableSlots = Math.max(0, maximumImages - remainingExistingCount());
                input.value = '';
                error.textContent = `You can add ${availableSlots} more image${availableSlots === 1 ? '' : 's'}. Remove a saved image first if needed.`;
                error.classList.remove('hidden');
            }

            renderPreviews();
        });

        removeInputs.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                clearError();
                updateCount();
            });
        });

        window.addEventListener('beforeunload', clearPreviewUrls);
        updateCount();
    });
});
