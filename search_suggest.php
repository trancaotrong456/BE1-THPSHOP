<?php
require_once "database.php";
$db = new Database();

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$qEsc = $q !== '' ? $db->conn->real_escape_string($q) : '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Tìm sản phẩm theo tên chứa chuỗi người dùng nhập
$sql = "SELECT MaSanPham, TenSanPham, hinh, GiaSanPham
        FROM product
        WHERE TenSanPham LIKE '%{$qEsc}%'
        ORDER BY MaSanPham DESC
        LIMIT 8";

$result = $db->select($sql);

$out = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $gia = isset($row['GiaSanPham']) ? (float)$row['GiaSanPham'] : 0;
        $out[] = [
            'id' => (int)$row['MaSanPham'],
            'ten' => $row['TenSanPham'],
            'hinh' => $row['hinh'] !== null && $row['hinh'] !== '' ? $row['hinh'] : 'default.jpg',
            'gia_fmt' => number_format($gia, 0, ',', '.' ) . 'đ'
        ];
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);