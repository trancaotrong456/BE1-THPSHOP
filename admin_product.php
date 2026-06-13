<?php
// 1. kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// 2. xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Kiểm tra xem sản phẩm có nằm trong đơn hàng nào không
    $check_orders = $db->select("SELECT COUNT(*) as total FROM chitietdonhang WHERE MaSanPham = $id");
    $has_orders = false;
    if ($check_orders && $row = $check_orders->fetch_assoc()) {
        if ($row['total'] > 0) {
            $has_orders = true;
        }
    }
    
    if ($has_orders) {
        echo "<script>alert('Không thể xóa sản phẩm này vì đã có người đặt mua! Bạn chỉ nên ẩn nó đi.'); window.location.href='admin_product.php';</script>";
        exit();
    } else {
        // Xóa khỏi giỏ hàng nếu có
        $db->execute("DELETE FROM cart WHERE MaSanPham = $id");
        // Xóa sản phẩm
        $db->execute("DELETE FROM product WHERE MaSanPham = $id");
        header("Location: admin_product.php");
        exit();
    }
}

// 3. xử lý thêm/sửa sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['productId']) ? intval($_POST['productId']) : 0;
    
    // Sử dụng real_escape_string để tránh lỗi khi tên/mô tả có chứa dấu nháy đơn (')
    $ten = $db->conn->real_escape_string($_POST['productName']);
    $gia = intval($_POST['productPrice']);
    $mota = $db->conn->real_escape_string($_POST['productDescription']);
    $danhmuc = intval($_POST['productCategory']);
    
    // Lấy tên các màu sắc và size
    $mau1 = $db->conn->real_escape_string($_POST['mau1']);
    $mau2 = $db->conn->real_escape_string($_POST['mau2']);
    $mau3 = $db->conn->real_escape_string($_POST['mau3']);
    $size = isset($_POST['productSize']) ? $db->conn->real_escape_string($_POST['productSize']) : '';
    $soluong = isset($_POST['productStock']) ? intval($_POST['productStock']) : 0;

    // --- XỬ LÝ HÌNH ẢNH 1 (Ảnh chính & Màu 1) ---
    $hinh = $_POST['currentImage']; 
    if (isset($_FILES['productImage']) && !empty($_FILES['productImage']['name'])) {
        $hinh = time() . '_1_' . $_FILES['productImage']['name'];
        move_uploaded_file($_FILES['productImage']['tmp_name'], "public/images/" . $hinh);
    } elseif ($id == 0 && empty($hinh)) {
        $hinh = 'default.png'; 
    }

    // --- XỬ LÝ HÌNH ẢNH 2 (Màu 2) ---
    $hinh2 = $_POST['currentImage2']; 
    if (isset($_FILES['productImage2']) && !empty($_FILES['productImage2']['name'])) {
        $hinh2 = time() . '_2_' . $_FILES['productImage2']['name'];
        move_uploaded_file($_FILES['productImage2']['tmp_name'], "public/images/" . $hinh2);
    }

    // --- XỬ LÝ HÌNH ẢNH 3 (Màu 3) ---
    $hinh3 = $_POST['currentImage3']; 
    if (isset($_FILES['productImage3']) && !empty($_FILES['productImage3']['name'])) {
        $hinh3 = time() . '_3_' . $_FILES['productImage3']['name'];
        move_uploaded_file($_FILES['productImage3']['tmp_name'], "public/images/" . $hinh3);
    }

    if ($id > 0) {
        // cập nhật
        $sql = "UPDATE product SET 
                TenSanPham='$ten', GiaSanPham='$gia', MoTa='$mota', MaDanhMuc='$danhmuc',
                hinh='$hinh', hinh2='$hinh2', hinh3='$hinh3',
                mau1='$mau1', mau2='$mau2', mau3='$mau3', size='$size', SoLuong=$soluong
                WHERE MaSanPham=$id";
    } else {
        // thêm mới
        $sql = "INSERT INTO product (TenSanPham, GiaSanPham, MoTa, MaDanhMuc, hinh, hinh2, hinh3, mau1, mau2, mau3, size, SoLuong) 
                VALUES ('$ten', '$gia', '$mota', '$danhmuc', '$hinh', '$hinh2', '$hinh3', '$mau1', '$mau2', '$mau3', '$size', $soluong)";
    }
    
    $db->execute($sql);
    header("Location: admin_product.php");
    exit();
}

// 4. lấy danh sách sản phẩm và danh mục
$products = $db->select("SELECT p.*, c.TenDanhMuc FROM product p JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc ORDER BY MaSanPham DESC");
$categories = $db->select("SELECT * FROM categories");
$categories_list = [];
if ($categories) {
    while($cat = $categories->fetch_assoc()) { 
        $categories_list[] = $cat; 
    }
}
?>

<?php include 'admin_header.php'; ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-box text-blue-600"></i> Danh sách sản phẩm
                    </h2>
                    <button onclick="openAddModal()"
                        class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition font-bold shadow-lg shadow-green-200 flex items-center gap-2">
                        <i class="fas fa-plus"></i> Thêm sản phẩm
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead
                            class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs font-bold tracking-wider">
                            <tr>
                                <th class="p-4 text-left w-16">ID</th>
                                <th class="p-4 text-left w-24">Hình</th>
                                <th class="p-4 text-left">Tên sản phẩm</th>
                                <th class="p-4 text-left">Danh mục</th>
                                <th class="p-4 text-left">Giá</th>
                                <th class="p-4 text-center">Tồn kho</th>
                                <th class="p-4 text-center w-28">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if($products): while($row = $products->fetch_assoc()): ?>
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-500">#<?php echo $row['MaSanPham']; ?></td>
                                <td class="p-4">
                                    <img src="public/images/<?php echo $row['hinh']; ?>"
                                        class="w-14 h-14 object-cover rounded-lg border border-gray-200 shadow-sm">
                                </td>
                                <td class="p-4 font-bold text-gray-800"><?php echo $row['TenSanPham']; ?></td>
                                <td class="p-4">
                                    <span
                                        class="px-3 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-full text-xs font-bold">
                                        <?php echo $row['TenDanhMuc']; ?>
                                    </span>
                                </td>
                                <td class="p-4 font-bold text-red-600 text-base">
                                    <?php echo number_format($row['GiaSanPham']); ?>đ
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 <?php echo $row['SoLuong'] > 0 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border rounded-lg font-bold text-xs">
                                        <?php echo $row['SoLuong']; ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)'
                                            class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-600 hover:text-white transition flex items-center justify-center"
                                            title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="admin_product.php?delete=<?php echo $row['MaSanPham']; ?>"
                                            onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')"
                                            class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition flex items-center justify-center"
                                            title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

    <div id="productModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-8 max-h-[90vh] overflow-y-auto">
            <h2 id="modalTitle" class="text-2xl font-black mb-6 text-gray-800 border-b pb-4">Thêm sản phẩm mới</h2>
            <form action="admin_product.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="productId" id="productId" value="0">
                <input type="hidden" name="currentImage" id="currentImage" value="">
                <input type="hidden" name="currentImage2" id="currentImage2" value="">
                <input type="hidden" name="currentImage3" id="currentImage3" value="">

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700">Tên sản phẩm <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="productName" id="productName" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700">Danh mục <span
                                class="text-red-500">*</span></label>
                        <select name="productCategory" id="productCategory" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer">
                            <?php foreach($categories_list as $cat): ?>
                            <option value="<?php echo $cat['MaDanhMuc']; ?>"><?php echo $cat['TenDanhMuc']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700">Giá (VNĐ) <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="productPrice" id="productPrice" required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-bold text-red-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700">Kích cỡ (Size)</label>
                        <input type="text" name="productSize" id="productSize"
                            placeholder="VD: S, M, L hoặc 36, 37..."
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-gray-700">Số lượng tồn kho <span class="text-red-500">*</span></label>
                        <input type="number" name="productStock" id="productStock" required value="0" min="0"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-bold text-gray-800">
                    </div>
                </div>

                <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                    <h3 class="font-bold text-blue-800 mb-2"><i class="fas fa-palette mr-2"></i>Tùy chọn Màu sắc & Hình
                        ảnh</h3>
                    <p class="text-xs text-blue-600 mb-4 opacity-80">* Để trống màu 2 và 3 nếu sản phẩm chỉ có 1 màu.
                    </p>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Tên Màu 1 (Mặc định)</label>
                                <input type="text" name="mau1" id="mau1" placeholder="VD: Trắng"
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Ảnh Màu 1 (Ảnh chính) <span
                                        class="text-red-500">*</span></label>
                                <input type="file" name="productImage" id="productImage"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-sm focus:outline-none focus:border-blue-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Tên Màu 2</label>
                                <input type="text" name="mau2" id="mau2" placeholder="VD: Đen"
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Ảnh Màu 2</label>
                                <input type="file" name="productImage2" id="productImage2"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-sm focus:outline-none focus:border-blue-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded-lg border border-gray-200 shadow-sm">
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Tên Màu 3</label>
                                <input type="text" name="mau3" id="mau3" placeholder="VD: Xanh"
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-1 text-gray-700">Ảnh Màu 3</label>
                                <input type="file" name="productImage3" id="productImage3"
                                    class="w-full border border-gray-200 p-1.5 rounded-lg bg-gray-50 text-sm focus:outline-none focus:border-blue-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>
                        </div>
                        <p id="imageHelper"
                            class="text-xs text-orange-500 mt-2 hidden text-center font-semibold bg-orange-50 py-1 rounded">
                            <i class="fas fa-info-circle mr-1"></i> Đang chỉnh sửa: Bỏ trống ô tải ảnh nếu muốn giữ
                            nguyên ảnh cũ.
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700">Mô tả sản phẩm</label>
                    <textarea name="productDescription" id="productDescription" rows="4"
                        placeholder="Nhập mô tả chi tiết sản phẩm..."
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition leading-relaxed"></textarea>
                </div>

                <div class="flex gap-4 pt-6 border-t border-gray-100">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-3.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Lưu thông tin
                    </button>
                    <button type="button" onclick="closeModal()"
                        class="w-1/3 bg-gray-100 py-3.5 rounded-xl font-bold hover:bg-gray-200 transition text-gray-700 flex items-center justify-center gap-2">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = 'Thêm sản phẩm mới';
        document.getElementById('productId').value = '0';

        document.getElementById('currentImage').value = '';
        document.getElementById('currentImage2').value = '';
        document.getElementById('currentImage3').value = '';

        document.getElementById('productName').value = '';
        document.getElementById('productPrice').value = '';
        document.getElementById('productDescription').value = '';
        document.getElementById('productSize').value = '';
        document.getElementById('productStock').value = '0';
        document.getElementById('mau1').value = '';
        document.getElementById('mau2').value = '';
        document.getElementById('mau3').value = '';

        document.getElementById('productImage').value = '';
        document.getElementById('productImage2').value = '';
        document.getElementById('productImage3').value = '';
        document.getElementById('imageHelper').classList.add('hidden');

        if (document.getElementById('productCategory').options.length > 0) {
            document.getElementById('productCategory').selectedIndex = 0;
        }

        document.getElementById('productModal').classList.remove('hidden');
    }

    function openEditModal(product) {
        document.getElementById('modalTitle').innerText = 'Chỉnh sửa sản phẩm';
        document.getElementById('productId').value = product.MaSanPham;

        document.getElementById('currentImage').value = product.hinh || '';
        document.getElementById('currentImage2').value = product.hinh2 || '';
        document.getElementById('currentImage3').value = product.hinh3 || '';

        document.getElementById('productName').value = product.TenSanPham;
        document.getElementById('productPrice').value = product.GiaSanPham;
        document.getElementById('productCategory').value = product.MaDanhMuc;
        document.getElementById('productDescription').value = product.MoTa;

        document.getElementById('productSize').value = product.size || '';
        document.getElementById('productStock').value = product.SoLuong || '0';

        document.getElementById('mau1').value = product.mau1 || '';
        document.getElementById('mau2').value = product.mau2 || '';
        document.getElementById('mau3').value = product.mau3 || '';

        document.getElementById('productImage').value = '';
        document.getElementById('productImage2').value = '';
        document.getElementById('productImage3').value = '';
        document.getElementById('imageHelper').classList.remove('hidden');

        document.getElementById('productModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('productModal').classList.add('hidden');
    }
    </script>
<?php include 'admin_footer.php'; ?>