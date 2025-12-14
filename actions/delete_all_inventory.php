<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete all inventory items
    $stmt = $conn->prepare("DELETE FROM vms_inventory");
    if ($stmt) {
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'All inventory items deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete inventory items: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>