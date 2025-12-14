<?php
session_start();
include '../config/connection.php';

if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header("Location: manage-inventory.php");
    exit();
}

// Fetch the inventory item
$stmt = $conn->prepare("SELECT * FROM vms_inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$inventory = $result->fetch_assoc();

if (!$inventory) {
    header("Location: manage-inventory.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $item_name = htmlspecialchars($_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    
    $update_stmt = $conn->prepare("UPDATE vms_inventory SET item_name = ?, total_stock = ? WHERE id = ?");
    $update_stmt->bind_param("sii", $item_name, $quantity, $id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = "Inventory item updated successfully!";
        header("Location: manage-inventory.php");
        exit();
    } else {
        $error_msg = "Error updating inventory: " . $conn->error;
    }
}

include __DIR__ . '/../includes/header.php';
// Top bar is already included in header.php, so we don't need top-bar.php
?>

<!-- Content -->
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="text-primary">📦 Edit Inventory Item</h2>
        </div>
        <a href="manage-inventory.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Inventory
        </a>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="card">
        <div class="card-body">
            <form method="post" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="item_name" name="item_name" 
                                   value="<?php echo htmlspecialchars($inventory['item_name']); ?>" required>
                            <label for="item_name">Item Name</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="<?php echo (int)$inventory['total_stock']; ?>" required min="0">
                            <label for="quantity">Total Stock</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" name="update_inventory" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Inventory
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>