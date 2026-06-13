<?php
session_start();
require_once "database.php";

$db = new Database();

// =========================
// KIỂM TRA ĐĂNG NHẬP
// =========================
if (!isset($_SESSION['user_id'])) {
    echo "<script>
        alert('Vui lòng đăng nhập để thanh toán!');
        window.location.href='login.php';
    </script>";
    exit();
}

 // =========================
 // KIỂM TRA GIỎ HÀNG
 // =========================
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Cấm admin đặt hàng / thanh toán
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    echo "<script>alert('Admin không được tự nhặt đồ vào giỏ và thanh toán. Vui lòng dùng tài khoản User phụ hoặc cơ chế tạo đơn hộ (Draft Order) nếu có.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Lấy thông tin user để tự điền form
$res_user = $db->select("SELECT TenNguoiDung, SoDienThoai, diachi FROM user WHERE IdNguoiDung = $user_id LIMIT 1");
$user_info = ($res_user && $res_user->num_rows > 0) ? $res_user->fetch_assoc() : [];

// =========================
// TÍNH TIỀN & LỌC SẢN PHẨM ĐÃ CHỌN
// =========================
$checkout_cart = [];
if (isset($_SESSION['checkout_selected_keys']) && is_array($_SESSION['checkout_selected_keys']) && !empty($_SESSION['checkout_selected_keys'])) {
    foreach ($_SESSION['checkout_selected_keys'] as $key) {
        if (isset($_SESSION['cart'][$key])) {
            $checkout_cart[$key] = $_SESSION['cart'][$key];
        }
    }
} else {
    $checkout_cart = $_SESSION['cart'];
}

// Cập nhật lại $_SESSION['cart_to_checkout'] để dùng nội bộ trang checkout nếu cần
$_SESSION['cart_to_checkout'] = $checkout_cart;

if (empty($checkout_cart)) {
    echo "<script>alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán!'); window.location.href='index.php';</script>";
    exit();
}

$tong_tien = 0;
foreach ($checkout_cart as $item) {
    $gia = (float)$item['gia'];
    $soluong = (int)$item['soluong'];
    $tong_tien += $gia * $soluong;
}

$phi_ship = 30000;

// Tự động áp dụng mã tốt nhất từ ví nếu chưa áp mã
if (empty($_SESSION['applied_discount']) && $tong_tien > 0) {
    $res_auto = $db->select("
        SELECT dc.* 
        FROM user_saved_codes usc
        JOIN discount_codes dc ON usc.discount_id = dc.id
        WHERE usc.user_id = $user_id 
          AND usc.is_used = 0
          AND dc.is_active = 1 
          AND dc.start_date <= NOW() 
          AND dc.end_date >= NOW()
          AND (dc.usage_limit = 0 OR dc.used_count < dc.usage_limit)
          AND dc.min_order_value <= $tong_tien
    ");
    
    $best = null;
    $best_amount = 0;
    if ($res_auto && $res_auto->num_rows > 0) {
        while ($d = $res_auto->fetch_assoc()) {
            $amt = ($d['discount_type'] === 'percent') ? intval($tong_tien * $d['discount_value'] / 100) : intval($d['discount_value']);
            if ($amt > $tong_tien) $amt = $tong_tien;
            if ($amt > $best_amount) {
                $best_amount = $amt;
                $best = $d;
            }
        }
    }
    
    if ($best) {
        $_SESSION['applied_discount'] = [
            'id'     => $best['id'],
            'code'   => $best['code'],
            'amount' => $best_amount,
            'type'   => $best['discount_type'],
            'value'  => $best['discount_value']
        ];
    }
}

// Mã giảm giá
$discount_amount = 0;
$discount_code = '';
if (!empty($_SESSION['applied_discount'])) {
    $d = $_SESSION['applied_discount'];
    if ($d['type'] === 'percent') {
        $discount_amount = intval($tong_tien * $d['value'] / 100);
    } else {
        $discount_amount = intval($d['value']);
    }
    if ($discount_amount > $tong_tien) $discount_amount = $tong_tien;
    $discount_code = $d['code'];
}

$tong_thanh_toan = $tong_tien + $phi_ship - $discount_amount;

$error = "";

// =========================
// XỬ LÝ ĐẶT HÀNG
// =========================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname       = trim($db->conn->real_escape_string($_POST['fullname'] ?? ''));
    $phone          = trim($db->conn->real_escape_string($_POST['phone'] ?? ''));
    $address        = trim($db->conn->real_escape_string($_POST['address'] ?? ''));
    $lat            = isset($_POST['lat']) && is_numeric($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng            = isset($_POST['lng']) && is_numeric($_POST['lng']) ? (float)$_POST['lng'] : null;
    $payment_method = $db->conn->real_escape_string($_POST['payment_method'] ?? 'COD');

    if ($fullname === '' || $phone === '' || $address === '') {
        $error = "Vui lòng điền đầy đủ thông tin nhận hàng!";
    } else {
        $lat_sql = ($lat !== null) ? $lat : 'NULL';
        $lng_sql = ($lng !== null) ? $lng : 'NULL';

        $dc_code_sql = $db->conn->real_escape_string($discount_code);
        $dc_amount_sql = intval($discount_amount);

        $sql_donhang = "INSERT INTO donhang (IdNguoiDung, TenNguoiNhan, SoDienThoai, diachi, TongTien, discount_code, discount_amount, phuong_thuc_thanh_toan, trangThai, NgayDat, lat_giao, lng_giao)
                        VALUES ($user_id, '$fullname', '$phone', '$address', $tong_thanh_toan, '$dc_code_sql', $dc_amount_sql, '$payment_method', '0', NOW(), $lat_sql, $lng_sql)";

        if ($db->execute($sql_donhang)) {
            $ma_don_hang = $db->conn->insert_id;

            foreach ($checkout_cart as $item) {
                $ma_sp   = intval($item['id']);
                $so_luong = intval($item['soluong']);
                $gia      = floatval($item['gia']);
                $mau      = isset($item['color']) ? $db->conn->real_escape_string($item['color']) : '';
                $size     = isset($item['size'])  ? $db->conn->real_escape_string($item['size'])  : '';

                $check_sp = $db->select("SELECT MaSanPham FROM product WHERE MaSanPham = $ma_sp");
                if ($check_sp && $check_sp->num_rows > 0) {
                    $phanLoai = trim($mau . ($mau !== '' && $size !== '' ? ' - ' : '') . $size);
                    if ($phanLoai === '') $phanLoai = 'Mặc định';
                    $phanLoai = $db->conn->real_escape_string($phanLoai);
                    $sql_chitiet = "INSERT INTO chitietdonhang (MaDonHang, MaSanPham, SoLuong, Gia, PhanLoai)
                                    VALUES ($ma_don_hang, $ma_sp, $so_luong, $gia, '$phanLoai')";
                    $db->execute($sql_chitiet);
                }
            }

            // Chỉ xóa các sản phẩm đã thanh toán khỏi giỏ hàng
            if (isset($_SESSION['checkout_selected_keys']) && is_array($_SESSION['checkout_selected_keys'])) {
                foreach ($_SESSION['checkout_selected_keys'] as $k) {
                    unset($_SESSION['cart'][$k]);
                }
            } else {
                $_SESSION['cart'] = [];
            }
            unset($_SESSION['checkout_selected_keys']);
            unset($_SESSION['cart_to_checkout']);

            if (isset($_COOKIE['shopping_cart'])) {
                setcookie('shopping_cart', json_encode($_SESSION['cart']), time() + (86400 * 30), '/');
            }

            // Đánh dấu mã giảm giá đã dùng + tăng used_count
            if (!empty($discount_code)) {
                $dc_escaped = $db->conn->real_escape_string($discount_code);
                $db->execute("UPDATE discount_codes SET used_count = used_count + 1 WHERE code = '$dc_escaped'");
                // Đánh dấu đã dùng trong ví của user nếu có
                $dc_res = $db->select("SELECT id FROM discount_codes WHERE code = '$dc_escaped'");
                if ($dc_res && $dc_row = $dc_res->fetch_assoc()) {
                    $db->execute("UPDATE user_saved_codes SET is_used = 1 WHERE user_id = $user_id AND discount_id = " . $dc_row['id']);
                }
                unset($_SESSION['applied_discount']);
            }

            require_once __DIR__ . '/_mail_order_notify.php';
            order_notify_send((int)$ma_don_hang, 'order_created');

            $_SESSION['toast'] = 'Đặt hàng thành công! Đang chuyển đến chi tiết đơn hàng...';
            $_SESSION['toast_type'] = 'success';
            header("Location: chitietdonhang.php?id=" . $ma_don_hang);
            exit();
        } else {
            $error = "Có lỗi xảy ra trong quá trình tạo đơn hàng. Vui lòng thử lại!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán - THP Shop</title>
    <link rel="icon" href="./public/images/web_be1.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet.js -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
    #map {
        height: 350px;
        border-radius: 12px;
        z-index: 1;
    }

    .leaflet-container {
        border-radius: 12px;
    }

    #map-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 9999;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        max-height: 220px;
        overflow-y: auto;
    }

    #map-search-results li {
        padding: 10px 14px;
        cursor: pointer;
        font-size: 13px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.15s;
    }

    #map-search-results li:hover {
        background: #eff6ff;
    }

    #map-search-results li:last-child {
        border-bottom: none;
    }

    .address-selected {
        background: linear-gradient(135deg, #eff6ff, #f0fdf4);
        border: 1.5px solid #3b82f6;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 13px;
        color: #1d4ed8;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .spinner {
        width: 18px;
        height: 18px;
        border: 2px solid #e5e7eb;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        display: inline-block;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <div class="max-w-6xl mx-auto py-10 px-4">
        <h1 class="text-3xl font-bold mb-8 text-center">Thanh Toán Đơn Hàng</h1>

        <?php if($error): ?>
        <div class="bg-red-100 text-red-600 p-4 rounded mb-6 border border-red-200">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkout-form" class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- FORM THÔNG TIN -->
            <div class="md:col-span-2 bg-white p-6 rounded-xl shadow space-y-5">

                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-user-circle text-blue-500"></i> Thông Tin Nhận Hàng
                </h2>

                <div>
                    <label class="block mb-1.5 font-semibold text-sm text-gray-700">Họ và tên *</label>
                    <input type="text" name="fullname" id="fullname" required
                        value="<?php echo htmlspecialchars($user_info['TenNguoiDung'] ?? ''); ?>"
                        placeholder="Nhập họ tên người nhận"
                        class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block mb-1.5 font-semibold text-sm text-gray-700">Số điện thoại *</label>
                    <input type="tel" name="phone" id="phone" required
                        value="<?php echo htmlspecialchars($user_info['SoDienThoai'] ?? ''); ?>"
                        placeholder="Nhập số điện thoại"
                        class="w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
                </div>

                <!-- ====== BẢN ĐỒ CHỌN ĐỊA CHỈ ====== -->
                <div>
                    <label class="block mb-1.5 font-semibold text-sm text-gray-700">
                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                        Địa chỉ giao hàng *
                    </label>

                    <!-- Ô tìm kiếm -->
                    <div class="relative mb-3">
                        <div class="flex gap-2">
                            <input type="text" id="map-search-input"
                                placeholder="🔍 Tìm kiếm địa chỉ... (vd: 120 Uyên Lãng, Thủ Đức)"
                                class="flex-1 border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition">
                            <button type="button" id="map-search-btn"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-xl text-sm font-semibold transition whitespace-nowrap flex items-center gap-2">
                                <i class="fas fa-search"></i> Tìm
                            </button>
                        </div>
                        <ul id="map-search-results" class="hidden"></ul>
                    </div>

                    <!-- Bản đồ Leaflet -->
                    <div id="map" class="mb-3 border border-gray-200"></div>

                    <!-- Nút lấy vị trí hiện tại -->
                    <button type="button" id="get-location-btn" class="mb-4 text-sm text-blue-600 font-semibold hover:text-blue-700 flex items-center gap-1.5 transition">
                        <i class="fas fa-location-arrow"></i> Lấy vị trí hiện tại của tôi
                    </button>

                    <!-- Địa chỉ đã chọn -->
                    <div id="address-display" class="hidden address-selected mb-3">
                        <i class="fas fa-map-pin text-blue-500 flex-shrink-0"></i>
                        <span id="address-display-text"></span>
                    </div>

                    <p class="text-xs text-gray-400 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Click lên bản đồ hoặc tìm kiếm để chọn địa chỉ giao hàng chính xác.
                    </p>

                    <!-- Input ẩn lưu dữ liệu -->
                    <input type="hidden" name="address" id="address-hidden" required>
                    <input type="hidden" name="lat" id="lat-hidden">
                    <input type="hidden" name="lng" id="lng-hidden">

                    <!-- Fallback nhập tay -->
                    <details class="mt-2">
                        <summary class="text-xs text-blue-500 cursor-pointer hover:underline">
                            Nhập địa chỉ thủ công
                        </summary>
                        <input type="text" id="address-manual" placeholder="Nhập địa chỉ đầy đủ..."
                            class="mt-2 w-full border border-gray-200 rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    </details>
                </div>
                <!-- ====== KẾT THÚC BẢN ĐỒ ====== -->

                <h2 class="text-xl font-bold text-gray-800 mt-4 flex items-center gap-2">
                    <i class="fas fa-credit-card text-green-500"></i> Phương Thức Thanh Toán
                </h2>

                <div class="space-y-3">
                    <label
                        class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition">
                        <input type="radio" name="payment_method" value="COD" checked class="accent-blue-500">
                        <span class="text-sm font-medium">💵 Thanh toán khi nhận hàng (COD)</span>
                    </label>
                    <label
                        class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-blue-50 hover:border-blue-300 transition">
                        <input type="radio" name="payment_method" value="BankTransfer" class="accent-blue-500">
                        <span class="text-sm font-medium">🏦 Chuyển khoản ngân hàng</span>
                    </label>
                </div>

            </div>

            <!-- ĐƠN HÀNG -->
            <div class="bg-white p-6 rounded-xl shadow h-fit sticky top-4">

                <h2 class="text-xl font-bold mb-4 text-gray-800">
                    <i class="fas fa-shopping-bag text-red-500 mr-1"></i> Đơn Hàng
                </h2>

                <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                    <?php foreach($checkout_cart as $item): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-700 flex-1 pr-2">
                            <?php echo htmlspecialchars($item['ten']); ?>
                            <span class="text-gray-400">x<?php echo $item['soluong']; ?></span>
                        </span>
                        <span class="font-bold text-gray-800 whitespace-nowrap">
                            <?php echo number_format($item['gia'] * $item['soluong']); ?>đ
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <!-- MÃ GIẢM GIÁ -->
                <div class="border border-dashed border-blue-200 rounded-xl bg-blue-50/50 p-4 space-y-3 mb-4">
                    <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-ticket-alt text-blue-500"></i> Mã giảm giá
                    </p>

                    <?php if (!empty($discount_code)): ?>
                    <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                        <span class="text-sm font-bold text-green-700"><i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($discount_code); ?></span>
                        <button type="button" onclick="removeDiscount()" class="text-xs text-red-500 hover:text-red-700 font-semibold transition">× Xóa</button>
                    </div>
                    <?php else: ?>
                    <div class="flex gap-2">
                        <input type="text" id="discount-input" placeholder="Nhập mã giảm giá..."
                            class="flex-1 text-sm px-3 py-2 border border-gray-200 rounded-lg outline-none focus:border-blue-400 bg-white uppercase"
                            oninput="this.value=this.value.toUpperCase()">
                        <button type="button" onclick="applyDiscount()" class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">Áp mã</button>
                    </div>
                    <div id="discount-msg" class="text-xs hidden"></div>
                    <a href="magiamgia.php" target="_blank" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                        <i class="fas fa-tag"></i>Xem kho mã giảm giá
                    </a>
                    <?php endif; ?>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Tạm tính:</span>
                        <span><?php echo number_format($tong_tien); ?>đ</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí ship:</span>
                        <span><?php echo number_format($phi_ship); ?>đ</span>
                    </div>
                    <?php if ($discount_amount > 0): ?>
                    <div class="flex justify-between text-green-600 font-semibold">
                        <span><i class="fas fa-tag mr-1 text-xs"></i>Giảm giá (<?= htmlspecialchars($discount_code); ?>):</span>
                        <span>-<?php echo number_format($discount_amount); ?>đ</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between font-bold text-red-600 text-lg pt-3 border-t mt-2">
                        <span>Tổng:</span>
                        <span><?php echo number_format($tong_thanh_toan); ?>đ</span>
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-4 rounded-xl font-bold mt-5 text-base shadow-lg shadow-red-200 hover:shadow-xl transition transform hover:-translate-y-0.5 duration-200">
                    <i class="fas fa-check-circle mr-2"></i>ĐẶT HÀNG
                </button>

                <p class="text-xs text-gray-400 text-center mt-3">
                    <i class="fas fa-shield-alt mr-1 text-green-400"></i>
                    Giao dịch được bảo mật
                </p>

            </div>

        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    // ========================
    // LEAFLET MAP INTEGRATION
    // ========================
    const defaultLat = 10.8705;
    const defaultLng = 106.8030;

    const map = L.map('map').setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Icon đánh dấu
    const markerIcon = L.icon({
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    let currentMarker = null;

    function setMarker(lat, lng, label) {
        if (currentMarker) map.removeLayer(currentMarker);
        currentMarker = L.marker([lat, lng], {
            icon: markerIcon
        }).addTo(map);
        if (label) currentMarker.bindPopup(label).openPopup();
        document.getElementById('lat-hidden').value = lat;
        document.getElementById('lng-hidden').value = lng;
    }

    function setAddress(addr, lat, lng) {
        document.getElementById('address-hidden').value = addr;
        document.getElementById('address-display-text').textContent = addr;
        document.getElementById('address-display').classList.remove('hidden');
        document.getElementById('address-manual').value = addr;
        setMarker(lat, lng, addr);
        map.setView([lat, lng], 16);
    }

    // Click trên bản đồ → reverse geocode
    map.on('click', async function(e) {
        const {
            lat,
            lng
        } = e.latlng;
        setMarker(lat, lng, 'Đang tải địa chỉ...');
        document.getElementById('address-display').classList.remove('hidden');
        document.getElementById('address-display-text').innerHTML =
            '<span class="spinner"></span> Đang lấy địa chỉ...';

        try {
            const r = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi`, {
                    headers: {
                        'Accept-Language': 'vi'
                    }
                });
            const data = await r.json();
            const addr = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            setAddress(addr, lat, lng);
        } catch {
            const addr = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            setAddress(addr, lat, lng);
        }
    });

    // Tìm kiếm địa chỉ
    let searchDebounce = null;
    const searchInput = document.getElementById('map-search-input');
    const searchResults = document.getElementById('map-search-results');
    const searchBtn = document.getElementById('map-search-btn');

    async function doSearch() {
        const q = searchInput.value.trim();
        if (q.length < 3) {
            searchResults.classList.add('hidden');
            return;
        }

        searchBtn.innerHTML = '<span class="spinner"></span>';
        try {
            const r = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=6&countrycodes=vn&accept-language=vi`
                );
            const data = await r.json();
            searchResults.innerHTML = '';
            if (data.length === 0) {
                searchResults.innerHTML = '<li class="text-gray-400 text-center py-3">Không tìm thấy kết quả</li>';
            } else {
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.display_name;
                    li.addEventListener('click', () => {
                        setAddress(item.display_name, parseFloat(item.lat), parseFloat(item.lon));
                        searchResults.classList.add('hidden');
                        searchInput.value = '';
                    });
                    searchResults.appendChild(li);
                });
            }
            searchResults.classList.remove('hidden');
        } catch {
            searchResults.innerHTML =
            '<li class="text-red-400 text-center py-3">Lỗi kết nối, vui lòng thử lại</li>';
            searchResults.classList.remove('hidden');
        }
        searchBtn.innerHTML = '<i class="fas fa-search"></i> Tìm';
    }

    searchBtn.addEventListener('click', doSearch);
    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            doSearch();
        }
    });
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(doSearch, 600);
    });

    // Ẩn kết quả khi click ra ngoài
    document.addEventListener('click', e => {
        if (!e.target.closest('#map-search-input') && !e.target.closest('#map-search-results')) {
            searchResults.classList.add('hidden');
        }
    });

    // Lấy vị trí hiện tại
    const getLocBtn = document.getElementById('get-location-btn');
    if (getLocBtn) {
        getLocBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Trình duyệt của bạn không hỗ trợ định vị.');
                return;
            }
            
            getLocBtn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-top-color:#1d4ed8;"></span> Đang lấy vị trí...';
            getLocBtn.disabled = true;

            navigator.geolocation.getCurrentPosition(async (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                try {
                    const r = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=vi`, {
                            headers: { 'Accept-Language': 'vi' }
                        }
                    );
                    const data = await r.json();
                    const addr = data.display_name || `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    setAddress(addr, lat, lng);
                } catch {
                    setAddress(`${lat.toFixed(6)}, ${lng.toFixed(6)}`, lat, lng);
                }
                getLocBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Lấy vị trí hiện tại của tôi';
                getLocBtn.disabled = false;
            }, (err) => {
                alert('Không thể lấy vị trí: Vui lòng cấp quyền vị trí cho trình duyệt!');
                getLocBtn.innerHTML = '<i class="fas fa-location-arrow"></i> Lấy vị trí hiện tại của tôi';
                getLocBtn.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });
    }

    // Nhập địa chỉ thủ công
    document.getElementById('address-manual').addEventListener('input', function() {
        document.getElementById('address-hidden').value = this.value;
        document.getElementById('address-display-text').textContent = this.value || '(chưa chọn)';
        if (this.value) document.getElementById('address-display').classList.remove('hidden');
    });

    // Validate trước khi submit
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const addr = document.getElementById('address-hidden').value.trim();
        if (!addr) {
            e.preventDefault();
            alert('Vui lòng chọn địa chỉ giao hàng trên bản đồ hoặc nhập thủ công!');
            document.getElementById('map').scrollIntoView({
                behavior: 'smooth'
            });
        }
    });

    // MÃ GIẢM GIÁ
    async function applyDiscount() {
        const code = document.getElementById('discount-input').value.trim();
        if (!code) return;
        const msgEl = document.getElementById('discount-msg');
        msgEl.className = 'text-xs text-gray-400';
        msgEl.classList.remove('hidden');
        msgEl.textContent = 'Đang kiểm tra mã...';

        const res = await fetch('xuly_magiamgia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=apply&code=' + encodeURIComponent(code)
        });
        const data = await res.json();
        if (data.success) {
            msgEl.className = 'text-xs text-green-600 font-semibold';
            msgEl.textContent = data.message;
            setTimeout(() => window.location.reload(), 800);
        } else {
            msgEl.className = 'text-xs text-red-500 font-semibold';
            msgEl.textContent = data.message;
        }
        msgEl.classList.remove('hidden');
    }

    async function removeDiscount() {
        await fetch('xuly_magiamgia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove'
        });
        window.location.reload();
    }
    </script>
</body>

</html>