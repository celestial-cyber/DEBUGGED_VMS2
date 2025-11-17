<?php
session_start();
include('connection.php');

if(empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="inventory_export_'.date('Y-m-d').'.csv"');

// Create CSV header
$output = fopen('php://output', 'w');
fputcsv($output, array('Item Name', 'Total Stock', 'Used Count', 'Status', 'Description', 'Last Updated'));

// Build the query based on filters
$sql = "SELECT * FROM tbl_inventory WHERE 1=1";

// Apply filters if they exist in the URL
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    $sql .= " AND status = '$status'";
}

if (isset($_GET['item_name']) && !empty($_GET['item_name'])) {
    $item_name = mysqli_real_escape_string($conn, $_GET['item_name']);
    $sql .= " AND item_name LIKE '%$item_name%'";
}

if (isset($_GET['stock_level'])) {
    $stock_level = $_GET['stock_level'];
    if ($stock_level == 'low') {
        $sql .= " AND total_stock < 10";
    } elseif ($stock_level == 'medium') {
        $sql .= " AND total_stock BETWEEN 10 AND 50";
    } elseif ($stock_level == 'high') {
        $sql .= " AND total_stock > 50";
    }
}

$sql .= " ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, array(
        $row['item_name'],
        $row['total_stock'],
        $row['used_count'],
        $row['status'],
        $row['description'],
        date('Y-m-d H:i:s', strtotime($row['updated_at']))
    ));
}

// Close the file handle
fclose($output);
exit();