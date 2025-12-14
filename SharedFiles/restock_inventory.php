<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'connection.php';
include 'include/guard_admin.php';

$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
if ($itemId <= 0 || $amount === 0) { header('Location: manage-inventory.php'); exit; }

$itemRes = mysqli_query($conn, "SELECT * FROM vms_inventory WHERE id=$itemId LIMIT 1");
$item = mysqli_fetch_assoc($itemRes);
if (!$item) { header('Location: manage-inventory.php'); exit; }

// Update stock
$newTotal = max(0, (int)$item['total_stock'] + $amount);
$status = $newTotal > 0 ? 'Available' : 'Out of Stock';
mysqli_query($conn, "UPDATE vms_inventory SET total_stock=$newTotal, status='".mysqli_real_escape_string($conn,$status)."' WHERE id=$itemId");

// Log change
$uid = (int)($_SESSION['id'] ?? 0);
$iname = mysqli_real_escape_string($conn, $item['item_name']);
mysqli_query($conn, "INSERT INTO vms_inventory_log (item_id,item_name,delta,user_id,action) VALUES ($itemId,'$iname',$amount,$uid,'RESTOCK')");

header('Location: manage-inventory.php');
exit;
?>






