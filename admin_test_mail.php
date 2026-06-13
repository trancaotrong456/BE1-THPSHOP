<?php
// admin_test_mail.php — Gửi email kiểm tra SMTP (chỉ Admin mới được gọi)
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['ok' => false, 'msg' => 'Không có quyền truy cập.']);
    exit;
}

require_once __DIR__ . '/mail_send.php';

$toEmail = trim($_POST['email'] ?? '');

if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'msg' => 'Địa chỉ email không hợp lệ.']);
    exit;
}

$toName  = $_SESSION['user_name'] ?? 'Admin';
$subject = 'THPSHOP — Kiểm tra cấu hình SMTP';
$html    = '
<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
  <div style="background:#2563eb;padding:24px 28px;">
    <h2 style="color:#fff;margin:0;font-size:20px;">✅ THPSHOP — Test SMTP</h2>
  </div>
  <div style="padding:28px;">
    <p style="color:#374151;">Xin chào <strong>' . htmlspecialchars($toName) . '</strong>,</p>
    <p style="color:#374151;">Email này xác nhận rằng cấu hình <strong>SMTP của THPSHOP đang hoạt động bình thường</strong>.</p>
    <p style="color:#6b7280;font-size:13px;margin-top:20px;">Thời gian gửi: ' . date('d/m/Y H:i:s') . '</p>
  </div>
</div>';

$ok = mail_send_order($toEmail, $toName, $subject, $html);

echo json_encode([
    'ok'  => $ok,
    'msg' => $ok
        ? 'Gửi thành công! Kiểm tra hộp thư ' . $toEmail
        : 'Gửi thất bại. Vui lòng kiểm tra lại SMTP Host, Port, User/Pass và đảm bảo PHPMailer đã được cài đặt.'
]);
