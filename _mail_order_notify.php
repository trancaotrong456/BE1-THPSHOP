<?php
// Nhận: $maDonHang, $mailType (order_created|order_success|order_cancel)
// Tự động chống gửi trùng và lấy thông tin email từ user.

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mail_send.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

function order_notify_send(int $maDonHang, string $mailType): void {
    $db = new Database();

    // Chống gửi trùng
    $typeEsc = $db->conn->real_escape_string($mailType);
    $check = $db->select("SELECT id FROM order_mail_log WHERE MaDonHang = $maDonHang AND mail_type = '$typeEsc' LIMIT 1");
    if ($check && $check->num_rows > 0) return;

    $sql = "SELECT d.*, u.email, u.TenNguoiDung FROM donhang d
            JOIN user u ON d.IdNguoiDung = u.IdNguoiDung
            WHERE d.MaDonHang = $maDonHang LIMIT 1";
    $res = $db->select($sql);
    if (!$res || $res->num_rows === 0) return;

    $row = $res->fetch_assoc();
    $toEmail = $row['email'] ?? '';
    $toName  = $row['TenNguoiDung'] ?? 'Khách hàng';
    if ($toEmail === '') return;

    $orderCode  = '#THP-' . str_pad($maDonHang, 6, '0', STR_PAD_LEFT);
    $tongTien   = (int)($row['TongTien'] ?? 0);
    $tongTienFmt = number_format($tongTien, 0, ',', '.');
    $diachi     = htmlspecialchars($row['diachi'] ?? 'Chưa có thông tin');
    $nguoiNhan  = htmlspecialchars($row['TenNguoiNhan'] ?? $toName);
    $sdt        = htmlspecialchars($row['SoDienThoai'] ?? '');
    $ngayDat    = isset($row['NgayDat']) ? date('H:i d/m/Y', strtotime($row['NgayDat'])) : '';

    // =====================
    // BASE HTML TEMPLATE
    // =====================
    $logoBlock  = "<div style='font-size:22px;font-weight:900;color:#fff;letter-spacing:-0.5px;'>🛍 THPSHOP</div>";

    $orderInfoBlock = "
        <table style='width:100%;border-collapse:collapse;margin-top:16px;font-size:13px;'>
            <tr style='background:#f8fafc;'><td style='padding:9px 12px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;width:40%'>Mã đơn hàng</td><td style='padding:9px 12px;color:#1d4ed8;font-weight:800;border-bottom:1px solid #e5e7eb;'>{$orderCode}</td></tr>
            <tr><td style='padding:9px 12px;color:#6b7280;font-weight:600;border-bottom:1px solid #f3f4f6;'>Người nhận</td><td style='padding:9px 12px;color:#111827;border-bottom:1px solid #f3f4f6;'>{$nguoiNhan}</td></tr>
            <tr style='background:#f8fafc;'><td style='padding:9px 12px;color:#6b7280;font-weight:600;border-bottom:1px solid #f3f4f6;'>SĐT</td><td style='padding:9px 12px;color:#111827;border-bottom:1px solid #f3f4f6;'>{$sdt}</td></tr>
            <tr><td style='padding:9px 12px;color:#6b7280;font-weight:600;border-bottom:1px solid #f3f4f6;'>Địa chỉ</td><td style='padding:9px 12px;color:#111827;border-bottom:1px solid #f3f4f6;'>{$diachi}</td></tr>
            <tr style='background:#f8fafc;'><td style='padding:9px 12px;color:#6b7280;font-weight:600;'>Ngày đặt</td><td style='padding:9px 12px;color:#111827;'>{$ngayDat}</td></tr>
        </table>
    ";

    $footerBlock = "
        <div style='background:#f9fafb;padding:16px 32px;text-align:center;border-top:1px solid #f3f4f6;'>
            <p style='color:#d1d5db;font-size:11px;margin:0;'>© 2026 THPSHOP · Email tự động, vui lòng không reply trực tiếp</p>
            <p style='color:#d1d5db;font-size:11px;margin:4px 0 0;'>Hotline: <b>1900xxxx</b> | 120 Uyên Lãng, Thủ Đức, TP.HCM</p>
        </div>
    ";

    // =====================
    // ORDER CREATED
    // =====================
    if ($mailType === 'order_created') {
        $subject = "THPSHOP - Xác nhận đặt hàng {$orderCode}";
        $html = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%);padding:28px 32px;'>
                {$logoBlock}
                <p style='color:rgba(255,255,255,0.75);font-size:13px;margin:6px 0 0;'>Xác nhận đặt hàng thành công</p>
            </div>
            <div style='padding:28px 32px;'>
                <p style='font-size:16px;color:#111827;margin:0 0 6px;'>Xin chào <b>{$toName}</b> 👋</p>
                <p style='font-size:14px;color:#6b7280;line-height:1.65;margin:0 0 20px;'>
                    Cảm ơn bạn đã tin tưởng mua hàng tại <b>THPSHOP</b>! Đơn hàng của bạn đã được tiếp nhận thành công và đang được xử lý.
                </p>

                <div style='background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:12px;padding:20px 24px;margin-bottom:20px;text-align:center;'>
                    <p style='color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 6px;'>Tổng thanh toán</p>
                    <span style='font-size:36px;font-weight:900;color:#1d4ed8;'>{$tongTienFmt}đ</span>
                </div>

                {$orderInfoBlock}

                <div style='background:#f0fdf4;border-left:4px solid #22c55e;border-radius:8px;padding:12px 16px;margin-top:20px;'>
                    <p style='color:#166534;font-size:13px;margin:0;'>
                        ✅ Chúng tôi sẽ cập nhật trạng thái đơn hàng qua email khi có thay đổi.
                    </p>
                </div>
            </div>
            {$footerBlock}
        </div>";

    // =====================
    // ORDER SUCCESS (Đã giao)
    // =====================
    } elseif ($mailType === 'order_success') {
        $subject = "THPSHOP - 🎉 Đơn hàng {$orderCode} đã giao thành công!";
        $html = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#065f46 0%,#10b981 100%);padding:28px 32px;'>
                {$logoBlock}
                <p style='color:rgba(255,255,255,0.8);font-size:13px;margin:6px 0 0;'>Đơn hàng đã giao thành công</p>
            </div>
            <div style='padding:28px 32px;'>
                <div style='text-align:center;margin-bottom:24px;'>
                    <div style='width:70px;height:70px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:12px;'>🎉</div>
                    <h2 style='color:#065f46;font-size:22px;font-weight:900;margin:0 0 6px;'>Giao hàng thành công!</h2>
                    <p style='color:#6b7280;font-size:14px;margin:0;'>Cảm ơn <b>{$toName}</b> đã mua sắm cùng THPSHOP!</p>
                </div>

                <div style='background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:12px;padding:20px 24px;margin-bottom:20px;text-align:center;'>
                    <p style='color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 6px;'>Tổng đơn hàng</p>
                    <span style='font-size:36px;font-weight:900;color:#059669;'>{$tongTienFmt}đ</span>
                </div>

                {$orderInfoBlock}

                <div style='background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 16px;margin-top:20px;'>
                    <p style='color:#78350f;font-size:13px;margin:0;font-weight:600;'>⭐ Bạn có hài lòng với đơn hàng không?</p>
                    <p style='color:#92400e;font-size:12px;margin:6px 0 0;'>Hãy vào website và để lại đánh giá sản phẩm để giúp chúng tôi cải thiện dịch vụ nhé!</p>
                </div>
            </div>
            {$footerBlock}
        </div>";

    // =====================
    // ORDER CANCEL (Đã hủy)
    // =====================
    } elseif ($mailType === 'order_cancel') {
        $subject = "THPSHOP - ⚠️ Đơn hàng {$orderCode} đã bị hủy";
        $html = "
        <div style='font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);border:1px solid #e5e7eb;'>
            <div style='background:linear-gradient(135deg,#7f1d1d 0%,#ef4444 100%);padding:28px 32px;'>
                {$logoBlock}
                <p style='color:rgba(255,255,255,0.8);font-size:13px;margin:6px 0 0;'>Thông báo hủy đơn hàng</p>
            </div>
            <div style='padding:28px 32px;'>
                <p style='font-size:16px;color:#111827;margin:0 0 6px;'>Xin chào <b>{$toName}</b>,</p>
                <p style='font-size:14px;color:#6b7280;line-height:1.65;margin:0 0 20px;'>
                    Đơn hàng của bạn đã bị hủy. Nếu bạn không yêu cầu hủy, vui lòng liên hệ với chúng tôi.
                </p>

                <div style='background:linear-gradient(135deg,#fef2f2,#fee2e2);border-radius:12px;padding:20px 24px;margin-bottom:20px;text-align:center;'>
                    <p style='color:#9ca3af;font-size:12px;text-transform:uppercase;letter-spacing:2px;margin:0 0 6px;'>Giá trị đơn hàng</p>
                    <span style='font-size:36px;font-weight:900;color:#dc2626;'>{$tongTienFmt}đ</span>
                </div>

                {$orderInfoBlock}

                <div style='background:#fef2f2;border-left:4px solid #ef4444;border-radius:8px;padding:12px 16px;margin-top:20px;'>
                    <p style='color:#991b1b;font-size:13px;margin:0;'>
                        ❓ Có thắc mắc? Liên hệ hotline <b>1900xxxx</b> hoặc email hỗ trợ để được giải đáp.
                    </p>
                </div>
            </div>
            {$footerBlock}
        </div>";

    } else {
        return;
    }

    mail_send_order($toEmail, $toName, $subject, $html);
    $db->execute("INSERT INTO order_mail_log (MaDonHang, mail_type, sent_at) VALUES ($maDonHang, '$typeEsc', NOW())");
}