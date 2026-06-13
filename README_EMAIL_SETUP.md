# HƯỚNG DẪN CHI TIẾT: CẤU HÌNH EMAIL SMTP GMAIL CHO WEBSITE

Tài liệu này hướng dẫn bạn từng bước cách tự cấu hình và sửa lỗi hệ thống gửi mail tự động (SMTP) bằng Gmail cho website của mình. Việc cấu hình đúng sẽ giúp các tính năng như **Gửi mã OTP quên mật khẩu**, **Thông báo đơn hàng mới**, **Xác nhận đăng ký**... hoạt động mượt mà.

---

## 🛠️ BƯỚC 1: LẤY MẬT KHẨU ỨNG DỤNG (APP PASSWORD) TỪ GOOGLE

> ⚠️ **Lưu ý cực kỳ quan trọng:** Bạn **nên sử dụng Gmail cá nhân (`@gmail.com`)**. Không nên sử dụng email do trường học cấp (`.edu.vn`) hoặc email doanh nghiệp vì các tài khoản này thường bị khóa tính năng kết nối SMTP bên ngoài bởi quản trị viên (Admin kỹ thuật của trường).

1. Mở một tab trình duyệt mới, truy cập vào trang quản lý tài khoản Google: [myaccount.google.com](https://myaccount.google.com).
2. Đăng nhập bằng tài khoản Gmail cá nhân của bạn.
3. Chọn mục **Bảo mật (Security)** ở danh sách menu bên trái.
4. Tìm đến mục **Xác minh 2 bước (2-Step Verification)** và đảm bảo rằng tính năng này đã được **BẬT**. (Google bắt buộc phải bật tính năng này mới cho phép tạo Mật khẩu ứng dụng).
5. Tại thanh tìm kiếm trên cùng của trang quản lý tài khoản, gõ từ khóa `"Mật khẩu ứng dụng"` (hoặc `"App passwords"` nếu dùng tiếng Anh) và chọn mục tương ứng hiện ra.
6. Hệ thống sẽ yêu cầu bạn nhập lại mật khẩu Gmail để xác minh danh tính.
7. Tại ô đặt tên, nhập một tên bất kỳ để dễ nhớ (Ví dụ: `Website THPSHOP`) rồi bấm nút **Tạo (Create)**.
8. Một cửa sổ nhỏ sẽ hiện ra hiển thị **dãy mã gồm 16 chữ cái ngẫu nhiên** màu vàng (Ví dụ: `niak dsvn ztoe ymdz`). 

> 📌 **QUY TẮC COPY:** Hãy copy dãy mã này, nhưng khi điền vào trang cấu hình của website, bạn **PHẢI XÓA BỎ TẤT CẢ KHOẢNG TRẮNG (DẤU CÁCH)**. Chuỗi điền vào web phải viết liền hoàn toàn thành một cụm duy nhất: `niakdsvnztoeymdz`.

---

## 💻 BƯỚC 2: ĐIỀN THÔNG TIN CẤU HÌNH VÀO WEBSITE

Bạn quay trở lại trang quản trị website (Trang cấu hình Email & SMTP) và điền thông tin chính xác theo bảng hướng dẫn dưới đây:

| Tên ô cấu hình | Giá trị chính xác cần điền | Hướng dẫn chi tiết |
| :--- | :--- | :--- |
| **SMTP Host** | `smtp.gmail.com` | Giữ nguyên mặc định của Google. |
| **SMTP Port** | `587` | Cổng kết nối tiêu chuẩn bảo mật. |
| **Mã hóa kết nối** | Tích chọn **TLS** (Port 587 — Khuyến nghị) | Không chọn SSL trừ khi hệ thống yêu cầu đặc biệt. |
| **Gmail (SMTP User)** | `tentaikhoan@gmail.com` | Nhập chính xác địa chỉ Gmail cá nhân của bạn. |
| **App Password (16 ký tự)** | `niakdsvnztoeymdz` | Dán 16 ký tự vừa lấy ở Bước 1 vào. **Nhớ xóa hết dấu cách**. |
| **Email hiển thị (From Email)** | `tentaikhoan@gmail.com` | Điền trùng khớp với ô *Gmail (SMTP User)* phía trên. |
| **Tên người gửi (From Name)** | `THPSHOP` | Nhập tên cửa hàng của bạn (Tên này sẽ hiển thị ở hộp thư của khách hàng). |

Sau khi điền xong tất cả các ô, hãy click vào nút màu xanh **Lưu cấu hình SMTP**.

---

## 🧪 BƯỚC 3: GỬI MAIL KIỂM TRA (TEST SYSTEM)

Để chắc chắn hệ thống đã thông suốt, bạn thực hiện bước kiểm tra ngay tại trang đó:
1. Nhìn xuống phần **Kiểm tra SMTP (gửi mail test)** ở phía dưới cùng.
2. Nhập một địa chỉ email bất kỳ đang hoạt động của bạn (hoặc của bạn bè) vào ô trống.
3. Bấm nút **Gửi test**.

* **Kịch bản thành công:** Hệ thống hiển thị thông báo màu xanh báo gửi thành công và bạn nhận được một email thử nghiệm trong hộp thư đến. Chúc mừng bạn! Lúc này tính năng lấy lại mật khẩu và gửi OTP đã hoạt động 100%.
* **Kịch bản thất bại:** Nếu màn hình hiện dòng thông báo lỗi màu đỏ, hãy bình tĩnh đọc tiếp Bước 4 để xử lý triệt để.

---

## ❌ BƯỚC 4: KHẮC PHỤC CÁC LỖI THƯỜNG GẶP (XỬ LÝ LỖI ĐỎ)

Nếu hệ thống báo lỗi gửi test thất bại, nguyên nhân chỉ nằm ở 1 trong 3 lý do sau:

### Lỗi 1: Do chưa cài đặt thư viện PHPMailer trong mã nguồn website
* **Dấu hiệu:** Dù bạn dùng Gmail cá nhân, nhập đúng App Password viết liền không dấu cách, điền đúng thông số kết nối nhưng web vẫn báo lỗi đỏ yêu cầu *"đảm bảo PHPMailer đã được cài đặt"*.
* **Nguyên nhân:** Mã nguồn website của bạn chưa được tích hợp thư viện gốc để đóng gói và gửi thư đi (PHPMailer).
* **Cách khắc phục:** * **Nếu bạn tự chạy code trên máy tính (Localhost):** Hãy mở Terminal / Command Prompt (CMD), chuyển thư mục làm việc vào thư mục gốc chứa source code website và chạy lệnh:
    ```bash
    composer require phpmailer/phpmailer
    ```
  * **Nếu bạn thuê người làm web hoặc mua code sẵn:** Hãy copy toàn bộ bức ảnh lỗi cấu hình gửi cho Lập trình viên / Kỹ thuật viên của bạn và nhắn: *"Anh/chị kiểm tra giúp em xem source code đã được chạy lệnh `composer require phpmailer/phpmailer` để cài đặt thư viện gửi mail chưa nhé!"*.

### Lỗi 2: Do copy dư khoảng trắng (dấu cách) trong ô mật khẩu ứng dụng
* **Dấu hiệu:** Điền nguyên trạng chuỗi dạng `abcd efgh ijkl mnop` có dấu cách như Google hiển thị. Máy tính sẽ nhận diện luôn các khoảng trắng đó là ký tự mật khẩu dẫn đến sai lệch thông tin xác thực.
* **Cách khắc phục:** Click vào biểu tượng con mắt ở ô mật khẩu để hiển thị rõ, xóa bỏ toàn bộ dấu cách rồi bấm Lưu lại.

### Lỗi 3: Do Firewall (Tường lửa) của nhà cung cấp Hosting chặn cổng 587
* **Dấu hiệu:** Bạn làm đúng mọi bước trên máy tính (localhost) thì chạy được, nhưng khi đẩy website lên mạng (Hosting/VPS) thì lại bị lỗi đỏ không gửi được thư.
* **Nguyên nhân:** Một số nhà cung cấp Hosting chặn các cổng gửi mail ra ngoài (`587`, `465`) để phòng ngừa việc website của bạn bị hack và gửi thư rác (Spam).
* **Cách khắc phục:** Gửi một phiếu hỗ trợ (Ticket) cho bên nhà cung cấp Hosting với nội dung: *"Nhờ kỹ thuật hỗ trợ mở giúp tôi cổng Outbound Port 587 để website kết nối tới SMTP Gmail"*.
