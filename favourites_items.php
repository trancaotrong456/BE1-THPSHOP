<?php
session_start();
require_once "database.php";

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// === 2. API XÓA 1 SẢN PHẨM KHỎI YÊU THÍCH (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'remove') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = intval($data['id']);
    
    $sql = "DELETE FROM wishlist WHERE IdNguoiDung = $user_id AND MaSanPham = $product_id";
    if ($db->execute($sql)) {
        $count_res = $db->select("SELECT COUNT(*) as total FROM wishlist WHERE IdNguoiDung = $user_id");
        $total_user_wishlist = $count_res ? (int)$count_res->fetch_assoc()['total'] : 0;
        echo json_encode(['success' => true, 'total_user_wishlist' => $total_user_wishlist]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// === 3. API XÓA TẤT CẢ YÊU THÍCH (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'clear_all') {
    header('Content-Type: application/json');
    $sql = "DELETE FROM wishlist WHERE IdNguoiDung = $user_id";
    if ($db->execute($sql)) {
        echo json_encode(['success' => true, 'total_user_wishlist' => 0]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// === 4. LẤY DANH SÁCH SẢN PHẨM YÊU THÍCH TỪ CSDL ===
$sql = "SELECT p.MaSanPham as id, p.TenSanPham as name, p.GiaSanPham as price, p.hinh as image, c.TenDanhMuc as category 
        FROM wishlist w 
        JOIN product p ON w.MaSanPham = p.MaSanPham 
        JOIN categories c ON p.MaDanhMuc = c.MaDanhMuc 
        WHERE w.IdNguoiDung = $user_id
        ORDER BY w.NgayThem DESC";

$result = $db->select($sql);
$products = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['image'] = 'public/images/' . $row['image'];
        $row['price'] = (int)$row['price'];
        $products[] = $row;
    }
}

// Mã hóa mảng PHP sang JSON
$productsJSON = json_encode($products);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/web_be1_wishlist.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm Yêu Thích - ShopTTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #1f2937;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .product-card {
        transition: all 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-8px);
    }

    .heart-btn {
        transition: all 0.2s ease;
    }

    .heart-btn:hover {
        transform: scale(1.1);
    }
    </style>
</head>

<body>
    <div id="app" class="min-h-screen bg-gradient-to-br from-pink-50 via-white to-purple-50 flex flex-col">

        <?php include 'header.php'; ?>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">

            <div class="mb-4">
                <a href="index.php"
                    class="inline-flex items-center gap-2 text-gray-500 hover:text-blue-600 transition font-medium text-sm">
                    <i class="fas fa-arrow-left"></i> Quay lại cửa hàng
                </a>
            </div>

            <div
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 md:p-6 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500 shrink-0">
                        <i class="fas fa-heart text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Sản phẩm yêu thích</h1>
                        <p class="text-gray-500 mt-1 text-sm md:text-base">
                            Bạn đang có <span class="font-bold text-red-500" id="product-count">0</span> sản phẩm trong
                            danh sách
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-48 group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="fas fa-layer-group text-sm"></i>
                        </div>
                        <select id="category-filter"
                            class="w-full pl-10 pr-8 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none cursor-pointer text-sm font-medium">
                            <option value="all">Tất cả danh mục</option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>

                    <div class="relative w-full sm:w-48 group">
                        <div
                            class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="fas fa-sort-amount-down text-sm"></i>
                        </div>
                        <select id="sort-filter"
                            class="w-full pl-10 pr-8 py-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none cursor-pointer text-sm font-medium">
                            <option value="default">Sắp xếp mặc định</option>
                            <option value="price-asc">Giá: Thấp đến cao</option>
                            <option value="price-desc">Giá: Cao đến thấp</option>
                            <option value="name">Tên A-Z</option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>

                    <button id="clear-all"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 border border-red-100 rounded-xl hover:bg-red-100 hover:text-red-700 transition font-medium w-full sm:w-auto shrink-0 h-[42px]">
                        <i class="fas fa-trash-alt"></i> Xóa tất cả
                    </button>
                </div>
            </div>

            <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            </div>

            <div id="stats"
                class="mt-12 bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white opacity-5 blur-2xl">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center relative z-10">
                    <div>
                        <p class="text-4xl font-black mb-1 text-pink-400" id="stat-count">0</p>
                        <p class="text-gray-400 font-medium">Sản phẩm yêu thích</p>
                    </div>
                    <div class="border-y md:border-y-0 md:border-x border-gray-700 py-4 md:py-0">
                        <p class="text-4xl font-black mb-1 text-red-400" id="stat-value">0K</p>
                        <p class="text-gray-400 font-medium">Tổng giá trị</p>
                    </div>
                    <div>
                        <p class="text-4xl font-black mb-1 text-blue-400" id="stat-categories">0</p>
                        <p class="text-gray-400 font-medium">Danh mục</p>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>

    <div id="toast" class="toast"></div>

    <script>
    let products = <?php echo $productsJSON; ?>;

    function showToast(message) {
        // Giữ toast element cũ nhưng ưu tiên SweetAlert2 theo yêu cầu UI
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2500,
            background: '#111827',
            color: '#fff',
            width: '320px'
        });
    }

    function formatPrice(price) {
        return price.toLocaleString('vi-VN') + 'đ';
    }

    function populateCategories() {
        const select = document.getElementById('category-filter');
        const categories = new Set(products.map(p => p.category));
        let html = '<option value="all">Tất cả danh mục</option>';
        categories.forEach(c => {
            html += `<option value="${c}">${c}</option>`;
        });
        select.innerHTML = html;
    }

    function createProductCard(product) {
        return `
            <div class="product-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group flex flex-col h-full">
                <div class="relative overflow-hidden aspect-[3/4]">
                    <a href="chitiet.php?id=${product.id}">
                        <img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </a>
                    <button onclick="removeFavorite(${product.id})" class="heart-btn absolute top-3 right-3 bg-white/90 backdrop-blur p-2.5 rounded-full shadow hover:bg-red-50 text-red-500 border border-red-100 transition" title="Xóa khỏi yêu thích">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <div class="p-5 flex flex-col flex-grow">
                    <span class="inline-block px-3 py-1 bg-purple-50 text-purple-700 border border-purple-100 text-xs font-bold rounded-full mb-3 self-start">${product.category}</span>
                    <a href="chitiet.php?id=${product.id}" class="block mb-2">
                        <h3 class="font-bold text-gray-900 line-clamp-2 hover:text-purple-600 transition">${product.name}</h3>
                    </a>
                    <div class="flex items-center justify-between mt-auto pt-4">
                        <p class="text-xl font-black text-red-500">${formatPrice(product.price)}</p>
                        <button onclick="addToCart(${product.id})" class="bg-gray-900 text-white w-10 h-10 flex items-center justify-center rounded-xl hover:bg-blue-600 transition shadow-md" title="Thêm vào giỏ">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function updateStats() {
        const totalValue = products.reduce((sum, p) => sum + p.price, 0);
        const categories = new Set(products.map(p => p.category));
        document.getElementById('product-count').textContent = products.length;
        document.getElementById('stat-count').textContent = products.length;
        document.getElementById('stat-value').textContent = Math.round(totalValue / 1000) + 'K';
        document.getElementById('stat-categories').textContent = categories.size;
    }

    function updateGlobalWishlistCount(total) {
        const badge = document.getElementById('globalWishlistCount');
        if (!badge || total === undefined) return;

        const count = parseInt(total, 10) || 0;
        badge.textContent = count;
        badge.classList.toggle('hidden', count <= 0);
        badge.style.transform = 'scale(1.3)';
        setTimeout(() => badge.style.transform = 'scale(1)', 200);
    }

    function renderProducts() {
        const categoryFilter = document.getElementById('category-filter').value;
        const sortFilter = document.getElementById('sort-filter').value;

        let filtered = [...products];

        if (categoryFilter !== 'all') filtered = filtered.filter(p => p.category === categoryFilter);

        if (sortFilter === 'price-asc') filtered.sort((a, b) => a.price - b.price);
        else if (sortFilter === 'price-desc') filtered.sort((a, b) => b.price - a.price);
        else if (sortFilter === 'name') filtered.sort((a, b) => a.name.localeCompare(b.name));

        const grid = document.getElementById('products-grid');

        if (filtered.length === 0) {
            grid.innerHTML = `
                <div class="col-span-full text-center py-20 bg-white rounded-2xl border border-dashed border-gray-300">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 rounded-full mb-4">
                        <i class="far fa-heart text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                        ${products.length === 0 ? 'Danh sách yêu thích trống' : 'Không tìm thấy sản phẩm'}
                    </h3>
                    <p class="text-gray-500">
                        ${products.length === 0 ? 'Hãy dạo quanh cửa hàng và thả tim cho sản phẩm bạn thích nhé.' : 'Thử thay đổi bộ lọc để xem sản phẩm khác.'}
                    </p>
                    ${products.length === 0 ? '<a href="index.php" class="mt-6 inline-block bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700 transition">Tiếp tục mua sắm</a>' : ''}
                </div>
            `;
        } else {
            grid.innerHTML = filtered.map(createProductCard).join('');
        }
        updateStats();
    }

    async function loadProductsFromServerAndRender() {
        window.location.href = `favourites_items.php?t=${Date.now()}`;
    }

    async function removeFavorite(id) {
        const product = products.find(p => Number(p.id) === Number(id));
        const productName = product?.name ? product.name : 'sản phẩm';

        const swalResult = await Swal.fire({
            title: 'Bạn có chắc muốn bỏ yêu thích sản phẩm này?',
            text: productName,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Có',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280'
        });

        if (!swalResult.isConfirmed) return;

        try {
            const response = await fetch(`?action=remove`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id
                })
            });

            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                Swal.fire({
                    icon: 'error',
                    title: 'Xóa thất bại',
                    text: 'Server không trả về dữ liệu hợp lệ.'
                });
                return;
            }

            if (data && data.success) {
                products = products.filter(p => Number(p.id) !== Number(id));
                populateCategories();
                renderProducts();
                updateGlobalWishlistCount(data.total_user_wishlist);

                Swal.fire({
                    icon: 'success',
                    title: 'Đã xóa thành công',
                    text: `Đã xóa "${productName}" khỏi danh sách yêu thích.`,
                    confirmButtonText: 'OK'
                });

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Xóa thất bại',
                    text: 'Có lỗi xảy ra khi xóa.'
                });
                console.error('Remove failed:', data);
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Xóa thất bại',
                text: 'Có lỗi xảy ra khi xóa.'
            });
        }
    }

    document.getElementById('clear-all').addEventListener('click', async () => {
        if (products.length <= 0) return;

        const swalResult = await Swal.fire({
            title: 'Bạn có chắc muốn xóa tất cả sản phẩm yêu thích?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Có',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280'
        });

        if (!swalResult.isConfirmed) return;

        try {
            const response = await fetch(`?action=clear_all`, {
                method: 'POST'
            });

            let data = null;
            try {
                data = await response.json();
            } catch (e) {
                console.error('Non-JSON response:', await response.text());
                Swal.fire({
                    icon: 'error',
                    title: 'Xóa thất bại',
                    text: 'Server không trả về dữ liệu hợp lệ.'
                });
                return;
            }

            if (data && data.success) {
                products = [];
                populateCategories();
                renderProducts();
                updateGlobalWishlistCount(data.total_user_wishlist);

                Swal.fire({
                    icon: 'success',
                    title: 'Đã xóa thành công',
                    text: 'Đã xóa toàn bộ danh sách yêu thích.',
                    confirmButtonText: 'OK'
                });

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Xóa thất bại',
                    text: 'Có lỗi xảy ra khi xóa.'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Xóa thất bại',
                text: 'Có lỗi xảy ra khi xóa.'
            });
        }
    });

    function addToCart(id) {
        const url = `xuly_giohang.php?id=${id}&ajax=1`;
        if (window.addCartByUrl) {
            window.addCartByUrl(url).catch(error => {
                console.error('Lỗi thêm giỏ hàng:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Thêm giỏ hàng thất bại',
                    text: 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ.'
                });
            });
            return;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.success && window.updateGlobalCartCount) {
                    window.updateGlobalCartCount(data.cart_count);
                }
            })
            .catch(error => console.error('Lỗi thêm giỏ hàng:', error));
    }

    document.getElementById('category-filter').addEventListener('change', renderProducts);
    document.getElementById('sort-filter').addEventListener('change', renderProducts);

    populateCategories();
    renderProducts();
    </script>
</body>

</html>