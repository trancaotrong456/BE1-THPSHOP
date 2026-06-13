<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$ten_user = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$avatar_link = 'https://ui-avatars.com/api/?name=' . urlencode($ten_user) . '&background=0D8ABC&color=fff&size=128';

if (isset($_SESSION['user_avatar']) && $_SESSION['user_avatar'] != 'default.png' && $_SESSION['user_avatar'] != '') {
    $avatar_link = 'public/images/' . $_SESSION['user_avatar'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="./public/images/icon_web.png" type="image/icon type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THP Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 h-screen flex flex-col font-sans overflow-hidden">

    <header class="bg-white shadow-sm border-b border-slate-200 z-50 shrink-0">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-blue-600 flex items-center gap-2">
                <i class="fas fa-shield-alt"></i> THP Admin
            </h1>

            <div class="flex items-center gap-6">
                <a href="index.php"
                    class="bg-blue-50 text-blue-600 px-4 py-2 rounded-lg font-bold hover:bg-blue-100 transition flex items-center gap-2 text-sm shadow-sm">
                    <i class="fas fa-globe"></i> Xem website
                </a>

                <div class="relative group inline-block z-50 text-sm">
                    <div class="flex items-center gap-2 cursor-pointer whitespace-nowrap py-2"
                        title="Tài khoản của tôi">
                        <span class="text-gray-500 group-hover:text-gray-800 transition">Xin chào,</span>
                        <img src="<?php echo htmlspecialchars($avatar_link); ?>" alt="Avatar"
                            class="w-9 h-9 rounded-full object-cover border-2 border-gray-200 group-hover:border-blue-400 transition shadow-sm">
                        <strong class="text-gray-800 group-hover:text-blue-600 flex items-center gap-1 transition">
                            <?php echo htmlspecialchars($ten_user); ?>
                            <i class="fas fa-chevron-down text-[10px] transition-transform group-hover:rotate-180"></i>
                        </strong>
                    </div>

                    <div class="absolute right-0 top-full mt-0 w-56 bg-white rounded-xl shadow-xl py-2 hidden group-hover:block border border-gray-100 transform opacity-0 group-hover:opacity-100 transition duration-300 text-left">
                        <div class="absolute -top-2 right-4 w-4 h-4 bg-white border-l border-t border-gray-100 transform rotate-45"></div>

                        <div class="relative z-10 flex flex-col text-gray-700">
                            <a href="user_profile.php" class="px-4 py-2.5 hover:bg-blue-50 hover:text-blue-600 transition flex items-center gap-3 font-medium">
                                <i class="fas fa-user-circle w-5 text-center"></i> Hồ sơ cá nhân
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="logout.php" class="px-4 py-2.5 hover:bg-red-50 hover:text-red-600 transition flex items-center gap-3 text-red-500 font-semibold">
                                <i class="fas fa-sign-out-alt w-5 text-center"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
<?php include "admin_sidebar.php"; ?>
