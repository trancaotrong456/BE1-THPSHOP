<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once "database.php";
$db = new Database();

// XỬ LÝ THẢ TIM (LIKE/UNLIKE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn cần đăng nhập để thả tim.']);
        exit;
    }
    
    $productId = intval($_POST['product_id']);
    $userId = intval($_SESSION['user_id']); 
    
    $check_sql = "SELECT * FROM wishlist WHERE IdNguoiDung = $userId AND MaSanPham = $productId";
    $check_res = $db->select($check_sql);
    
    if ($check_res && $check_res->num_rows > 0) {
        $db->execute("DELETE FROM wishlist WHERE IdNguoiDung = $userId AND MaSanPham = $productId");
        $is_liked = false;
    } else {
        $db->execute("INSERT INTO wishlist (IdNguoiDung, MaSanPham) VALUES ($userId, $productId)");
        $is_liked = true;
    }
    
    $count_res = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE MaSanPham = $productId");
    $total_likes = $count_res ? (int)$count_res->fetch_assoc()['total'] : 0;

    $user_count_res = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE IdNguoiDung = $userId");
    $total_user_wishlist = $user_count_res ? (int)$user_count_res->fetch_assoc()['total'] : 0;
    
    echo json_encode([
        'status' => 'success',
        'is_liked' => $is_liked,
        'total_likes' => $total_likes,
        'total_user_wishlist' => $total_user_wishlist
    ]);
    exit;
}
// =========================================================

// 1. Lấy id sản phẩm từ url
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    echo "ID sản phẩm không hợp lệ.";
    exit;
}

// 2. Lấy thông tin sản phẩm
$sql_sp = "SELECT p.*, c.TenDanhMuc FROM product p 
           JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc 
           WHERE p.MaSanPham = $id";
$res_sp = $db->select($sql_sp);
$row = ($res_sp && $res_sp->num_rows > 0) ? $res_sp->fetch_assoc() : die("Sản phẩm không tồn tại.");

// Kiểm tra user đã thả tim chưa
$da_thich = false;
$tong_tim = 0; 
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $check_like = $db->select("SELECT * FROM wishlist WHERE IdNguoiDung = $uid AND MaSanPham = $id");
    if ($check_like && $check_like->num_rows > 0) $da_thich = true;
}

$count_like_db = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE MaSanPham = $id");
if ($count_like_db) $tong_tim = $count_like_db->fetch_assoc()['total'];

// 3. Lấy thông tin đánh giá để hiển thị
$rating_avg = isset($row['SaoTrungBinh']) && $row['SaoTrungBinh'] > 0 ? (float) $row['SaoTrungBinh'] : 0;
$total_reviews = isset($row['TongDanhGia']) ? (int) $row['TongDanhGia'] : 0;

// 4. Lấy danh sách bình luận thực tế từ DB
$sql_reviews = "SELECT r.*, u.TenNguoiDung, u.AnhDaiDien 
                FROM review r 
                JOIN user u ON r.IdNguoiDung = u.IdNguoiDung 
                WHERE r.MaSanPham = $id 
                ORDER BY r.NgayBinhLuan DESC";
$res_reviews = $db->select($sql_reviews);

$reviews_data = [];
$star_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$total_comment_reviews = 0;

if ($res_reviews && $res_reviews->num_rows > 0) {
    while ($rv = $res_reviews->fetch_assoc()) {
        $reviews_data[] = $rv;
        $star_counts[$rv['SoSao']]++;
        if (!empty(trim($rv['NoiDung']))) {
            $total_comment_reviews++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sản phẩm - THPSHOP</title>

    <link rel="icon" href="./public/images/web_be1.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        transition: .35s ease;
    }

    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Manrope', sans-serif;
        background: #f5f5f3;
        color: #111827;
        overflow-x: hidden;
    }

    .lux-title {
        font-family: 'Cormorant Garamond', serif;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .nav-link {
        position: relative;
        font-size: 14px;
        font-weight: 600;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -7px;
        width: 0;
        height: 1px;
        background: black;
        transition: .3s;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    .footer-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .footer-link {
        color: #cbd5e1;
        transition: all .3s ease;
    }

    .footer-link:hover {
        color: white;
        padding-left: 4px;
    }

    .size-active {
        border-color: #1f2937 !important;
        background-color: #ffffff !important;
        color: #111827 !important;
        border-width: 2px !important;
    }

    .color-active {
        border-color: #2563eb !important;
    }

    .thumb-active {
        border-color: #1f2937 !important;
        opacity: 1 !important;
    }
    </style>
</head>

<body class="bg-[#f5f5f3] relative overflow-x-hidden">

    <div id="toast-success"
        class="fixed top-24 right-5 z-[100] transform transition-all duration-500 translate-x-[150%] opacity-0 bg-white border-l-4 border-green-500 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 min-w-[300px]">
        <i class="fas fa-check-circle text-green-500 text-3xl"></i>
        <div>
            <h4 class="text-gray-800 font-bold text-sm">Thành công!</h4>
            <p class="text-gray-500 text-xs">Đã thêm sản phẩm vào giỏ hàng.</p>
        </div>
    </div>

    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 max-w-6xl">

        <div class="text-sm text-gray-500 mb-6 mt-2 font-medium">
            <a href="index.php" class="hover:text-blue-600">Trang chủ</a> >
            <a href="categories.php?MaDanhMuc=<?php echo $row['MaDanhMuc']; ?>"
                class="hover:text-blue-600"><?php echo htmlspecialchars($row['TenDanhMuc']); ?></a> >
            <span class="text-gray-800"><?php echo htmlspecialchars($row['TenSanPham']); ?></span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col md:flex-row mb-12 p-6 md:p-8 gap-10">
            <div class="md:w-5/12 flex flex-col items-center">
                <img id="main-image" src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                    class="w-full aspect-[3/4] object-cover rounded-2xl mb-4 shadow-sm transition duration-300"
                    alt="<?php echo htmlspecialchars($row['TenSanPham']); ?>">

                <div class="flex items-center gap-3 w-full justify-center">
                    <button id="btn-prev-img"
                        class="w-10 h-10 bg-white border rounded-lg flex items-center justify-center font-bold hover:bg-gray-50 text-gray-500">&lt;</button>

                    <div id="thumbnail-container" class="flex gap-2">
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                            class="thumb-img w-16 h-20 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-60 hover:opacity-100 transition thumb-active"
                            data-index="0">
                        <?php if(!empty($row['hinh2'])): ?>
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh2']); ?>"
                            class="thumb-img w-16 h-20 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-60 hover:opacity-100 transition"
                            data-index="1">
                        <?php endif; ?>
                        <?php if(!empty($row['hinh3'])): ?>
                        <img src="public/images/<?php echo htmlspecialchars($row['hinh3']); ?>"
                            class="thumb-img w-16 h-20 object-cover rounded-lg border-2 border-gray-200 cursor-pointer opacity-60 hover:opacity-100 transition"
                            data-index="2">
                        <?php endif; ?>
                    </div>

                    <button id="btn-next-img"
                        class="w-10 h-10 bg-white border rounded-lg flex items-center justify-center font-bold hover:bg-gray-50 text-gray-500">&gt;</button>
                </div>
            </div>

            <div class="md:w-7/12 flex flex-col">
                <p class="text-sm font-bold text-gray-800 mb-2">Loại sản phẩm:
                    <a href="categories.php?MaDanhMuc=<?php echo $row['MaDanhMuc']; ?>"
                        class="text-blue-600 font-semibold hover:underline">
                        <?php echo htmlspecialchars($row['TenDanhMuc']); ?>
                    </a>
                </p>

                <h1 class="text-2xl md:text-3xl font-black text-gray-900 mb-4 uppercase leading-tight">
                    <?php echo htmlspecialchars($row['TenSanPham']); ?>
                </h1>

                <div class="flex items-center gap-2 mb-6">
                    <div class="flex text-yellow-400 text-lg">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating_avg) echo '<i class="fas fa-star"></i>';
                            elseif ($i - 0.5 <= $rating_avg) echo '<i class="fas fa-star-half-alt"></i>';
                            else echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span
                        class="text-gray-900 font-bold ml-1 text-lg"><?php echo number_format($rating_avg, 1); ?>/5</span>
                    <span class="text-gray-500 text-sm ml-2">(<?php echo $total_reviews; ?> lượt đánh giá)</span>
                </div>

                <div class="flex flex-col md:flex-row md:items-end gap-3 mb-6">
                    <div class="text-3xl md:text-4xl font-black text-red-600">
                        <?php echo number_format($row['GiaSanPham']); ?>Đ </div>

                    <div class="text-sm font-bold">
                        Tồn kho:
                        <?php $so_luong_ton = isset($row['SoLuong']) ? intval($row['SoLuong']) : 0; ?>
                        <span
                            class="ml-2 px-3 py-1 rounded-full border <?php echo ($so_luong_ton>0)?'border-green-200 bg-green-50 text-green-700':'border-red-200 bg-red-50 text-red-700'; ?>">
                            <?php echo $so_luong_ton; ?> sản phẩm
                        </span>
                    </div>
                </div>

                <?php 
                $has_sizes = !empty($row['size']); 
                $size_array = [];
                if ($has_sizes) {
                    $size_array = array_map('trim', explode(',', $row['size'])); 
                }
                ?>
                <?php if ($has_sizes && count($size_array) > 0): ?>
                <div class="flex items-center gap-4 mb-6" id="size-section">
                    <span class="font-bold text-gray-900 w-24">Size:</span>
                    <div class="flex gap-2 flex-wrap">
                        <?php foreach ($size_array as $index => $sz): ?>
                        <button
                            class="btn-size min-w-[3rem] px-2 h-10 rounded-lg bg-gray-100 border text-gray-600 font-semibold hover:bg-gray-200 transition <?php echo $index === 0 ? 'size-active' : ''; ?>"
                            data-size="<?php echo htmlspecialchars($sz); ?>">
                            <?php echo htmlspecialchars($sz); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-4 mb-8">
                    <span class="font-bold text-gray-900 w-24">Màu sắc:</span>
                    <div class="flex gap-3 flex-wrap">
                        <label
                            class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition color-active"
                            data-color="<?php echo !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'MacDinh'; ?>">
                            <img src="public/images/<?php echo htmlspecialchars($row['hinh']); ?>"
                                class="w-8 h-8 object-cover rounded">
                            <span
                                class="text-sm font-bold text-gray-900"><?php echo !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'Mặc định'; ?></span>
                        </label>
                        <?php if(!empty($row['mau2']) && !empty($row['hinh2'])): ?>
                        <label
                            class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition"
                            data-color="<?php echo htmlspecialchars($row['mau2']); ?>">
                            <img src="public/images/<?php echo htmlspecialchars($row['hinh2']); ?>"
                                class="w-8 h-8 object-cover rounded">
                            <span
                                class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['mau2']); ?></span>
                        </label>
                        <?php endif; ?>
                        <?php if(!empty($row['mau3']) && !empty($row['hinh3'])): ?>
                        <label
                            class="btn-color flex items-center gap-2 border-2 border-gray-200 rounded-lg p-1 pr-3 cursor-pointer transition"
                            data-color="<?php echo htmlspecialchars($row['mau3']); ?>">
                            <img src="public/images/<?php echo htmlspecialchars($row['hinh3']); ?>"
                                class="w-8 h-8 object-cover rounded">
                            <span
                                class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($row['mau3']); ?></span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-6 mb-10">
                    <div class="flex items-center gap-4">
                        <span class="font-bold text-gray-900 w-24">Số Lượng:</span>
                        <div class="flex items-center border rounded-lg overflow-hidden h-11">
                            <button id="btn-minus"
                                class="px-4 bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold outline-none border-r h-full transition">-</button>
                            <input type="text" id="input-qty" value="1" readonly
                                class="w-14 text-center font-bold outline-none bg-white text-gray-800">
                            <button id="btn-plus"
                                class="px-4 bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold outline-none border-l h-full transition">+</button>
                        </div>
                    </div>
                    <button id="btn-like" data-id="<?php echo $id; ?>"
                        class="flex items-center gap-2 transition ml-4 <?php echo $da_thich ? 'text-red-500' : 'text-gray-500 hover:text-red-500'; ?>">
                        <i id="icon-like"
                            class="<?php echo $da_thich ? 'fas' : 'far'; ?> fa-heart text-2xl transition-transform active:scale-75"></i>
                        <span class="text-sm font-medium">Đã thích (<span
                                id="like-count"><?php echo $tong_tim; ?></span>)</span>
                    </button>
                </div>

                <div class="flex gap-4 mt-auto pt-4 border-t border-gray-100">
                    <?php if ($row['SoLuong'] > 0): ?>
                    <a href="#" id="btn-add-cart"
                        class="flex-1 border-2 border-blue-600 text-blue-600 font-bold py-3.5 rounded-xl hover:bg-blue-50 transition flex items-center justify-center gap-2 text-lg">
                        Thêm vào giỏ hàng <i class="fas fa-cart-plus"></i>
                    </a>
                    <a href="#" id="btn-buy-now"
                        class="flex-1 bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center justify-center text-lg">
                        Mua Ngay
                    </a>
                    <?php else: ?>
                    <button disabled
                        class="flex-1 bg-gray-300 text-gray-500 font-bold py-3.5 rounded-xl cursor-not-allowed flex items-center justify-center gap-2 text-lg">
                        Sản Phẩm Đã Hết Hàng <i class="fas fa-box-open"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-12 border border-gray-100">
            <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Mô tả sản phẩm</h3>
            </div>
            <div class="p-8 text-gray-700 leading-relaxed text-[15px]">
                <?php 
                    $mota = htmlspecialchars($row['MoTa'] ?? '');
                    $mota = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $mota);
                    echo nl2br($mota); 
                ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-10 border border-gray-100">
            <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Sản phẩm liên quan</h3>
            </div>

            <div class="p-8">
                <?php
                    $related = [];
                    $sql_related = "SELECT MaSanPham, TenSanPham, GiaSanPham, hinh, SaoTrungBinh, TongDanhGia
                                    FROM product
                                    WHERE MaDanhMuc = {$row['MaDanhMuc']} AND MaSanPham <> {$id}
                                    ORDER BY MaSanPham DESC LIMIT 8";
                    $res_related = $db->select($sql_related);
                    if ($res_related && $res_related->num_rows > 0) {
                        while ($rp = $res_related->fetch_assoc()) { $related[] = $rp; }
                    }
                ?>

                <?php if (!empty($related)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach ($related as $rp): ?>
                    <div class="product-card bg-white rounded-2xl border border-gray-100 overflow-hidden flex flex-col">
                        <div class="relative aspect-[3/4] overflow-hidden bg-gray-50">
                            <img src="public/images/<?php echo htmlspecialchars($rp['hinh']); ?>"
                                class="w-full h-full object-cover"
                                alt="<?php echo htmlspecialchars($rp['TenSanPham']); ?>">
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                            <h4 class="font-bold text-gray-800 mb-2 line-clamp-2 h-12 hover:text-blue-600">
                                <a href="chitiet.php?id=<?php echo $rp['MaSanPham']; ?>"
                                    class="hover:text-blue-600"><?php echo htmlspecialchars($rp['TenSanPham']); ?></a>
                            </h4>

                            <div class="flex items-center gap-1 mb-3">
                                <div class="flex text-yellow-400 text-[10px]">
                                    <?php
                                                $s = round($rp['SaoTrungBinh'] ?? 0);
                                                for($i=1;$i<=5;$i++) echo ($i<=$s)?'<i class="fas fa-star"></i>':'<i class="far fa-star"></i>';
                                            ?>
                                </div>
                                <span class="text-[10px] text-gray-400">(<?php echo $rp['TongDanhGia'] ?? 0; ?>)</span>
                            </div>

                            <div class="mt-auto">
                                <div class="text-xl font-black text-red-600 mb-4">
                                    <?php echo number_format($rp['GiaSanPham']); ?>Đ</div>
                                <a href="chitiet.php?id=<?php echo $rp['MaSanPham']; ?>"
                                    class="block w-full bg-gray-900 text-white text-center py-2 rounded-lg text-sm font-bold hover:bg-black transition">Xem
                                    chi tiết</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">Chưa có sản phẩm liên quan.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-10 border border-gray-100">
            <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">ĐÁNH GIÁ SẢN PHẨM</h3>
            </div>


            <div class="p-8">
                <div
                    class="flex flex-col md:flex-row gap-8 items-center border border-gray-200 rounded-xl p-6 bg-red-50/20">
                    <div
                        class="flex flex-col items-center gap-2 w-full md:w-auto text-center border-r md:border-r-0 md:border-b-0 pr-0 md:pr-10 border-gray-200">
                        <p class="text-5xl font-black text-red-600"><?php echo number_format($rating_avg, 1); ?> <span
                                class="text-xl font-medium text-red-600">trên 5</span></p>
                        <div class="flex text-yellow-400 text-2xl">
                            <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating_avg) echo '<i class="fas fa-star"></i>';
                                    elseif ($i - 0.5 <= $rating_avg) echo '<i class="fas fa-star-half-alt"></i>';
                                    else echo '<i class="far fa-star text-gray-300"></i>';
                                }
                            ?>
                        </div>
                    </div>

                    <div class="flex-1 flex gap-2 flex-wrap justify-center md:justify-start" id="review-filters">
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium transition border-blue-500 text-blue-600 shadow-sm shadow-blue-100"
                            data-filter="all">Tất Cả (<?php echo $total_reviews; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="5">5 Sao (<?php echo $star_counts[5]; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="4">4 Sao (<?php echo $star_counts[4]; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="3">3 Sao (<?php echo $star_counts[3]; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="2">2 Sao (<?php echo $star_counts[2]; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="1">1 Sao (<?php echo $star_counts[1]; ?>)</button>
                        <button
                            class="filter-btn px-5 py-2.5 border rounded-md text-sm bg-white font-medium hover:bg-gray-100 transition"
                            data-filter="comment">Có Bình Luận (<?php echo $total_comment_reviews; ?>)</button>
                    </div>
                </div>
            </div>

            <div class="review-list">
                <?php if (count($reviews_data) > 0): ?>
                <?php foreach ($reviews_data as $rv): 
                        $so_sao = (int)$rv['SoSao'];
                        $noi_dung = trim($rv['NoiDung']);
                        $co_binh_luan = !empty($noi_dung) ? 'yes' : 'no';
                        
                        $ten_user = htmlspecialchars($rv['TenNguoiDung'] ?? 'Khách');

                        $avatar_db = trim(str_replace(["'", '"'], "", $rv['AnhDaiDien']));
                        if ($avatar_db !== "default.png" && !empty($avatar_db)) {
                            $avatar = "public/images/" . $avatar_db;
                        } else {
                            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($ten_user) . "&background=random&color=fff&size=128";
                        }
                    ?>
                <div class="review-item p-8 border-t border-gray-100 flex gap-5" data-star="<?php echo $so_sao; ?>"
                    data-comment="<?php echo $co_binh_luan; ?>">
                    <img src="<?php echo htmlspecialchars($avatar); ?>"
                        class="w-12 h-12 object-cover rounded-full border flex-shrink-0 shadow-sm" alt="Avatar">
                    <div class="flex-1 flex flex-col gap-2.5">
                        <p class="font-bold text-gray-900 text-[15px]"><?php echo $ten_user; ?></p>
                        <div class="flex text-yellow-400 text-sm gap-0.5">
                            <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $so_sao) echo '<i class="fas fa-star"></i>';
                                    else echo '<i class="far fa-star text-gray-300"></i>';
                                }
                                ?>
                        </div>
                        <div class="text-xs text-gray-500 mb-1">
                            <?php echo date('d/m/Y H:i', strtotime($rv['NgayBinhLuan'])); ?></div>
                        <p class="text-gray-900 text-[15px] mt-2 mb-3 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($noi_dung)); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="p-8 text-center text-gray-500 font-medium">Chưa có đánh giá nào cho sản phẩm này.
                    Hãy là
                    người đầu tiên đánh giá!</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-12 border border-gray-100">
            <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">VIẾT ĐÁNH GIÁ CỦA BẠN</h3>
            </div>
            <div class="p-8">
                <?php if(isset($_SESSION['user_id'])): ?>
                <form id="review_form" action="xuly_danhgia.php" method="POST" onsubmit="return validateReviewForm();"
                    class="space-y-6">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                    <input type="hidden" name="rating" id="rating_value" value="0">

                    <div>
                        <label class="block font-semibold mb-2.5 text-gray-800">Chọn số sao của bạn *:</label>
                        <div class="flex text-gray-300 text-3xl gap-1.5 cursor-pointer" id="star_rating_form">
                            <button type="button" class="transition hover:text-yellow-400" data-star="1"><i
                                    class="far fa-star"></i></button>
                            <button type="button" class="transition hover:text-yellow-400" data-star="2"><i
                                    class="far fa-star"></i></button>
                            <button type="button" class="transition hover:text-yellow-400" data-star="3"><i
                                    class="far fa-star"></i></button>
                            <button type="button" class="transition hover:text-yellow-400" data-star="4"><i
                                    class="far fa-star"></i></button>
                            <button type="button" class="transition hover:text-yellow-400" data-star="5"><i
                                    class="far fa-star"></i></button>
                        </div>
                        <p class="text-sm font-semibold text-red-500 mt-2 hidden" id="star_error_msg"><i
                                class="fas fa-exclamation-circle"></i> Vui lòng chọn số sao đánh giá!</p>
                    </div>

                    <div>
                        <label for="review_comment" class="block font-semibold mb-2.5 text-gray-800">Nội dung đánh
                            giá
                            *:</label>
                        <textarea name="comment" id="review_comment" rows="6"
                            placeholder="Chia sẻ cảm nhận của bạn về sản phẩm: Chất lượng vải, đường may, form dáng, dịch vụ giao hàng..."
                            class="w-full px-5 py-3.5 border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 bg-gray-50"
                            required></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <button type="submit" name="submit_review"
                            class="bg-blue-600 text-white font-bold py-3.5 px-10 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition text-lg flex items-center justify-center gap-2">
                            Gửi đánh giá <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                    <p class="text-gray-700 mb-3">Bạn cần đăng nhập để có thể gửi bình luận.</p>
                    <a href="login.php"
                        class="inline-block bg-blue-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-blue-700 transition">Äáº¿n
                        trang Ä‘Äƒng nháº­p</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>


    <?php include 'chatbox.php'; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. JS XỬ LÝ SỐ LƯỢNG, MÀU, SIZE VÀ NÚT GIỎ HÀNG ---
        let currentQty = 1;
        let currentSize =
            '<?php echo (isset($has_sizes) && $has_sizes && count($size_array) > 0) ? htmlspecialchars($size_array[0]) : ""; ?>';
        let currentColor = '<?php echo !empty($row['mau1']) ? htmlspecialchars($row['mau1']) : 'MacDinh'; ?>';
        let productId = <?php echo $id; ?>;

        const mainImg = document.getElementById('main-image');
        const thumbs = document.querySelectorAll('.thumb-img');
        let currentImgIndex = 0;

        function updateMainImage(index) {
            if (thumbs.length === 0) return;
            thumbs.forEach(t => t.classList.remove('thumb-active'));
            thumbs[index].classList.add('thumb-active');
            mainImg.src = thumbs[index].src;
            currentImgIndex = index;
        }

        thumbs.forEach((thumb, index) => {
            thumb.addEventListener('click', () => updateMainImage(index));
        });

        document.getElementById('btn-next-img').addEventListener('click', () => {
            if (thumbs.length > 0) {
                let next = (currentImgIndex + 1) % thumbs.length;
                updateMainImage(next);
            }
        });

        document.getElementById('btn-prev-img').addEventListener('click', () => {
            if (thumbs.length > 0) {
                let prev = (currentImgIndex - 1 + thumbs.length) % thumbs.length;
                updateMainImage(prev);
            }
        });

        setInterval(() => {
            if (thumbs.length > 0) {
                let next = (currentImgIndex + 1) % thumbs.length;
                updateMainImage(next);
            }
        }, 4000);

        const btnSizes = document.querySelectorAll('.btn-size');
        btnSizes.forEach(btn => {
            btn.addEventListener('click', () => {
                btnSizes.forEach(b => b.classList.remove('size-active'));
                btn.classList.add('size-active');
                currentSize = btn.getAttribute('data-size');
                updateLinks();
            });
        });

        const btnColors = document.querySelectorAll('.btn-color');
        btnColors.forEach(btn => {
            btn.addEventListener('click', function() {
                btnColors.forEach(b => b.classList.remove('color-active'));
                this.classList.add('color-active');
                currentColor = this.getAttribute('data-color');
                updateLinks();

                const imgInside = this.querySelector('img');
                if (imgInside) {
                    mainImg.src = imgInside.src;
                    thumbs.forEach((t, index) => {
                        t.classList.remove('thumb-active');
                        if (t.src === imgInside.src) {
                            t.classList.add('thumb-active');
                            currentImgIndex = index;
                        }
                    });
                }
            });
        });

        const inputQty = document.getElementById('input-qty');
        document.getElementById('btn-plus').addEventListener('click', () => {
            currentQty++;
            inputQty.value = currentQty;
            updateLinks();
        });
        document.getElementById('btn-minus').addEventListener('click', () => {
            if (currentQty > 1) {
                currentQty--;
                inputQty.value = currentQty;
                updateLinks();
            }
        });

        // HÀM QUAN TRỌNG: CẬP NHẬT LINK KHI BẤM "THÊM GIỎ HÀNG" HOẶC "MUA NGAY"
        function updateLinks() {
            const addCartBtn = document.getElementById('btn-add-cart');
            const buyNowBtn = document.getElementById('btn-buy-now');

            if (addCartBtn && buyNowBtn) {
                const params =
                    `?id=${productId}&size=${encodeURIComponent(currentSize)}&color=${encodeURIComponent(currentColor)}&qty=${currentQty}`;

                // Lưu đường dẫn thực sự vào data-href để xử lý AJAX, còn href = "#" để chặn chuyển trang
                addCartBtn.dataset.href = `xuly_giohang.php${params}&ajax=1`;
                addCartBtn.href = "#";

                // Nút Mua ngay vẫn giữ nguyên tính năng chuyển trang
                buyNowBtn.href = `xuly_giohang.php${params}&action=buynow`;
            }
        }
        updateLinks();

        // --- SỰ KIỆN BẤM NÚT THÊM VÀO GIỎ HÀNG BẰNG AJAX ---
        const addCartBtn = document.getElementById('btn-add-cart');
        if (addCartBtn) {
            addCartBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Ngăn trình duyệt nhảy sang trang xử lý giỏ hàng
                const url = this.dataset.href;

                // Gọi ngầm file PHP để thêm dữ liệu vào session giỏ hàng
                const request = window.addCartByUrl ? window.addCartByUrl(url, this) : fetch(url).then(
                    response => response.json());
                request
                    .then(data => {
                        if (!data || !data.success) {
                            throw new Error('Add cart failed');
                        }
                        // 1. Hiện thông báo (Toast) thành công
                        // SweetAlert2 toast is shown by addCartByUrl().

                        // Ẩn thông báo sau 3 giây


                        // 2. Cập nhật con số hiển thị trên icon giỏ hàng
                        if (window.updateGlobalCartCount && data.cart_count !== undefined) {
                            window.updateGlobalCartCount(data.cart_count);
                        }

                        const cartIconContainer = document.getElementById('cart-icon-container');
                        if (cartIconContainer) {
                            let badge = document.getElementById('cart-badge');
                            if (badge) {
                                // Nếu đã có số, thì cộng thêm vào
                                badge.innerText = parseInt(badge.innerText) + currentQty;
                            } else {
                                // Nếu trước đó giỏ hàng trống, tạo thẻ badge mới
                                cartIconContainer.innerHTML +=
                                    `<span id="cart-badge" class="absolute -top-1 right-2.5 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center border border-white">${currentQty}</span>`;
                            }
                        }
                    })
                    .catch(error => {
                        console.error("Lỗi:", error);
                        alert(
                            "Đã xảy ra lỗi khi thêm vào giỏ hàng. Vui lòng thử lại!"
                        );
                    });
            });
        }

        // --- 2. JS THẢ TIM ---
        const btnLike = document.getElementById('btn-like');
        const iconLike = document.getElementById('icon-like');
        const likeCount = document.getElementById('like-count');

        function updateWishlistBadge(total) {
            const wishlistBadge = document.getElementById('globalWishlistCount');
            if (!wishlistBadge || total === undefined) return;

            const count = parseInt(total, 10) || 0;
            wishlistBadge.textContent = count;
            wishlistBadge.classList.toggle('hidden', count <= 0);
            wishlistBadge.style.transform = 'scale(1.3)';
            setTimeout(() => wishlistBadge.style.transform = 'scale(1)', 200);
        }

        function showWishlistToast(isLiked) {
            if (!window.Swal) return;

            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: isLiked ? 'success' : 'info',
                title: isLiked ? 'Đã thêm vào danh sách yêu thích' :
                    'Đã bỏ khỏi danh sách yêu thích',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true
            });
        }

        if (btnLike) {
            btnLike.addEventListener('click', () => {
                const formData = new FormData();
                formData.append('action', 'toggle_like');
                formData.append('product_id', productId);

                fetch('chitiet.php?id=' + productId, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') {
                            alert(data.message);
                            window.location.href = 'login.php';
                        } else if (data.status === 'success') {
                            likeCount.textContent = data.total_likes;
                            updateWishlistBadge(data.total_user_wishlist);
                            showWishlistToast(data.is_liked);
                            if (data.is_liked) {
                                btnLike.classList.remove('text-gray-500');
                                btnLike.classList.add('text-red-500');
                                iconLike.classList.remove('far');
                                iconLike.classList.add('fas');
                            } else {
                                btnLike.classList.remove('text-red-500');
                                btnLike.classList.add('text-gray-500');
                                iconLike.classList.remove('fas');
                                iconLike.classList.add('far');
                            }
                        }
                    })
                    .catch(err => console.error('Lỗi:', err));
            });
        }

        // --- 3. JS LỌC BÌNH LUẬN ---
        const filterBtns = document.querySelectorAll('.filter-btn');
        const reviewItems = document.querySelectorAll('.review-item');

        if (filterBtns.length > 0) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    filterBtns.forEach(b => {
                        b.classList.remove('border-blue-500', 'text-blue-600',
                            'shadow-sm', 'shadow-blue-100');
                    });
                    btn.classList.add('border-blue-500', 'text-blue-600', 'shadow-sm',
                        'shadow-blue-100');

                    const filterValue = btn.getAttribute('data-filter');

                    reviewItems.forEach(item => {
                        const itemStar = item.getAttribute('data-star');
                        const itemComment = item.getAttribute('data-comment');

                        if (filterValue === 'all') {
                            item.style.display = 'flex';
                        } else if (filterValue === 'comment') {
                            item.style.display = itemComment === 'yes' ? 'flex' :
                                'none';
                        } else {
                            item.style.display = itemStar === filterValue ? 'flex' :
                                'none';
                        }
                    });
                });
            });
        }

        // --- 4. JS CHỌN SAO ĐÁNH GIÁ ---
        const starButtons = document.querySelectorAll('#star_rating_form button');
        const ratingInput = document.getElementById('rating_value');
        const errorMsg = document.getElementById('star_error_msg');

        if (starButtons.length > 0) {
            starButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    let value = this.getAttribute('data-star');
                    ratingInput.value = value;
                    errorMsg.classList.add('hidden');

                    starButtons.forEach((b, index) => {
                        let icon = b.querySelector('i');
                        if (index < value) {
                            icon.classList.remove('far', 'text-gray-300');
                            icon.classList.add('fas', 'text-yellow-400');
                        } else {
                            icon.classList.remove('fas', 'text-yellow-400');
                            icon.classList.add('far', 'text-gray-300');
                        }
                    });
                });
            });
        }
    });

    // --- 5. RÀNG BUỘC KHI GỬI BÌNH LUẬN ---
    function validateReviewForm() {
        const ratingInput = document.getElementById('rating_value');
        const errorMsg = document.getElementById('star_error_msg');

        if (ratingInput.value === "0") {
            errorMsg.classList.remove('hidden');
            return false;
        }
        return true;
    }
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>