<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$key    = $_POST['key'] ?? $_GET['key'] ?? '';

// Cập nhật số lượng
if ($action === 'update_qty' && $key !== '') {
    $change = (int)($_POST['change'] ?? 0);
    if (isset($_SESSION['cart'][$key])) {
        // Lấy thông tin kho để kiểm tra
        require_once __DIR__ . '/database.php';
        $db = new Database();
        $id = (int)$_SESSION['cart'][$key]['id'];
        $product = $db->select("SELECT SoLuong FROM product WHERE MaSanPham = $id")->fetch_assoc();
        $max_qty = $product ? (int)$product['SoLuong'] : 999;
        
        $new_qty = $_SESSION['cart'][$key]['soluong'] + $change;
        if ($new_qty > 0 && $new_qty <= $max_qty) {
            $_SESSION['cart'][$key]['soluong'] = $new_qty;
            setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
            echo json_encode(['success' => true]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá giới hạn']);
            exit();
        }
    }
}

// Lưu các sản phẩm được chọn để thanh toán
if ($action === 'select') {
    $selected_keys = isset($_POST['keys']) ? json_decode($_POST['keys'], true) : [];
    if (is_array($selected_keys) && !empty($selected_keys)) {
        $_SESSION['checkout_selected_keys'] = $selected_keys;
    } else {
        // Nếu không có gì được chọn, thanh toán tất cả
        $_SESSION['checkout_selected_keys'] = array_keys($_SESSION['cart']);
    }
    echo json_encode(['success' => true]);
    exit();
}

// Xóa 1 sản phẩm
if ($action === 'remove' && $key !== '') {
    unset($_SESSION['cart'][$key]);
    // Cập nhật selected keys nếu có
    if (isset($_SESSION['checkout_selected_keys'])) {
        $_SESSION['checkout_selected_keys'] = array_values(
            array_filter($_SESSION['checkout_selected_keys'], fn($k) => $k !== $key)
        );
    }
    setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
    echo json_encode(['success' => true, 'cart_count' => array_sum(array_column($_SESSION['cart'], 'soluong'))]);
    exit();
}

// Xóa tất cả
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_selected_keys']);
    setcookie('shopping_cart', '', time() - 3600, "/");
    echo json_encode(['success' => true, 'cart_count' => 0]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
