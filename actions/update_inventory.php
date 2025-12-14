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
    $item_id = intval($_POST['edit_item_id'] ?? 0);
    $item_name = trim($_POST['edit_item_name'] ?? '');
    $total_stock = intval($_POST['edit_total_stock'] ?? 0);
    $used_count = intval($_POST['edit_used_count'] ?? 0);

    if (empty($item_name) || $total_stock < 0 || $used_count < 0 || $item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }

    // Update inventory item
    $stmt = $conn->prepare("UPDATE vms_inventory SET item_name = ?, total_stock = ?, used_count = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("siii", $item_name, $total_stock, $used_count, $item_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update inventory item: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>