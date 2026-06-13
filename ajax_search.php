<?php
require_once "database.php";
$db = new Database();

if (isset($_POST['keyword'])) {
    $keyword = $db->conn->real_escape_string($_POST['keyword']);
    
    if (strlen($keyword) > 0) {
        // Tìm kiếm sản phẩm có tên chứa từ khóa, giới hạn 5 kết quả
        $sql = "SELECT MaSanPham, TenSanPham, hinh, GiaSanPham FROM product WHERE TenSanPham LIKE '%$keyword%' LIMIT 5";
        $result = $db->select($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // In ra từng dòng kết quả
                echo '
                <a href="chitiet.php?id='.$row['MaSanPham'].'" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition border-b border-gray-100 last:border-b-0 cursor-pointer">
                    <img src="./public/images/'. (isset($row['hinh']) ? $row['hinh'] : 'default.jpg') .'" alt="'.$row['TenSanPham'].'" class="w-12 h-12 object-cover rounded border">
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-800 line-clamp-1">'.$row['TenSanPham'].'</p>
                        <p class="text-xs text-red-600 font-bold mt-1">'.number_format($row['GiaSanPham']).'đ</p>
                    </div>
                </a>';
            }
        } else {
            echo '<div class="p-4 text-sm text-gray-500 text-center font-medium">Không tìm thấy sản phẩm nào!</div>';
        }
    }
}
?>