<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once "database.php";
$db = new Database();

// ============================================================
// 1. THỐNG KÊ TỔNG QUAN
// ============================================================
$res_dt = $db->select("SELECT SUM(TongTien) as DoanhThu FROM donhang WHERE trangThai = '3'");
$tong_doanh_thu = ($res_dt && $res_dt->num_rows > 0) ? ($res_dt->fetch_assoc()['DoanhThu'] ?? 0) : 0;

$res_dh = $db->select("SELECT COUNT(MaDonHang) as SoDon FROM donhang");
$tong_don_hang = ($res_dh && $res_dh->num_rows > 0) ? $res_dh->fetch_assoc()['SoDon'] : 0;

$res_user = $db->select("SELECT COUNT(IdNguoiDung) as SoUser FROM user WHERE quyen = 'user'");
$tong_thanh_vien = ($res_user && $res_user->num_rows > 0) ? $res_user->fetch_assoc()['SoUser'] : 0;

// Đơn hàng hôm nay
$res_today = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE DATE(NgayDat) = CURDATE()");
$don_hom_nay = ($res_today && $res_today->num_rows > 0) ? $res_today->fetch_assoc()['cnt'] : 0;

// Doanh thu tháng này
$res_month = $db->select("SELECT SUM(TongTien) as dt FROM donhang WHERE MONTH(NgayDat) = MONTH(NOW()) AND YEAR(NgayDat) = YEAR(NOW()) AND trangThai = '3'");
$dt_thang_nay = ($res_month && $res_month->num_rows > 0) ? ($res_month->fetch_assoc()['dt'] ?? 0) : 0;

// Sản phẩm hết hàng
$res_hethang = $db->select("SELECT COUNT(*) as cnt FROM product WHERE SoLuong = 0");
$sp_het_hang = ($res_hethang && $res_hethang->num_rows > 0) ? $res_hethang->fetch_assoc()['cnt'] : 0;

// ============================================================
// 2. BIỂU ĐỒ 1: Doanh thu 7 ngày gần nhất (Line)
// ============================================================
$res_chart = $db->select("SELECT DATE(NgayDat) as Ngay, SUM(TongTien) as DoanhThuTrongNgay
              FROM donhang WHERE trangThai = '3'
              GROUP BY DATE(NgayDat) ORDER BY Ngay DESC LIMIT 7");
$days = []; $revenues = [];
if ($res_chart && $res_chart->num_rows > 0) {
    while ($row = $res_chart->fetch_assoc()) {
        array_unshift($days, date('d/m', strtotime($row['Ngay'])));
        array_unshift($revenues, (int)$row['DoanhThuTrongNgay']);
    }
}
if (empty($days)) {
    $days = ['Chưa có'];
    $revenues = [0];
}

// ============================================================
// 3. BIỂU ĐỒ 2: Đơn hàng theo trạng thái (Doughnut)
// ============================================================
$status_labels = ['Chờ xác nhận', 'Đóng gói', 'Đang giao', 'Hoàn thành', 'Đã hủy'];
$status_counts = [0, 0, 0, 0, 0];
$res_status = $db->select("SELECT trangThai, COUNT(*) as cnt FROM donhang GROUP BY trangThai");
if ($res_status && $res_status->num_rows > 0) {
    while ($row = $res_status->fetch_assoc()) {
        $idx = (int)$row['trangThai'];
        if ($idx >= 0 && $idx <= 4) $status_counts[$idx] = (int)$row['cnt'];
    }
}

// ============================================================
// 4. BIỂU ĐỒ 3: Top 5 sản phẩm bán chạy (Bar ngang)
// ============================================================
$top_names = []; $top_qty = [];
$res_top = $db->select("SELECT p.TenSanPham, SUM(c.SoLuong) as TongBan
    FROM chitietdonhang c JOIN product p ON c.MaSanPham = p.MaSanPham
    JOIN donhang d ON c.MaDonHang = d.MaDonHang
    WHERE d.trangThai IN ('2','3')
    GROUP BY c.MaSanPham ORDER BY TongBan DESC LIMIT 5");
if ($res_top && $res_top->num_rows > 0) {
    while ($row = $res_top->fetch_assoc()) {
        $name = mb_strlen($row['TenSanPham']) > 25
            ? mb_substr($row['TenSanPham'], 0, 25) . '...'
            : $row['TenSanPham'];
        $top_names[] = $name;
        $top_qty[]   = (int)$row['TongBan'];
    }
}
if (empty($top_names)) {
    $top_names = ['Chưa có dữ liệu']; $top_qty = [0];
}

// ============================================================
// 5. BIỂU ĐỒ 4: Doanh thu 12 tháng trong năm (Bar)
// ============================================================
$month_labels = []; $month_revenue = [];
for ($m = 1; $m <= 12; $m++) { $month_labels[] = "T{$m}"; $month_revenue[] = 0; }
$cur_year = date('Y');
$res_months = $db->select("SELECT MONTH(NgayDat) as Thang, SUM(TongTien) as dt
    FROM donhang WHERE YEAR(NgayDat) = {$cur_year} AND trangThai = '3'
    GROUP BY MONTH(NgayDat)");
if ($res_months && $res_months->num_rows > 0) {
    while ($row = $res_months->fetch_assoc()) {
        $month_revenue[(int)$row['Thang'] - 1] = (int)$row['dt'];
    }
}

// ============================================================
// 6. BIỂU ĐỒ 5: Thành viên mới 6 tháng gần nhất (Line)
// ============================================================
// Note: user table has no created_at; dùng IdNguoiDung không đủ, ta dùng donhang để gần đúng
// Thay thế: đếm đơn hàng 6 tháng gần nhất (đơn mới)
$month6_labels = []; $month6_orders = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = mktime(0, 0, 0, date('n') - $i, 1, date('Y'));
    $month6_labels[] = date('T/Y', $ts);
    $m_num = date('n', $ts);
    $y_num = date('Y', $ts);
    $r = $db->select("SELECT COUNT(*) as cnt FROM donhang WHERE MONTH(NgayDat)=$m_num AND YEAR(NgayDat)=$y_num");
    $month6_orders[] = ($r && $r->num_rows > 0) ? (int)$r->fetch_assoc()['cnt'] : 0;
}

// ============================================================
// 7. ĐƠN HÀNG GẦN ĐÂY (Table nhỏ)
// ============================================================
$res_recent = $db->select("SELECT d.MaDonHang, u.TenNguoiDung, d.TongTien, d.trangThai, d.NgayDat
    FROM donhang d JOIN user u ON d.IdNguoiDung = u.IdNguoiDung
    ORDER BY d.MaDonHang DESC LIMIT 5");
?>
<?php include 'admin_header.php'; ?>

<style>
.stat-card { transition: transform 0.2s, box-shadow 0.2s; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.chart-card { background:#fff; border-radius:16px; box-shadow:0 1px 8px rgba(0,0,0,0.06); border:1px solid #f3f4f6; padding:24px; }
.chart-title { font-size:15px; font-weight:700; color:#1f2937; margin-bottom:4px; display:flex; align-items:center; gap:8px; }
.chart-sub { font-size:12px; color:#9ca3af; margin-bottom:16px; }
</style>

<!-- ============================================================ -->
<!-- CARDS THỐNG KÊ TỔNG QUAN -->
<!-- ============================================================ -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">

    <div class="bg-white rounded-xl p-5 border-l-4 border-blue-500 stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Tổng Đơn</p>
        <h3 class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($tong_don_hang); ?></h3>
        <p class="text-blue-500 text-xs mt-1 font-medium">📦 Tất cả trạng thái</p>
    </div>

    <div class="bg-white rounded-xl p-5 border-l-4 border-green-500 stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Doanh Thu</p>
        <h3 class="text-2xl font-black text-green-600 mt-1"><?php echo number_format($tong_doanh_thu / 1000000, 1); ?>M</h3>
        <p class="text-green-500 text-xs mt-1 font-medium">💰 Đơn hoàn thành</p>
    </div>

    <div class="bg-white rounded-xl p-5 border-l-4 border-purple-500 stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Thành Viên</p>
        <h3 class="text-2xl font-black text-gray-800 mt-1"><?php echo number_format($tong_thanh_vien); ?></h3>
        <p class="text-purple-500 text-xs mt-1 font-medium">👥 Người dùng</p>
    </div>

    <div class="bg-white rounded-xl p-5 border-l-4 border-orange-400 stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Hôm Nay</p>
        <h3 class="text-2xl font-black text-gray-800 mt-1"><?php echo $don_hom_nay; ?></h3>
        <p class="text-orange-400 text-xs mt-1 font-medium">🛒 Đơn mới</p>
    </div>

    <div class="bg-white rounded-xl p-5 border-l-4 border-teal-500 stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Tháng Này</p>
        <h3 class="text-2xl font-black text-teal-600 mt-1"><?php echo number_format($dt_thang_nay / 1000000, 1); ?>M</h3>
        <p class="text-teal-500 text-xs mt-1 font-medium">📅 Doanh thu T<?php echo date('n'); ?></p>
    </div>

    <div class="bg-white rounded-xl p-5 border-l-4 <?php echo $sp_het_hang > 0 ? 'border-red-500' : 'border-gray-300'; ?> stat-card col-span-1">
        <p class="text-gray-400 text-xs uppercase font-bold tracking-wide">Hết Hàng</p>
        <h3 class="text-2xl font-black <?php echo $sp_het_hang > 0 ? 'text-red-600' : 'text-gray-400'; ?> mt-1"><?php echo $sp_het_hang; ?></h3>
        <p class="<?php echo $sp_het_hang > 0 ? 'text-red-400' : 'text-gray-400'; ?> text-xs mt-1 font-medium">⚠️ Sản phẩm</p>
    </div>

</div>

<!-- ============================================================ -->
<!-- ROW 1: Doanh thu 7 ngày + Đơn hàng theo trạng thái -->
<!-- ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    <!-- Doanh thu 7 ngày (Line) -->
    <div class="lg:col-span-2 chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center text-blue-500 text-xs">📈</span>
            Doanh thu 7 ngày gần nhất
        </div>
        <p class="chart-sub">Chỉ tính đơn hàng hoàn thành (trangThai = 3)</p>
        <div style="height:240px;"><canvas id="revenueChart"></canvas></div>
    </div>

    <!-- Đơn hàng theo trạng thái (Doughnut) -->
    <div class="chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-500 text-xs">🍩</span>
            Trạng thái đơn hàng
        </div>
        <p class="chart-sub">Phân bổ tất cả đơn hàng</p>
        <div style="height:220px;position:relative;"><canvas id="statusChart"></canvas></div>
        <!-- Legend tùy chỉnh -->
        <div class="mt-3 space-y-1">
            <?php
            $sc = ['#f59e0b','#6366f1','#3b82f6','#22c55e','#ef4444'];
            $sl = $status_labels;
            foreach ($sl as $i => $lbl): ?>
            <div class="flex items-center justify-between text-xs">
                <span class="flex items-center gap-1.5">
                    <span style="background:<?php echo $sc[$i]; ?>" class="w-2.5 h-2.5 rounded-full inline-block"></span>
                    <?php echo $lbl; ?>
                </span>
                <span class="font-bold text-gray-700"><?php echo $status_counts[$i]; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ============================================================ -->
<!-- ROW 2: Top 5 sản phẩm + Doanh thu 12 tháng -->
<!-- ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    <!-- Top 5 sản phẩm bán chạy (Bar ngang) -->
    <div class="chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center text-amber-500 text-xs">🏆</span>
            Top 5 sản phẩm bán chạy
        </div>
        <p class="chart-sub">Theo số lượng đã bán (đơn đang giao + hoàn thành)</p>
        <div style="height:240px;"><canvas id="topProductChart"></canvas></div>
    </div>

    <!-- Doanh thu 12 tháng (Bar) -->
    <div class="chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-green-100 rounded-lg flex items-center justify-center text-green-600 text-xs">📊</span>
            Doanh thu năm <?php echo $cur_year; ?>
        </div>
        <p class="chart-sub">Theo từng tháng trong năm, đơn hoàn thành</p>
        <div style="height:240px;"><canvas id="monthlyChart"></canvas></div>
    </div>

</div>

<!-- ============================================================ -->
<!-- ROW 3: Đơn hàng 6 tháng + Đơn hàng gần đây -->
<!-- ============================================================ -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    <!-- Đơn hàng 6 tháng (Line) -->
    <div class="lg:col-span-1 chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-rose-100 rounded-lg flex items-center justify-center text-rose-500 text-xs">📉</span>
            Đơn hàng 6 tháng gần nhất
        </div>
        <p class="chart-sub">Tổng đơn phát sinh mỗi tháng</p>
        <div style="height:210px;"><canvas id="ordersLineChart"></canvas></div>
    </div>

    <!-- Đơn hàng gần đây -->
    <div class="lg:col-span-2 chart-card">
        <div class="chart-title">
            <span class="w-7 h-7 bg-sky-100 rounded-lg flex items-center justify-center text-sky-500 text-xs">🕐</span>
            Đơn hàng gần đây
        </div>
        <p class="chart-sub">5 đơn hàng mới nhất trong hệ thống</p>
        <table class="w-full text-sm mt-2">
            <thead>
                <tr class="text-left text-gray-400 text-xs uppercase border-b">
                    <th class="pb-2 font-semibold">Mã</th>
                    <th class="pb-2 font-semibold">Khách hàng</th>
                    <th class="pb-2 font-semibold">Tổng tiền</th>
                    <th class="pb-2 font-semibold">Trạng thái</th>
                    <th class="pb-2 font-semibold">Ngày</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_recent && $res_recent->num_rows > 0): ?>
                <?php while ($r = $res_recent->fetch_assoc()):
                    $st = (int)$r['trangThai'];
                    $badges = [
                        0 => ['Chờ XN','bg-yellow-100 text-yellow-700'],
                        1 => ['Đóng gói','bg-indigo-100 text-indigo-700'],
                        2 => ['Đang giao','bg-blue-100 text-blue-700'],
                        3 => ['Hoàn thành','bg-green-100 text-green-700'],
                        4 => ['Đã hủy','bg-red-100 text-red-600'],
                    ];
                    $badge = $badges[$st] ?? ['?','bg-gray-100 text-gray-500'];
                ?>
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="py-3 font-bold text-blue-600">#<?php echo $r['MaDonHang']; ?></td>
                    <td class="py-3 text-gray-700"><?php echo htmlspecialchars($r['TenNguoiDung']); ?></td>
                    <td class="py-3 font-semibold text-red-500"><?php echo number_format($r['TongTien']); ?>đ</td>
                    <td class="py-3"><span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $badge[1]; ?>"><?php echo $badge[0]; ?></span></td>
                    <td class="py-3 text-gray-400 text-xs"><?php echo date('d/m H:i', strtotime($r['NgayDat'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr><td colspan="5" class="text-center py-6 text-gray-400">Chưa có đơn hàng nào</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="admin_orders.php"
            class="mt-4 inline-flex items-center gap-1 text-blue-500 text-sm font-semibold hover:text-blue-700 transition">
            Xem tất cả đơn hàng <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>

</div>

<!-- ============================================================ -->
<!-- CHART.JS -->
<!-- ============================================================ -->
<script>
const defaultFont = { family: "'Inter', sans-serif" };
const vndFormat = v => new Intl.NumberFormat('vi-VN').format(v) + 'đ';
const gridColor  = 'rgba(0,0,0,0.04)';

// ── 1. Line: Doanh thu 7 ngày ──────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($days); ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2.5, pointRadius: 5, pointBorderWidth: 2,
            pointBackgroundColor: '#3b82f6', pointBorderColor: '#fff',
            fill: true, tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(17,24,39,0.9)', padding: 12,
                titleFont: { ...defaultFont, size: 13 }, bodyFont: { ...defaultFont, size: 13 },
                callbacks: { label: ctx => '  ' + vndFormat(ctx.raw) }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: defaultFont, callback: v => new Intl.NumberFormat('vi-VN').format(v/1000) + 'K' } },
            x: { grid: { display: false }, ticks: { font: defaultFont } }
        }
    }
});

// ── 2. Doughnut: Trạng thái ─────────────────────────────────────
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
            backgroundColor: ['#f59e0b','#6366f1','#3b82f6','#22c55e','#ef4444'],
            borderWidth: 2, borderColor: '#fff', hoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: 'rgba(17,24,39,0.88)', padding: 12, titleFont: defaultFont, bodyFont: defaultFont }
        }
    }
});

// ── 3. Bar ngang: Top 5 sản phẩm ────────────────────────────────
new Chart(document.getElementById('topProductChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($top_names); ?>,
        datasets: [{
            label: 'Số lượng bán',
            data: <?php echo json_encode($top_qty); ?>,
            backgroundColor: [
                'rgba(245,158,11,0.85)','rgba(99,102,241,0.85)',
                'rgba(59,130,246,0.85)','rgba(34,197,94,0.85)','rgba(239,68,68,0.85)'
            ],
            borderRadius: 8, borderSkipped: false
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: 'rgba(17,24,39,0.9)', padding: 10, titleFont: defaultFont, bodyFont: defaultFont }
        },
        scales: {
            x: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: defaultFont, stepSize: 1 } },
            y: { grid: { display: false }, ticks: { font: { ...defaultFont, size: 11 } } }
        }
    }
});

// ── 4. Bar: Doanh thu 12 tháng ──────────────────────────────────
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($month_labels); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode($month_revenue); ?>,
            backgroundColor: 'rgba(16,185,129,0.75)',
            hoverBackgroundColor: 'rgba(5,150,105,0.9)',
            borderRadius: 7, borderSkipped: false
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(17,24,39,0.9)', padding: 10, titleFont: defaultFont, bodyFont: defaultFont,
                callbacks: { label: ctx => '  ' + vndFormat(ctx.raw) }
            }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: defaultFont, callback: v => (v/1000000).toFixed(1) + 'M' } },
            x: { grid: { display: false }, ticks: { font: defaultFont } }
        }
    }
});

// ── 5. Line: Đơn hàng 6 tháng ───────────────────────────────────
new Chart(document.getElementById('ordersLineChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($month6_labels); ?>,
        datasets: [{
            label: 'Số đơn',
            data: <?php echo json_encode($month6_orders); ?>,
            borderColor: '#f43f5e',
            backgroundColor: 'rgba(244,63,94,0.08)',
            borderWidth: 2.5, pointRadius: 5,
            pointBackgroundColor: '#f43f5e', pointBorderColor: '#fff', pointBorderWidth: 2,
            fill: true, tension: 0.4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { backgroundColor: 'rgba(17,24,39,0.9)', padding: 10, titleFont: defaultFont, bodyFont: defaultFont }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { font: defaultFont, stepSize: 1 } },
            x: { grid: { display: false }, ticks: { font: defaultFont } }
        }
    }
});
</script>

<?php include 'admin_footer.php'; ?>