# TODO - TTPSHOP4.1 (BlackboxAI)

## Phase 0: Chuẩn bị
- [x] Tạo `README_DEPLOY.md` hướng dẫn deploy cPanel/FTP

## Phase 1: Map Leaflet ở Checkout + lưu lat/lng
- [ ] Thêm SQL ALTER TABLE vào `donhang`: cột `lat_giao`, `lng_giao`
- [ ] Cập nhật `checkout.php`:
  - [ ] Nhúng Leaflet + hiển thị map
  - [ ] Click lấy Lat/Lng + lưu vào hidden inputs
  - [ ] Lúc submit: ghi `lat_giao/lng_giao` vào `donhang`
- [ ] Cập nhật `chitietdonhang.php`:
  - [ ] Hiển thị map (read-only) dựa trên `lat_giao/lng_giao`

## Phase 2: Email PHPMailer (Gmail SMTP) + Reset mật khẩu
- [ ] Tạo `mail_config` model logic đọc SMTP config từ DB (bảng `config`)
- [ ] Tạo wrapper `mail_send.php` dùng PHPMailer
- [ ] Thêm bảng `password_resets` cho quên/lấy lại mật khẩu
- [ ] Tạo luồng quên mật khẩu:
  - [ ] `forgot_password.php`
  - [ ] `send_password_reset.php`
  - [ ] `reset_password.php`
  - [ ] `update_password_reset.php`
- [ ] Reset mật khẩu cập nhật mật khẩu mới vào bảng `user`
- [ ] Sửa `checkout.php`:
  - [ ] Gửi mail thông báo đơn hàng sau khi tạo thành công
- [ ] Sửa `cron_auto_update_donhang.php`:
  - [ ] Khi chuyển trạng thái 2->3 thì gửi mail “giao thành công”
- [ ] Sửa `admin_orders.php`:
  - [ ] Khi Admin chuyển 0/1->4 thì gửi mail “đã hủy”
- [ ] Thêm cơ chế chống gửi trùng (log bảng `order_mail_log` hoặc cột trong `donhang`)

## Phase 3: Dashboard Admin nâng cao
- [ ] Sửa `admin_dashboard.php` thêm:
  - [ ] Biểu đồ doanh thu theo tháng (Chart.js)
  - [ ] (Tuỳ chọn) Biểu đồ pie top danh mục bán chạy

## Phase 4: Kiểm thử & hoàn thiện
- [ ] Test map (checkout → lưu → admin/user xem chi tiết)
- [ ] Test forgot/reset password (email)
- [ ] Test gửi mail đơn hàng
- [ ] Test cron giao thành công
- [ ] Test hủy đơn bởi admin
- [ ] Test biểu đồ dashboard

