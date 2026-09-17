const salesPage = document.querySelector('[data-sales-page]');
const filterModal = salesPage?.querySelector('[data-sales-filter-modal]');
const openFilterButton = salesPage?.querySelector('[data-open-sales-filters]');
const filterSearchInput = filterModal?.querySelector('input[name="search"]');
let previousFocus = null;

const closeFilterModal = () => {
    filterModal?.classList.add('hidden');
    filterModal?.classList.remove('flex');
    filterModal?.setAttribute('aria-hidden', 'true');
    openFilterButton?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');

    if (previousFocus instanceof HTMLElement) {
        previousFocus.focus();
    }
};

const openFilterModal = () => {
    if (!filterModal) {
        return;
    }

    previousFocus = document.activeElement;
    filterModal.classList.remove('hidden');
    filterModal.classList.add('flex');
    filterModal.setAttribute('aria-hidden', 'false');
    openFilterButton?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
    filterSearchInput?.focus();
};

openFilterButton?.addEventListener('click', openFilterModal);
filterModal?.querySelectorAll('[data-close-sales-filters]').forEach((button) => {
    button.addEventListener('click', closeFilterModal);
});

document.addEventListener('keydown', (event) => {
    if (!filterModal || filterModal.classList.contains('hidden')) {
        return;
    }

    if (event.key === 'Escape') {
        closeFilterModal();

        return;
    }

    if (event.key === 'Tab') {
        const focusableElements = [...filterModal.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled])',
        )].filter((element) => element instanceof HTMLElement && element.tabIndex >= 0);
        const firstElement = focusableElements[0];
        const lastElement = focusableElements.at(-1);

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement?.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement?.focus();
        }
    }
});

if (filterModal?.dataset.openOnLoad === 'true') {
    openFilterModal();
}


document.querySelectorAll('[data-sales-detail-row]').forEach((row) => {
    row.addEventListener('click', (event) => {
        if (event.target.closest('a, button, input, select, textarea') || window.getSelection()?.toString()) {
            return;
        }
        row.querySelector('[data-sales-detail-link]')?.click();
    });
});


document.querySelectorAll('[data-sales-page] [aria-label="Sales period"], [data-sales-page] .sales-summary-strip, [data-horizontal-scroll-controls]').forEach((scroller, index) => {
    const shell = document.createElement('div');
    shell.className = 'sales-scroll-shell';
    scroller.before(shell);
    shell.append(scroller);
    scroller.id ||= `sales-scroll-${index}`;
    const buttons = [-1, 1].map((direction) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `sales-scroll-arrow ${direction < 0 ? 'is-left' : 'is-right'}`;
        button.textContent = direction < 0 ? '‹' : '›';
        button.setAttribute('aria-label', direction < 0 ? 'Tatal ke kiri' : 'Tatal ke kanan');
        button.setAttribute('aria-controls', scroller.id);
        button.hidden = true;
        button.addEventListener('click', () => scroller.scrollBy({
            left: direction * Math.max(175, scroller.clientWidth * 0.75),
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        }));
        shell.append(button);
        return button;
    });
    const updateArrows = () => {
        const overflow = scroller.scrollWidth > scroller.clientWidth + 2;
        buttons[0].hidden = !overflow;
        buttons[1].hidden = !overflow;
        buttons[0].disabled = scroller.scrollLeft <= 2;
        buttons[1].disabled = scroller.scrollLeft + scroller.clientWidth >= scroller.scrollWidth - 2;
    };
    scroller.addEventListener('scroll', updateArrows, { passive: true });
    const observer = new ResizeObserver(updateArrows);
    observer.observe(scroller);
    if (scroller.firstElementChild) observer.observe(scroller.firstElementChild);
    document.fonts?.ready.then(updateArrows);
    updateArrows();
});


const salesBackLink = document.querySelector('[data-sales-back]');
const salesNavigationKey = 'sales-return:';
const readSalesReturn = (key) => {
    try { return JSON.parse(sessionStorage.getItem(key) || 'null'); }
    catch { return null; }
};
if (salesPage) {
    salesPage.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;
        const destination = new URL(link.href, location.href);
        if (destination.origin !== location.origin || !/\/sales\/\d+$/.test(destination.pathname)) return;
        try {
            sessionStorage.setItem(salesNavigationKey + destination.pathname, JSON.stringify({url: location.href, scroll: window.scrollY}));
        } catch {}
    });
    const restoreKey = 'sales-restore:' + location.pathname + location.search;
    const saved = readSalesReturn(restoreKey);
    if (saved && Number.isFinite(saved.scroll)) {
        try { sessionStorage.removeItem(restoreKey); } catch {}
        const restore = () => window.scrollTo({top: saved.scroll, behavior: 'instant'});
        if (document.readyState === 'complete') requestAnimationFrame(restore);
        else window.addEventListener('load', restore, {once: true});
    }
}
if (salesBackLink) {
    const key = salesNavigationKey + location.pathname;
    const saved = readSalesReturn(key);
    try {
        const source = document.referrer ? new URL(document.referrer) : null;
        const destination = saved ? new URL(saved.url) : null;
        const base = new URL(salesBackLink.href);
        if (source?.origin === location.origin && destination?.origin === location.origin && [base.pathname, base.pathname + '/transactions'].includes(destination.pathname)) {
            salesBackLink.href = destination.href;
            salesBackLink.addEventListener('click', () => {
                try { sessionStorage.setItem('sales-restore:' + destination.pathname + destination.search, JSON.stringify({scroll: saved.scroll})); } catch {}
            });
        } else {
            sessionStorage.removeItem(key);
        }
    } catch {}
}
