<?php
// Job nền: tự cập nhật trạng thái đơn hàng (2 -> 3) sau 5 phút
// Chạy độc lập nên không cần session.

require_once __DIR__ . '/database.php';

$db = new Database();

// Đảm bảo timezone cho NOW() tính theo hệ thống đúng
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Auto chốt tất cả đơn đang ở "Đang giao" (trangThai=2) quá 5 phút (300 giây)
// Lấy danh sách đơn sẽ chuyển 2->3 để gửi mail
$resTargets = $db->select(
    "SELECT MaDonHang FROM donhang " .
    "WHERE trangThai = 2 " .
    "  AND thoi_gian_giao IS NOT NULL " .
    "  AND TIMESTAMPDIFF(SECOND, thoi_gian_giao, NOW()) >= 300"
);

$maDonhangList = [];
if ($resTargets && $resTargets->num_rows > 0) {
    while ($r = $resTargets->fetch_assoc()) {
        $maDonhangList[] = (int)$r['MaDonHang'];
    }
}

$db->execute(
    "UPDATE donhang " .
    "SET trangThai = 3 " .
    "WHERE trangThai = 2 " .
    "  AND thoi_gian_giao IS NOT NULL " .
    "  AND TIMESTAMPDIFF(SECOND, thoi_gian_giao, NOW()) >= 300"
);

require_once __DIR__ . '/_mail_order_notify.php';
foreach ($maDonhangList as $mid) {
    order_notify_send((int)$mid, 'order_success');
}


// Trả về 200 OK cho Task/HTTP scheduler
header('Content-Type: text/plain; charset=utf-8');
echo 'OK';