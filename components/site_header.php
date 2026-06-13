<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../database.php';
$siteHeaderDb = isset($db) && $db instanceof Database ? $db : new Database();

// Đồng bộ giỏ hàng từ cookie
if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $cookieCart = json_decode($_COOKIE['shopping_cart'], true);
    if (is_array($cookieCart)) $_SESSION['cart'] = $cookieCart;
}

// Tính giỏ hàng
$total_items = 0;
$cart_subtotal = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $qty = (int)($item['soluong'] ?? 1);
        $price = (float)($item['gia'] ?? 0);
        $total_items += $qty;
        $cart_subtotal += $price * $qty;
    }
}

// Đếm wishlist
$wishlist_count = 0;
if (!empty($_SESSION['user_id'])) {
    $wishlist_user_id = (int)$_SESSION['user_id'];
    $wishlist_res = $siteHeaderDb->select("SELECT COUNT(*) as total FROM wishlist WHERE IdNguoiDung = $wishlist_user_id");
    if ($wishlist_res && $wishlist_row = $wishlist_res->fetch_assoc()) {
        $wishlist_count = (int)$wishlist_row['total'];
    }
}

// Avatar
$ten_user = $_SESSION['user_name'] ?? 'Khách';
$avatar_link = 'https://ui-avatars.com/api/?name=' . urlencode($ten_user) . '&background=0D8ABC&color=fff&size=128';
if (!empty($_SESSION['user_avatar']) && $_SESSION['user_avatar'] !== 'default.png') {
    $avatar_link = 'public/images/' . $_SESSION['user_avatar'];
}
?>
<!-- Header -->
<div class="site-header bg-[#f3f3f3] border-b border-gray-200 sticky top-0 z-50">
    <div class="container mx-auto px-6 h-12 flex items-center justify-between text-[13px]">
        <div class="flex items-center gap-8">
            <button class="lg:hidden" type="button"><i class="fas fa-bars"></i></button>
            <nav class="hidden lg:flex items-center gap-8">
                <a href="index.php" class="nav-link">Trang Chủ</a>
                <a href="categories.php" class="nav-link">Tất Cả Sản Phẩm</a>
                <a href="magiamgia.php" class="nav-link text-red-600">Khuyến Mãi Mới</a>
                <a href="#" class="nav-link" id="nav-hotro-btn" onclick="openSupportChat(event)">Hỗ Trợ</a>
            </nav>
        </div>

        <a href="index.php" class="lux-title text-4xl font-bold tracking-wide">THP</a>

        <div class="flex items-center gap-5">
            <form action="search.php" method="GET" class="flex items-center">
                <div class="relative">
                    <input type="text" name="keyword" placeholder="Tìm kiếm..."
                        class="w-40 sm:w-56 md:w-72 px-4 py-2 border border-gray-200 rounded-full text-sm outline-none focus:border-blue-500 bg-white">
                    <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <a href="favourites_items.php" class="relative p-2 text-gray-700 hover:text-blue-600 transition" title="Sản phẩm yêu thích">
                <i class="far fa-heart text-xl"></i>
                <span id="globalWishlistCount"
                    class="<?php echo ($wishlist_count > 0) ? '' : 'hidden'; ?> absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
                    <?php echo $wishlist_count; ?>
                </span>
            </a>

            <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="relative" data-user-dropdown>
                <div class="flex items-center gap-2 cursor-pointer whitespace-nowrap" data-user-dropdown-toggle>
                    <span class="hidden sm:inline text-[13px] text-gray-600">Xin chào,</span>
                    <img src="<?php echo htmlspecialchars($avatar_link); ?>" alt="Avatar"
                        class="w-9 h-9 rounded-full object-cover border border-gray-300 transition">
                    <span class="text-[13px] font-semibold text-gray-900 hidden md:inline"><?php echo htmlspecialchars($ten_user); ?></span>
                    <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                </div>
                <div class="absolute right-0 top-full mt-2 bg-white shadow-xl rounded-xl border border-gray-100 hidden min-w-[220px] z-[60]" data-user-dropdown-menu>
                    <a href="user_profile.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition flex items-center gap-3"><i class="fas fa-user text-gray-400"></i> Tài khoản</a>
                    <a href="lichsu_donhang.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition flex items-center gap-3"><i class="fas fa-clipboard-list text-gray-400"></i> Đơn hàng của tôi</a>
                    <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin_dashboard.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition flex items-center gap-3"><i class="fas fa-cog text-gray-400"></i> Quản trị website</a>
                    <?php endif; ?>
                    <a href="logout.php" class="block px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition flex items-center gap-3"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </div>
            </div>
            <?php else: ?>
            <div class="flex items-center gap-3">
                <a href="login.php" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Đăng nhập</a>
                <a href="register.php" class="text-sm font-medium bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition whitespace-nowrap">Đăng ký</a>
            </div>
            <?php endif; ?>

            <button onclick="toggleCart()" class="relative p-2 text-gray-700 hover:text-blue-600 transition">
                <i class="fas fa-shopping-bag text-xl"></i>
                <span id="globalCartCount" data-cart-count
                    class="<?php echo ($total_items > 0) ? '' : 'hidden'; ?> absolute -top-1 -right-1 bg-amber-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
                    <?php echo $total_items; ?>
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Cart Drawer -->
<div id="cartOverlay" class="fixed inset-0 bg-black/50 z-[100] hidden" onclick="toggleCart()"></div>
<div id="cartDrawer" class="fixed top-0 right-0 w-full sm:w-[420px] h-full bg-white z-[110] transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col shadow-2xl">
    <!-- Header cố định -->
    <div class="flex items-center justify-between p-5 border-b shrink-0">
        <h2 class="text-xl font-black">Giỏ hàng</h2>
        <button onclick="toggleCart()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-500 text-gray-500 flex items-center justify-center transition"><i class="fas fa-times"></i></button>
    </div>
    <!-- Toolbar (chọn tất cả / xóa tất cả) — cập nhật qua JS -->
    <div id="cartDrawerToolbar" class="shrink-0">
        <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b text-sm">
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="cartSelectAll" checked
                    class="w-4 h-4 rounded accent-blue-600 cursor-pointer"
                    onchange="window.toggleSelectAll && window.toggleSelectAll(this.checked)">
                <span class="font-semibold text-gray-700">Chọn tất cả</span>
            </label>
            <button onclick="window.clearAllCart && window.clearAllCart()"
                class="text-red-500 hover:text-red-700 font-semibold flex items-center gap-1.5 transition">
                <i class="fas fa-trash-alt text-xs"></i> Xóa tất cả
            </button>
        </div>
        <?php endif; ?>
    </div>
    <!-- Danh sách sản phẩm (cuộn) -->
    <div id="cartDrawerItems" class="flex-1 overflow-y-auto p-5 space-y-3">
        <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
        <div class="cart-item flex gap-3 pb-4 border-b border-gray-100 last:border-0" data-key="<?php echo htmlspecialchars($key); ?>" data-price="<?php echo (float)($item['gia']??0); ?>" data-qty="<?php echo (int)($item['soluong']??1); ?>">
            <div class="flex items-start pt-1 shrink-0">
                <input type="checkbox" checked class="cart-item-check w-4 h-4 rounded border-gray-300 accent-blue-600 cursor-pointer mt-1"
                    data-key="<?php echo htmlspecialchars($key); ?>"
                    onchange="window.updateCartSummary && window.updateCartSummary()">
            </div>
            <a href="chitiet.php?id=<?php echo $item['id'] ?? ''; ?>" class="shrink-0">
                <img src="public/images/<?php echo htmlspecialchars($item['hinh'] ?? 'default.png'); ?>"
                    class="w-20 h-24 object-cover rounded-xl border border-gray-100" onerror="this.src='https://via.placeholder.com/80x96'">
            </a>
            <div class="flex-1 min-w-0">
                <div class="font-bold text-sm text-gray-900 line-clamp-2 mb-1"><?php echo htmlspecialchars($item['ten'] ?? ''); ?></div>
                <div class="text-red-600 font-black text-sm"><?php echo number_format((float)($item['gia'] ?? 0)); ?>đ</div>
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center border border-gray-200 rounded-md overflow-hidden bg-white shadow-sm h-7">
                        <button onclick="window.updateCartDrawerQty && window.updateCartDrawerQty('<?php echo htmlspecialchars($key); ?>', -1)" class="w-7 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold transition border-r">-</button>
                        <span class="w-8 h-full flex items-center justify-center text-xs font-bold text-gray-700 bg-white"><?php echo (int)($item['soluong'] ?? 1); ?></span>
                        <button onclick="window.updateCartDrawerQty && window.updateCartDrawerQty('<?php echo htmlspecialchars($key); ?>', 1)" class="w-7 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold transition border-l">+</button>
                    </div>
                    <div class="text-xs text-gray-400 font-bold">→ <?php echo number_format(((float)($item['gia'] ?? 0)) * ((int)($item['soluong'] ?? 1))); ?>đ</div>
                </div>
            </div>
            <button onclick="window.removeCartItem && window.removeCartItem(this, '<?php echo htmlspecialchars($key); ?>')"
                class="shrink-0 w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-500 text-gray-400 flex items-center justify-center transition mt-0.5" title="Xóa">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400 pt-20">
            <i class="fas fa-shopping-basket text-6xl mb-5 text-gray-200"></i>
            <p class="text-sm">Giỏ hàng của bạn đang trống</p>
        </div>
        <?php endif; ?>
    </div>
    <!-- Footer -->
    <div id="cartDrawerFooter" class="shrink-0">
    <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): 
        $cart_subtotal_init = 0;
        foreach ($_SESSION['cart'] as $it) $cart_subtotal_init += (float)($it['gia']??0) * (int)($it['soluong']??1);
    ?>
    <div class="p-5 border-t bg-gray-50">
        <div class="flex justify-between items-center mb-4">
            <span class="text-sm font-bold text-gray-500">Đã chọn <span id="cartSelectedCount" class="text-blue-600"><?php echo count($_SESSION['cart']); ?></span> sản phẩm</span>
            <div class="text-right">
                <div class="text-xs text-gray-400 mb-0.5">Tạm tính</div>
                <div id="cartSelectedTotal" class="text-xl font-black text-red-600"><?php echo number_format($cart_subtotal_init); ?>đ</div>
            </div>
        </div>
        <a href="#" onclick="window.proceedCheckout && window.proceedCheckout(event)" id="cartCheckoutBtn"
            class="block w-full bg-[#0f172a] hover:bg-black text-white text-center py-4 rounded-xl uppercase font-bold tracking-wide transition-colors text-sm">
            Tiến hành thanh toán
        </a>
    </div>
    <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="public/js/cart-ajax.js"></script>
<script>
function toggleCart() {
    const drawer = document.getElementById('cartDrawer');
    const overlay = document.getElementById('cartOverlay');
    if (!drawer || !overlay) return;
    if (drawer.classList.contains('translate-x-full')) {
        drawer.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // Làm mới nội dung giỏ hàng mỗi khi mở
        if (typeof window.refreshCartDrawer === 'function') {
            window.refreshCartDrawer();
        }
    } else {
        drawer.classList.add('translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// ===== CART ITEM MANAGEMENT =====

// Tính lại tổng tiền dựa trên các item đã chọn
window.updateCartSummary = function() {
    const items = document.querySelectorAll('.cart-item');
    let total = 0, count = 0;
    items.forEach(item => {
        const cb = item.querySelector('.cart-item-check');
        if (cb && cb.checked) {
            const price = parseFloat(item.dataset.price || 0);
            const qty   = parseInt(item.dataset.qty || 1);
            total += price * qty;
            count++;
        }
    });
    const totalEl = document.getElementById('cartSelectedTotal');
    const countEl = document.getElementById('cartSelectedCount');
    if (totalEl) totalEl.textContent = total.toLocaleString('vi-VN') + 'đ';
    if (countEl) countEl.textContent = count;

    // Đồng bộ checkbox "chọn tất cả"
    const all  = document.querySelectorAll('.cart-item-check');
    const allEl = document.getElementById('cartSelectAll');
    if (allEl) allEl.checked = count === all.length;
};

// Chọn / bỏ chọn tất cả
window.toggleSelectAll = function(checked) {
    document.querySelectorAll('.cart-item-check').forEach(cb => cb.checked = checked);
    window.updateCartSummary();
};

// Xóa 1 sản phẩm
window.removeCartItem = async function(btn, key) {
    const itemEl = btn.closest('.cart-item');
    if (itemEl) {
        itemEl.style.opacity = '0.4';
        itemEl.style.pointerEvents = 'none';
    }
    try {
        const res = await fetch('api_cart_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&key=' + encodeURIComponent(key)
        });
        const data = await res.json();
        if (data.success) {
            if (typeof window.refreshCartDrawer === 'function') {
                await window.refreshCartDrawer();
            }
            if (typeof window.updateGlobalCartCount === 'function') {
                window.updateGlobalCartCount(data.cart_count);
            }
        }
    } catch(e) {
        if (itemEl) { itemEl.style.opacity = ''; itemEl.style.pointerEvents = ''; }
    }
};

// Cập nhật số lượng trong drawer
window.updateCartDrawerQty = async function(key, change) {
    try {
        const formData = new FormData();
        formData.append('action', 'update_qty');
        formData.append('key', key);
        formData.append('change', change);

        const res = await fetch('api_cart_action.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            if (window.refreshCartDrawer) {
                await window.refreshCartDrawer();
            }
        } else {
            alert(data.message || 'Không thể cập nhật số lượng');
        }
    } catch (e) {
        console.error(e);
    }
};

// Xóa tất cả
window.clearAllCart = async function() {
    if (!confirm('Xóa tất cả sản phẩm trong giỏ hàng?')) return;
    try {
        const res = await fetch('api_cart_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear'
        });
        const data = await res.json();
        if (data.success) {
            if (typeof window.refreshCartDrawer === 'function') await window.refreshCartDrawer();
            if (typeof window.updateGlobalCartCount === 'function') window.updateGlobalCartCount(0);
        }
    } catch(e) { console.error(e); }
};

// Tiến hành thanh toán
window.proceedCheckout = async function(e) {
    e.preventDefault();
    const checkedBoxes = document.querySelectorAll('.cart-item-check:checked');
    if (checkedBoxes.length === 0) {
        if (window.Swal) Swal.fire('Lỗi', 'Vui lòng chọn ít nhất một sản phẩm để thanh toán!', 'error');
        else alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán!');
        return;
    }
    
    const keys = Array.from(checkedBoxes).map(cb => cb.dataset.key);
    
    try {
        const res = await fetch('api_cart_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=select&keys=' + encodeURIComponent(JSON.stringify(keys))
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = 'checkout.php';
        }
    } catch(e) {
        console.error(e);
    }
};

<?php if (isset($_SESSION['toast'])): ?>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: '<?php echo isset($_SESSION['toast_type']) ? $_SESSION['toast_type'] : 'success'; ?>',
                title: '<?php echo addslashes($_SESSION['toast']); ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            alert('<?php echo addslashes($_SESSION['toast']); ?>');
        }
    });
    <?php 
        unset($_SESSION['toast']); 
        unset($_SESSION['toast_type']);
    ?>
<?php endif; ?>

// Dropdown user: click để toggle
(function() {
    const dropdown = document.querySelector('[data-user-dropdown]');
    const toggle   = document.querySelector('[data-user-dropdown-toggle]');
    const menu     = document.querySelector('[data-user-dropdown-menu]');
    if (!dropdown || !toggle || !menu) return;
    toggle.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation();
        menu.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) menu.classList.add('hidden');
    });
})();

// Mở chatbox khi bấm "Hỗ Trợ"
function openSupportChat(e) {
    e.preventDefault();
    const chatBtn = document.getElementById('chatToggleBtn');
    const chatBox = document.getElementById('chatBox');

    if (chatBox) {
        // Chatbox tồn tại (user đã đăng nhập)
        if (chatBox.classList.contains('hidden')) {
            // Chưa mở → mở lên
            chatBox.classList.remove('hidden');
            chatBox.classList.add('flex');
            if (chatBtn) {
                chatBtn.classList.add('scale-0');
                setTimeout(() => chatBtn.classList.add('hidden'), 300);
            }
            // Focus vào input
            setTimeout(() => {
                const inp = document.getElementById('chatInput');
                if (inp) inp.focus();
            }, 200);
        } else {
            // Đã mở → focus lại
            const inp = document.getElementById('chatInput');
            if (inp) inp.focus();
        }
        // Scroll chatbox vào view nếu cần
        chatBox.scrollIntoView({ behavior: 'smooth', block: 'end' });
    } else {
        // User chưa đăng nhập → chuyển đến trang đăng nhập
        window.location.href = 'login.php';
    }
}
</script>