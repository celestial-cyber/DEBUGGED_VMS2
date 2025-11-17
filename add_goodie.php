<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include('connection.php');
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id)) {
    header("Location: index.php");
    exit();
}

// Initialize popup message
$popup_message = '';
$popup_type = '';

// Handle Add Goodie
if(isset($_POST['sbt-goodie'])) {
    $visitor_id = intval($_POST['visitor_id']);
    $goodie_name = mysqli_real_escape_string($conn, $_POST['goodie_name']);
    $quantity = intval($_POST['quantity']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    if (empty($goodie_name) || $quantity <= 0) {
        $popup_message = "Invalid input data";
        $popup_type = "danger";
    } else {
        $sql = "INSERT INTO tbl_goodies_distribution (visitor_id, goodie_name, quantity, remarks, distribution_time)
                VALUES ($visitor_id, '$goodie_name', $quantity, '$remarks', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $popup_message = "Goodie distribution recorded successfully!";
            $popup_type = "success";
        } else {
            $popup_message = "Error recording goodie distribution: " . mysqli_error($conn);
            $popup_type = "danger";
        }
    }
}

// Fetch visitors for dropdown
$visitors = mysqli_query($conn, "SELECT id, name FROM tbl_visitors ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>VMS - Add Goodie</title>
  <?php include('include/header.php'); ?>
</head>
<body id="page-top">
  <?php include('include/top-bar.php'); ?>
  <div id="wrapper">
    <?php include('include/side-bar.php'); ?>
    
    <!-- Content -->
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="title-row">
          <span class="chip"><i class="fa-solid fa-gift text-primary"></i> Goodies</span>
          <h2>🎁 Add Goodie Distribution</h2>
          <span class="badge">Live Form</span>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary" onclick="location.href='manage-goodies.php'"><i class="fa-solid fa-truck me-2"></i>View All</button>
          <button class="btn btn-outline-secondary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Reset</button>
        </div>
      </div>

      <!-- Goodie Form -->
      <div class="card-lite">
        <div class="card-head">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-gift text-primary"></i>
            <span class="fw-semibold">Goodie Distribution Details</span>
          </div>
        </div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Visitor <span class="text-danger">*</span></label>
              <select name="visitor_id" class="form-control" required>
                <option value="">Select Visitor</option>
                <?php while($visitor = mysqli_fetch_assoc($visitors)): ?>
                <option value="<?php echo $visitor['id']; ?>"><?php echo $visitor['name']; ?></option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Goodie Name <span class="text-danger">*</span></label>
              <input type="text" name="goodie_name" class="form-control" placeholder="Enter Goodie Name" required>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Quantity <span class="text-danger">*</span></label>
              <input type="number" name="quantity" class="form-control" placeholder="Enter Quantity" min="1" required>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" class="form-control" placeholder="Enter Remarks (optional)" rows="2"></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex gap-2">
                <button type="submit" name="sbt-goodie" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Distribution</button>
                <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-eraser me-2"></i>Clear Form</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Popup Modal -->
  <div class="modal fade" id="goodiePopup" tabindex="-1" aria-labelledby="goodiePopupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
          <h5 class="modal-title" id="goodiePopupLabel"><?php echo ucfirst($popup_type ?: 'Info'); ?></h5>
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

  <?php include('include/footer.php'); ?>

  <?php if($popup_message): ?>
  <script>
    var popupModal = new bootstrap.Modal(document.getElementById('goodiePopup'));
    popupModal.show();
  </script>
  <?php endif; ?>
</body>
</html>