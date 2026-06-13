<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// Cập nhật trạng thái
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $db->conn->real_escape_string($_POST['status']);
    
    // Nếu Admin chuyển sang trạng thái 2 (Đang giao), lưu luôn thời gian hiện tại
    $oldRes = $db->select("SELECT trangThai FROM donhang WHERE MaDonHang = $order_id LIMIT 1");
    $oldStatus = ($oldRes && $oldRes->num_rows > 0) ? (int)$oldRes->fetch_assoc()['trangThai'] : null;

    if ($new_status == '2') {
        $db->execute("UPDATE donhang SET trangThai = '$new_status', thoi_gian_giao = NOW() WHERE MaDonHang = $order_id");
    } else {
        $db->execute("UPDATE donhang SET trangThai = '$new_status' WHERE MaDonHang = $order_id");
    }

    // Gửi mail khi Admin hủy đơn (-> 4)
    if ((string)$new_status === '4' && (string)$oldStatus !== '4') {
        require_once __DIR__ . '/_mail_order_notify.php';
        order_notify_send((int)$order_id, 'order_cancel');
    }

    // Gửi mail khi Admin xác nhận đã giao thành công (-> 3)
    if ((string)$new_status === '3' && (string)$oldStatus !== '3') {
        require_once __DIR__ . '/_mail_order_notify.php';
        order_notify_send((int)$order_id, 'order_success');
    }

    echo "<script>alert('Cập nhật trạng thái thành công!'); window.location.href='admin_orders.php';</script>";
}

// Lấy danh sách đơn hàng
$sql = "SELECT donhang.*, user.TenNguoiDung FROM donhang 
        JOIN user ON donhang.IdNguoiDung = user.IdNguoiDung 
        ORDER BY MaDonHang DESC";
$result = $db->select($sql);
?>

<?php include 'admin_header.php'; ?>
<div class="bg-white rounded-xl shadow-md p-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Quản Lý Đơn Hàng</h1>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-blue-50 text-blue-800 border-b-2 border-blue-200">
                <th class="p-4 font-semibold">Mã ĐH</th>
                <th class="p-4 font-semibold">Khách Hàng</th>
                <th class="p-4 font-semibold">Ngày Đặt</th>
                <th class="p-4 font-semibold">Tổng Tiền</th>
                <th class="p-4 font-semibold text-center">Trạng Thái</th>
                <th class="p-4 font-semibold text-center">Thao Tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50 transition">
                <td class="p-4 font-bold text-gray-800">#<?php echo $row['MaDonHang']; ?></td>
                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($row['TenNguoiDung']); ?></td>
                <td class="p-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($row['NgayDat'])); ?></td>
                <td class="p-4 font-bold text-red-600"><?php echo number_format($row['TongTien']); ?>đ</td>
                <td class="p-4 text-center">
                    <?php 
                                $st = intval($row['trangThai']);
                                if ($st == 0) echo '<span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold">Chờ xác nhận</span>';
                                elseif ($st == 1) echo '<span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-bold">Đã đóng gói</span>';
                                elseif ($st == 2) echo '<span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-bold">Đang giao</span>';
                                elseif ($st == 3) echo '<span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold">Hoàn thành</span>';
                                elseif ($st == 4) echo '<span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold">Đã hủy</span>';
                            ?>
                </td>
                <td class="p-4 text-center">
                    <?php if ($row['trangThai'] == '0' || $row['trangThai'] == '1'): ?>
                    <form method="POST" class="flex items-center justify-center gap-2">
                        <input type="hidden" name="order_id" value="<?php echo $row['MaDonHang']; ?>">
                        <select name="status"
                            class="border rounded-lg p-1.5 text-sm outline-none focus:ring-2 focus:ring-blue-500">
                            <?php if ($row['trangThai'] == '0'): ?>
                            <option value="0" selected>Chờ xác nhận</option>
                            <option value="1">Xác nhận - Đã đóng gói</option>
                            <option value="4">Hủy đơn hàng</option>
                            <?php elseif ($row['trangThai'] == '1'): ?>
                            <option value="1" selected>Đã đóng gói</option>
                            <option value="2">Giao cho Vận chuyển</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" name="update_status"
                            class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition" title="Cập nhật">
                            <i class="fas fa-save"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="text-sm text-gray-500 font-medium bg-gray-100 p-2 rounded-lg">
                        <?php 
                if ($row['trangThai'] == '2') echo '<i class="fas fa-robot text-blue-500 mr-1"></i> Auto-Giao hàng';
                elseif ($row['trangThai'] == '3') echo '<i class="fas fa-lock text-green-500 mr-1"></i> Đã chốt đơn';
                elseif ($row['trangThai'] == '4') echo '<i class="fas fa-ban text-red-500 mr-1"></i> Đã hủy';
            ?>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
                <td colspan="6" class="text-center p-6 text-gray-500">Chưa có đơn hàng nào!</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include 'admin_footer.php'; ?>