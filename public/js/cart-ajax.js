(function() {
    if (window.__cartAjaxLoaded) return;
    window.__cartAjaxLoaded = true;

    function updateGlobalCartCount(total) {
        const count = parseInt(total, 10) || 0;
        document.querySelectorAll('#globalCartCount, [data-cart-count]').forEach((badge) => {
            badge.textContent = count;
            badge.classList.toggle('hidden', count <= 0);
            badge.style.transform = 'scale(1.3)';
            setTimeout(() => {
                badge.style.transform = 'scale(1)';
            }, 180);
        });
    }

    function showCartSuccessToast() {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: 'Đã thêm vào giỏ hàng thành công',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true
            });
            return;
        }

        alert('Đã thêm vào giỏ hàng thành công');
    }

    async function refreshCartDrawer() {
        try {
            const res = await fetch('api_cart_drawer.php', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (!data.success) return;

            const toolbarEl = document.getElementById('cartDrawerToolbar');
            const itemsEl   = document.getElementById('cartDrawerItems');
            const footerEl  = document.getElementById('cartDrawerFooter');

            if (toolbarEl) toolbarEl.innerHTML = data.toolbar_html || '';
            if (itemsEl)   itemsEl.innerHTML   = data.items_html  || '';
            if (footerEl)  footerEl.innerHTML  = data.footer_html || '';

            updateGlobalCartCount(data.cart_count);

            // Tính lại tổng tiền theo checkbox
            if (typeof window.updateCartSummary === 'function') {
                window.updateCartSummary();
            }
        } catch (e) {
            console.warn('refreshCartDrawer error', e);
        }
    }

    async function addCartByUrl(rawUrl, trigger) {
        const url = new URL(rawUrl, window.location.href);
        if (url.searchParams.get('action') === 'buynow') {
            window.location.href = url.toString();
            return;
        }

        url.searchParams.set('ajax', '1');

        if (trigger) {
            trigger.dataset.loading = '1';
            trigger.classList.add('opacity-70', 'pointer-events-none');
        }

        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!data || !data.success) {
                if (data && data.message) {
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Không thể thêm vào giỏ', text: data.message });
                    } else {
                        alert(data.message);
                    }
                } else {
                    throw new Error('Add cart failed');
                }
                return;
            }

            // Cập nhật badge
            updateGlobalCartCount(data.cart_count);
            showCartSuccessToast();

            // Làm mới nội dung drawer từ API chuyên dụng
            await refreshCartDrawer();

            // Tự động mở drawer nếu đang đóng
            const drawer = document.getElementById('cartDrawer');
            if (drawer && drawer.classList.contains('translate-x-full') && typeof toggleCart === 'function') {
                toggleCart();
            }

            window.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
            return data;
        } finally {
            if (trigger) {
                delete trigger.dataset.loading;
                trigger.classList.remove('opacity-70', 'pointer-events-none');
            }
        }
    }

    window.updateGlobalCartCount = updateGlobalCartCount;
    window.addCartByUrl = addCartByUrl;
    window.refreshCartDrawer = refreshCartDrawer;

    document.addEventListener('click', function(event) {
        const trigger = event.target.closest('[data-add-to-cart], a[href*="xuly_giohang.php?id="]');
        if (!trigger || trigger.dataset.loading === '1') return;

        const rawUrl = trigger.dataset.cartUrl || trigger.dataset.href || trigger.getAttribute('href');
        if (!rawUrl || rawUrl === '#') return;

        const url = new URL(rawUrl, window.location.href);
        if (url.searchParams.get('action') === 'buynow') return;

        event.preventDefault();
        addCartByUrl(rawUrl, trigger).catch((error) => {
            console.error('Lỗi thêm giỏ hàng:', error);
            alert('Đã xảy ra lỗi khi thêm vào giỏ hàng. Vui lòng thử lại!');
        });
    });
})();
