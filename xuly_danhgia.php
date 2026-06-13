<?php
session_start();
require_once "database.php";

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra đăng nhập (Tùy thuộc vào việc bạn lưu session ID là gì, thường là user_id hoặc IdNguoiDung)
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Vui lòng đăng nhập để đánh giá!'); window.location.href='login.php';</script>";
        exit;
    }

    $db = new Database();
    
    // Cấm admin tự viết review/đánh giá
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        echo "<script>alert('Tài khoản Admin không được tự viết review. Chỉ được Duyệt/Ẩn/Xóa/Reply review của khách hàng. Vui lòng dùng tài khoản User.'); window.location.href='index.php';</script>";
        exit();
    }

    // 2. Nhận dữ liệu từ form an toàn
    $product_id = intval($_POST['product_id']);
    $user_id = intval($_SESSION['user_id']); // Lấy ID người dùng từ session
    $rating = intval($_POST['rating']);
    $comment = isset($_POST['comment']) ? $db->conn->real_escape_string(trim($_POST['comment'])) : '';
    $ngay_binh_luan = date('Y-m-d H:i:s'); // Lấy giờ hiện tại

    // 3. Kiểm tra tính hợp lệ
    if ($rating < 1 || $rating > 5) {
        echo "<script>alert('Số sao không hợp lệ!'); history.back();</script>";
        exit;
    }

    // 4. Lưu vào Database (bảng `review`)
    $sql_insert = "INSERT INTO review (MaSanPham, IdNguoiDung, NoiDung, SoSao, NgayBinhLuan) 
                   VALUES ($product_id, $user_id, '$comment', $rating, '$ngay_binh_luan')";
    
    $result = $db->execute($sql_insert);

    if ($result) {
        // 5. Cập nhật lại số SaoTrungBinh và TongDanhGia vào bảng `product`
        $sql_calc = "SELECT COUNT(id) as Tong, AVG(SoSao) as TrungBinh FROM review WHERE MaSanPham = $product_id";
        $res_calc = $db->select($sql_calc);
        
        if ($res_calc && $res_calc->num_rows > 0) {
            $row_calc = $res_calc->fetch_assoc();
            $tong_dg = (int)$row_calc['Tong'];
            $sao_tb = round((float)$row_calc['TrungBinh'], 1); // Làm tròn 1 chữ số thập phân

            // Update bảng product
            $sql_update = "UPDATE product SET TongDanhGia = $tong_dg, SaoTrungBinh = $sao_tb WHERE MaSanPham = $product_id";
            $db->execute($sql_update);
        }

        // 6. Quay lại trang chi tiết sản phẩm
        header("Location: chitiet.php?id=" . $product_id);
        exit;
    } else {
        echo "<script>alert('Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.'); history.back();</script>";
    }
} else {
    header("Location: index.php");
    exit;
}
?>