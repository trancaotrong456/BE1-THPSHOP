<?php
session_start();

if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
}

require_once "database.php";

$db = new Database();
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

/* =========================
LẤY TỔNG SỐ LƯỢNG WISHLIST CỦA USER CHO HEADER
========================= */
$wishlist_count = 0;
if ($user_id > 0) {
    $sql_wl = "SELECT COUNT(*) as total FROM wishlist WHERE IdNguoiDung = $user_id";
    $res_wl = $db->select($sql_wl);
    if ($res_wl && $row_wl = $res_wl->fetch_assoc()) {
        $wishlist_count = $row_wl['total'];
    }
}

/* =========================
LOAD DATA FROM DATABASE
========================= */

// Cập nhật câu lệnh lấy dữ liệu để đếm tổng số tim và kiểm tra user đã thả tim chưa
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM wishlist w WHERE w.MaSanPham = p.MaSanPham) as total_likes,
        (SELECT COUNT(*) FROM wishlist w2 WHERE w2.MaSanPham = p.MaSanPham AND w2.IdNguoiDung = $user_id) as is_liked
        FROM product p ORDER BY p.MaSanPham DESC LIMIT 8";
$result = $db->select($sql);

$sql_categories = "SELECT * FROM categories";
$result_categories = $db->select($sql_categories);

$sql_config = "SELECT * FROM config";
$res_config = $db->select($sql_config);

$config = [];
if ($res_config && $res_config->num_rows > 0) {
    while ($row_c = $res_config->fetch_assoc()) {
        $config[$row_c['key']] = $row_c['value'];
    }
}

$hotline = $config['hotline'] ?? '0123456789';
$address = $config['address'] ?? 'Địa chỉ shop';

// Dữ liệu cấu hình cho 5 Banner tự động trượt
$bannerSlides = [
    [
        'image' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?q=80&w=2070&auto=format&fit=crop',
        'title' => 'Siêu Hội Mua Sắm 2026',
        'desc' => 'Giảm giá lên đến 50% toàn sàn',
        'btnText' => 'Săn Sale Ngay',
        'btnLink' => '#products',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?q=80&w=2070&auto=format&fit=crop',
        'title' => 'Thế Giới Công Nghệ',
        'desc' => 'Cập nhật thiết bị thông minh mới nhất',
        'btnText' => 'Khám phá ngay',
        'btnLink' => 'categories.php',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1583847268964-b28ce8fde1e5?q=80&w=2070&auto=format&fit=crop',
        'title' => 'Không Gian Sống Tiện Nghi',
        'desc' => 'Gia dụng thông minh - Nâng tầm cuộc sống',
        'btnText' => 'Xem Sản Phẩm',
        'btnLink' => 'categories.php',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?q=80&w=2070&auto=format&fit=crop',
        'title' => 'Thời Trang Hiện Đại',
        'desc' => 'Phong cách đa dạng cho mọi lứa tuổi',
        'btnText' => 'Mua sắm ngay',
        'btnLink' => '#products',
    ],
    [
        'image' => 'https://images.unsplash.com/photo-1607083206968-13611e3d76ba?q=80&w=2015&auto=format&fit=crop',
        'title' => 'Giao Hàng Siêu Tốc',
        'desc' => 'Freeship toàn quốc - Nhận hàng ngay hôm nay',
        'btnText' => 'Tìm hiểu thêm',
        'btnLink' => '#',
    ],
];

// Cập nhật SQL cho các danh sách gợi ý, nổi bật, bán chạy
$res_noibat = $db->select("SELECT p.*, (SELECT COUNT(*) FROM wishlist w WHERE w.MaSanPham = p.MaSanPham) as total_likes, (SELECT COUNT(*) FROM wishlist w2 WHERE w2.MaSanPham = p.MaSanPham AND w2.IdNguoiDung = $user_id) as is_liked FROM product p ORDER BY p.SaoTrungBinh DESC LIMIT 4");
$res_banchay = $db->select("SELECT p.*, (SELECT COUNT(*) FROM wishlist w WHERE w.MaSanPham = p.MaSanPham) as total_likes, (SELECT COUNT(*) FROM wishlist w2 WHERE w2.MaSanPham = p.MaSanPham AND w2.IdNguoiDung = $user_id) as is_liked FROM product p ORDER BY p.TongDanhGia DESC LIMIT 4");
$res_goiyi = $db->select("SELECT p.*, (SELECT COUNT(*) FROM wishlist w WHERE w.MaSanPham = p.MaSanPham) as total_likes, (SELECT COUNT(*) FROM wishlist w2 WHERE w2.MaSanPham = p.MaSanPham AND w2.IdNguoiDung = $user_id) as is_liked FROM product p ORDER BY RAND() LIMIT 4");

$total_items = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $total_items += $item['soluong'];
    }
}

/* =========================
HÀM RENDER SẢN PHẨM
========================= */
if (!function_exists('renderProducts')) {
    function renderProducts($res) {
        if ($res && $res->num_rows > 0):
            while ($row = $res->fetch_assoc()):
    ?>
<div class="product-card group flex flex-col relative">
    <div class="relative overflow-hidden aspect-[3/4] bg-gray-100">

        <button onclick="toggleWishlist(event, <?php echo $row['MaSanPham']; ?>, this)"
            class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2.5 py-1.5 rounded-full shadow-md flex items-center gap-1.5 z-20 transition-transform hover:scale-105 group/btn"
            title="Thêm vào yêu thích">
            <i
                class="<?php echo !empty($row['is_liked']) ? 'fas text-red-500' : 'far text-gray-400 group-hover/btn:text-red-400'; ?> fa-heart heart-icon transition-colors duration-300 text-sm"></i>
            <span
                class="text-xs font-bold <?php echo !empty($row['is_liked']) ? 'text-red-500' : 'text-gray-600'; ?> like-count">
                <?php echo $row['total_likes'] ?? 0; ?>
            </span>
        </button>

        <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
            class="w-full h-full object-cover rounded-t-lg group-hover:scale-105 transition-transform duration-1000"
            onerror="this.src='https://via.placeholder.com/300x400?text=No+Image';">
        <?php if (isset($row['GiaSanPham']) && $row['GiaSanPham'] < 500000): ?>
        <div class="badge">DEAL HOT</div>
        <?php endif; ?>
    </div>

    <div class="p-5 flex flex-col flex-1">
        <h3 class="lux-title text-[24px] md:text-[28px] leading-tight text-gray-900 mb-2 line-clamp-2">
            <a href="chitiet.php?id=<?php echo $row['MaSanPham']; ?>" class="hover:text-[#b08954]">
                <?php echo htmlspecialchars($row['TenSanPham']); ?>
            </a>
        </h3>
        <div class="text-gray-400 text-sm mb-4">Sản phẩm chính hãng</div>
        <div class="flex items-center gap-1 mb-5">
            <div class="flex text-yellow-400 text-[11px]">
                <?php
                    $s = round($row['SaoTrungBinh'] ?? 0);
                    for ($i = 1; $i <= 5; $i++) {
                        echo ($i <= $s) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                    }
                    ?>
            </div>
            <span class="text-xs text-gray-400">(<?php echo htmlspecialchars($row['TongDanhGia'] ?? 0); ?>)</span>
        </div>
        <div class="mt-auto">
            <div class="text-2xl font-bold text-[#111827] mb-5">
                <?php echo number_format($row['GiaSanPham']); ?>đ
            </div>
            <div class="flex gap-3">
                <a href="chitiet.php?id=<?php echo $row['MaSanPham']; ?>"
                    class="flex-1 product-btn flex items-center justify-center rounded-md">Chi tiết</a>
                <?php if (isset($row['SoLuong']) && $row['SoLuong'] <= 0): ?>
                <button disabled
                    class="cart-btn rounded-md flex items-center justify-center opacity-50 cursor-not-allowed bg-gray-200 text-gray-500"
                    title="Hết hàng">
                    <i class="fas fa-box-open"></i>
                </button>
                <?php else: ?>
                <a href="xuly_giohang.php?id=<?php echo $row['MaSanPham']; ?>" data-add-to-cart
                    class="cart-btn rounded-md flex items-center justify-center">
                    <i class="fas fa-shopping-bag"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
            endwhile;
        endif;
    }
}
?>

<?php include 'header.php'; ?>

    <section id="hero-carousel" class="relative h-[600px] md:h-[760px] overflow-hidden bg-[#07122b]">
        <?php foreach ($bannerSlides as $index => $slide): ?>
        <div class="carousel-item absolute inset-0 <?php echo $index === 0 ? 'active' : ''; ?>">
            <img src="<?php echo htmlspecialchars($slide['image']); ?>"
                class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 hero-overlay"></div>
            <div class="relative z-10 h-full flex items-center">
                <div class="container mx-auto px-7">
                    <div class="max-w-3xl">
                        <div class="text-white uppercase tracking-[4px] text-xs font-bold mb-6">
                            <?php echo htmlspecialchars($slide['desc']); ?></div>
                        <h1 class="lux-title text-white text-5xl md:text-8xl leading-none mb-8">
                            <?php echo htmlspecialchars($slide['title']); ?></h1>
                        <p class="text-gray-300 text-base md:text-lg leading-relaxed max-w-xl mb-10">Khám phá hàng ngàn
                            sản phẩm đa dạng từ đồ điện tử, gia dụng đến thời trang.</p>
                        <div class="flex flex-wrap gap-4">
                            <a href="<?php echo htmlspecialchars($slide['btnLink']); ?>"
                                class="hero-btn hero-btn-dark"><?php echo htmlspecialchars($slide['btnText']); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <button onclick="prevSlide()"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-30 bg-black/20 hover:bg-black/60 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors"><i
                class="fas fa-chevron-left"></i></button>
        <button onclick="nextSlide()"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-30 bg-black/20 hover:bg-black/60 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors"><i
                class="fas fa-chevron-right"></i></button>
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 flex gap-3">
            <?php foreach ($bannerSlides as $index => $slide): ?>
            <div onclick="goToSlide(<?php echo $index; ?>)"
                class="pagination-dot w-3 h-3 rounded-full <?php echo $index === 0 ? 'active' : ''; ?>"></div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="container mx-auto px-6 mt-16 flex flex-col md:flex-row gap-8 items-start">
        <aside class="sidebar w-full md:w-[260px] shrink-0 sticky top-16 z-20">
            <div class="font-black text-xl mb-4 border-b-2 border-black pb-2">DANH MỤC NGÀNH HÀNG</div>
            <?php if ($result_categories && $result_categories->num_rows > 0): ?>
            <?php while ($cate = $result_categories->fetch_assoc()): ?>
            <div>
                <div class="sidebar-title" onclick="toggleSub(this)">
                    <?php echo htmlspecialchars($cate['TenDanhMuc']); ?> <i
                        class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
                </div>
                <div class="sidebar-sub">
                    <a href="categories.php?MaDanhMuc=<?php echo $cate['MaDanhMuc']; ?>">Tất cả sản phẩm</a>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div>
                <div class="sidebar-title" onclick="toggleSub(this)">Danh mục trống</div>
            </div>
            <?php endif; ?>
        </aside>

        <main class="flex-1 w-full overflow-hidden">
            <section id="products" class="mb-24">
                <div class="text-center md:text-left uppercase tracking-[4px] text-[11px] text-gray-400 font-bold mb-4">
                    Gợi ý hôm nay</div>
                <h2 class="section-title text-center md:text-left lux-title">Dành Cho Bạn</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php renderProducts($result); ?>
                </div>
            </section>

            <section class="mb-24 bg-white p-6 md:p-8 rounded-xl border border-gray-100 shadow-sm">
                <h2 class="section-title text-center md:text-left lux-title">Sản Phẩm Đánh Giá Cao</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php renderProducts($res_noibat); ?>
                </div>
            </section>

            <section class="mb-24">
                <h2 class="section-title text-center md:text-left lux-title">Top Bán Chạy Trong Tuần</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php renderProducts($res_banchay); ?>
                </div>
            </section>

            <section class="mb-10 bg-white p-6 md:p-8 rounded-xl border border-gray-100 shadow-sm">
                <h2 class="section-title text-center md:text-left lux-title">Khám Phá Thêm</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php renderProducts($res_goiyi); ?>
                </div>
            </section>
        </main>
    </div>

    <script>
    function showWishlistToast(isLiked) {
        if (!window.Swal) return;

        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: isLiked ? 'success' : 'info',
            title: isLiked ? 'Đã thêm vào danh sách yêu thích' : 'Đã bỏ khỏi danh sách yêu thích',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }

    function toggleWishlist(event, productId, btnElement) {
        event.preventDefault();
        event.stopPropagation();

        const icon = btnElement.querySelector('.heart-icon');
        const countSpan = btnElement.querySelector('.like-count');

        btnElement.style.transform = 'scale(0.9)';
        setTimeout(() => btnElement.style.transform = 'scale(1)', 150);

        fetch('chitiet.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    'action': 'toggle_like',
                    'product_id': productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'error') {
                    alert(data.message);
                    if (data.message.toLowerCase().includes('đăng nhập')) window.location.href = 'login.php';
                    return;
                }

                if (data.status === 'success') {
                    if (data.is_liked) {
                        icon.classList.remove('far', 'text-gray-400', 'group-hover/btn:text-red-400');
                        icon.classList.add('fas', 'text-red-500');
                        countSpan.classList.remove('text-gray-600');
                        countSpan.classList.add('text-red-500');
                    } else {
                        icon.classList.remove('fas', 'text-red-500');
                        icon.classList.add('far', 'text-gray-400', 'group-hover/btn:text-red-400');
                        countSpan.classList.remove('text-red-500');
                        countSpan.classList.add('text-gray-600');
                    }
                    countSpan.innerText = data.total_likes;
                    countSpan.style.transform = 'scale(1.5)';
                    setTimeout(() => countSpan.style.transform = 'scale(1)', 200);
                    showWishlistToast(data.is_liked);

                    // --- UPDATE BADGE LÊN TRÊN HEADER ---
                    const wishlistBadge = document.getElementById('globalWishlistCount');
                    if (wishlistBadge && data.total_user_wishlist !== undefined) {
                        wishlistBadge.innerText = data.total_user_wishlist;
                        if (data.total_user_wishlist > 0) {
                            wishlistBadge.classList.remove('hidden');
                        } else {
                            wishlistBadge.classList.add('hidden');
                        }
                        wishlistBadge.style.transform = 'scale(1.3)';
                        setTimeout(() => wishlistBadge.style.transform = 'scale(1)', 200);
                    }
                }
            })
            .catch(error => console.error('Lỗi khi thả tim:', error));
    }

    // Các script phụ trợ khác (Slide + Cart)
    let currentSlideIndex = 0;
    const slides = document.querySelectorAll('#hero-carousel .carousel-item');
    const dots = document.querySelectorAll('#hero-carousel .pagination-dot');
    let slideTimer;

    function showSlide(index) {
        if (index >= slides.length) currentSlideIndex = 0;
        if (index < 0) currentSlideIndex = slides.length - 1;
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        slides[currentSlideIndex].classList.add('active');
        dots[currentSlideIndex].classList.add('active');
    }

    function nextSlide() {
        currentSlideIndex++;
        showSlide(currentSlideIndex);
        resetTimer();
    }

    function prevSlide() {
        currentSlideIndex--;
        showSlide(currentSlideIndex);
        resetTimer();
    }

    function goToSlide(index) {
        currentSlideIndex = index;
        showSlide(currentSlideIndex);
        resetTimer();
    }

    function startTimer() {
        slideTimer = setInterval(nextSlide, 4000);
    }

    function resetTimer() {
        clearInterval(slideTimer);
        startTimer();
    }
    document.addEventListener('DOMContentLoaded', startTimer);

    function toggleSub(el) {
        const sub = el.nextElementSibling;
        const icon = el.querySelector('i');
        if (!sub) return;
        if (sub.style.display === 'block') {
            sub.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        } else {
            sub.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        }
    }
    </script>
<?php include 'footer.php'; ?>
