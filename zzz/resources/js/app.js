import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();


/*
|--------------------------------------------------------------------------
| Cart UX
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', () => {
    const setBadge = (id, count) => {
        let badge = document.getElementById(id);
        if (!badge) return;
        const n = Number(count) || 0;
        badge.textContent = n > 99 ? '99+' : String(n);
        badge.classList.toggle('hidden', n < 1);
        badge.classList.remove('pulse');
        if (n > 0) {
            void badge.offsetWidth;
            badge.classList.add('pulse');
        }
    };

    const updateCartBadge = (count) => {
        setBadge('cart-count-badge', count);
        setBadge('guest-cart-count-badge', count);
        setBadge('cart-menu-count', count);
        const badge = document.getElementById('cart-count-badge');
        if (badge) badge.setAttribute('aria-label', `${Number(count) || 0} کالا در سبد خرید`);
    };

    document.querySelectorAll('form[action*="/cart/add/"]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"], button:not([type])');
            const originalText = button?.innerHTML;
            if (button) { button.disabled = true; button.innerHTML = 'در حال افزودن...'; }
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'افزودن محصول به سبد خرید انجام نشد.');
                updateCartBadge(data.cart_count);
                if (button) {
                    button.innerHTML = '✓ اضافه شد';
                    setTimeout(() => { button.innerHTML = originalText; }, 1200);
                }
            } catch (error) {
                console.error('Cart error:', error);
                HTMLFormElement.prototype.submit.call(form);
                return;
            } finally {
                if (button) setTimeout(() => { button.disabled = false; }, 1200);
            }
        });
    });
});
