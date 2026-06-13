<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once "database.php";

$db = new Database();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("<script>alert('Mã đơn hàng không hợp lệ!'); window.location.href='index.php';</script>");
}

// --- XỬ LÝ LẤY TRẠNG THÁI NGẦM (Dùng cho AJAX Polling chờ Admin) ---
if (isset($_GET['ajax_get_status'])) {
    $res = $db->select("SELECT trangThai FROM donhang WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id");
    echo $res && $res->num_rows > 0 ? intval($res->fetch_assoc()['trangThai']) : 0;
    exit;
}

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI NGẦM (Dùng khi Auto giao thành công) ---
if (isset($_GET['ajax_update'])) {
    $st = intval($_GET['ajax_update']);
    $db->execute("UPDATE donhang SET trangThai = '$st' WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id");
    exit;
}

// 2. Truy vấn thông tin đơn hàng (Dùng TIMESTAMPDIFF để MySQL tự tính số giây đã trôi qua)
$sql_order = "SELECT donhang.*, TIMESTAMPDIFF(SECOND, thoi_gian_giao, NOW()) as thoi_gian_da_troi 
              FROM donhang WHERE MaDonHang = $order_id AND IdNguoiDung = $user_id";
$res_order = $db->select($sql_order);

if (!$res_order || $res_order->num_rows == 0) {
    die("<script>alert('Không tìm thấy đơn hàng hoặc bạn không có quyền xem!'); window.location.href='index.php';</script>");
}
$order = $res_order->fetch_assoc(); 

// Cập nhật auto cho riêng đơn này nếu quá 5 phút (300 giây)
$db->execute("UPDATE donhang SET trangThai = 3 WHERE trangThai = 2 AND thoi_gian_giao IS NOT NULL AND TIMESTAMPDIFF(SECOND, thoi_gian_giao, NOW()) >= 300");
$order['trangThai'] = ($db->select("SELECT trangThai FROM donhang WHERE MaDonHang = $order_id")->fetch_assoc()['trangThai']);

// Lấy số giây chuẩn từ MySQL (Triệt tiêu 100% lỗi chênh lệch múi giờ)
$elapsed_seconds = 0;
if ($order['trangThai'] == 2 && isset($order['thoi_gian_da_troi'])) {
    $elapsed_seconds = max(0, intval($order['thoi_gian_da_troi']));
}

// Ngày đặt
$date_col = isset($order['NgayDat']) ? 'NgayDat' : (isset($order['NgayTao']) ? 'NgayTao' : 'created_at');
$ngay_dat = isset($order[$date_col]) ? $order[$date_col] : date('Y-m-d H:i:s'); 

// 3. Truy vấn chi tiết các sản phẩm của đơn hàng
$sql_details = "SELECT c.*, p.TenSanPham, p.hinh 
                FROM chitietdonhang c 
                JOIN product p ON c.MaSanPham = p.MaSanPham 
                WHERE c.MaDonHang = $order_id";
$order_items = $db->select($sql_details);

// 4. Khai báo tiêu đề trang và nhúng Header chung
$page_title = "Theo dõi đơn hàng #" . $order_id . " - THPSHOP";
$extra_body_class = "bg-gray-50 text-gray-800";
include 'header.php';
?>

<main class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mt-8">
        <h1 class="text-2xl font-bold uppercase tracking-wider">Chi tiết đơn hàng</h1>
        <div class="flex gap-2">
            <a href="lichsu_donhang.php"
                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold text-sm hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-1"></i> Trở về lịch sử
            </a>
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-blue-700 transition">
                <i class="fas fa-headset mr-1"></i> Liên hệ hỗ trợ
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-wrap justify-between items-center border-b pb-4 mb-8 gap-4">
            <div>
                <p class="text-sm text-gray-500">Mã đơn hàng</p>
                <p class="font-black text-xl text-blue-600">
                    #THP-<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Ngày đặt hàng</p>
                <p class="font-bold"><?php echo date('d/m/Y H:i', strtotime($ngay_dat)); ?></p>
            </div>
        </div>

        <div class="relative px-4 sm:px-10 mb-8">
            <div class="absolute left-0 top-6 transform -translate-y-1/2 w-full h-1.5 bg-gray-200 z-0 rounded-full">
            </div>

            <div id="progress-bar"
                class="absolute left-0 top-6 transform -translate-y-1/2 h-1.5 bg-blue-600 z-0 rounded-full transition-all ease-linear"
                style="width: 0%; transition-duration: 100ms;"></div>

            <div class="relative z-10 flex justify-between">
                <div class="flex flex-col items-center">
                    <div id="icon-step-0"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-bold bg-gray-200 text-gray-400">
                        <i class="fas fa-receipt text-xl"></i>
                    </div>
                    <p id="text-step-0" class="text-xs sm:text-sm font-bold mt-3 text-center text-gray-400">Chờ xác nhận
                    </p>
                </div>

                <div class="flex flex-col items-center">
                    <div id="icon-step-1"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-bold bg-gray-200 text-gray-400">
                        <i class="fas fa-box-open text-xl"></i>
                    </div>
                    <p id="text-step-1" class="text-xs sm:text-sm font-bold mt-3 text-center text-gray-400">Đã đóng gói
                    </p>
                </div>

                <div class="flex flex-col items-center">
                    <div id="icon-step-2"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-bold bg-gray-200 text-gray-400">
                        <i class="fas fa-truck-fast text-xl"></i>
                    </div>
                    <p id="text-step-2" class="text-xs sm:text-sm font-bold mt-3 text-center text-gray-400">Đang giao
                        hàng</p>
                </div>

                <div class="flex flex-col items-center">
                    <div id="icon-step-3"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-bold bg-gray-200 text-gray-400">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                    <p id="text-step-3" class="text-xs sm:text-sm font-semibold mt-3 text-center text-gray-400">Giao
                        thành công</p>
                </div>
            </div>
        </div>

        <div id="status-message"
            class="bg-blue-50 border border-blue-100 text-blue-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 shadow-inner">
            <i class="fas fa-spinner fa-spin"></i>
            Đang tải tiến trình...
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <h3 class="font-bold text-lg mb-2">Sản phẩm đã mua</h3>
            <?php 
                $tam_tinh = 0;
                if ($order_items && $order_items->num_rows > 0): 
                    while ($item = $order_items->fetch_assoc()):
                        $thanh_tien = $item['SoLuong'] * $item['Gia'];
                        $tam_tinh += $thanh_tien;
                ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex gap-4 transition hover:shadow-md">
                <img src="./public/images/<?php echo htmlspecialchars($item['hinh'] ?? 'default.png'); ?>" alt="Product"
                    class="w-24 h-28 object-cover rounded-lg border">
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-gray-800 line-clamp-2"><a
                                href="chitiet.php?id=<?php echo $item['MaSanPham']; ?>"><?php echo htmlspecialchars($item['TenSanPham']); ?></a>
                        </h4>
                        <p class="text-sm text-gray-500 mt-1">Phân loại:
                            <?php echo htmlspecialchars($item['PhanLoai'] ?? 'Mặc định'); ?></p>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-600">x <?php echo $item['SoLuong']; ?></span>
                        <span
                            class="font-bold text-red-600"><?php echo number_format($item['Gia'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <p class="text-gray-500">Đơn hàng này chưa có sản phẩm nào.</p>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2"><i
                        class="fas fa-map-marker-alt text-blue-600"></i> Địa chỉ nhận hàng</h3>
                <p class="font-bold text-gray-800">
                    <?php echo htmlspecialchars($order['TenNguoiNhan'] ?? 'Khách hàng'); ?></p>
                <p class="text-sm text-gray-600 mt-1">(+84)
                    <?php echo htmlspecialchars($order['SoDienThoai'] ?? '0123456789'); ?>
                </p>
                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                    <?php echo htmlspecialchars($order['DiaChiGiaoHang'] ?? $order['diachi'] ?? 'Chưa cập nhật địa chỉ'); ?>
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2"><i
                        class="fas fa-file-invoice-dollar text-blue-600"></i> Thanh toán</h3>
                <?php 
                        $phi_ship = 30000;
                        $giam_gia = ($tam_tinh + $phi_ship) - $order['TongTien'];
                        if ($giam_gia < 0) $giam_gia = 0;
                    ?>
                <div class="space-y-3 text-sm text-gray-600 border-b pb-4 mb-4">
                    <div class="flex justify-between"><span>Tạm tính:</span><span
                            class="font-semibold text-gray-800"><?php echo number_format($tam_tinh, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="flex justify-between"><span>Phí vận chuyển:</span><span
                            class="font-semibold text-gray-800"><?php echo number_format($phi_ship, 0, ',', '.'); ?>đ</span>
                    </div>
                    <?php if ($giam_gia > 0): ?><div class="flex justify-between"><span>Giảm giá:</span><span
                            class="font-semibold text-green-600">-<?php echo number_format($giam_gia, 0, ',', '.'); ?>đ</span>
                    </div><?php endif; ?>
                </div>
                <div class="flex justify-between items-end">
                    <span class="font-bold text-gray-800">Tổng cộng:</span>
                    <span
                        class="text-2xl font-black text-red-600"><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>đ</span>
                </div>
                <p class="text-xs text-center text-gray-400 mt-4 italic">Phương thức:
                    <?php echo htmlspecialchars($order['PhuongThucThanhToan'] ?? 'Thanh toán khi nhận hàng (COD)'); ?>
                </p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let dbStatus = <?php echo intval($order['trangThai']); ?>;
    let serverElapsed = <?php echo isset($elapsed_seconds) ? $elapsed_seconds : 0; ?>;
    const pageLoadTime = Date.now(); // Lưu mốc thời gian thực ngay lúc vừa load trang
    const AUTO_GIAO_TIME = 300; // 5 phút (300 giây)
    const orderId = <?php echo $order_id; ?>;

    function updateUI() {
        let width = 0;
        let currentElapsed = serverElapsed;

        // Nếu đang ở trạng thái Đang Giao, cộng thêm thời gian trình duyệt đã chạy
        if (dbStatus === 2) {
            currentElapsed += (Date.now() - pageLoadTime) / 1000;
        }

        if (dbStatus === 4) width = 100;
        else if (dbStatus === 0) width = 0;
        else if (dbStatus === 1) width = 33.33;
        else if (dbStatus === 2) {
            let progress = currentElapsed / AUTO_GIAO_TIME;
            if (progress < 0) progress = 0;
            if (progress > 1) progress = 1;
            width = 66.66 + (progress * 33.34);
        } else if (dbStatus === 3) width = 100;

        let progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            progressBar.style.width = width + '%';
            progressBar.style.backgroundColor = (dbStatus === 4) ? '#ef4444' : '';
        }

        // Đổi màu icon
        for (let i = 0; i <= 3; i++) {
            let icon = document.getElementById('icon-step-' + i);
            let text = document.getElementById('text-step-' + i);
            if (!icon || !text) continue;

            if (dbStatus === 4) {
                icon.className =
                    "w-12 h-12 rounded-full flex items-center justify-center font-bold bg-red-100 text-red-500 border-2 border-red-200";
                text.className = "text-xs sm:text-sm font-bold mt-3 text-center text-red-500 line-through";
            } else if (i < dbStatus) {
                icon.className =
                    "w-12 h-12 rounded-full flex items-center justify-center font-bold transition-all duration-500 bg-blue-600 text-white shadow-md shadow-blue-200";
                text.className = "text-xs sm:text-sm font-bold mt-3 text-center text-blue-600";
            } else if (i === dbStatus) {
                if (i === 3) {
                    icon.className =
                        "w-12 h-12 rounded-full flex items-center justify-center font-bold transition-all duration-500 bg-green-500 text-white shadow-md shadow-green-200 animate-bounce scale-110";
                    text.className = "text-xs sm:text-sm font-bold mt-3 text-center text-green-600";
                } else {
                    icon.className =
                        "w-12 h-12 rounded-full flex items-center justify-center font-bold transition-all duration-500 bg-blue-600 text-white shadow-lg shadow-blue-300 ring-4 ring-blue-200 animate-pulse scale-110";
                    text.className = "text-xs sm:text-sm font-bold mt-3 text-center text-blue-600";
                }
            } else {
                icon.className =
                    "w-12 h-12 rounded-full flex items-center justify-center font-bold transition-all duration-500 bg-gray-200 text-gray-400 border-2 border-white";
                text.className = "text-xs sm:text-sm font-bold mt-3 text-center text-gray-400";
            }
        }

        // Đổi thông báo
        let msgBox = document.getElementById('status-message');
        if (msgBox) {
            if (dbStatus === 4) {
                msgBox.innerHTML = '<i class="fas fa-times-circle"></i> Đơn hàng này đã bị hủy!';
                msgBox.className =
                    "bg-red-50 border border-red-100 text-red-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 font-bold shadow-inner";
            } else if (dbStatus === 0) {
                msgBox.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> Đang chờ hệ thống xác nhận đơn hàng...';
                msgBox.className =
                    "bg-yellow-50 border border-yellow-100 text-yellow-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 shadow-inner";
            } else if (dbStatus === 1) {
                msgBox.innerHTML = '<i class="fas fa-box"></i> Đơn hàng đã đóng gói. Đang chờ bàn giao...';
                msgBox.className =
                    "bg-indigo-50 border border-indigo-100 text-indigo-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 shadow-inner";
            } else if (dbStatus === 2) {
                msgBox.innerHTML =
                    '<i class="fas fa-truck-fast fa-bounce"></i> Đơn hàng đang được giao. Vui lòng giữ điện thoại...';
                msgBox.className =
                    "bg-blue-50 border border-blue-100 text-blue-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 shadow-inner";
            } else if (dbStatus === 3) {
                msgBox.innerHTML =
                    '<i class="fas fa-check-circle animate-bounce"></i> Đơn hàng đã được giao thành công!';
                msgBox.className =
                    "bg-green-50 border border-green-100 text-green-600 text-sm p-3 rounded-lg text-center flex justify-center items-center gap-2 font-bold shadow-inner";
            }
        }
    }

    // Timer tự động update thanh chạy & chốt đơn
    setInterval(() => {
        if (dbStatus === 2) {
            let currentElapsed = serverElapsed + (Date.now() - pageLoadTime) / 1000;
            if (currentElapsed >= AUTO_GIAO_TIME) {
                fetch(`chitietdonhang.php?id=${orderId}&ajax_update=3`).then(() => {
                    dbStatus = 3;
                    updateUI();
                });
            }
        }
        updateUI();
    }, 100);

    // Lắng nghe Admin update trạng thái (Polling)
    setInterval(() => {
        if (dbStatus < 2) {
            fetch(`chitietdonhang.php?id=${orderId}&ajax_get_status=1`)
                .then(res => res.text())
                .then(data => {
                    let newStatus = parseInt(data);
                    if (!isNaN(newStatus) && newStatus > dbStatus) {
                        location
                            .reload(); // Phải reload lại trang để lấy mốc thời gian chuẩn từ Database
                    }
                }).catch(err => console.log('Chờ server...'));
        }
    }, 3000);

    updateUI();
});
</script>
<?php include 'footer.php'; ?>