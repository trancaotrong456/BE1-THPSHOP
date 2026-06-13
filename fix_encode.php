<?php
$file = 'chitiet.php';
$content = file_get_contents($file);

$correct_strings = [
    "XỬ LÝ THẢ TIM",
    "Bạn cần đăng nhập để thả tim.",
    "Lấy id sản phẩm từ url",
    "ID sản phẩm không hợp lệ.",
    "Lấy thông tin sản phẩm",
    "Sản phẩm không tồn tại.",
    "Kiểm tra user đã thả tim chưa",
    "Lấy thông tin đánh giá để hiển thị",
    "Lấy danh sách bình luận thực tế từ DB",
    "Chi tiết sản phẩm - THPSHOP",
    "Trang chủ",
    "Loại sản phẩm",
    "lượt đánh giá",
    "Đ</div>",
    "Tồn kho",
    "sản phẩm",
    "Màu sắc",
    "Mặc định",
    "Số Lượng",
    "Đã thích",
    "Thêm vào giỏ hàng",
    "Mô tả sản phẩm",
    "Sản phẩm liên quan",
    "Xem chi tiết",
    "Chưa có sản phẩm liên quan.",
    "ĐÁNH GIÁ SẢN PHẨM",
    "trên 5",
    "Tất Cả",
    "Có Bình Luận",
    "Khách",
    "Chưa có đánh giá nào cho sản phẩm này.",
    "Hãy là người đầu tiên đánh giá!",
    "VIẾT ĐÁNH GIÁ CỦA BẠN",
    "Chọn số sao của bạn *:",
    "Vui lòng chọn số sao đánh giá!",
    "Nội dung đánh giá *:",
    "Chia sẻ cảm nhận của bạn về sản phẩm: Chất lượng vải, đường may, form dáng, dịch vụ giao hàng...",
    "Gửi đánh giá",
    "Bạn cần đăng nhập để có thể gửi bình luận.",
    "Đến trang đăng nhập",
    "JS XỬ LÝ SỐ LƯỢNG, MÀU, SIZE VÀ NÚT GIỎ HÀNG",
    "HÀM QUAN TRỌNG: CẬP NHẬT LINK KHI BẤM",
    "THÊM GIỎ HÀNG",
    "HOẶC",
    "MUA NGAY",
    "Lưu đường dẫn thực sự vào data-href để xử lý AJAX, còn href = \"#\" để chặn chuyển trang",
    "Nút Mua ngay vẫn giữ nguyên tính năng chuyển trang",
    "SỰ KIỆN BẤM NÚT THÊM VÀO GIỎ HÀNG BẰNG AJAX",
    "Ngăn trình duyệt nhảy sang trang xử lý giỏ hàng",
    "Gọi ngầm file PHP để thêm dữ liệu vào session giỏ hàng",
    "Hiện thông báo (Toast) thành công",
    "Ẩn thông báo sau 3 giây",
    "Cập nhật con số hiển thị trên icon giỏ hàng",
    "Nếu đã có số, thì cộng thêm vào",
    "Nếu trước đó giỏ hàng trống, tạo thẻ badge mới",
    "Lỗi:",
    "Đã xảy ra lỗi khi thêm vào giỏ hàng. Vui lòng thử lại!",
    "JS THẢ TIM",
    "Đã thêm vào danh sách yêu thích",
    "Đã bỏ khỏi danh sách yêu thích",
    "JS LỌC BÌNH LUẬN",
    "JS CHỌN SAO ĐÁNH GIÁ",
    "RÀNG BUỘC KHI GỬI BÌNH LUẬN",
    "Chưa có đánh giá nào cho sản phẩm này"
];

$map = [];
foreach ($correct_strings as $correct) {
    $garbled = mb_convert_encoding($correct, 'UTF-8', 'Windows-1252');
    $map[$garbled] = $correct;
}

$new_content = strtr($content, $map);

if ($new_content !== $content) {
    file_put_contents($file, $new_content);
    echo "Fixed encoding issues.\n";
} else {
    echo "No changes made.\n";
}
?>
