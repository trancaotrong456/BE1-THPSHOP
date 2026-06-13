<?php
session_start();
require_once "database.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// === 1. API ĐỔI MẬT KHẨU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'change_password') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    $current_pw = $db->conn->real_escape_string($data['current']);
    $new_pw = $db->conn->real_escape_string($data['new']);
    
    $check = $db->select("SELECT matkhau FROM user WHERE IdNguoiDung = $user_id");
    if ($check && $check->num_rows > 0) {
        $user = $check->fetch_assoc();
        if ($user['matkhau'] !== $current_pw) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng!']);
            exit();
        }
        
        $update = $db->execute("UPDATE user SET matkhau = '$new_pw' WHERE IdNguoiDung = $user_id");
        if ($update) {
            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống, không thể đổi mật khẩu.']);
        }
    }
    exit();
}

// === 2. API CẬP NHẬT THÔNG TIN HỒ SƠ & AVATAR ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] == 'update_profile') {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $db->conn->real_escape_string($data['name']);
    $phone = $db->conn->real_escape_string($data['phone']);
    $address = $db->conn->real_escape_string($data['address']);
    $gender = $db->conn->real_escape_string($data['gender']);
    $dob = $db->conn->real_escape_string($data['dateOfBirth']);
    
    // --- XỬ LÝ LƯU AVATAR ---
    $avatar_sql = "";
    // Kiểm tra xem frontend có gửi ảnh dưới dạng base64 không
    if (isset($data['avatar']) && strpos($data['avatar'], 'data:image/') === 0) {
        // Tách dữ liệu base64 để lưu thành file
        $image_parts = explode(";base64,", $data['avatar']);
        if (count($image_parts) == 2) {
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1]; // Lấy đuôi file (png, jpg, jpeg...)
            $image_base64 = base64_decode($image_parts[1]);
            
            // Đặt tên file ngẫu nhiên để tránh trùng lặp
            $file_name = time() . "_avatar_" . $user_id . '.' . $image_type;
            $file_path = "public/images/" . $file_name;
            
            // Lưu file vào thư mục public/images/
            if (file_put_contents($file_path, $image_base64)) {
                $avatar_sql = ", AnhDaiDien='$file_name'";
                $_SESSION['user_avatar'] = $file_name; // Cập nhật luôn session để Header đổi ảnh
            }
        }
    }
    // ------------------------

    // Cập nhật Database (có bao gồm Avatar nếu có sự thay đổi)
    $sql = "UPDATE user SET TenNguoiDung='$name', SoDienThoai='$phone', diachi='$address', GioiTinh='$gender', NgaySinh=" . ($dob ? "'$dob'" : "NULL") . " $avatar_sql WHERE IdNguoiDung = $user_id";
    
    if ($db->execute($sql)) {
        $_SESSION['user_name'] = $name;
        echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật hồ sơ.']);
    }
    exit();
}

// === 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ ===
$sql = "SELECT * FROM user WHERE IdNguoiDung = $user_id";
$result = $db->select($sql);

if ($result && $result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    echo "<script>alert('Không tìm thấy thông tin người dùng!'); window.location.href='index.php';</script>";
    exit();
}

$name = $user_data['TenNguoiDung'] ?? '';
$email = $user_data['email'] ?? '';
$phone = $user_data['SoDienThoai'] ?? '';
$gender = $user_data['GioiTinh'] ?? 'male'; 
$dob = $user_data['NgaySinh'] ?? '';
$address = $user_data['diachi'] ?? '';

// Xử lý avatar
$avatar = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0D8ABC&color=fff&size=160';
if (!empty($user_data['AnhDaiDien']) && $user_data['AnhDaiDien'] != "'default.png'" && $user_data['AnhDaiDien'] != 'default.png') {
    $avatar = 'public/images/' . str_replace("'", "", $user_data['AnhDaiDien']);
}

$userDataJSON = json_encode([
    'name' => $name, 'email' => $email, 'phone' => $phone,
    'gender' => $gender, 'dateOfBirth' => $dob, 'address' => $address, 'avatar' => $avatar
]);
// === 4. LẤY VÍ VOUCHER CỦA USER ===
$vouchers_data = [];
$res_vouchers = $db->select("
    SELECT dc.*, usc.is_used, usc.saved_at
    FROM user_saved_codes usc
    JOIN discount_codes dc ON usc.discount_id = dc.id
    WHERE usc.user_id = $user_id
    ORDER BY usc.saved_at DESC
");
if ($res_vouchers && $res_vouchers->num_rows > 0) {
    while ($v = $res_vouchers->fetch_assoc()) $vouchers_data[] = $v;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/web_be1.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ của tôi - TTP Shop</title>

    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
    body {
        font-family: 'Manrope', sans-serif;
        background: #f5f5f3;
        color: #111827;
    }

    .lux-title {
        font-family: 'Cormorant Garamond', serif;
    }

    .toast-enter {
        animation: slideDown 0.3s ease-out;
    }

    .anim-fade {
        animation: fadeIn 0.2s ease-out;
    }

    .anim-scale {
        animation: scaleIn 0.2s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 mt-6">
        <a href="index.php"
            class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-lg shadow hover:bg-gray-50 text-gray-700 font-medium transition">
            <i class="fas fa-arrow-left"></i> Về trang chủ
        </a>
    </div>

    <div id="root"></div>

    <script>
    window.USER_DATA = <?php echo $userDataJSON; ?>;
    window.USER_VOUCHERS = <?php echo json_encode($vouchers_data); ?>;
    </script>

    <script type="text/babel">
        const { useState, useEffect } = React;
        const initialUserData = window.USER_DATA;

        function ToastContainer() {
            const [toasts, setToasts] = useState([]);
            window.showToast = (message, type = 'success') => {
                const id = Date.now();
                setToasts(prev => [...prev, { id, message, type }]);
                setTimeout(() => setToasts(p => p.filter(t => t.id !== id)), 3000);
            };

            return (
                <div className="fixed top-5 left-1/2 -translate-x-1/2 z-[9999] flex flex-col gap-2">
                    {toasts.map(t => (
                        <div key={t.id} className={`toast-enter bg-white px-5 py-3 rounded-lg shadow-lg flex items-center gap-3 border-l-4 ${t.type === 'success' ? 'border-green-500' : 'border-red-500'}`}>
                            <i className={`fas text-lg ${t.type === 'success' ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-500'}`}></i>
                            <span className="text-gray-800 text-sm font-medium">{t.message}</span>
                        </div>
                    ))}
                </div>
            );
        }

        function PasswordDialog({ isOpen, onClose, onSubmit }) {
            const [data, setData] = useState({ current: '', new: '', confirm: '' });
            const [show, setShow] = useState({});

            if (!isOpen) return null;

            const fields = [
                { key: 'current', label: 'Mật khẩu hiện tại', ph: 'Nhập mật khẩu hiện tại' },
                { key: 'new', label: 'Mật khẩu mới', ph: 'Tối thiểu 6 ký tự' },
                { key: 'confirm', label: 'Xác nhận mật khẩu mới', ph: 'Nhập lại mật khẩu' }
            ];

            const handleClose = () => {
                setData({ current: '', new: '', confirm: '' });
                setShow({});
                onClose();
            };

            return (
                <>
                    <div className="fixed inset-0 bg-black/50 z-50 anim-fade" onClick={handleClose} />
                    <div className="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white p-6 rounded-2xl w-[90%] max-w-md z-[51] anim-scale shadow-2xl">
                        <h2 className="text-xl font-bold mb-1">Đổi mật khẩu</h2>
                        <p className="text-gray-500 text-sm mb-5">Nhập mật khẩu hiện tại và mật khẩu mới của bạn</p>
                        
                        <div className="space-y-4">
                            {fields.map(f => (
                                <div key={f.key}>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">{f.label}</label>
                                    <div className="relative">
                                        <input type={show[f.key] ? 'text' : 'password'} placeholder={f.ph}
                                            value={data[f.key]} onChange={e => setData({...data, [f.key]: e.target.value})}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-blue-500 pr-10 text-sm" />
                                        <button type="button" onClick={() => setShow({...show, [f.key]: !show[f.key]})}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-blue-600">
                                            <i className={`fas fa-eye${show[f.key] ? '-slash' : ''}`}></i>
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex gap-3 mt-6 justify-end">
                            <button onClick={handleClose} className="px-4 py-2 border rounded-lg hover:bg-gray-50 font-medium text-sm">Hủy</button>
                            <button onClick={() => onSubmit(data)} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm shadow-md">Đổi mật khẩu</button>
                        </div>
                    </div>
                </>
            );
        }

        function UserProfile() {
            const [activeTab, setActiveTab] = useState(new URLSearchParams(window.location.search).get('tab') === 'vouchers' ? 'vouchers' : 'profile');
            const [isEditing, setIsEditing] = useState(false);
            const [isPwdOpen, setIsPwdOpen] = useState(false);
            
            const [profile, setProfile] = useState(initialUserData);
            const [edited, setEdited] = useState(initialUserData);
            const [vouchers, setVouchers] = useState(window.USER_VOUCHERS || []);
            const [voucherFilter, setVoucherFilter] = useState('all');

            const handlePwdSubmit = async (d) => {
                if (!d.current) return window.showToast('Vui lòng nhập mật khẩu hiện tại', 'error');
                if (d.new.length < 6) return window.showToast('Mật khẩu mới tối thiểu 6 ký tự', 'error');
                if (d.new !== d.confirm) return window.showToast('Mật khẩu xác nhận không khớp', 'error');
                
                try {
                    const response = await fetch('user_profile.php?action=change_password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ current: d.current, new: d.new })
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.showToast(result.message, 'success');
                        setIsPwdOpen(false);
                    } else {
                        window.showToast(result.message, 'error');
                    }
                } catch (err) {
                    window.showToast('Lỗi kết nối Server!', 'error');
                }
            };

            const handleSaveProfile = async () => {
                try {
                    const response = await fetch('user_profile.php?action=update_profile', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(edited)
                    });
                    const result = await response.json();
                    if (result.success) {
                        setProfile(edited);
                        setIsEditing(false);
                        window.showToast(result.message, 'success');
                        
                        // Cập nhật lại giao diện sau khi lưu (Reload lại trang để hiển thị Avatar mới)
                        setTimeout(() => { window.location.reload(); }, 1500);
                    } else {
                        window.showToast(result.message, 'error');
                    }
                } catch (err) {
                    window.showToast('Lỗi kết nối Server!', 'error');
                }
            };

            const handleAvatar = (e) => {
                const file = e.target.files?.[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onloadend = () => setEdited({ ...edited, avatar: reader.result });
                    reader.readAsDataURL(file);
                }
            };

            const handleChange = (e) => {
                setEdited({ ...edited, [e.target.name]: e.target.value });
            };

            return (
                <div className="min-h-[80vh] bg-gray-100 pb-10 px-4 mt-6">
                    <ToastContainer />
                    <PasswordDialog isOpen={isPwdOpen} onClose={() => setIsPwdOpen(false)} onSubmit={handlePwdSubmit} />
                    
                    <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">
                        
                        <div className="bg-gradient-to-r from-blue-600 to-purple-600 h-32 relative">
                            <div className="absolute -bottom-16 left-8">
                                <div className="relative">
                                    <div className="w-32 h-32 rounded-full border-4 border-white shadow-md overflow-hidden bg-white">
                                        <img src={isEditing ? edited.avatar : profile.avatar} alt="Avatar" className="w-full h-full object-cover" />
                                    </div>
                                    {isEditing && activeTab === 'profile' && (
                                        <label className="absolute bottom-1 right-1 bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full cursor-pointer hover:bg-blue-700 shadow-sm text-sm">
                                            <i className="fas fa-camera"></i>
                                            <input type="file" accept="image/*" className="hidden" onChange={handleAvatar} />
                                        </label>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* TABS MENU */}
                        <div className="pt-20 px-8 border-b flex gap-6">
                            <button onClick={() => { setActiveTab('profile'); window.history.replaceState({}, '', 'user_profile.php'); }} className={`pb-4 font-bold text-[15px] border-b-2 transition-colors ${activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800'}`}>
                                <i className="fas fa-user-circle mr-2"></i>Hồ sơ cá nhân
                            </button>
                            <button onClick={() => { setActiveTab('vouchers'); window.history.replaceState({}, '', 'user_profile.php?tab=vouchers'); }} className={`pb-4 font-bold text-[15px] border-b-2 transition-colors ${activeTab === 'vouchers' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-800'}`}>
                                <i className="fas fa-wallet mr-2"></i>Ví Voucher <span className="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full ml-1">{vouchers.length}</span>
                            </button>
                        </div>

                        <div className="px-8 py-8">
                            {activeTab === 'profile' ? (
                                <>
                                    <div className="flex justify-between items-start mb-8 border-b pb-6">
                                <div>
                                    <h1 className="text-2xl font-bold text-gray-900">{profile.name}</h1>
                                    <p className="text-gray-500 mt-1 text-sm"><i className="fas fa-envelope mr-2"></i> {profile.email}</p>
                                </div>
                                <div className="flex gap-3">
                                    {!isEditing ? (
                                        <>
                                            <button onClick={() => setIsPwdOpen(true)} className="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50 font-medium text-sm transition">
                                                <i className="fas fa-lock mr-2"></i> Đổi mật khẩu
                                            </button>
                                            <button onClick={() => { setEdited(profile); setIsEditing(true); }} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm shadow-md transition">
                                                <i className="fas fa-pen mr-2"></i> Sửa hồ sơ
                                            </button>
                                        </>
                                    ) : (
                                        <>
                                            <button onClick={() => setIsEditing(false)} className="px-4 py-2 border rounded-lg hover:bg-gray-50 font-medium text-sm">Hủy</button>
                                            <button onClick={handleSaveProfile} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm shadow-md">Lưu thay đổi</button>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-600 mb-1">Họ và Tên</label>
                                        {isEditing ? (
                                            <input type="text" name="name" value={edited.name} onChange={handleChange} className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
                                        ) : (
                                            <p className="text-gray-800 text-md">{profile.name}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-600 mb-1">Số điện thoại</label>
                                        {isEditing ? (
                                            <input type="text" name="phone" value={edited.phone} onChange={handleChange} className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Chưa cập nhật" />
                                        ) : (
                                            <p className="text-gray-800 text-md">{profile.phone || <span className="text-gray-400 italic">Chưa cập nhật</span>}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-600 mb-1">Giới tính</label>
                                        {isEditing ? (
                                            <select name="gender" value={edited.gender} onChange={handleChange} className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                                <option value="male">Nam</option>
                                                <option value="female">Nữ</option>
                                            </select>
                                        ) : (
                                            <p className="text-gray-800 text-md">{profile.gender === 'male' ? 'Nam' : 'Nữ'}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-600 mb-1">Ngày sinh</label>
                                        {isEditing ? (
                                            <input type="date" name="dateOfBirth" value={edited.dateOfBirth} onChange={handleChange} className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" />
                                        ) : (
                                            <p className="text-gray-800 text-md">{profile.dateOfBirth ? new Date(profile.dateOfBirth).toLocaleDateString('vi-VN') : <span className="text-gray-400 italic">Chưa cập nhật</span>}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-600 mb-1">Địa chỉ</label>
                                        {isEditing ? (
                                            <textarea name="address" value={edited.address} onChange={handleChange} rows="3" className="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nhập địa chỉ của bạn..."></textarea>
                                        ) : (
                                            <p className="text-gray-800 text-md">{profile.address || <span className="text-gray-400 italic">Chưa cập nhật</span>}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                                    <div className="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-4 text-blue-800 flex gap-3 items-center">
                                        <span className="text-sm">💡 <strong>Lưu ý:</strong> Mật khẩu mới của bạn sẽ được bảo mật và áp dụng ngay trong lần đăng nhập tiếp theo. Việc cập nhật thông tin cá nhân giúp Shop hỗ trợ bạn tốt hơn trong quá trình giao hàng.</span>
                                    </div>
                                </>
                            ) : (
                                <div>
                                    <div className="flex justify-between items-center mb-6 flex-wrap gap-4">
                                        <h2 className="text-xl font-bold">Mã giảm giá đã lưu</h2>
                                        <div className="flex gap-2">
                                            {['all', 'percent', 'fixed'].map(t => (
                                                <button key={t} onClick={() => setVoucherFilter(t)} className={`px-4 py-1.5 rounded-full text-sm font-bold border transition ${voucherFilter === t ? 'bg-blue-600 text-white border-blue-600 shadow' : 'bg-white text-gray-600 hover:border-blue-400'}`}>
                                                    {t === 'all' ? 'Tất cả' : t === 'percent' ? 'Theo %' : 'Cố định'}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                    
                                    {vouchers.filter(v => voucherFilter === 'all' || v.discount_type === voucherFilter).length === 0 ? (
                                        <div className="text-center py-10">
                                            <i className="fas fa-box-open text-4xl text-gray-300 mb-3 block"></i>
                                            <p className="text-gray-500 font-medium">Chưa có mã giảm giá nào trong ví.</p>
                                            <a href="magiamgia.php" className="text-blue-600 font-bold hover:underline mt-2 inline-block">Đến kho Voucher ngay</a>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {vouchers.filter(v => voucherFilter === 'all' || v.discount_type === voucherFilter).map(v => {
                                                const label = v.discount_type === 'percent' ? `${v.discount_value}%` : `${new Intl.NumberFormat('vi-VN').format(v.discount_value)}đ`;
                                                const isExpired = new Date(v.end_date) < new Date();
                                                const isFull = v.usage_limit > 0 && v.used_count >= v.usage_limit;
                                                const unavailable = isExpired || isFull || parseInt(v.is_used) === 1;
                                                
                                                return (
                                                    <div key={v.id} className={`border rounded-xl p-4 flex items-center gap-4 relative overflow-hidden bg-white shadow-sm ${unavailable ? 'opacity-50' : 'hover:border-blue-400 hover:shadow-md transition'}`}>
                                                        <div className={`w-20 h-20 shrink-0 rounded-xl flex flex-col items-center justify-center text-white shadow-inner ${v.discount_type === 'percent' ? 'bg-gradient-to-br from-yellow-500 to-red-500' : 'bg-gradient-to-br from-blue-500 to-indigo-600'}`}>
                                                            <span className="font-black text-lg">{label}</span>
                                                            <span className="text-[9px] uppercase font-bold opacity-80 mt-1">GIẢM</span>
                                                        </div>
                                                        <div className="flex-1 min-w-0">
                                                            <div className="font-mono font-black text-lg text-gray-800">{v.code}</div>
                                                            <p className="text-xs text-gray-500 mt-1">Đơn tối thiểu: <strong>{new Intl.NumberFormat('vi-VN').format(v.min_order_value)}đ</strong></p>
                                                            <p className="text-[11px] text-gray-400 mt-1"><i className="fas fa-clock mr-1"></i>HSD: {new Date(v.end_date).toLocaleDateString('vi-VN')}</p>
                                                            {parseInt(v.is_used) === 1 && <div className="text-xs text-red-500 font-bold mt-1">Đã sử dụng</div>}
                                                            {isExpired && parseInt(v.is_used) !== 1 && <div className="text-xs text-red-500 font-bold mt-1">Đã hết hạn</div>}
                                                        </div>
                                                        <button onClick={async () => {
                                                            if (!confirm('Bạn muốn bỏ lưu mã này?')) return;
                                                            try {
                                                                const formData = new URLSearchParams(); formData.append('action', 'remove_saved'); formData.append('discount_id', v.id);
                                                                const res = await fetch('xuly_magiamgia.php', { method: 'POST', body: formData });
                                                                const data = await res.json();
                                                                if (data.success) {
                                                                    setVouchers(prev => prev.filter(item => item.id !== v.id));
                                                                    window.showToast('Đã xóa mã', 'success');
                                                                }
                                                            } catch (err) {}
                                                        }} className="absolute top-2 right-2 text-gray-300 hover:text-red-500 p-2" title="Bỏ lưu">
                                                            <i className="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<UserProfile />);
    </script>
    
    <?php include 'footer.php'; ?>
</body>

</html>