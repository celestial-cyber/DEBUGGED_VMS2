<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ('connection.php');

// session values (don't overwrite variable names used for visitor id)
$session_name = $_SESSION['name'] ?? '';
$session_user_id = $_SESSION['id'] ?? null;

if (empty($session_user_id)) {
    header("Location: index.php");
    exit();
}

// Security: Validate and sanitize visitor id from GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid visitor id");
}
$visitor_id = (int) $_GET['id'];

// Try to fetch visitor using prepared statement. If get_result() is unavailable
// or fails, fall back to a safe mysqli_query.
$row = null;
$stmt = $conn->prepare("SELECT * FROM vms_visitors WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $visitor_id);
    $stmt->execute();
    $res = null;
    // get_result may not be available on some builds — guard against that
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
        }
    }
    $stmt->close();
}

if ($row === null) {
    // Fallback: direct query (id is already cast to int)
    $res2 = mysqli_query($conn, "SELECT * FROM vms_visitors WHERE id = $visitor_id LIMIT 1");
    if ($res2 && mysqli_num_rows($res2) > 0) {
        $row = mysqli_fetch_assoc($res2);
    }
}

if (empty($row)) {
    // Visitor not found — redirect back to list with a message or show an error
    header('Location: manage-visitors.php');
    exit();
}

$popup_message = '';
$popup_type = '';

// Handle Save / Update Visitor
// Handle form submission with prepared statements
if(isset($_POST['sv-vstr'])) {
    // Sanitize inputs
    $fullname   = htmlspecialchars($_POST['fullname']);
    $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $mobile     = htmlspecialchars($_POST['mobile']);
    $address    = htmlspecialchars($_POST['address']);
    $department = htmlspecialchars($_POST['department']);
    $gender     = htmlspecialchars($_POST['gender']);
    $year       = (int)$_POST['year_of_graduation'];
    $roll_number = htmlspecialchars($_POST['roll_number']);
    $status     = (int)$_POST['status'];

    // Use prepared statements for update
    $sql = "UPDATE vms_visitors SET name=?, email=?, mobile=?, address=?, department=?, gender=?, year_of_graduation=?, roll_number=?, status=?";
        // types: name(s), email(s), mobile(s), address(s), department(s), gender(s), year(i), roll_number(s), status(i)
        $params = "ssssssisi";
    $values = [$fullname, $email, $mobile, $address, $department, $gender, $year, $roll_number, $status];

    if($status == 0) {
        $sql.= ", out_time=NOW()";
    }
    $sql.= " WHERE id=?";
    $params.= "i";
        $values[] = $visitor_id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params,...$values);
    
    if($stmt->execute()) {
        $popup_message = "Visitor updated successfully!";
        $popup_type = "success";
    } else {
        $popup_message = "Error updating visitor: ". $stmt->error;
        $popup_type = "danger";
    }
    $stmt->close();
}
?>
<?php include('include/header.php');?>
<div id="wrapper">
<?php include('include/side-bar.php');?>

<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Edit Visitor</a></li>
        </ol>
        
        <div class="card mb-3">
            <div class="card-header"><i class="fa fa-info-circle"></i> Edit Details</div>
            <form method="post" class="form-valide">
                <div class="card-body">
                    <!-- Name -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Name</label>
                        <div class="col-lg-6">
                            <input type="text" name="fullname" class="form-control"
                                value="<?php echo htmlspecialchars($row['name'] ?? '');?>"
                                minlength="2" maxlength="100"
                                title="Enter a valid name (2-100 characters)" required>
                            <div class="invalid-feedback">Please enter a valid name (2-100 characters)</div>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Email</label>
                        <div class="col-lg-6">
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email'] ?? '');?>" required>
                        </div>
                    </div>
                    <!-- Mobile -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Mobile</label>
                        <div class="col-lg-6">
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($row['mobile'] ?? '');?>" required>
                        </div>
                    </div>
                    <!-- Address -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Address</label>
                        <div class="col-lg-6">
                            <textarea name="address" class="form-control" required><?php echo htmlspecialchars($row['address'] ?? '');?></textarea>
                        </div>
                    </div>
                    <!-- Department -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Department</label>
                        <div class="col-lg-6">
                            <select name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php
                                $select_department = mysqli_query($conn,"SELECT * FROM vms_department WHERE status=1 ORDER BY department ASC");
                                while($dept = mysqli_fetch_assoc($select_department)){
                                    $selected = (($row['department'] ?? '') == $dept['department'])? 'selected' : '';
                                    echo "<option value='".$dept['department']."' ".$selected.">".$dept['department']."</option>";
                                }
                               ?>
                            </select>
                        </div>
                    </div>
                    <!-- Gender -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Gender</label>
                        <div class="col-lg-6">
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (($row['gender'] ?? '') == 'Male')? 'selected' : '';?>>Male</option>
                                <option value="Female" <?php echo (($row['gender'] ?? '') == 'Female')? 'selected' : '';?>>Female</option>
                                <option value="Other" <?php echo (($row['gender'] ?? '') == 'Other')? 'selected' : '';?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <!-- Year of Graduation -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Year of Graduation</label>
                        <div class="col-lg-6">
                            <select name="year_of_graduation" class="form-control" required>
                                <option value="">Select Year</option>
                                <?php for($y = 2007; $y <= date("Y"); $y++){
                                    $selected = (($row['year_of_graduation'] ?? '') == $y)? 'selected' : '';
                                    echo "<option value='$y' ".$selected.">$y</option>";
                                }?>
                            </select>
                        </div>
                    </div>
                    <!-- Roll Number -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Roll Number</label>
                        <div class="col-lg-6">
                            <input type="text" name="roll_number" class="form-control" value="<?php echo htmlspecialchars($row['roll_number'] ?? '');?>" required>
                        </div>
                    </div>
                    <!-- In Time -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">In Time</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['in_time'] ?? '');?>" readonly>
                        </div>
                    </div>
                    <!-- Out Time -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Out Time</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['out_time'] ?? 'N/A');?>" readonly>
                        </div>
                    </div>
                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Status</label>
                        <div class="col-lg-6">
                            <select name="status" class="form-control" required>
                                <option value="1" <?php echo (($row['status'] ?? '') == 1)? 'selected' : '';?>>In</option>
                                <option value="0" <?php echo (($row['status'] ?? '') == 0)? 'selected' : '';?>>Out</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-lg-8 ml-auto">
                            <button type="submit" name="sv-vstr" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Popup -->
<?php if($popup_message!= '') {?>
<div class="modal fade" id="visitorPopup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
        <h5 class="modal-title">
          <i class="fa-solid fa-<?php echo $popup_type === 'success' ? 'check-circle' : ($popup_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
          <?php echo ucfirst($popup_type ?: 'Info'); ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-<?php echo $popup_type ?: 'dark'; ?>">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-<?php echo $popup_type === 'success' ? 'check-circle' : ($popup_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> fa-2x me-3 text-<?php echo $popup_type ?: 'primary'; ?>"></i>
          <span><?php echo htmlspecialchars($popup_message ?? ''); ?></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Editing</button>
        <a href="manage-visitors.php" class="btn btn-<?php echo $popup_type ?: 'primary'; ?>">Back to Visitors</a>
      </div>
    </div>
  </div>
</div>
<script>
    // Ensure Bootstrap is loaded before showing modal
    document.addEventListener('DOMContentLoaded', function() {
        var popupModalElement = document.getElementById('visitorPopup');
        if (popupModalElement && typeof bootstrap !== 'undefined') {
            var popupModal = new bootstrap.Modal(popupModalElement, {
                backdrop: 'static',
                keyboard: false
            });
            popupModal.show();
        } else {
            // Fallback: show alert if Bootstrap modal fails
            setTimeout(function() {
                alert('<?php echo addslashes($popup_message ?? ''); ?>');
            }, 500);
        }
    });
</script>
<?php }?>
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>
<?php include('include/footer.php');?>
