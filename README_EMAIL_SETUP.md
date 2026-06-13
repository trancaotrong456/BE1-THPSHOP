# SMTP Gmail + PHPMailer cho TTPSHOP4.1

## 1) Cài thư viện PHPMailer
Dự án hiện chưa thấy `vendor/` nên bạn cần cài PHPMailer trước khi chạy.

Cách khuyến nghị (Composer):
1. Mở terminal tại thư mục dự án:
   `cd c:/wamp64/www/TTPSHOP4.1`
2. Chạy:
   `composer require phpmailer/phpmailer`

Sau đó sẽ có đường dẫn mặc định:
- `vendor/phpmailer/phpmailer/src/PHPMailer.php`

> Nếu bạn không dùng Composer, bạn phải đặt thư viện PHPMailer theo các đường dẫn mà `mail_send.php` cố gắng load.

## 2) Tạo config trong bảng `config`
Mở phpMyAdmin chạy các lệnh sau (bổ sung nếu chưa có):

```sql
INSERT INTO config (`key`, `value`) VALUES
('smtp_host','smtp.gmail.com')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_port','587')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_secure','tls')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_user','YOUR_GMAIL_ADDRESS')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_pass','YOUR_APP_PASSWORD')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_from_email','YOUR_GMAIL_ADDRESS')
ON DUPLICATE KEY UPDATE value=VALUES(value);

INSERT INTO config (`key`, `value`) VALUES
('smtp_from_name','THPSHOP')
ON DUPLICATE KEY UPDATE value=VALUES(value);
```

## 3) Bật lưu ý Gmail
- Gmail yêu cầu dùng **App Password** (không dùng mật khẩu thường).
- Đảm bảo tài khoản Gmail có bật 2FA.
- Test gửi mail từ luồng đặt hàng / reset mật khẩu.

