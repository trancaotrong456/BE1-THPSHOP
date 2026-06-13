<?php
require 'vendor/autoload.php';
require 'database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$res = $db->select("SELECT * FROM config");
$config = [];
while ($row = $res->fetch_assoc()) {
    $config[$row['key']] = $row['value'];
}

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 3; // Lấy log chi tiết nhất
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port       = $config['smtp_port'];

    $mail->setFrom($config['smtp_from_email'], $config['smtp_from_name']);
    $mail->addAddress('trancaotrong456@gmail.com', 'Test User');

    $mail->isHTML(true);
    $mail->Subject = 'Test Debug Email';
    $mail->Body    = 'Debug testing';

    echo "Bắt đầu gửi email...\n";
    $mail->send();
    echo "\nThành công!\n";
} catch (Exception $e) {
    echo "\nThất bại. Lỗi: {$mail->ErrorInfo}\n";
}
