<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'vudangminh2025@gmail.com';
    $mail->Password   = 'bckehvptkchqawlq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('vudangminh2025@gmail.com', 'THPSHOP Test');
    $mail->addAddress('vudangminh2025@gmail.com', 'Test User');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email from THPSHOP';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
