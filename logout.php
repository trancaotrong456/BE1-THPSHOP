<?php
session_start();

// Lưu giỏ hàng theo tài khoản trước khi hủy session
$uid = $_SESSION['user_id'] ?? null;
$cart = $_SESSION['cart'] ?? null;

// Chỉ lưu giỏ hàng nếu user đã đăng nhập và có sản phẩm trong giỏ
if (!empty($uid) && is_array($cart) && !empty($cart)) {
    $cartByUser = [];
    if (isset($_COOKIE['shopping_cart_by_user'])) {
        $tmp = json_decode($_COOKIE['shopping_cart_by_user'], true);
        if (is_array($tmp)) $cartByUser = $tmp;
    }

    $cartByUser[(string)$uid] = $cart;

    // Lưu giỏ hàng theo tài khoản
    setcookie(
        'shopping_cart_by_user',
        json_encode($cartByUser, JSON_UNESCAPED_UNICODE),
        time() + (86400 * 30),
        '/',
        '',
        false,
        false
    );
}

// LUÔN XÓA cookie giỏ hàng chung khi đăng xuất (rất quan trọng!)
// Điều này ngăn chặn giỏ hàng cũ được khôi phục cho user tiếp theo
if (isset($_COOKIE['shopping_cart'])) {
    setcookie('shopping_cart', '', time() - 3600, '/');
}

// Xóa toàn bộ session
$_SESSION = [];
session_unset();
session_destroy();

session_start();
$_SESSION['toast'] = 'Đã đăng xuất thành công!';
$_SESSION['toast_type'] = 'info';

header("Location: index.php");
exit();
?>