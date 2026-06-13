<?php
session_start();
require_once 'database.php';

$db = new Database();

// Kiểm tra session (phải qua forgot_password.php trước)
if (!isset($_SESSION['otp_email_or_username'])) {
    header('Location: forgot_password.php');
    exit();
}

$emailOrUsername = $_SESSION['otp_email_or_username'];
$error = '';
$success = '';

// Xử lý gửi lại OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    header('Location: forgot_password.php');
    exit();
}

// Xử lý xác thực OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $inputOtp = trim(implode('', array_map(fn($k) => $_POST[$k] ?? '', ['otp1','otp2','otp3','otp4','otp5','otp6'])));

    if (strlen($inputOtp) !== 6 || !ctype_digit($inputOtp)) {
        $error = 'Vui lòng nhập đủ 6 chữ số OTP.';
    } else {
        $eou = $db->conn->real_escape_string($emailOrUsername);
        $res = $db->select("SELECT * FROM password_resets WHERE email_or_username = '$eou' AND otp_code = '{$db->conn->real_escape_string($inputOtp)}' LIMIT 1");

        if (!$res || $res->num_rows === 0) {
            $error = 'Mã OTP không đúng. Vui lòng kiểm tra lại email.';
        } else {
            $row = $res->fetch_assoc();
            if (strtotime($row['expires_at']) < time()) {
                $error = 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.';
            } else {
                // OTP hợp lệ → lưu session và chuyển sang đặt mật khẩu mới
                $_SESSION['otp_verified'] = true;
                $_SESSION['otp_token'] = $row['token'];
                header('Location: password_reset.php');
                exit();
            }
        }
    }
}

include 'header.php';
?>
<div class="min-h-[75vh] flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                <i class="fas fa-shield-alt text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-black tracking-tight text-gray-900">Xác thực OTP</h2>
            <p class="text-gray-500 mt-2 text-sm">
                Mã OTP 6 số đã được gửi đến email của bạn.<br>
                Vui lòng kiểm tra hộp thư (kể cả thư mục Spam).
            </p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 border border-red-100 p-4 rounded-xl text-sm font-semibold mb-6 text-center flex items-center justify-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="verify_otp.php" id="otp-form" class="space-y-6">

            <!-- 6 ô nhập OTP -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-3 text-center">Nhập mã OTP</label>
                <div class="flex justify-center gap-3" id="otp-boxes">
                    <?php for($i = 1; $i <= 6; $i++): ?>
                    <input type="text" name="otp<?php echo $i; ?>" id="otp<?php echo $i; ?>"
                        maxlength="1" inputmode="numeric" pattern="[0-9]"
                        class="w-12 h-14 text-center text-2xl font-black border-2 border-gray-200 rounded-xl outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition bg-gray-50 focus:bg-white otp-input"
                        autocomplete="off">
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="otp" id="otp-combined">
            </div>

            <!-- Đếm ngược -->
            <div class="text-center">
                <p class="text-sm text-gray-500">Mã hết hạn sau: <span id="countdown" class="font-bold text-blue-600">10:00</span></p>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:-translate-y-0.5 transition transform duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-check-circle"></i> Xác thực OTP
            </button>
        </form>

        <form method="POST" action="verify_otp.php" class="mt-4">
            <button type="submit" name="resend_otp" value="1"
                class="w-full text-blue-600 font-semibold py-3 rounded-xl border border-blue-200 hover:bg-blue-50 transition text-sm flex items-center justify-center gap-2">
                <i class="fas fa-redo"></i> Gửi lại mã OTP
            </button>
        </form>

        <div class="text-center mt-4 text-sm text-gray-500 border-t border-gray-100 pt-4">
            <a href="login.php" class="text-gray-400 hover:text-blue-600 transition">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
            </a>
        </div>
    </div>
</div>

<script>
// ========================
// OTP INPUT - Tự động chuyển ô
// ========================
const otpInputs = document.querySelectorAll('.otp-input');

otpInputs.forEach((input, idx) => {
    input.addEventListener('input', function() {
        // Chỉ nhận số
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length === 1 && idx < otpInputs.length - 1) {
            otpInputs[idx + 1].focus();
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) {
            otpInputs[idx - 1].focus();
        }
        if (e.key === 'ArrowLeft' && idx > 0) otpInputs[idx - 1].focus();
        if (e.key === 'ArrowRight' && idx < otpInputs.length - 1) otpInputs[idx + 1].focus();
    });

    // Paste toàn bộ mã
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        [...text.slice(0,6)].forEach((ch, i) => {
            if (otpInputs[i]) otpInputs[i].value = ch;
        });
        const next = Math.min(text.length, 5);
        otpInputs[next].focus();
    });
});

// Gộp OTP trước khi submit
document.getElementById('otp-form').addEventListener('submit', function() {
    let combined = '';
    otpInputs.forEach(i => combined += i.value);
    document.getElementById('otp-combined').value = combined;
});

// ========================
// ĐẾM NGƯỢC 10 PHÚT
// ========================
const sentAt = <?php echo isset($_SESSION['otp_sent_at']) ? (int)$_SESSION['otp_sent_at'] : 'Date.now()/1000'; ?>;
function updateCountdown() {
    const remaining = 600 - (Math.floor(Date.now() / 1000) - sentAt);
    if (remaining <= 0) {
        document.getElementById('countdown').textContent = 'Hết hạn';
        document.getElementById('countdown').className = 'font-bold text-red-500';
        return;
    }
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    document.getElementById('countdown').textContent = `${m}:${s.toString().padStart(2, '0')}`;
}
updateCountdown();
setInterval(updateCountdown, 1000);

// Focus vào ô đầu tiên
otpInputs[0].focus();
</script>

<?php include 'footer.php'; ?>
