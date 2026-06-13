<?php
require 'database.php';
$db = new Database();
$res = $db->select('SHOW TABLES');
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
?>
