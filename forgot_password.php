<?php
session_start();
require_once 'database.php';

$db = new Database();

$msg = '';
$step = 'input'; // input | sent

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['email_or_username'] ?? '');

    if ($emailOrUsername === '') {
        $msg = 'error:Vui lòng nhập email hoặc tên tài khoản.';
    } else {
        $res = $db->select("SELECT * FROM user WHERE email = '{$db->conn->real_escape_string($emailOrUsername)}' OR TenNguoiDung = '{$db->conn->real_escape_string($emailOrUsername)}' LIMIT 1");

        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $email    = $user['email'];
            $username = $user['TenNguoiDung'];

            // Tạo OTP 6 số
            $otp = sprintf('%06d', random_int(0, 999999));
            $expiresAt = date('Y-m-d H:i:s', time() + 60 * 10); // 10 phút

            // Xóa token cũ
            $db->execute("DELETE FROM password_resets WHERE email_or_username = '{$db->conn->real_escape_string($emailOrUsername)}'");

            // Lưu OTP (dùng cột token lưu email_or_username, cột otp_code lưu mã OTP)
            $tokenPlaceholder = bin2hex(random_bytes(8)); // token ngắn làm khóa duy nhất
            $db->execute("INSERT INTO password_resets (email_or_username, token, otp_code, expires_at, created_at) VALUES (
                '{$db->conn->real_escape_string($emailOrUsername)}',
                '{$db->conn->real_escape_string($tokenPlaceholder)}',
                '{$db->conn->real_escape_string($otp)}',
                '{$db->conn->real_escape_string($expiresAt)}',
                NOW()
            )");

            // Lưu session để verify_otp.php dùng
            $_SESSION['otp_email_or_username'] = $emailOrUsername;
            $_SESSION['otp_sent_at'] = time();

            // Gửi email OTP
            require_once 'mail_send.php';
            $subject = 'THPSHOP - Mã xác thực OTP đặt lại mật khẩu';
            $html = "
            <div style='font-family: Inter, Arial, sans-serif; max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); border: 1px solid #e5e7eb;'>
                <div style='background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 32px; text-align: center;'>
                    <h1 style='color: white; font-size: 24px; margin: 0; font-weight: 800; letter-spacing: -0.5px;'>🔐 THPSHOP</h1>
                    <p style='color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 14px;'>Xác thực tài khoản</p>
                </div>
                <div style='padding: 36px 32px;'>
                    <p style='color: #374151; font-size: 16px; margin: 0 0 8px;'>Xin chào <strong>{$username}</strong>,</p>
                    <p style='color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0 0 28px;'>
                        Chúng tôi nhận được yêu cầu <strong>đặt lại mật khẩu</strong> từ tài khoản của bạn.<br>
                        Vui lòng sử dụng mã OTP bên dưới để xác thực:
                    </p>

                    <div style='background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 2px dashed #3b82f6; border-radius: 16px; padding: 28px; text-align: center; margin-bottom: 24px;'>
                        <p style='color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 2px; margin: 0 0 12px;'>Mã xác thực OTP</p>
                        <span style='font-size: 48px; font-weight: 900; letter-spacing: 12px; color: #1d4ed8; display: block; font-family: monospace;'>{$otp}</span>
                        <p style='color: #ef4444; font-size: 12px; margin: 12px 0 0;'>⏱ Mã hết hạn sau <strong>10 phút</strong></p>
                    </div>

                    <div style='background: #fef9c3; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px;'>
                        <p style='color: #92400e; font-size: 13px; margin: 0;'>
                            ⚠️ <strong>Không chia sẻ mã này</strong> với bất kỳ ai. THPSHOP sẽ không bao giờ hỏi bạn mã OTP qua điện thoại hay tin nhắn.
                        </p>
                    </div>

                    <p style='color: #9ca3af; font-size: 12px; text-align: center; margin: 0;'>
                        Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.
                    </p>
                </div>
                <div style='background: #f9fafb; padding: 16px 32px; text-align: center; border-top: 1px solid #f3f4f6;'>
                    <p style='color: #d1d5db; font-size: 12px; margin: 0;'>© 2026 THPSHOP · Hệ thống gửi tự động, vui lòng không reply</p>
                </div>
            </div>
            ";

            mail_send_order($email, $username, $subject, $html);
        }

        // Luôn chuyển sang verify_otp (tránh tiết lộ tồn tại tài khoản)
        header('Location: verify_otp.php');
        exit();
    }
}

list($msgType, $msgText) = str_contains($msg, ':') ? explode(':', $msg, 2) : ['', $msg];

include 'header.php';
?>
<div class="min-h-[75vh] flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50 px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 border border-gray-100">

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                <i class="fas fa-key text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-black tracking-tight text-gray-900">Quên mật khẩu</h2>
            <p class="text-gray-500 mt-2 text-sm">Nhập email hoặc tên tài khoản.<br>Chúng tôi sẽ gửi mã OTP 6 số để xác thực.</p>
        </div>

        <?php if ($msgText): ?>
        <div class="<?php echo $msgType === 'error' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-green-50 text-green-700 border-green-100'; ?> p-4 rounded-xl text-sm font-semibold mb-6 border text-center">
            <?php echo htmlspecialchars($msgText); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php" class="space-y-5">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">Email hoặc Tên tài khoản</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="text" name="email_or_username" required
                        placeholder="example@gmail.com hoặc tên tài khoản"
                        class="w-full pl-10 pr-4 py-3 bg-gray-50/50 rounded-xl border border-gray-200 outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition text-sm">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 hover:shadow-xl hover:-translate-y-0.5 transition transform duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Gửi mã OTP
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-gray-500 font-medium border-t border-gray-100 pt-6">
            <a href="login.php" class="text-blue-600 font-bold hover:underline">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
            </a>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>