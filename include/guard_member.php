<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['id']) || ($_SESSION['role'] ?? 'member') !== 'member') {
    header('Location: index.php');
    exit();
}
?>






