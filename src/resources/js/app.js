import './admin-product-balance';
import './admin-product-actions';
import './admin-product-discontinuation';
import './admin-product-images';
import './admin-agent-email-template-images';
import './product-gallery';
import './weekly-closing-payment';
import './business-site-operations';
import './business-site-reports';
import './admin-sales-filters';

import './sale-corrections';

import './admin-access';

import './admin-casing-stock';

import './pos-clicker';

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-customer-link]');
    if (!button) return;
    const input = document.querySelector('[data-customer-share-link]');
    try { await navigator.clipboard.writeText(input.value); button.textContent = 'Link disalin'; }
    catch { input.select(); button.textContent = 'Pilih dan salin link di atas'; }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-customer-back]');
    if (button && window.history.length > 1) {
        event.preventDefault();
        window.history.back();
    }
});

document.addEventListener('click', async (event) => {
    const copy = event.target.closest('[data-copy-order-link]');
    if (copy) {
        try { await navigator.clipboard.writeText(copy.dataset.copyOrderLink); copy.textContent = 'Link disalin'; }
        catch { window.prompt('Salin link pesanan:', copy.dataset.copyOrderLink); }
    }
    if (event.target.closest('[data-print-customer-receipt]')) window.print();
});

if (document.getElementById('business-dashboard')) {
    import('./admin-dashboard').catch((error) => {
        const status = document.getElementById('dashboard-status');
        if (status) {
            status.textContent = 'Graf tidak dapat dimuatkan. Muat semula halaman untuk mencuba lagi.';
        }
        console.error('Dashboard assets could not be loaded.', error);
    });
}

import './admin-order-discounts';
