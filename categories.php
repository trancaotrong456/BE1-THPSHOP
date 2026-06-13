<?php
session_start();
require_once "database.php";
$db = new Database();

$MaDanhMuc = isset($_GET['MaDanhMuc']) ? intval($_GET['MaDanhMuc']) : 0;

// Lấy tên danh mục hiện tại
$sql_cate = "SELECT TenDanhMuc FROM categories WHERE MaDanhMuc = $MaDanhMuc";
$res_cate = $db->select($sql_cate);
if ($res_cate && $res_cate->num_rows > 0) {
    $row_cate = $res_cate->fetch_assoc();
    $TenDanhMuc = $row_cate['TenDanhMuc'];
} else {
    $TenDanhMuc = "Tất cả sản phẩm";
}

// Thiết lập phân trang và bộ lọc
$perPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

$sort = isset($_GET['sort']) ? $_GET['sort'] : 'new';
$price_min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? floatval($_GET['price_min']) : null;
$price_max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? floatval($_GET['price_max']) : null;

$orderBy = 'MaSanPham DESC';
if ($sort === 'price_asc') $orderBy = 'GiaSanPham ASC';
if ($sort === 'price_desc') $orderBy = 'GiaSanPham DESC';
if ($sort === 'popular') $orderBy = 'SaoTrungBinh DESC, TongDanhGia DESC';

// Xây dựng câu lệnh WHERE
$where = "1=1";
if ($MaDanhMuc > 0) $where .= " AND MaDanhMuc = $MaDanhMuc";
if ($price_min !== null) $where .= " AND GiaSanPham >= $price_min";
if ($price_max !== null) $where .= " AND GiaSanPham <= $price_max";

// Đếm tổng số sản phẩm để chia trang
$sql_count = "SELECT COUNT(*) as total FROM product WHERE $where";
$res_count = $db->select($sql_count);
$totalRows = $res_count ? $res_count->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $perPage);

// Lấy danh sách sản phẩm
$sql = "SELECT * FROM product WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$result = $db->select($sql);

// Lấy toàn bộ danh mục cho Sidebar
$sql_all_cate = "SELECT * FROM categories";
$res_all_cate = $db->select($sql_all_cate);

// Hàm hỗ trợ build URL giữ nguyên các tham số
$buildQuery = function($p) use ($MaDanhMuc, $sort, $price_min, $price_max) {
    $q = "?page=$p";
    if ($MaDanhMuc > 0) $q .= "&MaDanhMuc=$MaDanhMuc";
    if ($sort) $q .= "&sort=$sort";
    if ($price_min !== null) $q .= "&price_min=$price_min";
    if ($price_max !== null) $q .= "&price_max=$price_max";
    return $q;
};

$page_title = $TenDanhMuc . " - THPSHOP";
include 'header.php';
?>

<div class="bg-gray-50 min-h-screen py-8 text-gray-800">
    <div class="container mx-auto px-4 max-w-7xl">

        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-blue-600 transition"><i class="fas fa-home"></i> Trang chủ</a>
            <span><i class="fas fa-chevron-right text-[10px]"></i></span>
            <span class="text-blue-600 font-bold"><?php echo htmlspecialchars($TenDanhMuc); ?></span>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="w-full lg:w-1/4 shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">

                    <h3 class="text-lg font-black uppercase text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-list-ul text-blue-600"></i> Danh mục
                    </h3>
                    <ul class="space-y-2 mb-8">
                        <li>
                            <a href="categories.php"
                                class="flex justify-between items-center px-3 py-2 rounded-lg transition-colors font-medium <?php echo ($MaDanhMuc == 0) ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                                Tất cả sản phẩm
                            </a>
                        </li>
                        <?php if ($res_all_cate && $res_all_cate->num_rows > 0): ?>
                        <?php while ($c = $res_all_cate->fetch_assoc()): ?>
                        <li>
                            <a href="categories.php?MaDanhMuc=<?php echo $c['MaDanhMuc']; ?>"
                                class="flex justify-between items-center px-3 py-2 rounded-lg transition-colors font-medium <?php echo ($MaDanhMuc == $c['MaDanhMuc']) ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600'; ?>">
                                <?php echo htmlspecialchars($c['TenDanhMuc']); ?>
                            </a>
                        </li>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </ul>

                    <form method="GET" action="categories.php">
                        <?php if ($MaDanhMuc > 0): ?>
                        <input type="hidden" name="MaDanhMuc" value="<?php echo $MaDanhMuc; ?>">
                        <?php endif; ?>

                        <h3
                            class="text-lg font-black uppercase text-gray-900 mb-4 flex items-center gap-2 border-t pt-6">
                            <i class="fas fa-filter text-blue-600"></i> Bộ lọc nâng cao
                        </h3>

                        <div class="mb-5">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Sắp xếp theo</label>
                            <select name="sort"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-sm">
                                <option value="new" <?php echo $sort == 'new' ? 'selected' : ''; ?>>Sản phẩm mới nhất
                                </option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Mua nhiều &
                                    Đánh giá cao</option>
                                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Giá:
                                    Thấp đến Cao</option>
                                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Giá:
                                    Cao đến Thấp</option>
                            </select>
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Khoảng giá (VNĐ)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="price_min" placeholder="TỪ" value="<?php echo $price_min; ?>"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 outline-none focus:border-blue-500">
                                <span class="text-gray-400">-</span>
                                <input type="number" name="price_max" placeholder="ĐẾN"
                                    value="<?php echo $price_max; ?>"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-gray-50 outline-none focus:border-blue-500">
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-blue-600 transition shadow-md">
                            Áp dụng bộ lọc
                        </button>

                        <?php if ($price_min !== null || $price_max !== null || $sort != 'new'): ?>
                        <a href="categories.php?MaDanhMuc=<?php echo $MaDanhMuc; ?>"
                            class="block text-center text-sm text-red-500 font-bold mt-3 hover:underline">
                            Xóa bộ lọc
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </aside>

            <main class="w-full lg:w-3/4">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                        <?php echo htmlspecialchars($TenDanhMuc); ?>
                    </h1>
                    <p
                        class="text-gray-500 font-medium bg-white px-4 py-1.5 rounded-full shadow-sm border border-gray-100">
                        <span class="text-blue-600 font-bold"><?php echo $totalRows; ?></span> sản phẩm
                    </p>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div
                        class="product-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group flex flex-col h-full hover:shadow-xl hover:-translate-y-1 transition duration-300">
                        <div class="relative overflow-hidden aspect-[3/4] bg-gray-100">
                            <a href="chitiet.php?id=<?php echo $row['MaSanPham']; ?>" class="block w-full h-full">
                                <img src="public/images/<?php echo htmlspecialchars($row['hinh'] ?? 'default.png'); ?>"
                                    alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            </a>
                        </div>
                        <div class="p-4 sm:p-5 flex flex-col flex-grow">
                            <a href="chitiet.php?id=<?php echo $row['MaSanPham']; ?>" class="block mb-2">
                                <h3
                                    class="font-bold text-gray-900 text-sm sm:text-base line-clamp-2 group-hover:text-blue-600 transition leading-snug">
                                    <?php echo htmlspecialchars($row['TenSanPham']); ?>
                                </h3>
                            </a>
                            <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-50">
                                <p class="text-lg sm:text-xl font-black text-red-600">
                                    <?php echo number_format($row['GiaSanPham'], 0, ',', '.'); ?>đ
                                </p>
                                <?php if (isset($row['SoLuong']) && $row['SoLuong'] <= 0): ?>
                                <button disabled
                                    class="bg-gray-100 text-gray-400 w-10 h-10 flex items-center justify-center rounded-xl cursor-not-allowed"
                                    title="Hết hàng">
                                    <i class="fas fa-box-open"></i>
                                </button>
                                <?php else: ?>
                                <a href="xuly_giohang.php?id=<?php echo $row['MaSanPham']; ?>" data-add-to-cart
                                    class="bg-gray-100 text-gray-800 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-blue-600 hover:text-white transition"
                                    title="Thêm vào giỏ">
                                    <i class="fas fa-cart-plus"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="mt-12 flex justify-center">
                    <nav class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                        <a href="<?php echo $buildQuery($page-1); ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition font-bold"><i
                                class="fas fa-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="<?php echo $buildQuery($p); ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg border <?php echo ($p == $page) ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'; ?> font-bold transition">
                            <?php echo $p; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $buildQuery($page+1); ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-white border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition font-bold"><i
                                class="fas fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="bg-white rounded-2xl border border-dashed border-gray-300 py-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 rounded-full mb-4">
                        <i class="fas fa-box-open text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Không tìm thấy sản phẩm nào</h3>
                    <p class="text-gray-500">Vui lòng thử thay đổi khoảng giá hoặc chọn danh mục khác.</p>
                    <a href="categories.php"
                        class="inline-block mt-6 px-6 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-md shadow-blue-200">
                        Xóa tất cả bộ lọc
                    </a>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>