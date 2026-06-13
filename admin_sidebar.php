<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="flex flex-1 overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col hidden md:flex shrink-0 shadow-2xl relative">
        <div class="absolute inset-0 bg-gradient-to-b from-blue-900/20 to-transparent pointer-events-none"></div>
        <div class="p-6 border-b border-slate-800 relative z-10">
            <h2
                class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400 tracking-wider flex items-center justify-center gap-2">
                <i class="fas fa-crown text-blue-400"></i>
                ADMIN
            </h2>
        </div>

        <div class="flex-1 overflow-y-auto py-4 px-3 custom-scrollbar relative z-10 space-y-1">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3 px-3">Main Menu</p>

            <a href="admin_dashboard.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_dashboard.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-tachometer-alt w-5 mr-3 text-lg <?php echo ($current_page == 'admin_dashboard.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Dashboard
            </a>

            <a href="admin_product.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_product.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-box w-5 mr-3 text-lg <?php echo ($current_page == 'admin_product.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Sản phẩm
            </a>

            <a href="admin_categories.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_categories.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-tags w-5 mr-3 text-lg <?php echo ($current_page == 'admin_categories.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Danh mục
            </a>

            <a href="admin_orders.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_orders.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-shopping-cart w-5 mr-3 text-lg <?php echo ($current_page == 'admin_orders.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Đơn hàng
            </a>

            <a href="admin_discounts.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_discounts.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-ticket-alt w-5 mr-3 text-lg <?php echo ($current_page == 'admin_discounts.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Mã giảm giá
            </a>

            <a href="admin_banner.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_banner.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-images w-5 mr-3 text-lg <?php echo ($current_page == 'admin_banner.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Banner
            </a>

            <a href="admin_users.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_users.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-users w-5 mr-3 text-lg <?php echo ($current_page == 'admin_users.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Khách hàng
            </a>

            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mt-6 mb-3 px-3">System</p>

            <a href="admin_config.php"
                class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 <?php echo ($current_page == 'admin_config.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?>">
                <i
                    class="fas fa-cog w-5 mr-3 text-lg <?php echo ($current_page == 'admin_config.php') ? 'text-white' : 'text-slate-400 group-hover:text-white'; ?>"></i>
                Cấu hình
            </a>
        </div>

        <div class="p-4 border-t border-slate-800 relative z-10 space-y-2">
            <a href="index.php"
                class="flex items-center px-4 py-2 text-sm text-blue-400 font-medium rounded-xl hover:bg-slate-800 transition-colors">
                <i class="fas fa-globe w-5 mr-3"></i> Về trang chủ
            </a>
            <a href="logout.php"
                class="flex items-center px-4 py-2 text-sm text-red-400 font-medium rounded-xl hover:bg-red-900/30 hover:text-red-300 transition-colors">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i> Đăng xuất
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 bg-slate-50 w-full overflow-y-auto relative scroll-smooth p-4 md:p-8">
        <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Sidebar scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
        </style>