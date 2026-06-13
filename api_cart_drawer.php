<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/database.php';
$db = new Database();

// Đồng bộ giỏ hàng từ cookie
if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $cookieCart = json_decode($_COOKIE['shopping_cart'], true);
    if (is_array($cookieCart)) $_SESSION['cart'] = $cookieCart;
}

$cart = $_SESSION['cart'] ?? [];
$total_items = 0;
$cart_subtotal = 0;
$items_html = '';

if (!empty($cart) && is_array($cart)) {
    $cart_keys = array_keys($cart);
    // Tổng toàn bộ sản phẩm
    foreach ($cart as $key => $item) {
        $qty   = (int)($item['soluong'] ?? 1);
        $total_items += $qty;
        $cart_subtotal += (float)($item['gia'] ?? 0) * $qty;
    }

    foreach ($cart as $key => $item) {
        $qty   = (int)($item['soluong'] ?? 1);
        $price = (float)($item['gia'] ?? 0);
        $subtotal = $price * $qty;

        $ten   = htmlspecialchars($item['ten'] ?? 'Sản phẩm');
        $hinh  = htmlspecialchars($item['hinh'] ?? 'default.png');
        $key_esc = htmlspecialchars($key);

        $meta = '';
        if (!empty($item['size']))  $meta .= '<span class="text-xs bg-gray-100 rounded px-1.5 py-0.5">' . htmlspecialchars($item['size']) . '</span>';
        if (!empty($item['color'])) $meta .= '<span class="text-xs bg-gray-100 rounded px-1.5 py-0.5">' . htmlspecialchars($item['color']) . '</span>';

        $items_html .= '
        <div class="cart-item flex gap-3 pb-4 border-b border-gray-100 last:border-0" data-key="' . $key_esc . '" data-price="' . $price . '" data-qty="' . $qty . '">
            <!-- Checkbox -->
            <div class="flex items-start pt-1 shrink-0">
                <input type="checkbox" checked
                    class="cart-item-check w-4 h-4 rounded border-gray-300 text-blue-600 accent-blue-600 cursor-pointer mt-1"
                    data-key="' . $key_esc . '"
                    onchange="window.updateCartSummary && window.updateCartSummary()">
            </div>

            <!-- Ảnh -->
            <a href="chitiet.php?id=' . ($item['id'] ?? '') . '" class="shrink-0">
                <img src="public/images/' . $hinh . '"
                    class="w-20 h-24 object-cover rounded-xl border border-gray-100 hover:opacity-90 transition"
                    onerror="this.src=\'https://via.placeholder.com/80x96\'">
            </a>

            <!-- Thông tin -->
            <div class="flex-1 min-w-0">
            <div class="font-bold text-sm text-gray-900 line-clamp-2 leading-snug mb-1">' . $ten . '</div>
            ' . ($meta ? '<div class="flex gap-1 flex-wrap mb-1">' . $meta . '</div>' : '') . '
            <div class="text-red-600 font-black text-sm">' . number_format($price) . 'đ</div>
            <div class="flex items-center justify-between mt-2">
                <div class="flex items-center border border-gray-200 rounded-md overflow-hidden bg-white shadow-sm h-7">
                    <button onclick="window.updateCartDrawerQty && window.updateCartDrawerQty(\'' . $key_esc . '\', -1)" class="w-7 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold transition border-r">-</button>
                    <span class="w-8 h-full flex items-center justify-center text-xs font-bold text-gray-700 bg-white">' . $qty . '</span>
                    <button onclick="window.updateCartDrawerQty && window.updateCartDrawerQty(\'' . $key_esc . '\', 1)" class="w-7 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold transition border-l">+</button>
                </div>
                <div class="text-xs text-gray-400 font-bold">→ ' . number_format($subtotal) . 'đ</div>
            </div>
        </div>

            <!-- Nút xóa -->
            <button
                onclick="window.removeCartItem && window.removeCartItem(this, \'' . $key_esc . '\')"
                class="shrink-0 w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-500 text-gray-400 flex items-center justify-center transition mt-0.5"
                title="Xóa sản phẩm">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>';
    }
}

if (empty($items_html)) {
    $items_html = '
    <div class="flex flex-col items-center justify-center h-full text-gray-400 pt-20 pb-10">
        <i class="fas fa-shopping-basket text-6xl mb-5 text-gray-200"></i>
        <p class="text-sm">Giỏ hàng của bạn đang trống</p>
    </div>';
}

// Toolbar (Xóa tất cả + Chọn tất cả)
$toolbar_html = '';
if ($total_items > 0) {
    $toolbar_html = '
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
    </div>';
}

// Footer
$footer_html = '';
if ($total_items > 0) {
    $footer_html = '
    <div class="p-5 border-t bg-gray-50 shrink-0">
        <div class="flex justify-between items-center mb-4">
            <span class="text-sm font-bold text-gray-500">Đã chọn <span id="cartSelectedCount" class="text-blue-600">' . count($cart) . '</span> sản phẩm</span>
            <div class="text-right">
                <div class="text-xs text-gray-400 mb-0.5">Tạm tính</div>
                <div id="cartSelectedTotal" class="text-xl font-black text-red-600">' . number_format($cart_subtotal) . 'đ</div>
            </div>
        </div>
        <a href="#" onclick="window.proceedCheckout && window.proceedCheckout(event)" id="cartCheckoutBtn"
            class="block w-full bg-[#0f172a] hover:bg-black text-white text-center py-4 rounded-xl uppercase font-bold tracking-wide transition-colors text-sm">
            Tiến hành thanh toán
        </a>
    </div>';
}

echo json_encode([
    'success'      => true,
    'cart_count'   => $total_items,
    'subtotal'     => $cart_subtotal,
    'toolbar_html' => $toolbar_html,
    'items_html'   => $items_html,
    'footer_html'  => $footer_html,
]);
