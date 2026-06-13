<?php
session_start();
require_once "database.php";
$db = new Database();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $email = $db->conn->real_escape_string($email);
    $password = $db->conn->real_escape_string($password);

    $sql = "SELECT * FROM user WHERE (email = '$email' OR TenNguoiDung = '$email') AND matkhau = '$password'";
    $result = $db->select($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (isset($user['trang_thai']) && (int)$user['trang_thai'] === 1) {
            $error = "Tài khoản của bạn đã bị khóa bởi quản trị viên!";
        } else {
            $_SESSION['user_id']     = $user['IdNguoiDung'];
            $_SESSION['user_name']   = $user['TenNguoiDung'];
            $_SESSION['user_role']   = $user['quyen'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? 'default.png';

            // Khôi phục giỏ hàng riêng của tài khoản
            if (isset($_SESSION['user_id'])) {
                $uid = (string)$_SESSION['user_id'];
                unset($_SESSION['cart']);

                if (isset($_COOKIE['shopping_cart_by_user'])) {
                    $cartByUser = json_decode($_COOKIE['shopping_cart_by_user'], true);
                    if (is_array($cartByUser) && isset($cartByUser[$uid]) && is_array($cartByUser[$uid])) {
                        $_SESSION['cart'] = $cartByUser[$uid];
                        setcookie(
                            'shopping_cart',
                            json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE),
                            time() + (86400 * 30),
                            '/'
                        );
                    }
                }

                if (isset($_COOKIE['shopping_cart'])) {
                    setcookie('shopping_cart', '', time() - 3600, '/');
                }
            }

            if ($user['quyen'] === 'admin') {
                $_SESSION['toast'] = 'Đăng nhập thành công! Chào mừng Admin.';
                $_SESSION['toast_type'] = 'success';
                header("Location: admin_dashboard.php");
            } else {
                $_SESSION['toast'] = 'Đăng nhập thành công! Chào mừng ' . $user['TenNguoiDung'];
                $_SESSION['toast_type'] = 'success';
                header("Location: index.php");
            }
            exit();
        }
    } else {
        $error = "Tài khoản hoặc Mật khẩu không chính xác!";
    }
}
?>
<?php 
$page_title = "Đăng Nhập - THPSHOP";
include 'header.php'; 
?>

    <div class="min-h-[75vh] flex items-center justify-center bg-gray-50 px-4 py-12">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transition-all hover:shadow-2xl">

            <div class="text-center mb-8">
                <h2 class="text-3xl font-black tracking-tight text-gray-900">Đăng Nhập</h2>
                <p class="text-gray-500 mt-2 text-sm">
                    Chào mừng bạn quay lại với <span class="text-blue-600 font-bold">THPSHOP</span>
                </p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold mb-6 border border-red-100 text-center">
                <i class="fas fa-exclamation-circle mr-1.5"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-5">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Email hoặc Tên tài khoản</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="text" name="email" required placeholder="Nhập email hoặc username..."
                            class="w-full pl-11 pr-4 py-3 bg-gray-50/50 rounded-xl border border-gray-200 outline-none
                                   focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Mật khẩu</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu..."
                            class="w-full pl-11 pr-12 py-3 bg-gray-50/50 rounded-xl border border-gray-200 outline-none
                                   focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                        <button type="button" id="togglePassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 transition outline-none">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs sm:text-sm font-medium pt-1">
                    <label class="flex items-center text-gray-600 cursor-pointer select-none">
                        <input type="checkbox"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mr-2">
                        Ghi nhớ đăng nhập
                    </label>
                    <!-- ✅ ĐÃ SỬA: trỏ đúng tới forgot_password.php -->
                    <a href="forgot_password.php" class="text-blue-600 hover:underline">Quên mật khẩu?</a>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                           text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200
                           hover:shadow-xl hover:-translate-y-0.5 transition transform duration-200">
                    Đăng Nhập Hệ Thống
                </button>

            </form>

            <div class="text-center mt-8 text-sm text-gray-500 font-medium border-t border-gray-100 pt-6">
                Bạn chưa có tài khoản?
                <a href="register.php" class="text-orange-500 font-bold hover:text-orange-600 hover:underline">
                    Đăng ký ngay
                </a>
            </div>

        </div>
    </div>

    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
    </script>

<?php include 'footer.php'; ?>
