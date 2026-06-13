<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

$success_msg = '';
$error_msg = '';

// ===== XÓA BANNER =====
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Xóa file ảnh upload nếu có
    $res_del = $db->select("SELECT image_file FROM banners WHERE id = $del_id");
    if ($res_del && $row_del = $res_del->fetch_assoc()) {
        if (!empty($row_del['image_file']) && file_exists("public/images/" . $row_del['image_file'])) {
            unlink("public/images/" . $row_del['image_file']);
        }
    }
    $db->execute("DELETE FROM banners WHERE id = $del_id");
    header("Location: admin_banner.php?msg=deleted");
    exit();
}

// ===== BẬT/TẮT BANNER =====
if (isset($_GET['toggle'])) {
    $tog_id = intval($_GET['toggle']);
    $db->execute("UPDATE banners SET is_active = 1 - is_active WHERE id = $tog_id");
    header("Location: admin_banner.php?msg=toggled");
    exit();
}

// ===== THÊM / SỬA BANNER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id   = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title     = $db->conn->real_escape_string($_POST['title']);
    $desc      = $db->conn->real_escape_string($_POST['description']);
    $btn_text  = $db->conn->real_escape_string($_POST['btn_text']);
    $btn_link  = $db->conn->real_escape_string($_POST['btn_link']);
    $image_url = $db->conn->real_escape_string($_POST['image_url']);
    $sort      = intval($_POST['sort_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Xử lý upload ảnh
    $image_file = $_POST['current_image_file'] ?? '';
    if (isset($_FILES['image_file_upload']) && !empty($_FILES['image_file_upload']['name'])) {
        $ext = pathinfo($_FILES['image_file_upload']['name'], PATHINFO_EXTENSION);
        $new_name = 'banner_' . time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image_file_upload']['tmp_name'], "public/images/" . $new_name)) {
            // Xóa ảnh cũ nếu có
            if (!empty($image_file) && file_exists("public/images/" . $image_file)) {
                unlink("public/images/" . $image_file);
            }
            $image_file = $new_name;
        }
    }
    $image_file_esc = $db->conn->real_escape_string($image_file);

    if ($edit_id > 0) {
        $sql = "UPDATE banners SET 
                title='$title', description='$desc', btn_text='$btn_text', btn_link='$btn_link',
                image_url='$image_url', image_file='$image_file_esc',
                sort_order=$sort, is_active=$is_active
                WHERE id=$edit_id";
    } else {
        $sql = "INSERT INTO banners (title, description, btn_text, btn_link, image_url, image_file, sort_order, is_active)
                VALUES ('$title','$desc','$btn_text','$btn_link','$image_url','$image_file_esc',$sort,$is_active)";
    }
    $db->execute($sql);
    header("Location: admin_banner.php?msg=" . ($edit_id > 0 ? 'updated' : 'added'));
    exit();
}

// ===== LẤY DANH SÁCH =====
$banners_res = $db->select("SELECT * FROM banners ORDER BY sort_order ASC, id ASC");
$banners = [];
if ($banners_res) {
    while ($b = $banners_res->fetch_assoc()) $banners[] = $b;
}

// ===== THÔNG BÁO =====
$msgs = [
    'added'   => ['🎉 Thêm banner thành công!', 'green'],
    'updated' => ['✅ Cập nhật banner thành công!', 'blue'],
    'deleted' => ['🗑️ Đã xóa banner!', 'red'],
    'toggled' => ['🔄 Đã thay đổi trạng thái banner!', 'yellow'],
];
$flash = isset($_GET['msg'], $msgs[$_GET['msg']]) ? $msgs[$_GET['msg']] : null;
?>
<?php include 'admin_header.php'; ?>

<?php if ($flash): ?>
<div id="flash-msg" class="fixed top-6 right-6 z-[9999] px-6 py-4 rounded-2xl shadow-2xl bg-<?php echo $flash[1]; ?>-50 border border-<?php echo $flash[1]; ?>-200 text-<?php echo $flash[1]; ?>-700 font-bold text-sm flex items-center gap-3">
    <i class="fas fa-check-circle text-<?php echo $flash[1]; ?>-500 text-xl"></i>
    <?php echo htmlspecialchars($flash[0]); ?>
</div>
<script>setTimeout(() => { const el = document.getElementById('flash-msg'); if(el) el.remove(); }, 3000);</script>
<?php endif; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-images text-blue-600"></i> Quản lý Banner
        </h2>
        <button onclick="openAddModal()"
            class="bg-blue-600 text-white px-6 py-2.5 rounded-xl hover:bg-blue-700 transition font-bold shadow-lg shadow-blue-200 flex items-center gap-2">
            <i class="fas fa-plus"></i> Thêm Banner
        </button>
    </div>

    <!-- Danh sách banner dạng grid -->
    <?php if (empty($banners)): ?>
    <div class="text-center text-gray-400 py-16">
        <i class="fas fa-images text-6xl mb-4 text-gray-200"></i>
        <p class="text-lg">Chưa có banner nào. Bấm "Thêm Banner" để bắt đầu.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <?php foreach ($banners as $b):
            $img = !empty($b['image_file']) ? 'public/images/' . $b['image_file'] : $b['image_url'];
        ?>
        <div class="group relative bg-white rounded-2xl border <?php echo $b['is_active'] ? 'border-gray-200' : 'border-gray-200 opacity-60'; ?> overflow-hidden shadow-sm hover:shadow-xl transition-shadow duration-300">
            <!-- Ảnh preview -->
            <div class="relative aspect-[16/7] overflow-hidden bg-gray-100">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    onerror="this.src='https://via.placeholder.com/800x350?text=No+Image'">
                <!-- Badge thứ tự -->
                <span class="absolute top-3 left-3 bg-black/60 text-white text-xs font-bold px-2.5 py-1 rounded-full backdrop-blur-sm">
                    #<?php echo $b['sort_order']; ?>
                </span>
                <!-- Badge trạng thái -->
                <span class="absolute top-3 right-3 <?php echo $b['is_active'] ? 'bg-green-500' : 'bg-gray-500'; ?> text-white text-xs font-bold px-2.5 py-1 rounded-full">
                    <?php echo $b['is_active'] ? 'Đang hiển thị' : 'Ẩn'; ?>
                </span>
            </div>

            <!-- Nội dung -->
            <div class="p-4">
                <h3 class="font-black text-gray-900 text-base mb-1 line-clamp-1"><?php echo htmlspecialchars($b['title']); ?></h3>
                <p class="text-gray-500 text-sm mb-2 line-clamp-1"><?php echo htmlspecialchars($b['description']); ?></p>
                <div class="flex items-center gap-2 text-xs text-gray-400 mb-4">
                    <i class="fas fa-link"></i>
                    <span class="truncate"><?php echo htmlspecialchars($b['btn_link']); ?></span>
                    <span class="ml-auto shrink-0 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg px-2 py-0.5 font-bold">
                        <?php echo htmlspecialchars($b['btn_text']); ?>
                    </span>
                </div>

                <!-- Thao tác -->
                <div class="flex gap-2">
                    <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)'
                        class="flex-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-xl py-2 font-bold text-sm hover:bg-blue-600 hover:text-white transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <a href="admin_banner.php?toggle=<?php echo $b['id']; ?>"
                        class="flex-1 bg-yellow-50 text-yellow-700 border border-yellow-100 rounded-xl py-2 font-bold text-sm hover:bg-yellow-500 hover:text-white transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-eye<?php echo $b['is_active'] ? '-slash' : ''; ?>"></i>
                        <?php echo $b['is_active'] ? 'Ẩn' : 'Hiện'; ?>
                    </a>
                    <a href="admin_banner.php?delete=<?php echo $b['id']; ?>"
                        onclick="return confirm('Xóa banner này?')"
                        class="w-10 bg-red-50 text-red-500 border border-red-100 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ===== MODAL THÊM/SỬA ===== -->
<div id="bannerModal" class="hidden fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 id="modalTitle" class="text-2xl font-black text-gray-800">Thêm Banner mới</h2>
            <button onclick="closeModal()" class="w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:text-gray-800 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="admin_banner.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="edit_id" id="edit_id" value="0">
            <input type="hidden" name="current_image_file" id="current_image_file" value="">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block font-bold mb-1.5 text-gray-700">Tiêu đề Banner <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="b_title" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                <div class="col-span-2">
                    <label class="block font-bold mb-1.5 text-gray-700">Mô tả phụ</label>
                    <input type="text" name="description" id="b_desc"
                        placeholder="Dòng chữ nhỏ bên dưới tiêu đề"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700">Nút bấm (text)</label>
                    <input type="text" name="btn_text" id="b_btn_text" placeholder="VD: Mua Ngay"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-gray-700">Link nút bấm</label>
                    <input type="text" name="btn_link" id="b_btn_link" placeholder="VD: categories.php hoặc #"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                <div class="col-span-2 border border-blue-100 rounded-xl p-4 bg-blue-50/40 space-y-3">
                    <p class="font-bold text-blue-800 text-sm"><i class="fas fa-image mr-1"></i> Ảnh Banner (chọn 1 trong 2)</p>

                    <div>
                        <label class="block text-sm font-semibold mb-1 text-gray-700">Tải ảnh lên từ máy tính</label>
                        <input type="file" name="image_file_upload" id="b_file" accept="image/*"
                            class="w-full border border-gray-200 p-1.5 rounded-xl bg-white text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <div id="img_file_preview" class="mt-2 hidden">
                            <img id="img_file_preview_el" class="h-24 rounded-xl object-cover border border-gray-200" src="">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <span class="font-bold">HOẶC</span>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1 text-gray-700">URL ảnh từ internet</label>
                        <input type="text" name="image_url" id="b_image_url"
                            placeholder="https://example.com/image.jpg"
                            oninput="previewUrl()"
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-sm">
                        <div id="img_url_preview" class="mt-2 hidden">
                            <img id="img_url_preview_el" class="h-24 rounded-xl object-cover border border-gray-200" src="">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-1.5 text-gray-700">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" id="b_sort" value="0" min="0"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition font-bold">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="is_active" id="b_active" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-blue-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 bg-white w-5 h-5 rounded-full shadow-sm transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="font-bold text-gray-700">Hiển thị banner này</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-4 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white py-3.5 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> Lưu Banner
                </button>
                <button type="button" onclick="closeModal()"
                    class="w-36 bg-gray-100 py-3.5 rounded-xl font-bold hover:bg-gray-200 transition text-gray-700">
                    Hủy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Thêm Banner mới';
    document.getElementById('edit_id').value = '0';
    document.getElementById('current_image_file').value = '';
    document.getElementById('b_title').value = '';
    document.getElementById('b_desc').value = '';
    document.getElementById('b_btn_text').value = '';
    document.getElementById('b_btn_link').value = '';
    document.getElementById('b_image_url').value = '';
    document.getElementById('b_sort').value = '<?php echo count($banners) + 1; ?>';
    document.getElementById('b_active').checked = true;
    document.getElementById('b_file').value = '';
    document.getElementById('img_file_preview').classList.add('hidden');
    document.getElementById('img_url_preview').classList.add('hidden');
    document.getElementById('bannerModal').classList.remove('hidden');
}

function openEditModal(b) {
    document.getElementById('modalTitle').innerText = 'Chỉnh sửa Banner';
    document.getElementById('edit_id').value = b.id;
    document.getElementById('current_image_file').value = b.image_file || '';
    document.getElementById('b_title').value = b.title;
    document.getElementById('b_desc').value = b.description || '';
    document.getElementById('b_btn_text').value = b.btn_text || '';
    document.getElementById('b_btn_link').value = b.btn_link || '';
    document.getElementById('b_image_url').value = b.image_url || '';
    document.getElementById('b_sort').value = b.sort_order;
    document.getElementById('b_active').checked = b.is_active == '1';
    document.getElementById('b_file').value = '';
    
    const urlPreview = document.getElementById('img_url_preview');
    const urlPreviewEl = document.getElementById('img_url_preview_el');
    if (b.image_url) {
        urlPreviewEl.src = b.image_url;
        urlPreview.classList.remove('hidden');
    } else if (b.image_file) {
        urlPreviewEl.src = 'public/images/' + b.image_file;
        urlPreview.classList.remove('hidden');
    } else {
        urlPreview.classList.add('hidden');
    }
    document.getElementById('img_file_preview').classList.add('hidden');
    document.getElementById('bannerModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('bannerModal').classList.add('hidden');
}

function previewUrl() {
    const url = document.getElementById('b_image_url').value;
    const preview = document.getElementById('img_url_preview');
    const img = document.getElementById('img_url_preview_el');
    if (url) {
        img.src = url;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

// Preview ảnh upload
document.getElementById('b_file').addEventListener('change', function() {
    const file = this.files[0];
    const preview = document.getElementById('img_file_preview');
    const img = document.getElementById('img_file_preview_el');
    if (file) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
    }
});

// Đóng modal khi click ngoài
document.getElementById('bannerModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'admin_footer.php'; ?>
