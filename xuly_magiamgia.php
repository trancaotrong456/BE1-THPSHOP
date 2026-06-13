<?php
session_start();
header('Content-Type: application/json');
require_once "database.php";
$db = new Database();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =========================================
// API: Lưu mã vào ví
// =========================================
if ($action === 'save') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để lưu mã!']);
        exit;
    }
    $discount_id = intval($_POST['discount_id'] ?? 0);
    $user_id = intval($_SESSION['user_id']);

    // Kiểm tra mã tồn tại & còn hạn & đang active
    $res = $db->select("SELECT * FROM discount_codes WHERE id = $discount_id AND is_active = 1 AND end_date >= NOW()");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ hoặc đã hết hạn!']);
        exit;
    }

    // Kiểm tra đã lưu chưa
    $check = $db->select("SELECT id FROM user_saved_codes WHERE user_id = $user_id AND discount_id = $discount_id");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Bạn đã lưu mã này rồi!']);
        exit;
    }

    $db->execute("INSERT INTO user_saved_codes (user_id, discount_id) VALUES ($user_id, $discount_id)");
    echo json_encode(['success' => true, 'message' => 'Đã lưu mã vào ví voucher!']);
    exit;
}

// =========================================
// API: Áp mã vào giỏ hàng (session)
// =========================================
if ($action === 'apply') {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_POST['code'] ?? ''));

    // Tính tạm tính giỏ hàng
    $subtotal = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['gia'] * $item['soluong'];
        }
    }
    $code_escaped = $db->conn->real_escape_string($code);
    $res = $db->select("SELECT * FROM discount_codes WHERE code = '$code_escaped' AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");

    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn!']);
        exit;
    }

    $d = $res->fetch_assoc();

    // Kiểm tra giới hạn lượt dùng
    if ($d['usage_limit'] > 0 && $d['used_count'] >= $d['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng!']);
        exit;
    }

    // Kiểm tra đơn tối thiểu
    if ($subtotal < $d['min_order_value']) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($d['min_order_value']) . 'đ để dùng mã này!']);
        exit;
    }

    // Tính số tiền giảm
    $discount_amount = 0;
    if ($d['discount_type'] === 'percent') {
        $discount_amount = intval($subtotal * $d['discount_value'] / 100);
    } else {
        $discount_amount = intval($d['discount_value']);
    }
    if ($discount_amount > $subtotal) $discount_amount = $subtotal;

    $_SESSION['applied_discount'] = [
        'id'     => $d['id'],
        'code'   => $d['code'],
        'amount' => $discount_amount,
        'type'   => $d['discount_type'],
        'value'  => $d['discount_value']
    ];

    echo json_encode([
        'success'         => true,
        'message'         => 'Áp mã thành công! Bạn được giảm ' . number_format($discount_amount) . 'đ',
        'discount_amount' => number_format($discount_amount),
        'discount_raw'    => $discount_amount,
        'code'            => $d['code']
    ]);
    exit;
}

// =========================================
// API: Xóa mã khỏi giỏ
// =========================================
if ($action === 'remove') {
    unset($_SESSION['applied_discount']);
    echo json_encode(['success' => true, 'message' => 'Đã xóa mã giảm giá']);
    exit;
}

// =========================================
// API: Tự động gợi ý mã tốt nhất (Auto-apply)
// =========================================
if ($action === 'auto') {
    $subtotal = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['gia'] * $item['soluong'];
        }
    }

    $res = $db->select("SELECT * FROM discount_codes WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() AND (usage_limit = 0 OR used_count < usage_limit) AND min_order_value <= $subtotal ORDER BY discount_value DESC");

    $best = null;
    $best_amount = 0;

    if ($res && $res->num_rows > 0) {
        while ($d = $res->fetch_assoc()) {
            $amt = ($d['discount_type'] === 'percent') ? intval($subtotal * $d['discount_value'] / 100) : intval($d['discount_value']);
            if ($amt > $best_amount) {
                $best_amount = $amt;
                $best = $d;
            }
        }
    }

    if ($best) {
        echo json_encode([
            'success' => true,
            'code'    => $best['code'],
            'amount'  => number_format($best_amount),
            'raw'     => $best_amount,
            'label'   => ($best['discount_type'] === 'percent') ? $best['discount_value'] . '%' : number_format($best['discount_value']) . 'đ'
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// =========================================
// API: Xóa mã khỏi ví đã lưu
// =========================================
if ($action === 'remove_saved') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
        exit;
    }
    $discount_id = intval($_POST['discount_id'] ?? 0);
    $user_id = intval($_SESSION['user_id']);

    $db->execute("DELETE FROM user_saved_codes WHERE user_id = $user_id AND discount_id = $discount_id");
    echo json_encode(['success' => true, 'message' => 'Đã bỏ lưu mã giảm giá!']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
?>
