<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include __DIR__ . '/../config/connection.php';
include __DIR__ . '/../includes/guard_admin.php';

if (empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit();
}

// Log each item removal then delete
$uid = (int)($_SESSION['id'] ?? 0);
$items = mysqli_query($conn, "SELECT id,item_name,total_stock,used_count FROM vms_inventory");
while ($it = mysqli_fetch_assoc($items)) {
  $iname = mysqli_real_escape_string($conn, $it['item_name']);
  $remaining = (int)$it['total_stock'];
  if ($remaining !== 0) {
    mysqli_query($conn, "INSERT INTO vms_inventory_log (item_id,item_name,delta,user_id,action) VALUES (".(int)$it['id'].",'$iname',-".$remaining.",$uid,'CLEAR_ALL')");
  }
}
mysqli_query($conn, "TRUNCATE TABLE vms_inventory");

header('Location: ../admin/manage-inventory.php');
exit;
?>


