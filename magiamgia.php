<?php
session_start();
require_once "database.php";
$db = new Database();

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Lấy tất cả mã đang active & còn hạn
$discounts = $db->select("SELECT dc.*, 
    " . ($user_id > 0 ? "(SELECT COUNT(*) FROM user_saved_codes WHERE user_id = $user_id AND discount_id = dc.id) as is_saved" : "0 as is_saved") . "
    FROM discount_codes dc 
    WHERE dc.is_active = 1 AND dc.end_date >= NOW() AND dc.start_date <= NOW()
    ORDER BY dc.discount_value DESC");

$discs = [];
if ($discounts && $discounts->num_rows > 0) {
    while ($row = $discounts->fetch_assoc()) $discs[] = $row;
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khuyến Mãi & Mã Giảm Giá - THP SHOP</title>
    <style>
    .voucher-card {
        border: 2px dashed #e5e7eb;
        border-radius: 16px;
        background: #fff;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .voucher-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 8px 30px rgba(59,130,246,0.12);
        transform: translateY(-2px);
    }
    .voucher-card::before {
        content:'';
        position:absolute;
        left:0;top:0;bottom:0;
        width:6px;
        background: linear-gradient(180deg, #3b82f6, #6366f1);
        border-radius: 4px 0 0 4px;
    }
    .voucher-notch-left {
        position:absolute;
        left:-12px;top:50%;transform:translateY(-50%);
        width:24px;height:24px;
        background:#f5f5f3;
        border-radius:50%;
        border: 2px dashed #e5e7eb;
    }
    .voucher-notch-right {
        position:absolute;
        right:-12px;top:50%;transform:translateY(-50%);
        width:24px;height:24px;
        background:#f5f5f3;
        border-radius:50%;
        border: 2px dashed #e5e7eb;
    }
    .badge-percent { background: linear-gradient(135deg, #f59e0b, #ef4444); }
    .badge-fixed   { background: linear-gradient(135deg, #3b82f6, #6366f1); }
    </style>
</head>
<body>

<div class="max-w-5xl mx-auto px-4 py-12">
    <!-- Hero Banner -->
    <div class="relative rounded-3xl overflow-hidden mb-12 bg-gradient-to-r from-blue-600 to-indigo-700 p-8 md:p-14 text-white shadow-2xl shadow-blue-200">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-white/10 rounded-full"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full"></div>
        <div class="relative z-10">
            <p class="text-blue-200 uppercase tracking-widest text-xs font-bold mb-3">Ưu đãi đặc biệt</p>
            <h1 class="text-4xl md:text-5xl font-black mb-4 leading-tight">🎟️ Kho Voucher <br>Khuyến Mãi</h1>
            <p class="text-blue-100 text-base mb-6 max-w-lg">Lưu mã ngay hôm nay, áp dụng khi mua hàng để nhận ngay ưu đãi tốt nhất!</p>
            <?php if (!$user_id): ?>
            <a href="login.php" class="inline-block bg-white text-blue-600 font-bold px-6 py-3 rounded-xl hover:shadow-lg transition">
                <i class="fas fa-user mr-2"></i>Đăng nhập để lưu mã
            </a>
            <?php else: ?>
            <a href="user_profile.php?tab=vouchers" class="inline-block bg-white text-blue-600 font-bold px-6 py-3 rounded-xl hover:shadow-lg transition">
                <i class="fas fa-wallet mr-2"></i>Xem ví voucher của tôi
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bộ lọc -->
    <div class="flex gap-3 mb-8 flex-wrap">
        <button onclick="filterVouchers('all')" id="filter-all" class="filter-btn px-5 py-2 rounded-full text-sm font-bold bg-blue-600 text-white shadow">Tất cả</button>
        <button onclick="filterVouchers('percent')" id="filter-percent" class="filter-btn px-5 py-2 rounded-full text-sm font-bold bg-white border text-gray-600 hover:border-blue-400">Giảm theo %</button>
        <button onclick="filterVouchers('fixed')" id="filter-fixed" class="filter-btn px-5 py-2 rounded-full text-sm font-bold bg-white border text-gray-600 hover:border-blue-400">Giảm tiền cố định</button>
    </div>

    <!-- Danh sách Voucher -->
    <?php if (empty($discs)): ?>
    <div class="text-center py-20 text-gray-400">
        <i class="fas fa-ticket-alt text-5xl mb-4 block"></i>
        <p class="text-lg font-bold">Hiện chưa có mã khuyến mãi nào.</p>
        <p class="text-sm">Quay lại sau để săn deal nhé!</p>
    </div>
    <?php else: ?>
    <div id="voucher-list" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($discs as $d): 
            $is_expired = strtotime($d['end_date']) < time();
            $is_full = $d['usage_limit'] > 0 && $d['used_count'] >= $d['usage_limit'];
            $unavailable = $is_expired || $is_full;
            $label = $d['discount_type'] === 'percent' ? $d['discount_value'] . '%' : number_format($d['discount_value']) . 'đ';
            $badge_class = $d['discount_type'] === 'percent' ? 'badge-percent' : 'badge-fixed';
            $days_left = max(0, ceil((strtotime($d['end_date']) - time()) / 86400));
        ?>
        <div class="voucher-card px-8 py-5 pl-10 <?= $unavailable ? 'opacity-60' : ''; ?>" data-type="<?= $d['discount_type']; ?>">
            <div class="voucher-notch-left"></div>
            <div class="voucher-notch-right"></div>

            <div class="flex gap-5 items-center">
                <!-- Badge giá trị -->
                <div class="<?= $badge_class; ?> text-white rounded-2xl flex flex-col items-center justify-center px-3 min-w-[6.5rem] h-20 shrink-0 shadow-lg">
                    <span class="text-xl md:text-2xl font-black whitespace-nowrap"><?= $label; ?></span>
                    <span class="text-[10px] font-bold opacity-80 uppercase mt-0.5">GIẢM</span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-black text-gray-900 text-xl tracking-wider font-mono"><?= htmlspecialchars($d['code']); ?></span>
                        <button onclick="copyCode('<?= htmlspecialchars($d['code']); ?>')" class="text-blue-400 hover:text-blue-600 text-xs p-1" title="Sao chép mã">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-gray-500 text-xs mb-1">
                        <i class="fas fa-shopping-bag mr-1"></i>
                        Đơn tối thiểu: <strong><?= number_format($d['min_order_value']); ?>đ</strong>
                    </p>
                    <div class="flex items-center gap-2 flex-wrap text-xs text-gray-400">
                        <span><i class="fas fa-clock mr-1"></i>
                            <?php if ($unavailable): ?>
                                <span class="text-red-500 font-bold">Đã hết</span>
                            <?php elseif ($days_left <= 3): ?>
                                <span class="text-orange-500 font-bold">Còn <?= $days_left; ?> ngày!</span>
                            <?php else: ?>
                                HSD: <?= date('d/m/Y', strtotime($d['end_date'])); ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($d['usage_limit'] > 0): ?>
                        <span>•</span>
                        <span>Còn <?= max(0, $d['usage_limit'] - $d['used_count']); ?> lượt</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nút lưu -->
                <div class="shrink-0">
                    <?php if ($unavailable): ?>
                        <span class="text-xs text-red-400 font-bold">Hết hạn</span>
                    <?php elseif (!$user_id): ?>
                        <a href="login.php" class="bg-blue-50 text-blue-600 border border-blue-200 text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-600 hover:text-white transition">
                            <i class="fas fa-sign-in-alt mr-1"></i>Đăng nhập
                        </a>
                    <?php elseif ($d['is_saved']): ?>
                        <span class="bg-green-50 text-green-600 border border-green-200 text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-1">
                            <i class="fas fa-check"></i>Đã lưu
                        </span>
                    <?php else: ?>
                        <button onclick="saveVoucher(this, <?= $d['id']; ?>)" class="save-btn bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm shadow-blue-200">
                            <i class="fas fa-plus mr-1"></i>Lưu mã
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function showToast(msg, success = true) {
    if (window.Swal) {
        Swal.fire({
            toast: true,
            position: 'bottom-end',
            icon: success ? 'success' : 'error',
            title: msg,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        alert(msg);
    }
}

function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => showToast('Đã sao chép mã: ' + code));
}

function saveVoucher(btn, discountId) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang lưu...';
    fetch('xuly_magiamgia.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=save&discount_id=' + discountId
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success);
        if (data.success) {
            btn.className = 'bg-green-50 text-green-600 border border-green-200 text-xs font-bold px-4 py-2 rounded-lg flex items-center gap-1';
            btn.innerHTML = '<i class="fas fa-check"></i>Đã lưu';
            btn.disabled = true;
            btn.onclick = null;
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus mr-1"></i>Lưu mã';
        }
    });
}

function filterVouchers(type) {
    document.querySelectorAll('.filter-btn').forEach(b => b.className = 'filter-btn px-5 py-2 rounded-full text-sm font-bold bg-white border text-gray-600 hover:border-blue-400');
    document.getElementById('filter-' + type).className = 'filter-btn px-5 py-2 rounded-full text-sm font-bold bg-blue-600 text-white shadow';

    document.querySelectorAll('#voucher-list .voucher-card').forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.closest('div[data-type]') ? null : null;
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php include 'footer.php'; ?>
