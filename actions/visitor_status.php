<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include __DIR__ . '/../config/connection.php';
include __DIR__ . '/../includes/guard_member.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';
$from = $_GET['from'] ?? 'member/member_manage_visitors.php';
if ($id <= 0 || !in_array($action, ['checkin','checkout'])) {
  header('Location: ' . $from);
  exit;
}

if ($action === 'checkin') {
  mysqli_query($conn, "UPDATE vms_visitors SET in_time=IFNULL(in_time, NOW()), out_time=NULL, status=1 WHERE id=$id");
} else if ($action === 'checkout') {
  mysqli_query($conn, "UPDATE vms_visitors SET out_time=NOW(), status=0 WHERE id=$id AND in_time IS NOT NULL");
}

$redirect = $from;
if (strpos($redirect, '../') !== 0 && strpos($redirect, '/') !== 0 && stripos($redirect, 'http') !== 0) {
    $redirect = '../member/' . ltrim($redirect, '/');
}

header('Location: ' . $redirect);
exit;
?>






