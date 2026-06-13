<?php
/**
 * components/layout.php — Layout master dùng chung cho tất cả trang frontend
 *
 * Biến có thể set trước khi include:
 *   $page_title  — Tiêu đề trang (mặc định: 'THP ONLINE STORE')
 *   $extra_head  — Chuỗi HTML bổ sung vào <head> (CSS riêng của từng trang)
 *   $extra_body_class — Class bổ sung cho <body>
 */
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'THP ONLINE STORE'; ?></title>
    <link rel="icon" href="public/images/web_be1.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/common.css">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body<?php echo !empty($extra_body_class) ? ' class="' . $extra_body_class . '"' : ''; ?>>
    <?php include __DIR__ . '/site_header.php'; ?>
