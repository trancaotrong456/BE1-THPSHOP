<?php
require 'database.php';
$db = new Database();
try {
    $db->execute("ALTER TABLE donhang ADD COLUMN discount_code VARCHAR(50) NULL AFTER TongTien");
    echo "Added discount_code.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }

try {
    $db->execute("ALTER TABLE donhang ADD COLUMN discount_amount INT NOT NULL DEFAULT 0 AFTER discount_code");
    echo "Added discount_amount.\n";
} catch (Exception $e) { echo $e->getMessage() . "\n"; }
?>
