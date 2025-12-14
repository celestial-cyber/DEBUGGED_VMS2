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
    $item_name = trim($_POST['item_name'] ?? '');
    $total_stock = intval($_POST['total_stock'] ?? 0);

    if (empty($item_name) || $total_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data. Item name is required and stock must be 0 or greater.']);
        exit();
    }

    // Check if item already exists
    $check_stmt = $conn->prepare("SELECT id FROM vms_inventory WHERE item_name = ?");
    $check_stmt->bind_param("s", $item_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An item with this name already exists.']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Insert new inventory item
    $stmt = $conn->prepare("INSERT INTO vms_inventory (item_name, total_stock, used_count, status, created_at) VALUES (?, ?, 0, 'Available', NOW())");
    if ($stmt) {
        $stmt->bind_param("si", $item_name, $total_stock);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inventory item added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add inventory item: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>