<?php
session_start();
require_once "database.php";
$db = new Database();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Tự động quét và chốt TẤT CẢ các đơn hàng đã giao quá 5 phút (300 giây)
$db->execute("UPDATE donhang SET trangThai = 3 WHERE trangThai = 2 AND thoi_gian_giao IS NOT NULL AND TIMESTAMPDIFF(SECOND, thoi_gian_giao, NOW()) >= 300");

// Lệnh cũ lấy danh sách đơn hàng
$sql = "SELECT * FROM donhang WHERE IdNguoiDung = $user_id ORDER BY MaDonHang DESC";
$result = $db->select($sql);

$page_title = "Lịch sử đơn hàng - THPSHOP";
include 'header.php';
?>
<div class="container mx-auto px-4 py-8 max-w-5xl mt-8">
    <h1 class="text-3xl font-black mb-8 uppercase text-gray-800 border-l-4 border-blue-600 pl-4">Lịch sử đơn hàng</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 border-b border-gray-100">
                    <th class="p-4 font-bold">Mã ĐH</th>
                    <th class="p-4 font-bold">Ngày Đặt</th>
                    <th class="p-4 font-bold">Tổng Tiền</th>
                    <th class="p-4 font-bold">Trạng Thái</th>
                    <th class="p-4 font-bold text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                        $st = intval($row['trangThai']);
                        $status_color = "";
                        $status_text = "";
                        
                        if ($st == 0) { $status_text = "Chờ xác nhận"; $status_color = "bg-yellow-100 text-yellow-800"; }
                        elseif ($st == 1) { $status_text = "Đã đóng gói"; $status_color = "bg-indigo-100 text-indigo-800"; }
                        elseif ($st == 2) { $status_text = "Đang giao"; $status_color = "bg-blue-100 text-blue-800"; }
                        elseif ($st == 3) { $status_text = "Giao thành công"; $status_color = "bg-green-100 text-green-800"; }
                        elseif ($st == 4) { $status_text = "Đã hủy"; $status_color = "bg-red-100 text-red-800"; }
                    ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="p-4 font-bold text-blue-600">
                        #THP-<?php echo str_pad($row['MaDonHang'], 6, '0', STR_PAD_LEFT); ?></td>
                    <td class="p-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($row['NgayDat'])); ?></td>
                    <td class="p-4 text-red-600 font-bold"><?php echo number_format($row['TongTien']); ?>đ</td>
                    <td class="p-4">
                        <span
                            class="px-3 py-1.5 rounded-lg text-xs font-bold <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                    </td>
                    <td class="p-4 text-center">
                        <a href="chitietdonhang.php?id=<?php echo $row['MaDonHang']; ?>"
                            class="bg-blue-50 text-blue-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-600 hover:text-white transition">Theo
                            dõi đơn</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center p-10 text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i><br>
                        Bạn chưa có đơn hàng nào.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include 'footer.php'; ?>