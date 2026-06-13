-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th6 06, 2026 lúc 09:19 PM
-- Phiên bản máy phục vụ: 9.1.0
-- Phiên bản PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `be1`
--
CREATE DATABASE IF NOT EXISTS `be1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `be1`;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `idCart` int NOT NULL AUTO_INCREMENT,
  `MaSanPham` int NOT NULL,
  `ngayTao` date NOT NULL,
  `trangThai` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `TongTien` decimal(10,0) NOT NULL,
  `IdNguoiDung` int NOT NULL,
  PRIMARY KEY (`idCart`),
  KEY `fk_giohang` (`IdNguoiDung`),
  KEY `fk_card_sp` (`MaSanPham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `MaDanhMuc` int NOT NULL AUTO_INCREMENT,
  `TenDanhMuc` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MoTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`MaDanhMuc`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`MaDanhMuc`, `TenDanhMuc`, `MoTa`) VALUES
(1, 'Quần', 'Quần là trang phục mặc ở phần dưới cơ thể, dùng để che và bảo vệ chân, đồng thời tạo phong cách thời trang.'),
(2, 'Áo', 'Áo là trang phục mặc ở phần trên cơ thể, giúp bảo vệ cơ thể và thể hiện phong cách cá nhân.'),
(3, 'Giày', 'Giày là sản phẩm dùng để bảo vệ bàn chân và hỗ trợ di chuyển.'),
(4, 'Phụ Kiện', 'Phụ kiện là các sản phẩm đi kèm với trang phục để làm nổi bật phong cách và tăng tính thẩm mỹ.'),
(101, 'Điện thoại & Phụ kiện', 'Các dòng điện thoại thông minh, máy tính bảng và phụ kiện chính hãng'),
(102, 'Máy tính & Laptop', 'Laptop văn phòng, laptop gaming, PC và linh kiện máy tính'),
(103, 'Thời trang Nam', 'Quần áo, phụ kiện thời trang, phong cách nam tính hiện đại'),
(104, 'Thời trang Nữ', 'Váy vóc, áo kiểu, thời trang xu hướng dành cho phái đẹp'),
(105, 'Giày dép Nam Nữ', 'Giày thể thao, giày tây, sneaker, sandal đa dạng mẫu mã'),
(106, 'Gia dụng thông minh', 'Thiết bị điện gia dụng, chăm sóc nhà cửa và đời sống'),
(107, 'Sức khỏe & Sắc đẹp', 'Mỹ phẩm chính hãng, chăm sóc da, trang điểm, thực phẩm chức năng');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chitietdonhang`
--

DROP TABLE IF EXISTS `chitietdonhang`;
CREATE TABLE IF NOT EXISTS `chitietdonhang` (
  `MaChiTiet` int NOT NULL AUTO_INCREMENT,
  `MaDonHang` int NOT NULL,
  `MaSanPham` int NOT NULL,
  `PhanLoai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SoLuong` int NOT NULL,
  `Gia` decimal(10,0) NOT NULL,
  PRIMARY KEY (`MaChiTiet`),
  KEY `fk_ctdh_sanpham` (`MaSanPham`),
  KEY `fk_ctdh_donhang` (`MaDonHang`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chitietdonhang`
--

INSERT INTO `chitietdonhang` (`MaChiTiet`, `MaDonHang`, `MaSanPham`, `PhanLoai`, `SoLuong`, `Gia`) VALUES
(1, 1, 2, 'Mặc định', 1, 50000),
(2, 2, 4, 'Mặc định', 5, 40000),
(3, 4, 4, 'Mặc định', 1, 40000),
(4, 4, 6, 'Mặc định', 1, 100000),
(5, 4, 3, 'Mặc định', 1, 150000),
(6, 4, 55, 'Mặc định', 1, 250000),
(7, 4, 54, 'Mặc định', 1, 550000),
(9, 5, 4, 'Mặc định', 1, 40000),
(10, 5, 6, 'Mặc định', 1, 100000),
(11, 5, 3, 'Mặc định', 1, 150000),
(12, 5, 55, 'Mặc định', 1, 250000),
(13, 5, 54, 'Mặc định', 1, 550000),
(15, 7, 4, 'Mặc định', 1, 40000),
(16, 7, 6, 'Mặc định', 1, 100000),
(17, 7, 3, 'Mặc định', 1, 150000),
(18, 7, 55, 'Mặc định', 1, 250000),
(19, 7, 54, 'Mặc định', 1, 550000),
(20, 7, 25, 'MacDinh - 29', 4, 450000),
(21, 8, 8, 'Mặc định', 1, 29500000),
(22, 8, 55, 'Mặc định', 1, 250000),
(23, 8, 40, 'Mặc định', 1, 1250000),
(24, 9, 8, 'Mặc định', 1, 29500000),
(25, 9, 55, 'Mặc định', 1, 250000),
(26, 9, 40, 'Mặc định', 1, 1250000),
(27, 10, 4, 'Mặc định', 2, 40000),
(28, 11, 51, 'Mặc định', 1, 380000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `config`
--

INSERT INTO `config` (`id`, `key`, `value`) VALUES
(1, 'hotline', '1900xxxx'),
(2, 'address', '120 uyên lãng, Thủ Đức, TP.HCM'),
(3, 'banner_image', 'anhdau.jpg'),
(4, 'smtp_host', 'smtp.gmail.com'),
(5, 'smtp_port', '587'),
(6, 'smtp_secure', 'tls'),
(7, 'smtp_from_name', 'THPSHOP'),
(8, 'smtp_user', '24211tt1101@mail.tdc.edu.vn'),
(9, 'smtp_pass', '12345678910'),
(10, 'smtp_from_email', '24211tt1101@mail.tdc.edu.vn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `donhang`
--

DROP TABLE IF EXISTS `donhang`;
CREATE TABLE IF NOT EXISTS `donhang` (
  `MaDonHang` int NOT NULL AUTO_INCREMENT,
  `IdNguoiDung` int NOT NULL,
  `TenNguoiNhan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SoDienThoai` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `diachi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `TongTien` decimal(10,0) NOT NULL,
  `phuong_thuc_thanh_toan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COD',
  `trangThai` int NOT NULL DEFAULT '0' COMMENT '0: Đã đặt, 1: Đóng gói, 2: Đang giao, 3: Thành công',
  `NgayDat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lat_giao` double DEFAULT NULL,
  `lng_giao` double DEFAULT NULL,
  `thoi_gian_giao` datetime DEFAULT NULL,
  PRIMARY KEY (`MaDonHang`),
  KEY `fk_donhang_user` (`IdNguoiDung`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `donhang`
--

INSERT INTO `donhang` (`MaDonHang`, `IdNguoiDung`, `TenNguoiNhan`, `SoDienThoai`, `diachi`, `TongTien`, `phuong_thuc_thanh_toan`, `trangThai`, `NgayDat`, `lat_giao`, `lng_giao`, `thoi_gian_giao`) VALUES
(1, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 80000, 'COD', 3, '2026-03-19 00:33:00', NULL, NULL, NULL),
(2, 1, 'Ngô Bá Thắng', '0123456789', '120 uyên lãng', 230000, 'COD', 0, '2026-05-09 10:18:53', NULL, NULL, NULL),
(3, 1, 'Bá Thắng', '0123456789', '120 yên lãng ,hà nội', 4120000, 'COD', 0, '2026-05-30 02:54:19', NULL, NULL, NULL),
(4, 1, 'Trần Cao Trọng', '0123456789', '123.th.hcm', 2920000, 'COD', 0, '2026-06-05 18:31:08', NULL, NULL, NULL),
(5, 1, 'Trần Cao Trọng', '0123456789', '123.th.hcm', 2920000, 'COD', 0, '2026-06-05 18:31:08', NULL, NULL, NULL),
(6, 1, '', '', '', 2920000, 'COD', 0, '2026-06-06 02:10:20', NULL, NULL, NULL),
(7, 1, '', '', '', 2920000, 'COD', 3, '2026-06-06 02:40:06', NULL, NULL, NULL),
(8, 1, '', '', '', 31030000, 'COD', 2, '2026-06-06 03:07:57', NULL, NULL, '2026-06-06 09:54:51'),
(9, 1, '', '', '', 31030000, 'COD', 3, '2026-06-06 03:08:03', NULL, NULL, '2026-06-06 09:27:56'),
(10, 1, '', '', '', 110000, 'COD', 3, '2026-06-06 03:08:37', NULL, NULL, NULL),
(11, 1, '', '', '', 410000, 'COD', 3, '2026-06-06 03:11:52', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_mail_log`
--

DROP TABLE IF EXISTS `order_mail_log`;
CREATE TABLE IF NOT EXISTS `order_mail_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `MaDonHang` int NOT NULL,
  `mail_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_mail` (`MaDonHang`,`mail_type`),
  KEY `idx_type` (`mail_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_or_username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_email_or_username` (`email_or_username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `password_resets`
--

INSERT INTO `password_resets` (`id`, `email_or_username`, `token`, `otp_code`, `expires_at`, `created_at`) VALUES
(5, 'trancaotrong456@gmail.com', '6e46e82afc939e68', '452649', '2026-06-06 20:34:01', '2026-06-07 03:24:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `MaSanPham` int NOT NULL AUTO_INCREMENT,
  `TenSanPham` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `GiaSanPham` decimal(10,0) NOT NULL,
  `SoLuong` int NOT NULL DEFAULT '0',
  `hinh` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MoTa` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `MaDanhMuc` int NOT NULL,
  `SaoTrungBinh` float NOT NULL,
  `TongDanhGia` int NOT NULL,
  `hinh2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `hinh3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `mau3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`MaSanPham`),
  KEY `fk_sp_dm` (`MaDanhMuc`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `product`
--

INSERT INTO `product` (`MaSanPham`, `TenSanPham`, `GiaSanPham`, `SoLuong`, `hinh`, `MoTa`, `MaDanhMuc`, `SaoTrungBinh`, `TongDanhGia`, `hinh2`, `hinh3`, `mau1`, `mau2`, `mau3`, `size`) VALUES
(1, 'Quần Short Jeans Denim Rách', 100000, 0, 'quan.jpg', 'Quần Short Jeans Denim - Thiết Kế Rách Cá Tính - Chất Denim Co Giãn - Bền Màu & Bụi Bặm\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Quần Short Jeans nam nữ rách phong cách Streetwear\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Denim cotton 100% dày dặn, thấm hút mồ hôi tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size S: 40-50kg\r\n???? Size M: 50-60kg\r\n???? Size L: 60-70kg\r\n???? Size XL: 70-80kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Chi tiết rách (distressed) được làm thủ công, tạo vẻ ngoài bụi bặm và phá cách\r\n✅ Màu xanh Denim sáng trẻ trung, cực kỳ dễ phối cùng các loại giày Sneaker\r\n✅ Sợi vải được xử lý chống co rút, không bị biến dạng sau nhiều lần giặt\r\n✅ Gấu quần cắt lai tua rua tạo điểm nhấn thời trang cực chất cho mùa hè\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Nên giặt bằng nước lạnh trong vài lần đầu để giữ màu Indigo đặc trưng\r\n✅ Lộn trái sản phẩm khi giặt máy để bảo vệ các chi tiết rách\r\n✅ Không phơi trực tiếp dưới nắng gắt để tránh làm cứng vải\r\n\r\n???? Sản phẩm \"Must-have\" cho những chuyến du lịch và dạo phố! ????', 1, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Áo thun tay lỡ form rộng phong cách Hàn Quốc', 50000, 0, '1773689314_1_ao.jpg', 'Áo Thun Tay Lỡ Unisex - Form Rộng Oversize - Chất Cotton Co Giãn 4 Chiều - Phong Cách Hàn Quốc Trẻ Trung\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Áo thun tay lỡ form rộng nam nữ, áo phông Oversize phong cách Ulzzang\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Cotton 100% cao cấp, mềm mịn, thấm hút mồ hôi cực tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size M: 1m50 - 1m65; 45-55kg\r\n???? Size L: 1m60 - 1m75; 55-65kg\r\n???? Size XL: 1m70 - 1m80; 65-75kg\r\n???? Size XXL: 1m75 - 1m85; 75-85kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Thiết kế form rộng thoải mái, tay lỡ năng động, phù hợp cho cả nam và nữ (Unisex)\r\n✅ Chất vải cotton dày dặn, không xù lông, không nhão sau khi giặt\r\n✅ Kiểu dáng basic dễ phối đồ, có thể kết hợp cùng quần jean, kaki hay quần short đều cực đẹp\r\n✅ Đường may móc xích tỉ mỉ, cổ áo bo thun dày dặn không bị giãn\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Giặt tay hoặc giặt máy đều được, nên lộn trái áo khi giặt\r\n✅ Không đổ trực tiếp thuốc tẩy lên hình in hoặc bề mặt vải\r\n✅ Phơi ở nơi thoáng mát, tránh ánh nắng gắt để giữ màu áo bền lâu\r\n\r\n???? Mua ngay để sở hữu chiếc áo thun \"quốc dân\" chuẩn style Hàn Quốc này! ????', 2, 4.7, 3, '1773689314_2_aotrang.jpg', '1773689314_3_aoxanh.jpg', 'Đen', 'Trắng', 'Xanh', 'S,M,L,XL,XXL'),
(3, 'Giày Sneaker Retro Multi-Color', 150000, 0, 'giay.jpg', 'Giày Sneaker Nam Nữ Retro Chunky - Phối Màu Đa Sắc Ấn Tượng - Đế Cao Hack Dáng - Phong Cách Streetwear\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Giày Sneaker Chunky phối màu Retro (Cam/Xanh/Tím)\r\n???? Xuất xứ: Hàng nhập khẩu/Việt Nam\r\n???? Chất liệu: Da PU phối lưới thoáng khí, đế cao su đúc nguyên khối\r\n\r\n???? Bảng size: 36 - 37 - 38 - 39 - 40 - 41 - 42 - 43 - 44\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Thiết kế phối màu đa sắc cực chất, tạo điểm nhấn cá tính cho mọi outfit\r\n✅ Đế cao 4-5cm, giúp hack chiều cao một cách tự nhiên\r\n✅ Lớp lót trong êm ái, thoáng khí, không gây bí chân khi vận động cả ngày\r\n✅ Đế cao su có rãnh chống trượt, độ bền cao và bám dính tốt\r\n\r\n???? HƯỚNG DẪN BẢO QUẢN ????\r\n✅ Hạn chế tiếp xúc trực tiếp với nước trong thời gian dài\r\n✅ Vệ sinh bằng khăn ẩm hoặc dung dịch vệ sinh giày chuyên dụng\r\n✅ Tránh phơi trực tiếp dưới ánh nắng gắt\r\n\r\n???? Sắm ngay đôi Sneaker \"quốc dân\" để nâng tầm phong cách của bạn! ????', 3, 5, 1, '', '', '', '', '', '36,37,38,39,40,41,42,43,44'),
(4, 'Túi Đeo Chéo Nữ Họa Tiết Wonder', 40000, 0, 'phukien.jpg', 'Túi Xách Nữ Đeo Chéo Dây Xích - Họa Tiết Chữ Wonder Cá Tính - Da Cao Cấp - Sang Trọng & Năng Động\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Túi đeo chéo Wonder Box Bag\r\n???? Chất liệu: Da PU cao cấp chống thấm nước nhẹ\r\n⛓️ Phụ kiện: Dây xích kim loại mạ bạc sáng bóng\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Họa tiết chữ Wonder dập nổi hiện đại, mang đậm hơi thở thời trang đường phố\r\n✅ Form túi cứng cáp, chuẩn dáng, không bị mất form sau thời gian dài sử dụng\r\n✅ Ngăn chứa rộng rãi, thoải mái đựng điện thoại, ví tiền và mỹ phẩm\r\n✅ Dây xích linh hoạt, có thể đeo chéo hoặc đeo vai tùy ý\r\n\r\n???? HƯỚNG DẪN BẢO QUẢN ????\r\n✅ Dùng khăn mềm lau sạch khi bám bẩn\r\n✅ Tránh để túi ở nơi ẩm ướt hoặc nhiệt độ quá cao\r\n✅ Không dùng chất tẩy rửa mạnh để vệ sinh bề mặt da\r\n\r\n???? Phụ kiện không thể thiếu cho các nàng sành điệu - Chốt đơn ngay! ????', 4, 3, 2, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Quần Kaki Ống Đứng', 100000, 0, 'quan3.jpg', 'Quần Kaki Ống Đứng - Thiết kế Tối Giản Thời Thượng - Chất Vải Bền Đẹp - Form Suông Thanh Lịch\r\n\r\n✨ THÔNG TIN SẢN PHẨM ✨\r\n✅ Tên sản phẩm: Quần kaki ống đứng, màu xanh Olive basic phong cách Casual\r\n???? Xuất xứ: Việt Nam\r\n???? Chất liệu: Kaki Cotton cao cấp, giữ form tốt\r\n\r\n???? Bảng size tham khảo:\r\n???? Size M: 1m55 - 1m65; 45-55kg\r\n???? Size L: 1m60 - 1m70; 55-65kg\r\n???? Size XL: 1m65 - 1m75; 65-75kg\r\n???? Size XXL: 1m70 - 1m80; 75-85kg\r\n\r\n???? ĐẶC ĐIỂM NỔI BẬT ????\r\n✅ Quần kaki vải dày dặn, bề mặt vải mịn, không xù lông, ít nhăn\r\n✅ Thiết kế ống đứng che khuyết điểm, tạo cảm giác chân dài và thon gọn hơn\r\n✅ Màu xanh Olive dễ phối đồ, phù hợp đi học, đi làm hay đi chơi\r\n✅ Đường may tỉ mỉ, chắc chắn, túi quần sâu tiện lợi\r\n\r\n???? HƯỚNG DẪN SỬ DỤNG ????\r\n✅ Giặt lần đầu với nước lạnh để giữ màu vải\r\n✅ Lộn trái khi giặt và phơi để tránh phai màu trực tiếp dưới ánh nắng\r\n✅ Phù hợp cả giặt máy & giặt tay\r\n\r\n???? Mua ngay để sở hữu chiếc quần kaki thanh lịch cực HOT này! ????', 1, 5, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Apple iPhone 15 Pro Max 256GB', 29500000, 0, 'iphone15promax.jpg', 'Siêu phẩm điện thoại Apple mới nhất với khung Titan và chip A17 Pro siêu mạnh mẽ.', 101, 4.9, 2500, NULL, NULL, 'Titan tự nhiên', 'Trắng', 'Đen', ''),
(9, 'Samsung Galaxy S24 Ultra', 27990000, 0, 's24ultra.jpg', 'Điện thoại Android mạnh mẽ nhất với Galaxy AI, camera 200MP và bút S-Pen tiện dụng.', 101, 4.8, 1850, NULL, NULL, 'Xám Titanium', 'Đen', 'Tím', ''),
(10, 'Xiaomi 14 5G 12GB/256GB', 20490000, 0, 'xiaomi14.jpg', 'Smartphone nhỏ gọn, camera Leica chụp ảnh đỉnh cao, sạc siêu tốc 90W.', 101, 4.7, 850, NULL, NULL, 'Đen', 'Trắng', 'Xanh lá', ''),
(11, 'OPPO Reno11 Pro 5G', 16990000, 0, 'reno11pro.jpg', 'Chuyên gia chân dung, thiết kế mặt lưng lấp lánh sang trọng.', 101, 4.6, 620, NULL, NULL, 'Trắng ngọc trai', 'Xám', '', ''),
(12, 'Apple AirPods Pro 2 (Type-C)', 5890000, 0, 'airpodspro2.jpg', 'Tai nghe chống ồn chủ động xuất sắc, âm thanh không gian sống động.', 101, 4.9, 3200, NULL, NULL, 'Trắng', '', '', ''),
(13, 'Sạc dự phòng Anker 10000mAh', 750000, 0, 'anker10k.jpg', 'Sạc dự phòng nhỏ gọn, hỗ trợ sạc nhanh 20W PD an toàn cho thiết bị.', 101, 4.8, 410, NULL, NULL, 'Đen', 'Trắng', '', ''),
(14, 'Cáp sạc Baseus Type-C 100W', 120000, 0, 'baseus_cable.jpg', 'Cáp bọc dù siêu bền, tích hợp chip tự điều chỉnh dòng điện an toàn.', 101, 4.7, 890, NULL, NULL, 'Đen', 'Xanh', '', ''),
(15, 'Đồng hồ Apple Watch Series 9', 9500000, 0, 'aw_series9.jpg', 'Đồng hồ thông minh theo dõi sức khỏe chuyên sâu, màn hình siêu sáng.', 101, 4.9, 1100, NULL, NULL, 'Hồng', 'Đen', 'Bạc', '41mm, 45mm'),
(16, 'Apple MacBook Air M2 2022', 23490000, 0, 'macbook_air_m2.jpg', 'Chiếc laptop mỏng nhẹ quốc dân, pin trâu 18 tiếng, hiệu năng mượt mà.', 102, 4.9, 2100, NULL, NULL, 'Starlight', 'Midnight', 'Silver', ''),
(17, 'Laptop Gaming Acer Nitro 5', 18990000, 0, 'nitro5.jpg', 'Quái vật chiến game eSports, tản nhiệt mát mẻ, card rời RTX 3050.', 102, 4.7, 950, NULL, NULL, 'Đen', '', '', ''),
(18, 'Laptop Dell XPS 15 9530', 45990000, 0, 'dell_xps15.jpg', 'Laptop cao cấp dành cho doanh nhân và creator, màn hình OLED viền siêu mỏng.', 102, 4.8, 420, NULL, NULL, 'Bạc', '', '', ''),
(19, 'Laptop Asus Zenbook 14 OLED', 25990000, 0, 'zenbook14.jpg', 'Thiết kế thời trang, bản lề ErgoLift, màn hình OLED 2.8K siêu đẹp.', 102, 4.8, 670, NULL, NULL, 'Xanh đen', '', '', ''),
(20, 'Màn hình LG 24 inch 144Hz', 3500000, 0, 'lg_monitor.jpg', 'Màn hình chơi game tốc độ phản hồi 1ms, màu sắc chân thực.', 102, 4.6, 880, NULL, NULL, 'Đen', '', '', ''),
(21, 'Chuột không dây Logitech MX Master 3S', 2450000, 0, 'mxmaster3s.jpg', 'Chuột làm việc đỉnh cao, cuộn siêu tốc vô cực, click êm ái chống ồn.', 102, 4.9, 1540, NULL, NULL, 'Đen', 'Xám nhạt', '', ''),
(22, 'Bàn phím cơ AKKO 3098B', 1890000, 0, 'akko_keyboard.jpg', 'Bàn phím cơ không dây Bluetooth, keycap PBT đẹp mắt, switch êm ái.', 102, 4.7, 530, NULL, NULL, 'Xanh', 'Hồng', '', ''),
(23, 'Áo thun nam Basic Cotton 100%', 150000, 0, 'aothun_nam.jpg', 'Áo thun form regular fit, thấm hút mồ hôi tốt, cực kỳ thoáng mát.', 103, 4.8, 3500, NULL, NULL, 'Đen', 'Trắng', 'Xám', 'S, M, L, XL'),
(24, 'Áo Sơ mi nam dài tay Oxford', 350000, 0, 'somi_nam.jpg', 'Chất vải Oxford dày dặn, đứng form, chuẩn phong cách soái ca.', 103, 4.7, 1200, NULL, NULL, 'Trắng', 'Xanh nhạt', 'Đen', 'M, L, XL, XXL'),
(25, 'Quần Jean nam Slimfit cao cấp', 450000, 0, 'jean_nam.jpg', 'Quần jean co giãn nhẹ, ôm dáng vừa vặn, không phai màu khi giặt.', 103, 4.6, 980, NULL, NULL, 'Xanh dương', 'Xanh đen', 'Đen', '29, 30, 31, 32'),
(26, 'Áo khoác Bomber nam lót nỉ', 550000, 0, 'bomber_nam.jpg', 'Áo khoác giữ ấm tốt mùa đông, phong cách đường phố năng động.', 103, 4.8, 760, NULL, NULL, 'Đen', 'Rêu', '', 'M, L, XL'),
(27, 'Quần Short nam thể thao', 180000, 0, 'short_nam.jpg', 'Chất gió nhẹ nhàng, có khóa kéo túi tiện lợi khi đi tập gym, chạy bộ.', 103, 4.9, 2100, NULL, NULL, 'Đen', 'Xám', '', 'M, L, XL, XXL'),
(28, 'Áo Polo nam cổ bẻ thanh lịch', 290000, 0, 'polo_nam.jpg', 'Áo polo vải cá sấu cao cấp, không nhăn, mặc đi làm hay đi chơi đều đẹp.', 103, 4.7, 1450, NULL, NULL, 'Đen', 'Trắng', 'Đỏ đô', 'M, L, XL'),
(29, 'Bộ đồ thể thao nam nỉ da cá', 420000, 0, 'set_thethao_nam.jpg', 'Set quần áo thu đông, phong cách khỏe khoắn trẻ trung.', 103, 4.6, 520, NULL, NULL, 'Ghi xám', 'Đen', '', 'M, L, XL'),
(30, 'Váy hoa nhí dáng dài Vintage', 320000, 0, 'vayhoanhi.jpg', 'Chiếc váy nhẹ nhàng nữ tính, chất lụa mềm bay bổng, đi chơi đi biển đều hợp.', 104, 4.8, 1800, NULL, NULL, 'Đỏ', 'Xanh lam', 'Vàng', 'S, M, L'),
(31, 'Áo Croptop len tăm', 120000, 0, 'croptop_nu.jpg', 'Áo croptop ôm body tôn dáng, chất len tăm co giãn 4 chiều.', 104, 4.9, 4500, NULL, NULL, 'Trắng', 'Đen', 'Hồng', 'Freesize'),
(32, 'Quần ống rộng nữ vải tuyết mưa', 280000, 0, 'quanongrong.jpg', 'Hack dáng cực đỉnh, lưng cao che khuyết điểm vòng 2 hoàn hảo.', 104, 4.7, 3100, NULL, NULL, 'Đen', 'Be', 'Nâu', 'S, M, L, XL'),
(33, 'Chân váy chữ A xếp ly ngắn', 190000, 0, 'chanvay_chua.jpg', 'Chân váy lưng cao phong cách nữ sinh Hàn Quốc, có quần bảo hộ bên trong.', 104, 4.8, 2600, NULL, NULL, 'Đen', 'Trắng', 'Xám', 'S, M, L'),
(34, 'Áo Sơ mi kiểu nữ cổ bèo', 250000, 0, 'somi_nu.jpg', 'Thiết kế tiểu thư sang chảnh, chất voan tơ mềm mại.', 104, 4.6, 950, NULL, NULL, 'Trắng', 'Be', '', 'S, M, L'),
(35, 'Đầm dạ hội đính đá cao cấp', 850000, 0, 'damdahoi.jpg', 'Váy thiết kế xẻ tà quyến rũ, chất nhung sang trọng dự tiệc.', 104, 4.9, 340, NULL, NULL, 'Đen', 'Đỏ đô', '', 'S, M, L'),
(36, 'Áo khoác Cardigan len nữ', 220000, 0, 'cardigan_nu.jpg', 'Cardigan mỏng nhẹ cho mùa thu, họa tiết kẻ sọc trẻ trung.', 104, 4.7, 1120, NULL, NULL, 'Be', 'Trắng', 'Đen', 'Freesize'),
(37, 'Giày Sneaker Nike Air Force 1', 2800000, 0, 'af1.jpg', 'Đôi giày huyền thoại không bao giờ lỗi mốt, dễ phối mọi outfit.', 105, 4.9, 5600, NULL, NULL, 'Trắng All-White', '', '', '36, 37, 38, 39, 40, 41, 42, 43'),
(38, 'Giày chạy bộ Adidas Ultraboost', 3500000, 0, 'ultraboost.jpg', 'Đế boost siêu êm ái, trợ lực cực tốt cho những buổi chạy đường dài.', 105, 4.8, 2100, NULL, NULL, 'Đen', 'Trắng', 'Xám', '39, 40, 41, 42'),
(39, 'Giày Converse Chuck Taylor Cổ Cao', 1500000, 0, 'converse_high.jpg', 'Biểu tượng của tuổi trẻ, mang phong cách classic vượt thời gian.', 105, 4.8, 3400, NULL, NULL, 'Đen', 'Trắng', '', '36, 37, 38, 39, 40'),
(40, 'Giày Tây nam da bò thật', 1250000, 0, 'giaytay_nam.jpg', 'Giày Oxford phong cách lịch lãm, da bò nguyên tấm sang trọng cho dân công sở.', 105, 4.7, 850, NULL, NULL, 'Đen', 'Nâu sẫm', '', '39, 40, 41, 42, 43'),
(41, 'Giày cao gót nữ 7 phân mũi nhọn', 450000, 0, 'caogot_nu.jpg', 'Form chuẩn êm chân, tôn dáng thanh lịch cho phái nữ.', 105, 4.7, 1500, NULL, NULL, 'Đen', 'Nude', 'Đỏ', '35, 36, 37, 38, 39'),
(42, 'Sandal nam quai chéo dã ngoại', 320000, 0, 'sandal_nam.jpg', 'Sandal quai dù bền bỉ, đế cao su bám đường tốt, phù hợp đi mưa, đi phượt.', 105, 4.6, 920, NULL, NULL, 'Đen', 'Xanh rêu', '', '40, 41, 42, 43'),
(43, 'Dép bánh mì nữ siêu nhẹ', 150000, 0, 'depbanhmi.jpg', 'Dép đi trong nhà hoặc đi chơi cực dễ thương, chất EVA êm như giẫm trên mây.', 105, 4.9, 4800, NULL, NULL, 'Hồng', 'Vàng', 'Trắng', '36, 37, 38, 39'),
(44, 'Nồi chiên không dầu Philips 5L', 2450000, 0, 'noichien_philips.jpg', 'Công nghệ Rapid Air giảm 90% dầu mỡ, màn hình cảm ứng tiện lợi.', 106, 4.8, 1800, NULL, NULL, 'Đen', '', '', ''),
(45, 'Robot hút bụi lau nhà Xiaomi', 5990000, 0, 'robot_xiaomi.jpg', 'Lực hút 4000Pa siêu mạnh, điều hướng laser LDS thông minh, điều khiển qua app.', 106, 4.7, 950, NULL, NULL, 'Trắng', '', '', ''),
(46, 'Máy lọc không khí Sharp', 3200000, 0, 'maylockhongkhi.jpg', 'Lọc bụi mịn PM2.5, khử mùi diệt khuẩn bằng công nghệ Plasmacluster ion.', 106, 4.8, 1200, NULL, NULL, 'Trắng', '', '', ''),
(47, 'Bàn ủi hơi nước cầm tay Tefal', 850000, 0, 'banui_tefal.jpg', 'Khởi động nhanh trong 15s, ủi phẳng nếp nhăn dễ dàng không cần cầu ủi.', 106, 4.6, 2100, NULL, NULL, 'Xanh dương', '', '', ''),
(48, 'Máy xay sinh tố đa năng Sunhouse', 650000, 0, 'mayxay_sunhouse.jpg', 'Cối thủy tinh siêu bền, 3 tốc độ xay nhuyễn đá viên dễ dàng.', 106, 4.5, 870, NULL, NULL, 'Đỏ', 'Trắng', '', ''),
(49, 'Nồi cơm điện tử Toshiba 1.8L', 1950000, 0, 'noicom_toshiba.jpg', 'Công nghệ nấu 3D lòng nồi niêu chống dính, cơm chín đều thơm ngon.', 106, 4.9, 3100, NULL, NULL, 'Bạc', '', '', ''),
(50, 'Quạt đứng thông minh Xiaomi', 1850000, 0, 'quat_xiaomi.jpg', 'Quạt DC tiết kiệm điện, giả lập gió tự nhiên siêu êm, có pin tích hợp.', 106, 4.8, 1400, NULL, NULL, 'Trắng', '', '', ''),
(51, 'Sữa rửa mặt Cerave Foaming', 380000, 0, 'cerave.jpg', 'Sữa rửa mặt quốc dân cho da dầu mụn, làm sạch sâu không gây khô da (236ml).', 107, 4.9, 8500, NULL, NULL, 'Xanh ngọc', '', '', ''),
(52, 'Kem chống nắng La Roche-Posay', 450000, 0, 'larocheposay.jpg', 'Chống nắng phổ rộng SPF50+, kiềm dầu cực đỉnh cho mùa hè.', 107, 4.8, 6200, NULL, NULL, 'Cam', '', '', ''),
(53, 'Nước tẩy trang Bioderma', 495000, 0, 'bioderma.jpg', 'Tẩy trang dịu nhẹ, sạch sâu lớp makeup, an toàn cho cả da nhạy cảm (500ml).', 107, 4.9, 9400, NULL, NULL, 'Hồng', 'Xanh', '', ''),
(54, 'Son thỏi MAC Matte Lipstick', 550000, 0, 'son_mac.jpg', 'Chất son lì mịn màng, lên màu chuẩn, không làm khô môi.', 107, 4.8, 3800, NULL, NULL, 'Ruby Woo', 'Chili', 'Devoted To Chili', ''),
(55, 'Serum Vitamin C Garnier', 250000, 0, 'garnier_vitc.jpg', 'Làm sáng da, mờ thâm mụn hiệu quả chỉ sau 14 ngày sử dụng.', 107, 4.7, 5100, NULL, NULL, 'Vàng', '', '', ''),
(56, 'Kem dưỡng ẩm Neutrogena', 350000, 0, 'neutrogena.jpg', 'Cấp nước dạng gel siêu mỏng nhẹ, thấm ngay lập tức không gây bết dính.', 107, 4.8, 4300, NULL, NULL, 'Xanh dương', '', '', ''),
(57, 'Nước hoa nam Dior Sauvage EDP', 3200000, 0, 'dior_sauvage.jpg', 'Hương thơm nam tính, mạnh mẽ và cuốn hút, lưu hương trên 8 tiếng.', 107, 4.9, 1200, NULL, NULL, 'Xanh đen', '', '', '100ml');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review`
--

DROP TABLE IF EXISTS `review`;
CREATE TABLE IF NOT EXISTS `review` (
  `id` int NOT NULL AUTO_INCREMENT,
  `MaSanPham` int NOT NULL,
  `IdNguoiDung` int NOT NULL,
  `NoiDung` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `SoSao` int NOT NULL,
  `NgayBinhLuan` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_rv_sp` (`MaSanPham`),
  KEY `fk_rv_nd` (`IdNguoiDung`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `review`
--

INSERT INTO `review` (`id`, `MaSanPham`, `IdNguoiDung`, `NoiDung`, `SoSao`, `NgayBinhLuan`) VALUES
(1, 2, 6, 'quá đẹp', 5, '2026-03-12 15:20:15'),
(2, 2, 1, 'đẹp', 5, '2026-03-16 20:33:11'),
(3, 3, 1, 'đẹp điên luôn :))', 5, '2026-03-16 20:40:24'),
(4, 6, 1, 'đẹp', 5, '2026-03-17 06:03:11'),
(5, 2, 8, 'cũng cũng :v', 4, '2026-03-17 19:48:10'),
(6, 4, 9, 'đẹp', 5, '2026-05-09 03:22:28'),
(7, 4, 9, 'xấu', 1, '2026-05-09 03:22:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `IdNguoiDung` int NOT NULL AUTO_INCREMENT,
  `TenNguoiDung` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `email` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `matkhau` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `quyen` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'user',
  `diachi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `SoDienThoai` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `GioiTinh` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'male',
  `NgaySinh` date DEFAULT NULL,
  `AnhDaiDien` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '''default.png''',
  `trang_thai` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`IdNguoiDung`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Đang đổ dữ liệu cho bảng `user`
--

INSERT INTO `user` (`IdNguoiDung`, `TenNguoiDung`, `email`, `matkhau`, `quyen`, `diachi`, `SoDienThoai`, `GioiTinh`, `NgaySinh`, `AnhDaiDien`, `trang_thai`) VALUES
(1, 'Admin Trọng', 'trancaotrong456@gmail.com', '12345678910', 'admin', '120 uyên lãng', '0123456789', 'male', '2006-07-06', '\'default.png\'', 0),
(2, 'nguyễn văn b', 'nguyenvanb@gmail.com', '123', 'user', '123 Thủ đức', '', 'male', NULL, '\'default.png\'', 0),
(3, 'ngô bá thắng', 'ngobathang@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 0),
(4, 'độ mixi', 'mixi@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 0),
(5, 'Trần Cao Trọng', 'trancaotrong@gmail.com', '123', 'user', 'chưa cập nhật', '', 'male', NULL, '\'default.png\'', 0),
(6, 'ngobathang', 'trong@mail.com', '123', 'user', 'đường 123- thủ đức,tp.hcm', '', 'male', NULL, '\'default.png\'', 0),
(7, 'Huynhabc', 'abc@abc', '123', 'user', 'đường 123- thủ đức,tp.hcm', '', 'male', NULL, '\'default.png\'', 0),
(8, 'Trần Cao Trọng', 'trancaotrong040506@gmail.com', '040506', 'user', 'đường ABC- thủ đức,tp.hcm', '0123456789', 'male', '2006-05-04', '1773776859_avatar_8.png', 0),
(9, 'Nguyễn Hữu Phong', 'phong@gmail.com', '123456789', 'user', 'đường 123- thủ đức,tp.hcm', '0123456789', 'male', '2018-03-09', '1778296898_avatar_9.jpeg', 0),
(10, 'Trong Tran Cao', '24211tt1101@mail.tdc.edu.vn', '123456789', 'user', 'đường 123- thủ đức,tp.hcm', '0915780867', 'male', '2006-05-04', '\'default.png\'', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id_yeuthich` int NOT NULL AUTO_INCREMENT,
  `IdNguoiDung` int NOT NULL,
  `MaSanPham` int NOT NULL,
  `NgayThem` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_yeuthich`),
  UNIQUE KEY `IdNguoiDung` (`IdNguoiDung`,`MaSanPham`),
  KEY `fk_wishlist_products` (`MaSanPham`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wishlist`
--

INSERT INTO `wishlist` (`id_yeuthich`, `IdNguoiDung`, `MaSanPham`, `NgayThem`) VALUES
(8, 8, 2, '2026-03-16 16:41:46'),
(16, 1, 2, '2026-03-17 06:02:43'),
(17, 1, 6, '2026-03-17 06:03:14'),
(18, 1, 3, '2026-05-09 02:20:11'),
(19, 9, 4, '2026-05-09 03:21:54'),
(25, 1, 57, '2026-05-30 01:56:11'),
(27, 1, 37, '2026-06-05 18:20:07'),
(30, 1, 52, '2026-06-06 00:39:09');

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chitietdonhang`
--
ALTER TABLE `chitietdonhang`
  ADD CONSTRAINT `fk_ctdh_donhang` FOREIGN KEY (`MaDonHang`) REFERENCES `donhang` (`MaDonHang`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_ctdh_sanpham` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `donhang`
--
ALTER TABLE `donhang`
  ADD CONSTRAINT `fk_donhang_user` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_sp_dm` FOREIGN KEY (`MaDanhMuc`) REFERENCES `categories` (`MaDanhMuc`);

--
-- Các ràng buộc cho bảng `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_rv_nd` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_rv_sp` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE CASCADE ON UPDATE RESTRICT;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `fk_wishlist_products` FOREIGN KEY (`MaSanPham`) REFERENCES `product` (`MaSanPham`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`IdNguoiDung`) REFERENCES `user` (`IdNguoiDung`) ON DELETE CASCADE ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
