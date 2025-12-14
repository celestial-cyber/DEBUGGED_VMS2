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
    $item_id = intval($_POST['item_id'] ?? 0);

    if ($item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit();
    }

    // Delete inventory item
    $stmt = $conn->prepare("DELETE FROM vms_inventory WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete inventory item: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>