const actionMenus = [...(document.querySelector('[data-product-index]')?.querySelectorAll('[data-action-menu]') || [])];
let activeMenu;

function closeProductActions(restoreFocus = false) {
    if (!activeMenu) return;
    const { button, panel } = activeMenu;
    if (typeof panel.hidePopover === 'function' && panel.matches(':popover-open')) panel.hidePopover();
    panel.classList.add('hidden');
    button.setAttribute('aria-expanded', 'false');
    activeMenu = null;
    if (restoreFocus) button.focus({ preventScroll: true });
}

function positionProductActions() {
    if (!activeMenu) return;
    const { button, panel } = activeMenu;
    const viewport = window.visualViewport;
    const leftEdge = viewport?.offsetLeft || 0;
    const topEdge = viewport?.offsetTop || 0;
    const width = viewport?.width || window.innerWidth;
    const height = viewport?.height || window.innerHeight;
    const edge = 12;
    const gap = 8;
    const rect = button.getBoundingClientRect();
    if (rect.bottom < topEdge || rect.top > topEdge + height) {
        closeProductActions();
        return;
    }
    panel.style.maxWidth = `${Math.max(0, width - edge * 2)}px`;
    panel.style.maxHeight = `${Math.max(0, height - edge * 2)}px`;
    const bounds = panel.getBoundingClientRect();
    const below = topEdge + height - rect.bottom - gap - edge;
    const above = rect.top - topEdge - gap - edge;
    const opensUp = below < bounds.height && above > below;
    const desiredTop = opensUp ? rect.top - gap - bounds.height : rect.bottom + gap;
    panel.style.top = `${Math.max(topEdge + edge, Math.min(desiredTop, topEdge + height - bounds.height - edge))}px`;
    panel.style.left = `${Math.max(leftEdge + edge, Math.min(rect.right - bounds.width, leftEdge + width - bounds.width - edge))}px`;
    panel.dataset.placement = opensUp ? 'top' : 'bottom';
}

for (const [index, menu] of actionMenus.entries()) {
    const button = menu.querySelector('[data-action-menu-button]');
    const panel = menu.querySelector('[data-action-menu-panel]');
    if (!button || !panel) continue;
    panel.id = `product-actions-${index}`;
    button.setAttribute('aria-controls', panel.id);
    if (typeof panel.showPopover === 'function') panel.setAttribute('popover', 'manual');
    Object.assign(panel.style, { position: 'fixed', inset: 'auto', margin: '0', zIndex: '100', overflowY: 'auto' });
    button.addEventListener('click', (event) => {
        event.stopPropagation();
        const wasOpen = activeMenu?.button === button;
        closeProductActions();
        if (wasOpen) return;
        activeMenu = { button, panel };
        panel.classList.remove('hidden');
        if (typeof panel.showPopover === 'function') panel.showPopover();
        button.setAttribute('aria-expanded', 'true');
        positionProductActions();
    });
    button.addEventListener('keydown', (event) => {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        event.stopPropagation();
        if (activeMenu?.button !== button) button.click();
        const items = panel.querySelectorAll('a[href], button:not([disabled])');
        (event.key === 'ArrowUp' ? items[items.length - 1] : items[0])?.focus();
    });
}

document.addEventListener('admin:close-product-actions', () => closeProductActions());
document.addEventListener('click', (event) => {
    if (activeMenu && !activeMenu.panel.contains(event.target) && !activeMenu.button.contains(event.target)) closeProductActions();
});
document.addEventListener('keydown', (event) => {
    if (!activeMenu) return;
    if (event.key === 'Escape') { event.preventDefault(); closeProductActions(true); return; }
    const items = [...activeMenu.panel.querySelectorAll('a[href], button:not([disabled])')];
    const index = items.indexOf(document.activeElement);
    if (index < 0 || !['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
    items[next]?.focus();
});
window.addEventListener('resize', positionProductActions);
window.addEventListener('scroll', (event) => {
    if (activeMenu && !activeMenu.panel.contains(event.target)) positionProductActions();
}, true);
window.visualViewport?.addEventListener('resize', positionProductActions);
window.visualViewport?.addEventListener('scroll', positionProductActions);
