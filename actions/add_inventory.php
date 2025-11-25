<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../config/connection.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id)) {
    header("Location: index.php");
    exit();
}

// Initialize popup message
$popup_message = '';
$popup_type = '';

// Handle Add Inventory
if(isset($_POST['sbt-inv'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $total_stock = intval($_POST['total_stock'] ?? 0);
    
    if (empty($item_name) || $total_stock < 0) {
        $popup_message = "Invalid input data. Item name is required and stock must be 0 or greater.";
        $popup_type = "danger";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO vms_inventory (item_name, total_stock, used_count, status, created_at) VALUES (?, ?, 0, 'Available', NOW())");
        if ($stmt) {
            $stmt->bind_param("si", $item_name, $total_stock);
            if ($stmt->execute()) {
                $popup_message = "Inventory item added successfully!";
                $popup_type = "success";
            } else {
                $popup_message = "Error adding inventory item: " . $stmt->error;
                $popup_type = "danger";
            }
            $stmt->close();
        } else {
            $popup_message = "Database error: " . $conn->error;
            $popup_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>VMS - Add Inventory</title>
  <?php include __DIR__ . '/../includes/header.php'; ?>
</head>
<body id="page-top">
  <?php
  $breadcrumbs = [
      ['url' => 'admin/dashboard.php', 'text' => 'Dashboard'],
      ['url' => 'manage-inventory.php', 'text' => 'Inventory'],
      ['text' => 'Add Inventory Item']
  ];
  include __DIR__ . '/../includes/top-bar.php';
  ?>
  <div id="wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Content -->
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="title-row">
          <span class="chip"><i class="fa-solid fa-boxes text-primary"></i> Inventory</span>
          <h2>📦 Add Inventory Item</h2>
          <span class="badge">Live Form</span>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary" onclick="location.href='manage-inventory.php'"><i class="fa-solid fa-warehouse me-2"></i>View All</button>
          <button class="btn btn-outline-secondary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Reset</button>
        </div>
      </div>

      <!-- Inventory Form -->
      <div class="card-lite">
        <div class="card-head">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-box text-primary"></i>
            <span class="fw-semibold">Inventory Details</span>
          </div>
        </div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Item Name <span class="text-danger">*</span></label>
              <input type="text" name="item_name" class="form-control" placeholder="Enter Item Name" required>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Total Stock <span class="text-danger">*</span></label>
              <input type="number" name="total_stock" class="form-control" placeholder="Enter Stock Quantity" min="0" required>
            </div>

            <div class="col-12">
              <div class="d-flex gap-2">
                <button type="submit" name="sbt-inv" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Item</button>
                <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-eraser me-2"></i>Clear Form</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Popup Modal -->
  <div class="modal fade" id="inventoryPopup" tabindex="-1" aria-labelledby="inventoryPopupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
          <h5 class="modal-title" id="inventoryPopupLabel"><?php echo ucfirst($popup_type ?: 'Info'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php echo $popup_message; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-<?php echo $popup_type ?: 'primary'; ?>" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <?php if($popup_message): ?>
  <script>
    var popupModal = new bootstrap.Modal(document.getElementById('inventoryPopup'));
    popupModal.show();
  </script>
  <?php endif; ?>
</body>
</html>