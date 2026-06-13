# Deploy TTPSHOP4.1 (PHP/MySQL) lên hosting cPanel / FTP

## 1) Chuẩn bị
- Code dự án: thư mục `TTPSHOP4.1`
- Database: file SQL có sẵn `be1.sql`
- Hosting cPanel:
  - Truy cập File Manager hoặc FTP
  - Truy cập phpMyAdmin

## 2) Upload code lên hosting
### Cách A: File Manager
1. Vào cPanel → **File Manager**
2. Chọn thư mục web (thường là `public_html`)
3. Upload toàn bộ nội dung dự án `TTPSHOP4.1` vào thư mục này.
4. Kiểm tra URL chạy được trang `index.php`.

### Cách B: FTP
1. Kết nối FTP bằng thông tin host/user/pass.
2. Upload toàn bộ nội dung dự án `TTPSHOP4.1` vào thư mục gốc web.
3. Mở `index.php` để kiểm tra.

## 3) Import database
1. cPanel → **phpMyAdmin**
2. Tạo database mới (nếu cần) (ví dụ: `be1`)
3. Vào **Import**
4. Upload chọn file `be1.sql` trong dự án.
5. Nhấn **Go/Import**.

## 4) Cập nhật cấu hình kết nối DB
Mở `database.php` và chỉnh:
- `$host`
- `$username`
- `$password`
- `$dbname` (ví dụ `be1`)

Ví dụ (sửa tương ứng):
```php
private $host = "localhost";
private $username = "root";
private $password = "";
private $dbname = "be1";
```

## 5) Kiểm tra quyền thư mục upload
- Đảm bảo có quyền ghi cho thư mục `public/images/` (để upload banner/sản phẩm).

## 6) Thử nghiệm các luồng chính
- Trang chủ: `index.php`
- Danh mục: `categories.php`
- Chi tiết: `chitiet.php?id=...`
- Cart/Checkout: `cart.php`, `checkout.php`
- Admin (nếu có): `admin_dashboard.php`

## 7) Lưu ý bảo mật
- Dự án hiện tại dùng mật khẩu dạng text trong DB (theo `matkhau`). Khi deploy thực tế nên nâng cấp hashing (password_hash).
- Nếu dùng SMTP Gmail: nên tạo app password và cấu hình đúng trong `admin_config.php` (phần code email sẽ được thêm trong batch cập nhật).

