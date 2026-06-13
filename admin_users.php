<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// Xử lý Khóa/Mở khóa tài khoản
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Cột trang_thai trong DB: 0 là Hoạt động, 1 là Bị khóa
    if ($action == 'lock') {
        $db->execute("UPDATE user SET trang_thai = 1 WHERE IdNguoiDung = $id AND quyen = 'user'");
        echo "<script>alert('Đã khóa tài khoản!'); window.location.href='admin_users.php';</script>";
    } elseif ($action == 'unlock') {
        $db->execute("UPDATE user SET trang_thai = 0 WHERE IdNguoiDung = $id");
        echo "<script>alert('Đã mở khóa tài khoản!'); window.location.href='admin_users.php';</script>";
    }
}

// Lấy danh sách user (Không lấy admin)
$sql = "SELECT * FROM user WHERE quyen = 'user' ORDER BY IdNguoiDung DESC";
$result = $db->select($sql);
?>

<?php include 'admin_header.php'; ?>

        <div class="bg-white rounded-xl shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Danh Sách Khách Hàng</h1>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-gray-700 text-sm uppercase">
                        <th class="p-4">ID</th>
                        <th class="p-4">Họ Tên</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Trạng Thái</th>
                        <th class="p-4 text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <?php $trang_thai = isset($row['trang_thai']) ? $row['trang_thai'] : 0; ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-600">#<?php echo $row['IdNguoiDung']; ?></td>
                        <td class="p-4 font-semibold text-gray-800"><?php echo $row['TenNguoiDung']; ?></td>
                        <td class="p-4 text-gray-600"><?php echo $row['email']; ?></td>
                        <td class="p-4">
                            <?php if($trang_thai == 0): ?>
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Đang
                                hoạt động</span>
                            <?php else: ?>
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">Đã bị
                                khóa</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if($trang_thai == 0): ?>
                            <a href="admin_users.php?action=lock&id=<?php echo $row['IdNguoiDung']; ?>"
                                class="text-red-500 hover:text-red-700 font-bold text-sm bg-red-50 p-2 rounded inline-block"
                                onclick="return confirm('Bạn chắc chắn muốn khóa tài khoản này?');">
                                <i class="fas fa-lock"></i> Khóa
                            </a>
                            <?php else: ?>
                            <a href="admin_users.php?action=unlock&id=<?php echo $row['IdNguoiDung']; ?>"
                                class="text-green-500 hover:text-green-700 font-bold text-sm bg-green-50 p-2 rounded inline-block">
                                <i class="fas fa-unlock"></i> Mở khóa
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center p-6 text-gray-500">Chưa có khách hàng nào đăng ký!</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

<?php include 'admin_footer.php'; ?>