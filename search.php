<?php
session_start();
require_once "database.php";
$db = new Database();

// Lấy từ khóa tìm kiếm
$keyword = isset($_GET['keyword']) ? $db->conn->real_escape_string(trim($_GET['keyword'])) : '';

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
if ($keyword !== '') {
    $where .= " AND TenSanPham LIKE '%$keyword%'";
}
if ($price_min !== null) $where .= " AND GiaSanPham >= $price_min";
if ($price_max !== null) $where .= " AND GiaSanPham <= $price_max";

// Đếm tổng số sản phẩm để chia trang
$sql_count = "SELECT COUNT(*) as total FROM product WHERE $where";
$res_count = $db->select($sql_count);
$totalRows = $res_count ? $res_count->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $perPage);

// Lấy danh sách sản phẩm theo tìm kiếm
$sql = "SELECT * FROM product WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$result = $db->select($sql);

// Hàm hỗ trợ build URL giữ nguyên các tham số
$buildQuery = function($p) use ($keyword, $sort, $price_min, $price_max) {
    $q = "?page=$p";
    if ($keyword !== '') $q .= "&keyword=" . urlencode($keyword);
    if ($sort) $q .= "&sort=$sort";
    if ($price_min !== null) $q .= "&price_min=$price_min";
    if ($price_max !== null) $q .= "&price_max=$price_max";
    return $q;
};

$page_title = "Tìm kiếm: " . ($keyword ? htmlspecialchars($keyword) : "Tất cả") . " - THPSHOP";
include 'header.php';
?>

<div class="bg-gray-50 min-h-screen py-8 text-gray-800">
    <div class="container mx-auto px-4 max-w-7xl">

        <div class="flex items-center gap-2 text-sm text-gray-500 mb-6 font-medium">
            <a href="index.php" class="hover:text-blue-600 transition"><i class="fas fa-home"></i> Trang chủ</a>
            <span><i class="fas fa-chevron-right text-[10px]"></i></span>
            <span class="text-blue-600 font-bold">Tìm kiếm</span>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="w-full lg:w-1/4 shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">

                    <form method="GET" action="search.php">

                        <h3 class="text-lg font-black uppercase text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-filter text-blue-600"></i> Bộ lọc tìm kiếm
                        </h3>

                        <div class="mb-5 relative">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Từ khóa tìm kiếm</label>
                            <input type="text" id="search-keyword" name="keyword"
                                value="<?php echo htmlspecialchars($keyword); ?>"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition text-sm"
                                placeholder="Nhập tên sản phẩm..." autocomplete="off">

                            <div id="search-suggestions"
                                class="absolute z-50 w-full bg-white rounded-xl shadow-2xl border border-gray-100 mt-1 hidden max-h-80 overflow-y-auto">
                            </div>
                        </div>

                        <div class="mb-5">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Sắp xếp kết quả</label>
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

                        <?php if ($price_min !== null || $price_max !== null || $sort != 'new' || $keyword !== ''): ?>
                        <a href="search.php"
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
                        Kết quả cho: <span
                            class="text-blue-600">"<?php echo htmlspecialchars($keyword ?: 'Tất cả'); ?>"</span>
                    </h1>
                    <p
                        class="text-gray-500 font-medium bg-white px-4 py-1.5 rounded-full shadow-sm border border-gray-100">
                        Có <span class="text-blue-600 font-bold"><?php echo $totalRows; ?></span> sản phẩm
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
                                <button onclick="addToCart(<?php echo $row['MaSanPham']; ?>)"
                                    class="bg-gray-100 text-gray-800 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-blue-600 hover:text-white transition"
                                    title="Thêm vào giỏ">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
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
                <div
                    class="relative flex flex-col items-center justify-center py-20 md:py-24 text-center bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden z-0 px-4">
                    <h2 class="text-3xl md:text-5xl font-bold text-gray-800 mb-6 relative z-10 tracking-wide">
                        404 <span class="text-green-500">PAGE NOT FOUND</span>
                    </h2>

                    <img src="https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif"
                        alt="Not Found Animation"
                        class="w-72 md:w-[400px] h-auto rounded-2xl mb-8 relative z-10 object-cover">

                    <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-4 relative z-10">
                        Trông có vẻ như bạn đang đi lạc...
                    </h2>

                    <p
                        class="text-base md:text-lg text-gray-500 mb-8 max-w-xl mx-auto relative z-10 leading-relaxed w-full">
                        Rất tiếc, chúng tôi không tìm thấy sản phẩm nào khớp với từ khóa <br class="hidden md:block">
                        "<strong
                            class="text-gray-800 inline-block align-bottom truncate max-w-[150px] sm:max-w-[250px] md:max-w-[300px]"><?php echo htmlspecialchars($keyword); ?></strong>".
                        <br class="hidden md:block">
                        Vui lòng thử lại với một từ khóa khác nhé!
                    </p>

                    <a href="index.php"
                        class="relative z-10 inline-block px-8 py-3 text-lg bg-[#39ac31] text-white font-bold rounded-lg hover:bg-[#2e8a27] transition-all duration-300 shadow-md hover:shadow-lg hover:-translate-y-1">
                        Quay về trang chủ
                    </a>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-keyword');
    const suggestBox = document.getElementById('search-suggestions');
    let timeout = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const q = this.value.trim();

        if (q.length < 1) {
            suggestBox.classList.add('hidden');
            suggestBox.innerHTML = '';
            return;
        }

        // Delay 300ms để tránh spam request liên tục (Debouncing)
        timeout = setTimeout(() => {
            fetch('search_suggest.php?q=' + encodeURIComponent(q))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            // Tạo HTML cho từng item được gợi ý
                            html += `
                                <a href="chitiet.php?id=${item.id}" class="flex items-center gap-3 p-3 hover:bg-blue-50 transition border-b border-gray-50 last:border-0">
                                    <img src="public/images/${item.hinh}" alt="${item.ten}" class="w-12 h-12 object-cover rounded-md border border-gray-100">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-gray-800 truncate">${item.ten}</h4>
                                        <div class="text-[13px] text-red-600 font-black mt-0.5">${item.gia_fmt}</div>
                                    </div>
                                </a>
                            `;
                        });
                        suggestBox.innerHTML = html;
                        suggestBox.classList.remove('hidden');
                    } else {
                        suggestBox.innerHTML =
                            '<div class="p-4 text-sm text-gray-500 text-center font-medium">Không tìm thấy sản phẩm phù hợp</div>';
                        suggestBox.classList.remove('hidden');
                    }
                })
                .catch(err => console.error('Lỗi khi tải gợi ý tìm kiếm:', err));
        }, 300);
    });

    // Ẩn box gợi ý khi click ra ngoài vùng tìm kiếm
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestBox.contains(e.target)) {
            suggestBox.classList.add('hidden');
        }
    });
});
</script>

<?php include 'footer.php'; ?>