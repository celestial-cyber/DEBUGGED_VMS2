<?php
session_start();
include('connection.php');
include 'include/guard_member.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if (empty($id)) {
    header("Location: index.php");
    exit();
}

// Initialize popup message
$popup_message = '';
$popup_type = '';
$redirect_page = 'member_dashboard.php';

if (isset($_POST['sbt-vstr'])) {
    // Safely get POST variables
    $fullname    = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, $_POST['full_name']) : '';
    $email       = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $phone       = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
    $gender      = isset($_POST['gender']) ? mysqli_real_escape_string($conn, $_POST['gender']) : '';
    $department  = isset($_POST['department']) ? mysqli_real_escape_string($conn, $_POST['department']) : '';
    $roll_number = isset($_POST['roll_number']) ? mysqli_real_escape_string($conn, $_POST['roll_number']) : '';

    $event_id    = isset($_POST['event_id']) ? mysqli_real_escape_string($conn, $_POST['event_id']) : '';
    $year        = isset($_POST['year_of_graduation']) ? mysqli_real_escape_string($conn, $_POST['year_of_graduation']) : '';
    $address     = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';

    // Redirect logic
    $redirect_page = 'member_dashboard.php';

    // Detect phone/mobile column
    $hasMobile = false;
    $probe = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_visitors' AND COLUMN_NAME='mobile' LIMIT 1");
    if ($probe && mysqli_num_rows($probe) > 0) { $hasMobile = true; }
    $phoneCol = $hasMobile ? 'mobile' : 'phone';

    $insert_visitor = mysqli_query($conn, "
        INSERT INTO tbl_visitors (event_id, full_name, email, $phoneCol, address, department, gender, roll_number,year_of_graduation, in_time)
VALUES ('$event_id', '$fullname', '$email', '$phone', '$address', '$department', '$gender', '$roll_number' ,'$year', NOW())

    ");

    if ($insert_visitor) {
        $popup_message = "Visitor registered successfully!";
        $popup_type = "success";
    } else {
        $popup_message = "Error registering visitor!";
        $popup_type = "danger";
    }
}
?>

<?php include('include/header.php'); ?>

<style>
/* --- Improved modal form spacing and clarity --- */
.modal-body label {
  font-weight: 500;
  color: #333;
}
.modal-body .form-control,
.modal-body .form-select {
  border-radius: 8px;
  padding: 8px 10px;
}
.modal-body textarea {
  resize: vertical;
}
</style>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-solid fa-user-plus text-primary"></i> Add Visitor</span>
      <h2>➕ Visitor Registration</h2>
      <span class="badge">Member</span>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="member_manage_visitors.php">
        <i class="fa-solid fa-users me-2"></i>Manage Visitors
      </a>
    </div>
  </div>

  <div class="d-flex justify-content-end mb-2">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitorModal">
      <i class="fa-solid fa-user-plus me-1"></i>Add Visitor
    </button>
  </div>

  <!-- Modal: Add Visitor -->
  <div class="modal fade" id="addVisitorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Visitor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <form action="" method="POST">
            <input type="hidden" name="form_source" value="<?php echo isset($form_source) ? $form_source : 'admin'; ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required placeholder="e.g., John Smith">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com">
              </div>

              <div class="col-md-6">
                <label class="form-label">Mobile Number</label>
                <input type="text" name="phone" class="form-control" required placeholder="e.g., 9876543210">
              </div>
              <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select" required>
                  <option value="">Select</option>
                  <option>Male</option>
                  <option>Female</option>
                  <option>Other</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Event</label>
                <select name="event_id" class="form-select" required>
                  <option value="">Select Event</option>
                  <?php
                  $select_events = mysqli_query($conn, "SELECT * FROM tbl_events ORDER BY event_name ASC");
                  while ($event = mysqli_fetch_assoc($select_events)) {
                      echo "<option value='".$event['event_id']."'>".$event['event_name']."</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <select name="department" class="form-select" required>
                  <option value="">Select Department</option>
                  <?php
                  $select_department = mysqli_query($conn, "SELECT * FROM tbl_department WHERE status=1 ORDER BY department ASC");
                  while ($dept = mysqli_fetch_assoc($select_department)) {
                      echo "<option value='".$dept['department']."'>".$dept['department']."</option>";
                  }
                  ?>
                </select>
              </div>
              

              <div class="col-md-6">
              <label class="form-label">Roll Number</label>
              <input type="text" name="roll_number" class="form-control" placeholder="e.g., 20CSE1234" required>
              </div>


              <div class="col-md-6">
                <label class="form-label">Year of Graduation</label>
                <select name="year_of_graduation" class="form-select" required>
                  <option value="">Select Year</option>
                  <?php for ($y = 2007; $y <= date("Y"); $y++) { echo "<option value='$y'>$y</option>"; } ?>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" required placeholder="House No, Street, City"></textarea>
              </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
              <button type="submit" name="sbt-vstr" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i>Submit
              </button>
              <a href="member_manage_visitors.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-users me-1"></i>View Visitors
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Visitors -->
  <div class="card-lite mt-3">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-right-left text-primary"></i>
        <span class="fw-semibold">Recent Visitors</span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Name</th><th>RollNo.</th><th>Department</th><th>Year</th><th>In</th><th>Out</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $recent = mysqli_query($conn, "SELECT * FROM tbl_visitors ORDER BY created_at DESC LIMIT 10");
            while ($v = mysqli_fetch_assoc($recent)) { ?>
              <tr>
                <td><?php echo htmlspecialchars($v['name']); ?></td>
                <td><?php echo htmlspecialchars($v['roll_number'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($v['department']); ?></td>
                <td><?php echo htmlspecialchars($v['year_of_graduation'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($v['in_time'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($v['out_time'] ?: '—'); ?></td>
                <td>
                  <span class="badge <?php echo $v['out_time'] ? 'text-bg-success' : ($v['in_time'] ? 'text-bg-primary' : 'text-bg-secondary'); ?>">
                    <?php echo $v['out_time'] ? 'Checked Out' : ($v['in_time'] ? 'Checked In' : 'New'); ?>
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <?php if (!$v['in_time']) { ?>
                      <a class="btn btn-outline-primary" href="visitor_status.php?action=checkin&id=<?php echo (int)$v['id']; ?>&from=member_new-visitor.php"><i class="fa-solid fa-door-open"></i></a>
                    <?php } elseif (!$v['out_time']) { ?>
                      <a class="btn btn-outline-success" href="visitor_status.php?action=checkout&id=<?php echo (int)$v['id']; ?>&from=member_new-visitor.php"><i class="fa-solid fa-door-closed"></i></a>
                    <?php } else { ?>
                      <button class="btn btn-outline-secondary" disabled><i class="fa-regular fa-circle-check"></i></button>
                    <?php } ?>
                  </div>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Popup Modal -->
<div class="modal fade" id="visitorPopup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
        <h5 class="modal-title"><?php echo ucfirst($popup_type ?: 'Info'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><?php echo $popup_message; ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-<?php echo $popup_type ?: 'primary'; ?>" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<?php include('include/footer.php'); ?>

<?php if($popup_message): ?>
<script>
var popupModal = new bootstrap.Modal(document.getElementById('visitorPopup'));
popupModal.show();
setTimeout(function(){
    window.location.href = "<?php echo $redirect_page; ?>";
}, 2500);
</script>
<?php endif; ?>
</body>
</html>
