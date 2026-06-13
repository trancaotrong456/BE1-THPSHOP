<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/components/site_footer.php';
if (!empty($_SESSION['user_id']) && file_exists(__DIR__ . '/chatbox.php')) {
    include __DIR__ . '/chatbox.php';
}
?>
</body>
</html>
