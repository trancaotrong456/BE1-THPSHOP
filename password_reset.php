<?php
session_start();
require_once 'database.php';

$db = new Database();

// Kiểm tra đã xác thực OTP chưa
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['otp_token'])) {
    echo "<script>alert('Vui lòng xác thực OTP trước!'); window.location.href='forgot_password.php';</script>";
    exit();
}

$token = $_SESSION['otp_token'];

// Lấy email_or_username từ DB theo token
$res = $db->select("SELECT * FROM password_resets WHERE token = '{$db->conn->real_escape_string($token)}' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo "<script>alert('Phiên xác thực không hợp lệ. Vui lòng thử lại.'); window.location.href='forgot_password.php';</script>";
    exit();
}
$row = $res->fetch_assoc();
$emailOrUsername = $row['email_or_username'] ?? '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm     = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($newPassword === '' || $confirm === '') {
        $error = 'Vui lòng nhập đầy đủ mật khẩu.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($newPassword !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $newPasswordEsc = $db->conn->real_escape_string($newPassword);
        $eou = $db->conn->real_escape_string($emailOrUsername);

        $sqlUser = "SELECT IdNguoiDung FROM user WHERE (email = '$eou' OR TenNguoiDung = '$eou') LIMIT 1";
        $rUser = $db->select($sqlUser);

        if (!$rUser || $rUser->num_rows === 0) {
            $error = 'Không tìm thấy tài khoản tương ứng.';
        } else {
            $idUser = (int)$rUser->fetch_assoc()['IdNguoiDung'];
            $db->execute("UPDATE user SET matkhau = '$newPasswordEsc' WHERE IdNguoiDung = $idUser");
            $db->execute("DELETE FROM password_resets WHERE token = '{$db->conn->real_escape_string($token)}'");

            // Xóa session OTP
            unset($_SESSION['otp_verified'], $_SESSION['otp_token'], $_SESSION['otp_email_or_username'], $_SESSION['otp_sent_at']);

            echo "<script>alert('Đã đặt lại mật khẩu thành công! Vui lòng đăng nhập.'); window.location.href='login.php';</script>";
            exit();
        }
    }
}

include 'header.php';
?>
<div class="min-h-[75vh] flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-200">
                <i class="fas fa-lock-open text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-black tracking-tight text-gray-900">Đặt mật khẩu mới</h2>
            <p class="text-gray-500 mt-2 text-sm">Tạo mật khẩu mới an toàn cho tài khoản của bạn.</p>
        </div>

        <!-- Badge xác thực thành công -->
        <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-xl text-sm font-semibold mb-6 text-center flex items-center justify-center gap-2">
            <i class="fas fa-check-circle text-green-500"></i>
            OTP đã được xác thực thành công
        </div>

        <?php if (!empty($error)): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold mb-6 border border-red-100 text-center">
            <i class="fas fa-exclamation-circle mr-1"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="password_reset.php" class="space-y-5" id="reset-form">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Mật khẩu mới</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" id="password" required
                        placeholder="Tối thiểu 6 ký tự"
                        class="w-full pl-10 pr-12 py-3 bg-gray-50/50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    <button type="button" onclick="togglePass('password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500 transition">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <!-- Thanh độ mạnh mật khẩu -->
                <div class="mt-2 space-y-1">
                    <div class="flex gap-1">
                        <div class="h-1 flex-1 rounded-full bg-gray-200 transition-all" id="strength-1"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200 transition-all" id="strength-2"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200 transition-all" id="strength-3"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200 transition-all" id="strength-4"></div>
                    </div>
                    <p class="text-xs text-gray-400" id="strength-label">Độ mạnh: chưa nhập</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Xác nhận mật khẩu mới</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="confirm_password" id="confirm_password" required
                        placeholder="Nhập lại mật khẩu mới"
                        class="w-full pl-10 pr-12 py-3 bg-gray-50/50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                    <button type="button" onclick="togglePass('confirm_password', this)"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500 transition">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <p class="text-xs mt-1 hidden" id="match-msg"></p>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-200 hover:shadow-xl hover:-translate-y-0.5 transition transform duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> Cập nhật mật khẩu
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-gray-500 border-t border-gray-100 pt-4">
            <a href="login.php" class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
            </a>
        </div>
    </div>
</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}

// Thanh độ mạnh mật khẩu
document.getElementById('password').addEventListener('input', function() {
    const val = this.value;
    let score = 0;
    if (val.length >= 6) score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) || /[0-9]/.test(val)) score++;
    if (/[!@#$%^&*]/.test(val)) score++;

    const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-green-400'];
    const labels = ['Rất yếu', 'Yếu', 'Trung bình', 'Mạnh'];

    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('strength-' + i);
        el.className = 'h-1 flex-1 rounded-full transition-all ' + (i <= score ? colors[score - 1] : 'bg-gray-200');
    }
    document.getElementById('strength-label').textContent = val ? ('Độ mạnh: ' + (labels[score - 1] || 'Rất yếu')) : 'Độ mạnh: chưa nhập';
    document.getElementById('strength-label').className = 'text-xs ' + (score >= 3 ? 'text-green-600' : score >= 2 ? 'text-yellow-600' : 'text-red-500');
});

// Kiểm tra khớp mật khẩu
document.getElementById('confirm_password').addEventListener('input', function() {
    const pw = document.getElementById('password').value;
    const msg = document.getElementById('match-msg');
    msg.classList.remove('hidden');
    if (this.value === pw) {
        msg.textContent = '✓ Mật khẩu khớp';
        msg.className = 'text-xs mt-1 text-green-600';
    } else {
        msg.textContent = '✗ Mật khẩu không khớp';
        msg.className = 'text-xs mt-1 text-red-500';
    }
});
</script>

<?php include 'footer.php'; ?>