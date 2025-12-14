<?php
session_start();
if (empty($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'member'])) {
    header("Location: index.php");
    exit();
}
?>
