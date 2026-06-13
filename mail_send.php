<?php
// Wrapper gửi mail bằng PHPMailer (Gmail SMTP)
// Cấu hình đọc từ bảng config trong DB.

require_once __DIR__ . '/database.php';

function mail_get_config_value(Database $db, string $key, $default = '') {
    $key = $db->conn->real_escape_string($key);
    $res = $db->select("SELECT value FROM config WHERE `key` = '$key' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return $row['value'];
    }
    return $default;
}

function mail_send_order(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool {
    $toEmail = trim($toEmail);
    $toName = trim($toName);

    if ($toEmail === '') return false;

    // Load PHPMailer
    $loaded = false;
    $paths = [
        __DIR__ . '/vendor/autoload.php',
        __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php',
        __DIR__ . '/libs/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/PHPMailer/src/PHPMailer.php',
    ];

    foreach ($paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            // Nếu load thủ công PHPMailer.php thì cần load thêm Exception và SMTP
            if (strpos($p, 'PHPMailer.php') !== false) {
                $dir = dirname($p);
                if (file_exists($dir . '/Exception.php')) require_once $dir . '/Exception.php';
                if (file_exists($dir . '/SMTP.php')) require_once $dir . '/SMTP.php';
            }
            $loaded = true;
            break;
        }
    }

    if (!$loaded) {
        return false;
    }

    $db = new Database();

    $smtpHost = mail_get_config_value($db, 'smtp_host', 'smtp.gmail.com');
    $smtpPort = (int)mail_get_config_value($db, 'smtp_port', '587');
    $smtpUser = mail_get_config_value($db, 'smtp_user', '');
    $smtpPass = mail_get_config_value($db, 'smtp_pass', '');
    $smtpFromEmail = mail_get_config_value($db, 'smtp_from_email', $smtpUser);
    $smtpFromName = mail_get_config_value($db, 'smtp_from_name', 'THPSHOP');
    $smtpSecure = mail_get_config_value($db, 'smtp_secure', ($smtpPort === 465 ? 'ssl' : 'tls'));

    if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '' || $smtpFromEmail === '') {
        return false;
    }

    $subject = trim($subject);

    try {
        // @phpstan-ignore-next-line
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpFromEmail, $smtpFromName);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody !== '' ? $plainBody : strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}