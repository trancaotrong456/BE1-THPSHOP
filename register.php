<?php
require_once "database.php";
$db = new Database();

$swal_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $db->conn->real_escape_string(trim($_POST['fullname']));
    $email = $db->conn->real_escape_string(trim($_POST['email']));
    $phone = $db->conn->real_escape_string(trim($_POST['phone']));
    $gender = $db->conn->real_escape_string(trim($_POST['gender']));
    $dob = $db->conn->real_escape_string(trim($_POST['dob']));
    $address = $db->conn->real_escape_string(trim($_POST['address'])); 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    if (empty($fullname) || empty($email) || empty($phone) || empty($address) || empty($password)) {
        $swal_script = "Swal.fire('Thất bại', 'Vui lòng điền đầy đủ các thông tin bắt buộc!', 'warning');";
    } elseif ($password !== $confirm_password) {
        $swal_script = "Swal.fire('Thất bại', 'Mật khẩu xác nhận không khớp nhau!', 'error');";
    } else {
        // Kiểm tra trùng Email hoặc Username
        $check = $db->select("SELECT * FROM user WHERE email = '$email' OR TenNguoiDung = '$fullname'");
        if ($check && $check->num_rows > 0) {
            $swal_script = "Swal.fire('Trùng lặp', 'Tên tài khoản hoặc Email này đã tồn tại!', 'warning');";
        } else {
            // Thêm user mới, quyen mặc định 'user', trang_thai mặc định 0 (hoạt động)
            // Lưu ý: DB hiện tại dùng SoDienThoai/GioiTinh/NgaySinh/AnhDaiDien/trang_thai.
            $sql = "INSERT INTO user (TenNguoiDung, email, matkhau, SoDienThoai, GioiTinh, NgaySinh, diachi, quyen, trang_thai) 
                    VALUES ('$fullname', '$email', '$password', '$phone', '$gender', '$dob', '$address', 'user', 0)";
            if ($db->execute($sql)) {
                session_start();
                $_SESSION['toast'] = 'Đăng ký tài khoản mới thành công! Vui lòng đăng nhập.';
                $_SESSION['toast_type'] = 'success';
                header('Location: login.php');
                exit;
            } else {
                $swal_script = "Swal.fire('Lỗi', 'Đã có lỗi hệ thống xảy ra. Vui lòng thử lại!', 'error');";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - THP SHOP</title>
    <link rel="icon" href="./public/images/web_be1.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        font-family: 'Manrope', sans-serif;
        background: #f5f5f3;
        color: #111827;
    }

    .lux-title {
        font-family: 'Cormorant Garamond', serif;
    }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="min-h-[85vh] flex items-center justify-center bg-gray-50 px-4 py-12">
        <div
            class="max-w-2xl w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transition-all hover:shadow-2xl">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-black tracking-tight text-gray-900">Đăng Ký Thành Viên</h2>
                <p class="text-gray-500 mt-2 text-sm">Gia nhập THPSHOP để nhận hàng ngàn voucher ưu đãi độc quyền</p>
            </div>

            <form method="POST" action="register.php" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Họ và Tên / Tên tài khoản</label>
                        <input type="text" name="fullname" required placeholder="Nhập họ và tên..."
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Địa chỉ Email liên hệ</label>
                        <input type="email" name="email" required placeholder="example@gmail.com"
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Số điện thoại di động</label>
                        <input type="tel" name="phone" required placeholder="Nhập số điện thoại..."
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Giới tính</label>
                        <select name="gender"
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Ngày sinh</label>
                        <input type="date" name="dob"
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Địa chỉ giao hàng mặc định</label>
                        <input type="text" name="address" required placeholder="Số nhà, tên đường, quận, tp..."
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Mật khẩu đăng ký</label>
                        <input type="password" name="password" required placeholder="Tạo mật khẩu..."
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Xác nhận lại mật khẩu</label>
                        <input type="password" name="confirm-password" required placeholder="Nhập lại mật khẩu..."
                            class="w-full px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <label class="flex items-center text-sm font-medium text-gray-600 cursor-pointer">
                        <input type="checkbox" name="agree" required
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-2">
                        Tôi đồng ý tuân thủ toàn bộ <a href="#" class="text-blue-600 ml-1 hover:underline">Điều khoản &
                            Điều
                            kiện dịch vụ</a> của THPSHOP
                    </label>
                </div>

                <button type="submit"
                    class="w-full mt-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:-translate-y-0.5 transition transform duration-200">
                    Đăng Ký Tài Khoản Mới
                </button>
            </form>

            <div class="text-center mt-8 text-sm text-gray-500 font-medium border-t border-gray-100 pt-6">
                Bạn đã có tài khoản sẵn? <a href="login.php"
                    class="text-orange-500 font-bold hover:text-orange-600 hover:underline">Đăng nhập ngay</a>
            </div>
        </div>
    </div>

    <script>
    // In kịch bản SweetAlert từ PHP nếu có sự kiện kích hoạt
    <?php echo $swal_script; ?>
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>