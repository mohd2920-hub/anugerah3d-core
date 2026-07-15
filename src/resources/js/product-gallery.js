const initializeProductGallery = () => {
    const track = document.querySelector('[data-product-gallery]');
    const placeholder = document.querySelector('[data-product-gallery-placeholder]');
    const dots = document.querySelector('[data-product-gallery-dots]');
    const counter = document.querySelector('[data-gallery-counter]');
    const previous = document.querySelector('[data-gallery-previous]');
    const next = document.querySelector('[data-gallery-next]');

    if (!track || !placeholder || !dots || !counter || !previous || !next) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let images = [];
    let currentIndex = 0;
    let autoplayTimer;
    let scrollFrame;

    const stopAutoplay = () => window.clearInterval(autoplayTimer);

    const updateControls = () => {
        const hasMultipleImages = images.length > 1;
        previous.classList.toggle('hidden', !hasMultipleImages);
        previous.classList.toggle('grid', hasMultipleImages);
        next.classList.toggle('hidden', !hasMultipleImages);
        next.classList.toggle('grid', hasMultipleImages);
        counter.classList.toggle('hidden', images.length === 0);
        counter.textContent = images.length ? `${currentIndex + 1} / ${images.length}` : '';

        dots.querySelectorAll('button').forEach((dot, index) => {
            const isActive = index === currentIndex;
            dot.classList.toggle('w-5', isActive);
            dot.classList.toggle('bg-[#e7682b]', isActive);
            dot.classList.toggle('w-2', !isActive);
            dot.classList.toggle('bg-slate-300', !isActive);
            dot.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    };

    const goTo = (index, behavior = 'smooth') => {
        if (!images.length) {
            return;
        }

        currentIndex = (index + images.length) % images.length;
        track.scrollTo({
            left: currentIndex * track.clientWidth,
            behavior,
        });
        updateControls();
    };

    const startAutoplay = () => {
        stopAutoplay();

        if (images.length < 2 || reduceMotion.matches) {
            return;
        }

        autoplayTimer = window.setInterval(() => goTo(currentIndex + 1), 4000);
    };

    const restartAutoplay = () => {
        stopAutoplay();
        window.setTimeout(startAutoplay, 1500);
    };

    const render = (event) => {
        images = Array.isArray(event.detail?.images) ? event.detail.images.slice(0, 5) : [];
        const productName = event.detail?.productName || 'Product';
        currentIndex = 0;
        track.innerHTML = '';
        dots.innerHTML = '';

        placeholder.classList.toggle('hidden', images.length > 0);
        placeholder.classList.toggle('grid', images.length === 0);

        images.forEach((image, index) => {
            const slide = document.createElement('div');
            slide.className = 'h-full w-full flex-none snap-center';

            const picture = document.createElement('img');
            picture.src = image.src;
            picture.alt = image.alt || `${productName} picture ${index + 1}`;
            picture.className = 'h-full w-full object-cover';
            picture.loading = index === 0 ? 'eager' : 'lazy';
            slide.append(picture);
            track.append(slide);

            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'h-2 rounded-full transition-all';
            dot.setAttribute('aria-label', `View picture ${index + 1}`);
            dot.addEventListener('click', () => {
                goTo(index);
                restartAutoplay();
            });
            dots.append(dot);
        });

        track.scrollLeft = 0;
        updateControls();
        startAutoplay();
    };

    previous.addEventListener('click', () => {
        goTo(currentIndex - 1);
        restartAutoplay();
    });

    next.addEventListener('click', () => {
        goTo(currentIndex + 1);
        restartAutoplay();
    });

    track.addEventListener('touchstart', stopAutoplay, {passive: true});
    track.addEventListener('touchend', restartAutoplay, {passive: true});
    track.addEventListener('scroll', () => {
        window.cancelAnimationFrame(scrollFrame);
        scrollFrame = window.requestAnimationFrame(() => {
            const nextIndex = Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
            if (nextIndex !== currentIndex && nextIndex < images.length) {
                currentIndex = nextIndex;
                updateControls();
            }
        });
    }, {passive: true});

    reduceMotion.addEventListener('change', startAutoplay);
    window.addEventListener('product-gallery:open', render);
    window.addEventListener('product-gallery:close', stopAutoplay);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProductGallery);
} else {
    initializeProductGallery();
}
