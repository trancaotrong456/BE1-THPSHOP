<?php
session_start();
require_once "database.php";

// Kiểm tra quyền admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$db = new Database();

// ============================================================
// XỬ LÝ POST
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tab = $_POST['tab'] ?? 'general';

    // --- Tab 1: Cấu hình chung ---
    if ($tab === 'general') {
        $hotline = $db->conn->real_escape_string(trim($_POST['hotline'] ?? ''));
        $address = $db->conn->real_escape_string(trim($_POST['address'] ?? ''));

        $db->execute("UPDATE config SET value = '$hotline' WHERE `key` = 'hotline'");
        $db->execute("UPDATE config SET value = '$address' WHERE `key` = 'address'");

        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext     = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $new_filename = time() . '_banner.' . $ext;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], "public/images/$new_filename")) {
                    $db->execute("UPDATE config SET value = '$new_filename' WHERE `key` = 'banner_image'");
                }
            }
        }

        echo "<script>alert('Cập nhật cấu hình chung thành công!'); window.location.href='admin_config.php?tab=general';</script>";
        exit();
    }

    // --- Tab 2: SMTP ---
    if ($tab === 'smtp') {
        $smtp_fields = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_from_email', 'smtp_from_name'];
        foreach ($smtp_fields as $field) {
            $val = $db->conn->real_escape_string(trim($_POST[$field] ?? ''));
            $db->execute("INSERT INTO config (`key`, `value`) VALUES ('$field', '$val') ON DUPLICATE KEY UPDATE value = '$val'");
        }
        // Chỉ cập nhật password khi được điền mới (tránh ghi đè bằng chuỗi rỗng)
        $new_pass = trim($_POST['smtp_pass'] ?? '');
        if ($new_pass !== '') {
            $pass_esc = $db->conn->real_escape_string($new_pass);
            $db->execute("INSERT INTO config (`key`, `value`) VALUES ('smtp_pass', '$pass_esc') ON DUPLICATE KEY UPDATE value = '$pass_esc'");
        }

        echo "<script>alert('Cập nhật cấu hình Email (SMTP) thành công!'); window.location.href='admin_config.php?tab=smtp';</script>";
        exit();
    }
}

// ============================================================
// ĐỌC CONFIG
// ============================================================
$res_config = $db->select("SELECT * FROM config");
$config = [];
if ($res_config && $res_config->num_rows > 0) {
    while ($row = $res_config->fetch_assoc()) {
        $config[$row['key']] = $row['value'];
    }
}

// Helper lấy giá trị đã escape HTML
function cfg(string $key, string $default, array $config): string {
    return htmlspecialchars($config[$key] ?? $default);
}

$active_tab = (isset($_GET['tab']) && $_GET['tab'] === 'smtp') ? 'smtp' : 'general';
?>
<?php include 'admin_header.php'; ?>

<div class="max-w-3xl mx-auto space-y-6">

    <!-- TABS NAV -->
    <div class="flex gap-1 bg-white rounded-xl border border-gray-200 p-1 shadow-sm">
        <a href="admin_config.php?tab=general"
           class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition
                  <?php echo $active_tab === 'general' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
            <i class="fas fa-sliders-h"></i> Cấu hình chung
        </a>
        <a href="admin_config.php?tab=smtp"
           class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg font-bold text-sm transition
                  <?php echo $active_tab === 'smtp' ? 'bg-blue-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'; ?>">
            <i class="fas fa-envelope"></i> Email &amp; SMTP
            <?php if (!empty($config['smtp_pass'])): ?>
            <span class="bg-green-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1">✓</span>
            <?php else: ?>
            <span class="bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full ml-1">!</span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ==================== TAB 1: GENERAL ==================== -->
    <?php if ($active_tab === 'general'): ?>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-cog text-blue-600"></i> Thông tin cửa hàng
        </h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="tab" value="general">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Hotline</label>
                <input type="text" name="hotline" value="<?php echo cfg('hotline', '', $config); ?>"
                    class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Địa chỉ cửa hàng</label>
                <input type="text" name="address" value="<?php echo cfg('address', '', $config); ?>"
                    class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Banner Trang chủ</label>
                <img id="preview" src="public/images/<?php echo cfg('banner_image', 'anhdau.jpg', $config); ?>"
                     class="w-full h-48 object-cover rounded-lg border mb-3">
                <input type="file" name="banner_image" accept="image/*"
                    class="w-full border rounded-lg p-2 bg-gray-50 outline-none"
                    onchange="document.getElementById('preview').src = window.URL.createObjectURL(this.files[0])">
                <p class="text-xs text-gray-400 mt-1">Định dạng: JPG, PNG, WEBP.</p>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu cấu hình
                </button>
            </div>
        </form>
    </div>

    <!-- ==================== TAB 2: SMTP ==================== -->
    <?php else: ?>
    <div class="bg-white rounded-xl shadow-md p-8">
        <h2 class="text-xl font-bold text-gray-800 mb-1 flex items-center gap-2">
            <i class="fas fa-paper-plane text-blue-600"></i> Cấu hình Email (SMTP)
        </h2>
        <p class="text-sm text-gray-500 mb-6">
            Dùng để gửi mail xác nhận đơn hàng, thông báo giao hàng và đặt lại mật khẩu.
            Khuyến nghị dùng Gmail với <strong>App Password</strong>.
        </p>

        <!-- Hướng dẫn lấy App Password -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <p class="font-bold text-blue-800 text-sm mb-2">
                <i class="fas fa-info-circle mr-1"></i> Cách lấy Gmail App Password:
            </p>
            <ol class="text-blue-700 text-sm space-y-1 list-decimal list-inside leading-relaxed">
                <li>Truy cập <a href="https://myaccount.google.com/security" target="_blank" class="underline font-semibold">myaccount.google.com</a> → <strong>Bảo mật</strong></li>
                <li>Bật <strong>Xác minh 2 bước</strong> (nếu chưa bật)</li>
                <li>Tìm <strong>Mật khẩu ứng dụng</strong> → Tạo mới, tên tuỳ ý (ví dụ: "THPSHOP")</li>
                <li>Copy mật khẩu <strong>16 ký tự</strong> vào ô App Password bên dưới</li>
            </ol>
        </div>

        <form method="POST" class="space-y-5">
            <input type="hidden" name="tab" value="smtp">

            <!-- Host & Port -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Host</label>
                    <input type="text" name="smtp_host"
                        value="<?php echo cfg('smtp_host', 'smtp.gmail.com', $config); ?>"
                        class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">SMTP Port</label>
                    <input type="number" name="smtp_port"
                        value="<?php echo cfg('smtp_port', '587', $config); ?>"
                        class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
            </div>

            <!-- Secure -->
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Mã hoá kết nối</label>
                <div class="flex gap-6">
                    <?php $cur_secure = $config['smtp_secure'] ?? 'tls'; ?>
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700">
                        <input type="radio" name="smtp_secure" value="tls"
                            <?php echo $cur_secure === 'tls' ? 'checked' : ''; ?>>
                        TLS <span class="text-gray-400 font-normal">(Port 587 — Khuyến nghị)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-gray-700">
                        <input type="radio" name="smtp_secure" value="ssl"
                            <?php echo $cur_secure === 'ssl' ? 'checked' : ''; ?>>
                        SSL <span class="text-gray-400 font-normal">(Port 465)</span>
                    </label>
                </div>
            </div>

            <!-- User & Pass -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Gmail (SMTP User)</label>
                    <input type="email" name="smtp_user"
                        value="<?php echo cfg('smtp_user', '', $config); ?>"
                        placeholder="youremail@gmail.com"
                        class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        App Password (16 ký tự)
                        <?php if (!empty($config['smtp_pass'])): ?>
                        <span class="text-green-600 font-normal text-xs ml-1">
                            <i class="fas fa-check-circle"></i> Đã lưu
                        </span>
                        <?php else: ?>
                        <span class="text-red-500 font-normal text-xs ml-1">
                            <i class="fas fa-exclamation-circle"></i> Chưa có
                        </span>
                        <?php endif; ?>
                    </label>
                    <div class="relative">
                        <input type="password" name="smtp_pass" id="smtpPass"
                            placeholder="<?php echo !empty($config['smtp_pass']) ? '(Giữ nguyên)' : 'Nhập App Password...'; ?>"
                            class="w-full border rounded-lg p-3 pr-12 outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="togglePass()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 transition outline-none"
                            title="Hiện/Ẩn mật khẩu">
                            <i class="fas fa-eye" id="passIcon"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Để trống nếu không muốn thay đổi mật khẩu đã lưu.</p>
                </div>
            </div>

            <!-- From Email & From Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Email hiển thị (From Email)</label>
                    <input type="email" name="smtp_from_email"
                        value="<?php echo cfg('smtp_from_email', '', $config); ?>"
                        placeholder="noreply@gmail.com"
                        class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Thường giống với SMTP User.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tên người gửi (From Name)</label>
                    <input type="text" name="smtp_from_name"
                        value="<?php echo cfg('smtp_from_name', 'THPSHOP', $config); ?>"
                        placeholder="THPSHOP"
                        class="w-full border rounded-lg p-3 outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- PHPMailer install hint -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 text-sm text-yellow-800">
                <i class="fas fa-terminal mr-1"></i>
                Đảm bảo đã cài PHPMailer:
                <code class="bg-yellow-100 border border-yellow-300 rounded px-2 py-0.5 font-mono text-xs ml-1 select-all">
                    composer require phpmailer/phpmailer
                </code>
            </div>

            <!-- SMTP Save button -->
            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-600 text-white font-bold px-8 py-3 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Lưu cấu hình SMTP
                </button>
            </div>
        </form>

        <!-- ==== GỬI MAIL TEST ==== -->
        <div class="mt-8 border-t border-gray-100 pt-6">
            <h3 class="text-base font-bold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-flask text-purple-600"></i> Kiểm tra SMTP (gửi mail test)
            </h3>
            <p class="text-sm text-gray-500 mb-4">
                Lưu cấu hình trước, sau đó nhập email và nhấn gửi để kiểm tra kết nối SMTP.
            </p>
            <div class="flex gap-3">
                <input type="email" id="testEmail"
                    placeholder="Nhập email nhận test..."
                    class="flex-1 border rounded-lg px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
                <button type="button" id="testBtn" onclick="sendTestMail()"
                    class="bg-purple-600 text-white font-bold px-5 py-2.5 rounded-lg hover:bg-purple-700 transition text-sm whitespace-nowrap flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Gửi test
                </button>
            </div>
            <div id="testResult" class="hidden mt-3 text-sm font-semibold rounded-lg px-4 py-2.5"></div>
        </div>
    </div>

    <!-- Thông tin các sự kiện gửi mail -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-bell text-blue-600"></i> Các sự kiện gửi mail tự động
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                <div class="font-bold text-green-700 mb-1"><i class="fas fa-shopping-bag mr-1"></i> Đặt hàng thành công</div>
                <p class="text-green-600">Gửi ngay khi khách hoàn tất checkout.</p>
                <p class="text-xs text-green-500 mt-1 font-mono">type: order_created</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="font-bold text-blue-700 mb-1"><i class="fas fa-check-circle mr-1"></i> Giao hàng thành công</div>
                <p class="text-blue-600">Tự động sau 5 phút trạng thái "Đang giao".</p>
                <p class="text-xs text-blue-500 mt-1 font-mono">type: order_success</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="font-bold text-red-700 mb-1"><i class="fas fa-times-circle mr-1"></i> Hủy đơn hàng</div>
                <p class="text-red-600">Khi Admin chuyển trạng thái sang Hủy.</p>
                <p class="text-xs text-red-500 mt-1 font-mono">type: order_cancel</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Toggle show/hide App Password
function togglePass() {
    const input = document.getElementById('smtpPass');
    const icon  = document.getElementById('passIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Gửi mail test qua AJAX
async function sendTestMail() {
    const email = document.getElementById('testEmail').value.trim();
    const resultEl = document.getElementById('testResult');
    const btn = document.getElementById('testBtn');

    if (!email) {
        alert('Vui lòng nhập địa chỉ email kiểm tra!');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
    resultEl.className = 'mt-3 text-sm font-semibold rounded-lg px-4 py-2.5 bg-gray-50 border border-gray-200 text-gray-600';
    resultEl.classList.remove('hidden');
    resultEl.textContent = 'Đang kết nối SMTP và gửi email...';

    try {
        const fd = new FormData();
        fd.append('email', email);
        const res  = await fetch('admin_test_mail.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            resultEl.className = 'mt-3 text-sm font-semibold rounded-lg px-4 py-2.5 bg-green-50 border border-green-200 text-green-700';
            resultEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i>' + data.msg;
        } else {
            resultEl.className = 'mt-3 text-sm font-semibold rounded-lg px-4 py-2.5 bg-red-50 border border-red-200 text-red-600';
            resultEl.innerHTML = '<i class="fas fa-times-circle mr-1"></i>' + data.msg;
        }
    } catch (e) {
        resultEl.className = 'mt-3 text-sm font-semibold rounded-lg px-4 py-2.5 bg-red-50 border border-red-200 text-red-600';
        resultEl.textContent = 'Lỗi kết nối tới server. Kiểm tra lại PHP/server.';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Gửi test';
    }
}
</script>

<?php include 'admin_footer.php'; ?>
