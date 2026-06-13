<?php
session_start();

/* Chặn truy cập trang giỏ khi chưa đăng nhập */
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Bạn phải đăng nhập để xem giỏ hàng!'); window.location.href='login.php';</script>";
    exit();
}

/* Cấm admin thao tác giỏ hàng */
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' && isset($_GET['action'])) {
    echo "<script>alert('Admin không được thao tác giỏ hàng / mua hàng. Vui lòng dùng tài khoản User phụ.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

// Đồng bộ giỏ hàng từ cookie (nếu có) vào session cho user đang đăng nhập
if (empty($_SESSION['cart']) && isset($_COOKIE['shopping_cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['shopping_cart'], true);
}

require_once "database.php";
$db = new Database();


/* =========================
   XỬ LÝ AJAX / GIỎ HÀNG
========================= */
if (isset($_GET['action'])) {

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    switch ($_GET['action']) {

        case 'delete':
            if (isset($_SESSION['cart'][$id])) {
                unset($_SESSION['cart'][$id]);
            }
            break;

        case 'update':
            $change = isset($_GET['change']) ? intval($_GET['change']) : 0;

            if (isset($_SESSION['cart'][$id])) {

                $_SESSION['cart'][$id]['soluong'] += $change;

                if ($_SESSION['cart'][$id]['soluong'] < 1) {
                    unset($_SESSION['cart'][$id]);
                }
            }
            break;

        case 'clear':
            unset($_SESSION['cart']);
            break;
    }

    /* =========================
       UPDATE COOKIE
    ========================= */
    if (!empty($_SESSION['cart'])) {

        setcookie(
            'shopping_cart',
            json_encode($_SESSION['cart']),
            time() + (86400 * 30),
            "/"
        );

    } else {

        setcookie(
            'shopping_cart',
            '',
            time() - 3600,
            "/"
        );
    }

    /* =========================
       AJAX RESPONSE
    ========================= */
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {

        $subtotal = 0;

        if (!empty($_SESSION['cart'])) {

            foreach ($_SESSION['cart'] as $item) {

                $subtotal += $item['gia'] * $item['soluong'];
            }
        }

        $shipping = ($subtotal > 500000 || $subtotal == 0) ? 0 : 30000;

        $total = $subtotal + $shipping;

        header('Content-Type: application/json');

        echo json_encode([
            'empty'      => empty($_SESSION['cart']),
            'item_qty'   => isset($_SESSION['cart'][$id])
                ? $_SESSION['cart'][$id]['soluong']
                : 0,

            'item_total' => isset($_SESSION['cart'][$id])
                ? number_format(
                    $_SESSION['cart'][$id]['gia'] *
                    $_SESSION['cart'][$id]['soluong']
                ) . 'đ'
                : '0đ',

            'subtotal'   => number_format($subtotal) . 'đ',
            'shipping'   => number_format($shipping) . 'đ',
            'total'      => number_format($total) . 'đ'
        ]);

        exit();
    }

    header("Location: cart.php");
    exit();
}

/* =========================
   TÍNH TIỀN
========================= */
$subtotal = 0;

if (!empty($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $item) {

        $subtotal += $item['gia'] * $item['soluong'];
    }
}

// =========================================
// MÃ GIẢM GIÁ
// =========================================

// Tự động áp dụng mã tốt nhất từ ví nếu chưa áp mã
if (empty($_SESSION['applied_discount']) && isset($_SESSION['user_id']) && $subtotal > 0) {
    $uid = intval($_SESSION['user_id']);
    $res_auto = $db->select("
        SELECT dc.* 
        FROM user_saved_codes usc
        JOIN discount_codes dc ON usc.discount_id = dc.id
        WHERE usc.user_id = $uid 
          AND usc.is_used = 0
          AND dc.is_active = 1 
          AND dc.start_date <= NOW() 
          AND dc.end_date >= NOW()
          AND (dc.usage_limit = 0 OR dc.used_count < dc.usage_limit)
          AND dc.min_order_value <= $subtotal
    ");
    
    $best = null;
    $best_amount = 0;
    if ($res_auto && $res_auto->num_rows > 0) {
        while ($d = $res_auto->fetch_assoc()) {
            $amt = ($d['discount_type'] === 'percent') ? intval($subtotal * $d['discount_value'] / 100) : intval($d['discount_value']);
            if ($amt > $subtotal) $amt = $subtotal;
            if ($amt > $best_amount) {
                $best_amount = $amt;
                $best = $d;
            }
        }
    }
    
    if ($best) {
        $_SESSION['applied_discount'] = [
            'id'     => $best['id'],
            'code'   => $best['code'],
            'amount' => $best_amount,
            'type'   => $best['discount_type'],
            'value'  => $best['discount_value']
        ];
    }
}

$discount_amount = 0;
$discount_code_applied = '';
if (!empty($_SESSION['applied_discount'])) {
    // Tánh lại discount dựa trên subtotal mới nhất
    $d = $_SESSION['applied_discount'];
    if ($d['type'] === 'percent') {
        $discount_amount = intval($subtotal * $d['value'] / 100);
    } else {
        $discount_amount = intval($d['value']);
    }
    if ($discount_amount > $subtotal) $discount_amount = $subtotal;
    $discount_code_applied = $d['code'];
    $_SESSION['applied_discount']['amount'] = $discount_amount; // cập nhật
}
$total = $subtotal + $shipping - $discount_amount;
?>
<?php 
$page_title = "Giỏ Hàng - THP SHOP";
include 'header.php'; 
?>


<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="flex justify-between items-center mb-8">

        <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center gap-2">
            <i class="fas fa-shopping-cart text-blue-600"></i>
            Giỏ Hàng Của Bạn
        </h1>

        <?php if (!empty($_SESSION['cart'])): ?>

        <a href="cart.php?action=clear"
            onclick="return confirm('Bạn chắc chắn muốn xóa toàn bộ sản phẩm trong giỏ hàng?')"
            class="text-sm font-semibold text-red-500 hover:text-red-700 transition flex items-center gap-1.5">

            <i class="fas fa-trash-alt"></i>
            Xóa sạch giỏ hàng

        </a>

        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['cart'])): ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- DANH SÁCH SẢN PHẨM -->
        <div class="lg:col-span-2 space-y-4">

            <?php foreach ($_SESSION['cart'] as $key => $item): ?>

            <div id="product_row_<?php echo $key; ?>"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col sm:flex-row items-center gap-5 transition hover:shadow-md">

                <img src="./public/images/<?php echo !empty($item['hinh']) ? htmlspecialchars($item['hinh']) : 'default.jpg'; ?>"
                    class="w-24 h-24 object-cover bg-gray-50 rounded-xl border shrink-0 shadow-inner">

                <div class="flex-1 text-center sm:text-left min-w-0">

                    <h3 class="text-base font-bold text-gray-900 truncate">
                        <?php echo htmlspecialchars($item['ten']); ?>
                    </h3>

                    <p class="text-xs text-gray-400 mt-1 font-medium">

                        Phân loại:

                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-bold mx-1">
                            <?php echo htmlspecialchars($item['size']); ?>
                        </span>

                        /

                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-md font-bold mx-1">
                            <?php echo htmlspecialchars($item['color']); ?>
                        </span>

                    </p>

                    <div class="text-sm font-bold text-gray-800 mt-2">
                        <?php echo number_format($item['gia']); ?>đ
                    </div>

                </div>

                <!-- QUANTITY -->
                <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 p-1">

                    <button onclick="updateCartAjax('<?php echo $key; ?>', -1)"
                        class="w-8 h-8 rounded-lg bg-white text-gray-600 hover:bg-gray-100 flex items-center justify-center transition font-bold">
                        -
                    </button>

                    <span id="qty_<?php echo $key; ?>" class="px-4 font-bold text-sm text-gray-800 select-none">

                        <?php echo $item['soluong']; ?>

                    </span>

                    <button onclick="updateCartAjax('<?php echo $key; ?>', 1)"
                        class="w-8 h-8 rounded-lg bg-white text-gray-600 hover:bg-gray-100 flex items-center justify-center transition font-bold">
                        +
                    </button>

                </div>

                <!-- TỔNG -->
                <div class="text-right min-w-[100px]">

                    <div id="item_total_<?php echo $key; ?>" class="text-base font-black text-blue-600">

                        <?php echo number_format($item['gia'] * $item['soluong']); ?>đ

                    </div>

                    <button onclick="updateCartAjax('<?php echo $key; ?>', 'delete')"
                        class="text-xs font-semibold text-gray-400 hover:text-red-500 transition mt-1.5">

                        <i class="far fa-trash-alt mr-1"></i>
                        Xóa

                    </button>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

        <!-- TÓM TẮT -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit space-y-6 sticky top-28">

            <!-- MÃ GIẢM GIÁ -->
            <div class="border border-dashed border-blue-200 rounded-xl bg-blue-50/50 p-4 space-y-3">
                <p class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-ticket-alt text-blue-500"></i> Mã giảm giá
                </p>

                <?php if (!empty($discount_code_applied)): ?>
                <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                    <span class="text-sm font-bold text-green-700"><i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($discount_code_applied); ?></span>
                    <button onclick="removeDiscount()" class="text-xs text-red-500 hover:text-red-700 font-semibold transition">× Xóa</button>
                </div>
                <?php else: ?>
                <div class="flex gap-2">
                    <input type="text" id="discount-input" placeholder="Nhập mã giảm giá..."
                        class="flex-1 text-sm px-3 py-2 border border-gray-200 rounded-lg outline-none focus:border-blue-400 bg-white uppercase"
                        oninput="this.value=this.value.toUpperCase()">
                    <button onclick="applyDiscount()" class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">Áp mã</button>
                </div>
                <div id="discount-msg" class="text-xs hidden"></div>
                <a href="magiamgia.php" class="text-xs text-blue-500 hover:underline flex items-center gap-1">
                    <i class="fas fa-tag"></i>Xem kho mã giảm giá
                </a>
                <?php endif; ?>
            </div>

            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">
                Tóm tắt đơn hàng
            </h3>

            <div class="space-y-3.5 text-sm font-medium text-gray-600">

                <div class="flex justify-between">
                    <span>Tổng tiền sản phẩm:</span>

                    <span id="subtotal" class="text-gray-900 font-bold">
                        <?php echo number_format($subtotal); ?>đ
                    </span>
                </div>

                <div class="flex justify-between">
                    <span>Phí vận chuyển:</span>

                    <span id="shipping" class="text-gray-900 font-bold">
                        <?php echo number_format($shipping); ?>đ
                    </span>
                </div>

                <?php if ($discount_amount > 0): ?>
                <div class="flex justify-between text-green-600">
                    <span class="font-semibold"><i class="fas fa-tag mr-1 text-xs"></i>Giảm giá:</span>
                    <span class="font-bold">-<?php echo number_format($discount_amount); ?>đ</span>
                </div>
                <?php endif; ?>

                <?php if ($shipping == 0 && $subtotal > 0): ?>

                <div class="text-xs text-green-600 bg-green-50 rounded-lg p-2.5 font-semibold">

                    <i class="fas fa-check-circle mr-1"></i>
                    Đơn hàng được miễn phí vận chuyển

                </div>

                <?php endif; ?>

                <div class="border-t border-gray-100 my-4 pt-4 flex justify-between items-center">

                    <span class="text-base text-gray-800 font-bold">
                        Tổng thanh toán:
                    </span>

                    <span id="total" class="text-2xl font-black text-red-600">

                        <?php echo number_format($total); ?>đ

                    </span>

                </div>

            </div>

            <div class="space-y-3 pt-2">

                <a href="checkout.php"
                    class="block w-full text-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg transition uppercase tracking-wide text-sm">

                    Tiến hành đặt hàng

                </a>

                <a href="index.php"
                    class="block w-full text-center font-bold text-sm bg-gray-50 hover:bg-gray-100 text-gray-700 py-3.5 rounded-xl transition border border-gray-200">

                    Tiếp tục mua sắm

                </a>

            </div>

        </div>

    </div>

    <?php else: ?>

    <!-- GIỎ HÀNG RỖNG -->
    <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center shadow-sm max-w-xl mx-auto my-12">

        <div
            class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">

            <i class="fas fa-shopping-cart text-3xl animate-bounce"></i>

        </div>

        <h2 class="text-2xl font-black text-gray-900 mb-2">
            Giỏ hàng rỗng!
        </h2>

        <p class="text-sm text-gray-500 mb-8 max-w-sm mx-auto">

            Bạn chưa thêm sản phẩm nào vào giỏ hàng.

        </p>

        <a href="index.php"
            class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold px-8 py-3.5 rounded-xl shadow-lg transition">

            Quay về Trang Chủ

        </a>

    </div>

    <?php endif; ?>

</div>

<script>
async function updateCartAjax(productId, change) {

    if (change === 'delete') {

        const confirmDelete = confirm(
            'Bạn chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?'
        );

        if (!confirmDelete) return;
    }

    let url =
        `cart.php?action=update&id=${productId}&change=${change}&ajax=1`;

    if (change === 'delete') {

        url =
            `cart.php?action=delete&id=${productId}&ajax=1`;
    }

    try {

        const response = await fetch(url);

        const data = await response.json();

        if (data.empty) {

            window.location.reload();

            return;
        }

        if (data.item_qty <= 0 || change === 'delete') {

            const row = document.getElementById(
                `product_row_${productId}`
            );

            if (row) row.remove();

        } else {

            const qty = document.getElementById(
                `qty_${productId}`
            );

            const itemTotal = document.getElementById(
                `item_total_${productId}`
            );

            if (qty) qty.innerText = data.item_qty;

            if (itemTotal) itemTotal.innerText = data.item_total;
        }

        document.getElementById('subtotal').innerText =
            data.subtotal;

        document.getElementById('shipping').innerText =
            data.shipping;

        document.getElementById('total').innerText =
            data.total;

        /* UPDATE CART COUNT */
        let countRes = 0;

        let qtyNodes = document.querySelectorAll(
            "[id^='qty_']"
        );

        qtyNodes.forEach(node => {

            countRes += parseInt(node.innerText);
        });

        const globalCart = document.getElementById(
            'globalCartCount'
        );

        if (globalCart) {

            globalCart.innerText = countRes;
        }

    } catch (error) {

        console.error('Lỗi cập nhật giỏ hàng:', error);
    }
}

async function applyDiscount() {
    const code = document.getElementById('discount-input').value.trim();
    if (!code) return;
    const msgEl = document.getElementById('discount-msg');
    msgEl.className = 'text-xs text-gray-400';
    msgEl.classList.remove('hidden');
    msgEl.textContent = 'Đang kiểm tra mã...';

    const res = await fetch('xuly_magiamgia.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=apply&code=' + encodeURIComponent(code)
    });
    const data = await res.json();
    if (data.success) {
        msgEl.className = 'text-xs text-green-600 font-semibold';
        msgEl.textContent = data.message;
        setTimeout(() => window.location.reload(), 800);
    } else {
        msgEl.className = 'text-xs text-red-500 font-semibold';
        msgEl.textContent = data.message;
    }
    msgEl.classList.remove('hidden');
}

async function removeDiscount() {
    await fetch('xuly_magiamgia.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=remove'
    });
    window.location.reload();
}

// Tự động gợi ý mã tốt nhất khi tải trang
(async function autoSuggestDiscount() {
    <?php if (empty($discount_code_applied)): ?>
    const res = await fetch('xuly_magiamgia.php?action=auto');
    const data = await res.json();
    if (data.success) {
        const msgEl = document.getElementById('discount-msg');
        if (msgEl) {
            msgEl.className = 'text-xs text-blue-500 font-semibold';
            msgEl.innerHTML = `💡 Gợi ý: Mã <strong>${data.code}</strong> giảm ${data.label}. <button onclick="document.getElementById('discount-input').value='${data.code}'; applyDiscount();" class="underline text-blue-600">Áp dụng ngay</button>`;
            msgEl.classList.remove('hidden');
        }
    }
    <?php endif; ?>
})();
</script>

<?php include 'footer.php'; ?>