<?php
// 1. kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// 2. xử lý xóa danh mục
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Kiểm tra xem danh mục có chứa sản phẩm nào không
    $check_products = $db->select("SELECT COUNT(*) as total FROM product WHERE MaDanhMuc = $id");
    $has_products = false;
    if ($check_products && $row = $check_products->fetch_assoc()) {
        if ($row['total'] > 0) {
            $has_products = true;
        }
    }
    
    if ($has_products) {
        echo "<script>alert('Không thể xóa danh mục này vì đang có sản phẩm thuộc danh mục!'); window.location.href='admin_categories.php';</script>";
        exit();
    } else {
        $db->execute("DELETE FROM categories WHERE MaDanhMuc = $id");
        header("Location: admin_categories.php");
        exit();
    }
}

// 3. xử lý thêm/sửa danh mục
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['catId']) ? intval($_POST['catId']) : 0;
    $ten = $db->conn->real_escape_string($_POST['catName']);
    $mota = $db->conn->real_escape_string($_POST['catDescription']);

    if ($id > 0) {
        // cập nhật danh mục đã có
        $sql = "UPDATE categories SET TenDanhMuc='$ten', MoTa='$mota' WHERE MaDanhMuc=$id";
    } else {
        // thêm danh mục mới
        $sql = "INSERT INTO categories (TenDanhMuc, MoTa) VALUES ('$ten', '$mota')";
    }
    
    $db->execute($sql);
    header("Location: admin_categories.php");
    exit();
}

// 4. lấy danh sách danh mục
$categories = $db->select("SELECT * FROM categories ORDER BY MaDanhMuc DESC");
?>

<?php include 'admin_header.php'; ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list-alt text-blue-600"></i> Danh sách danh mục
                    </h2>
                    <button onclick="openAddModal()"
                        class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold shadow-lg shadow-green-200 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Thêm danh mục
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead
                            class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="p-4 text-left w-20">Mã</th>
                                <th class="p-4 text-left w-64">Tên danh mục</th>
                                <th class="p-4 text-left">Mô tả</th>
                                <th class="p-4 text-center w-32">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while($row = $categories->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 font-mono text-gray-500 font-bold">#<?php echo $row['MaDanhMuc']; ?></td>
                                <td class="p-4 font-bold text-blue-600 text-base">
                                    <?php echo htmlspecialchars($row['TenDanhMuc']); ?></td>
                                <td class="p-4 text-gray-600 italic leading-relaxed">
                                    <?php echo htmlspecialchars($row['MoTa'] ?? 'Chưa có mô tả'); ?>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)'
                                            class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition flex items-center justify-center"
                                            title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="admin_categories.php?delete=<?php echo $row['MaDanhMuc']; ?>"
                                            onclick="return confirm('Lưu ý: Nếu xóa danh mục có chứa sản phẩm, bạn phải xóa sản phẩm trước. Bạn vẫn muốn tiếp tục xóa?')"
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
                                <td colspan="4" class="p-6 text-center text-gray-500">Chưa có danh mục nào.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    <div id="catModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl max-w-lg w-full p-8 shadow-2xl">
            <h2 id="modalTitle" class="text-2xl font-black mb-6 text-gray-800 border-b pb-4">Thêm danh mục mới</h2>
            <form action="admin_categories.php" method="POST" class="space-y-5">
                <input type="hidden" name="catId" id="catId">

                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Tên danh mục <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="catName" id="catName" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Mô tả danh mục</label>
                    <textarea name="catDescription" id="catDescription" rows="4" placeholder="Viết mô tả ngắn gọn..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition leading-relaxed"></textarea>
                </div>

                <div class="flex gap-4 pt-6 border-t border-gray-100">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Lưu danh mục
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
        document.getElementById('modalTitle').innerText = 'Thêm danh mục mới';
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catDescription').value = '';
        document.getElementById('catModal').classList.remove('hidden');
    }

    function openEditModal(cat) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa danh mục';
        document.getElementById('catId').value = cat.MaDanhMuc;
        document.getElementById('catName').value = cat.TenDanhMuc;
        document.getElementById('catDescription').value = cat.MoTa;
        document.getElementById('catModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('catModal').classList.add('hidden');
    }
    </script>
<?php include 'admin_footer.php'; ?>