<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->execute("DELETE FROM discount_codes WHERE id = $id");
    header("Location: admin_discounts.php");
    exit();
}

// Xử lý Thêm / Sửa
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['discountId']) ? intval($_POST['discountId']) : 0;
    $code = $db->conn->real_escape_string(strtoupper($_POST['code']));
    $type = $db->conn->real_escape_string($_POST['discount_type']);
    $value = intval($_POST['discount_value']);
    $min_order = intval($_POST['min_order_value']);
    $usage_limit = intval($_POST['usage_limit']);
    $start_date = $db->conn->real_escape_string($_POST['start_date']);
    $end_date = $db->conn->real_escape_string($_POST['end_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $sql = "UPDATE discount_codes SET 
                code='$code', discount_type='$type', discount_value=$value, 
                min_order_value=$min_order, usage_limit=$usage_limit, 
                start_date='$start_date', end_date='$end_date', is_active=$is_active 
                WHERE id=$id";
    } else {
        $sql = "INSERT INTO discount_codes (code, discount_type, discount_value, min_order_value, usage_limit, start_date, end_date, is_active) 
                VALUES ('$code', '$type', $value, $min_order, $usage_limit, '$start_date', '$end_date', $is_active)";
    }
    
    if(!$db->execute($sql)){
        echo "<script>alert('Lỗi: Mã giảm giá có thể đã tồn tại!'); window.history.back();</script>";
        exit();
    }
    header("Location: admin_discounts.php");
    exit();
}

// Lấy danh sách
$discounts = $db->select("SELECT * FROM discount_codes ORDER BY id DESC");
?>

<?php include 'admin_header.php'; ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-ticket-alt text-blue-600"></i> Quản lý mã giảm giá
        </h2>
        <button onclick="openAddModal()"
            class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold shadow-lg shadow-green-200 flex items-center gap-2">
            <i class="fas fa-plus"></i> Thêm mã giảm giá
        </button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-4 text-left">Mã Code</th>
                    <th class="p-4 text-left">Giá trị</th>
                    <th class="p-4 text-left">Đơn tối thiểu</th>
                    <th class="p-4 text-left">Lượt dùng</th>
                    <th class="p-4 text-left">Hạn sử dụng</th>
                    <th class="p-4 text-center">Trạng thái</th>
                    <th class="p-4 text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                <?php if ($discounts && $discounts->num_rows > 0): ?>
                <?php while($row = $discounts->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-4 font-mono font-bold text-blue-600 text-base">
                        <?php echo htmlspecialchars($row['code']); ?>
                    </td>
                    <td class="p-4 font-bold text-red-600">
                        <?php 
                        if ($row['discount_type'] == 'percent') echo $row['discount_value'] . '%';
                        else echo number_format($row['discount_value']) . 'đ';
                        ?>
                    </td>
                    <td class="p-4 text-gray-600">
                        <?php echo number_format($row['min_order_value']); ?>đ
                    </td>
                    <td class="p-4 text-gray-600">
                        <?php echo $row['used_count'] . ' / ' . ($row['usage_limit'] > 0 ? $row['usage_limit'] : '∞'); ?>
                    </td>
                    <td class="p-4 text-xs text-gray-500">
                        Từ: <?php echo date('d/m/Y H:i', strtotime($row['start_date'])); ?><br>
                        Đến: <?php echo date('d/m/Y H:i', strtotime($row['end_date'])); ?>
                    </td>
                    <td class="p-4 text-center">
                        <?php if ($row['is_active']): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">Kích hoạt</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">Vô hiệu</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4">
                        <div class="flex justify-center gap-2">
                            <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)'
                                class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition flex items-center justify-center"
                                title="Sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="admin_discounts.php?delete=<?php echo $row['id']; ?>"
                                onclick="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')"
                                class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition flex items-center justify-center"
                                title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-500">Chưa có mã giảm giá nào.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="discountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-8 shadow-2xl overflow-y-auto max-h-[90vh] custom-scrollbar">
        <h2 id="modalTitle" class="text-2xl font-black mb-6 text-gray-800 border-b pb-4">Thêm mã giảm giá mới</h2>
        <form action="admin_discounts.php" method="POST" class="space-y-5">
            <input type="hidden" name="discountId" id="discountId">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Mã Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" required placeholder="VD: SUMMER20"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 uppercase transition">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Loại giảm giá <span class="text-red-500">*</span></label>
                    <select name="discount_type" id="discount_type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                        <option value="fixed">Giảm số tiền cố định (VNĐ)</option>
                        <option value="percent">Giảm theo phần trăm (%)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Giá trị giảm <span class="text-red-500">*</span></label>
                    <input type="number" name="discount_value" id="discount_value" required min="1"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Đơn tối thiểu (VNĐ)</label>
                    <input type="number" name="min_order_value" id="min_order_value" value="0" min="0"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Ngày bắt đầu <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="start_date" id="start_date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Ngày kết thúc <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="end_date" id="end_date" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
                </div>
            </div>

            <div>
                <label class="block font-bold text-gray-700 mb-1.5">Giới hạn số lượt dùng (0 = Không giới hạn)</label>
                <input type="number" name="usage_limit" id="usage_limit" value="0" min="0"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition">
            </div>

            <div class="flex items-center gap-2 mt-4">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-5 h-5 text-blue-600 rounded">
                <label for="is_active" class="font-bold text-gray-700 cursor-pointer">Kích hoạt mã này</label>
            </div>

            <div class="flex gap-4 pt-6 border-t border-gray-100">
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Lưu mã giảm giá
                </button>
                <button type="button" onclick="closeModal()"
                    class="w-1/3 bg-gray-100 py-3 rounded-xl font-bold hover:bg-gray-200 transition text-gray-700 flex items-center justify-center gap-2">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Thêm mã giảm giá mới';
    document.getElementById('discountId').value = '';
    document.getElementById('code').value = '';
    document.getElementById('discount_type').value = 'fixed';
    document.getElementById('discount_value').value = '';
    document.getElementById('min_order_value').value = '0';
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('usage_limit').value = '0';
    document.getElementById('is_active').checked = true;
    document.getElementById('discountModal').classList.remove('hidden');
}

function openEditModal(d) {
    document.getElementById('modalTitle').innerText = 'Chỉnh sửa mã giảm giá';
    document.getElementById('discountId').value = d.id;
    document.getElementById('code').value = d.code;
    document.getElementById('discount_type').value = d.discount_type;
    document.getElementById('discount_value').value = d.discount_value;
    document.getElementById('min_order_value').value = d.min_order_value;
    
    // Format datetime-local (YYYY-MM-DDThh:mm)
    document.getElementById('start_date').value = d.start_date.replace(' ', 'T');
    document.getElementById('end_date').value = d.end_date.replace(' ', 'T');
    
    document.getElementById('usage_limit').value = d.usage_limit;
    document.getElementById('is_active').checked = (d.is_active == 1);
    document.getElementById('discountModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('discountModal').classList.add('hidden');
}
</script>

<?php include 'admin_footer.php'; ?>
