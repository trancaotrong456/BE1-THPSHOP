<?php
session_start();
require_once "database.php";

// Chặn admin tự thêm vào giỏ/mua hàng
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    echo "<script>alert('Admin không được tự thêm vào giỏ hàng/thanh toán. Vui lòng dùng tài khoản User phụ hoặc cơ chế tạo đơn hộ (Draft Order) nếu có.'); window.location.href='index.php';</script>";
    exit();
}

$db = new Database();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function get_cart_count() {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += isset($item['soluong']) ? (int)$item['soluong'] : 1;
        }
    }
    return $count;
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1;
    $size = isset($_GET['size']) ? $db->conn->real_escape_string($_GET['size']) : '';
    $color = isset($_GET['color']) ? $db->conn->real_escape_string($_GET['color']) : '';
    
    // lấy thông tin sản phẩm từ db
    $sql = "SELECT * FROM product WHERE MaSanPham = $id";
    $res = $db->select($sql);
    
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
        
        // Tạo key duy nhất cho sản phẩm với size và màu sắc
        $cart_item_key = $id . '_' . $size . '_' . $color;
        
        $current_qty_in_cart = isset($_SESSION['cart'][$cart_item_key]) ? $_SESSION['cart'][$cart_item_key]['soluong'] : 0;
        $total_requested_qty = $current_qty_in_cart + $qty;

        if ($total_requested_qty > $product['SoLuong']) {
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sản phẩm này chỉ còn ' . $product['SoLuong'] . ' sản phẩm trong kho!'
                ]);
                exit();
            } else {
                echo "<script>alert('Sản phẩm này chỉ còn " . $product['SoLuong'] . " sản phẩm trong kho!'); window.history.back();</script>";
                exit();
            }
        }

        // tạo cấu trúc sản phẩm trong giỏ
        $item = [
            'id' => $product['MaSanPham'],
            'ten' => $product['TenSanPham'],
            'gia' => $product['GiaSanPham'],
            'hinh' => (!empty($color) && $color == $product['mau2'] && !empty($product['hinh2'])) ? $product['hinh2'] : ((!empty($color) && $color == $product['mau3'] && !empty($product['hinh3'])) ? $product['hinh3'] : $product['hinh']),
            'soluong' => $qty,
            'size' => $size,
            'color' => $color
        ];

        // nếu giỏ hàng đã có sản phẩm này thì tăng số lượng
        if (isset($_SESSION['cart'][$cart_item_key])) {
            $_SESSION['cart'][$cart_item_key]['soluong'] += $qty;
        } else {
            // nếu chưa có thì thêm mới vào giỏ
            $_SESSION['cart'][$cart_item_key] = $item;
        }
    }
}

// Nếu là hành động Mua Ngay
if (isset($_GET['action']) && $_GET['action'] == 'buynow') {
    setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
    header("Location: cart.php");
    exit();
}

// Nếu là thêm giỏ hàng bằng AJAX thì trả về JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
    
    ob_start();
    ?>
    <div class="flex items-center justify-between p-5 border-b">
        <h2 class="text-xl font-black">Giỏ hàng</h2>
        <button onclick="toggleCart()" class="text-gray-500 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
    </div>
    <div class="flex-1 overflow-y-auto p-5 space-y-4">
        <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): 
            $cart_subtotal = 0;
            foreach ($_SESSION['cart'] as $item): 
                $cart_subtotal += $item['gia'] * $item['soluong'];
        ?>
        <div class="flex gap-4 border-b pb-4">
            <img src="public/images/<?php echo htmlspecialchars($item['hinh'] ?? 'default.png'); ?>"
                class="w-24 h-28 object-cover rounded-xl border" onerror="this.src='https://via.placeholder.com/150';">
            <div class="flex-1">
                <div class="font-bold line-clamp-2"><?php echo htmlspecialchars($item['ten'] ?? 'Sản phẩm'); ?></div>
                <div class="text-red-600 font-black mt-3"><?php echo number_format((float)($item['gia'] ?? 0)); ?>đ</div>
                <div class="mt-3 text-gray-500">Số lượng: <?php echo htmlspecialchars((string)($item['soluong'] ?? 1)); ?></div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400 pt-20">
            <i class="fas fa-shopping-basket text-6xl mb-5 text-gray-200"></i>
            <p>Giỏ hàng của bạn đang trống</p>
        </div>
        <?php endif; ?>
    </div>
    <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
    <div class="p-5 border-t bg-gray-50">
        <div class="flex justify-between items-center mb-5 text-xl font-black">
            <span>Tạm tính</span>
            <span class="text-red-600"><?php echo number_format($cart_subtotal ?? 0); ?>đ</span>
        </div>
        <a href="checkout.php" class="block w-full bg-[#0f172a] hover:bg-black text-white text-center py-4 rounded-xl uppercase font-bold transition-colors">Tiến hành thanh toán</a>
    </div>
    <?php endif; ?>
    <?php
    $sidebar_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'cart_count' => get_cart_count(),
        'sidebar_html' => $sidebar_html
    ]);
    exit();
}

// Nếu không có action (click từ trang chủ/danh mục) thì chuyển tới giỏ hàng
setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), "/");
header("Location: cart.php");
exit();
?>