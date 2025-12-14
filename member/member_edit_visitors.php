<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../config/connection.php';
include __DIR__ . '/../includes/guard_member.php';

// -----------------------------
// Session & role checks
// -----------------------------
$name = $_SESSION['name'];
$id = $_SESSION['id'];  // logged-in member ID
$role = $_SESSION['role']?? 'member';

if(empty($id)) {
    header("Location: index.php"); 
    exit();
}

// Only members can access this page
if($role!= 'member') {
    header("Location: admin_dashboard.php");
    exit();
}

// -----------------------------
// Get visitor ID
// -----------------------------
$visitor_id = $_GET['id']?? 0;
$fetch_query = mysqli_query($conn, "SELECT * FROM vms_visitors WHERE id='$visitor_id'");
$row = mysqli_fetch_assoc($fetch_query);

// -----------------------------
// Popup variables
// -----------------------------
$popup_message = '';
$popup_type = '';

// -----------------------------
// Handle Save / Update Visitor
// -----------------------------
if(isset($_POST['sv-vstr'])) {

    $fullname   = htmlspecialchars($_POST['fullname'] ?? '');
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $mobile     = htmlspecialchars($_POST['mobile'] ?? '');
    $address    = htmlspecialchars($_POST['address'] ?? '');
    $department = htmlspecialchars($_POST['department'] ?? '');
    $gender     = htmlspecialchars($_POST['gender'] ?? '');
    $year       = (int)($_POST['year_of_graduation'] ?? 0);
    $roll_number = htmlspecialchars($_POST['roll_number'] ?? '');
    $status     = (int)($_POST['status'] ?? 0);

    // Handle check in/out times
    $in_time = $_POST['in_time'] ?? '';
    $out_time = $_POST['out_time'] ?? '';
    $auto_checkout = isset($_POST['auto_checkout']);

    // Handle goodies distribution if selected
    $goodie_name = isset($_POST['goodie_name']) ? htmlspecialchars($_POST['goodie_name']) : '';
    $goodie_quantity = isset($_POST['goodie_quantity']) ? (int)$_POST['goodie_quantity'] : 0;

    $sql = "UPDATE vms_visitors
            SET name=?, email=?, mobile=?, address=?, department=?, gender=?, year_of_graduation=?, roll_number=?, status=?";
    $params = "ssssssisi";
    $values = [$fullname, $email, $mobile, $address, $department, $gender, $year, $roll_number, $status];

    // Handle in_time
    if (!empty($in_time)) {
        $sql .= ", in_time=?";
        $params .= "s";
        $values[] = date('Y-m-d H:i:s', strtotime($in_time));
    } else {
        $sql .= ", in_time=NULL";
    }

    // Handle out_time
    if (!empty($out_time)) {
        $sql .= ", out_time=?";
        $params .= "s";
        $values[] = date('Y-m-d H:i:s', strtotime($out_time));
    } elseif ($status == 0 && $auto_checkout) {
        $sql .= ", out_time=NOW()";
    } else {
        $sql .= ", out_time=NULL";
    }

    $sql .= " WHERE id=?";
    $params .= "i";
    $values[] = $visitor_id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params, ...$values);
    
    if($stmt->execute()) {
        $popup_message = "Visitor updated successfully!";
        $popup_type = "success";
        
        // Handle goodies distribution if selected
        $distribute_goodies = isset($_POST['distribute_goodies']) && $_POST['distribute_goodies'] == '1';
        $goodie_name = isset($_POST['goodie_name']) ? htmlspecialchars(trim($_POST['goodie_name'])) : '';
        $goodie_quantity = isset($_POST['goodie_quantity']) ? (int)$_POST['goodie_quantity'] : 0;
        
        if ($distribute_goodies && !empty($goodie_name) && $goodie_quantity > 0) {
            // Insert goodies distribution record
            $dist_stmt = $conn->prepare("INSERT INTO vms_goodies_distribution (visitor_id, goodie_name, quantity, distribution_time) VALUES (?, ?, ?, NOW())");
            $dist_stmt->bind_param("isi", $visitor_id, $goodie_name, $goodie_quantity);
            if ($dist_stmt->execute()) {
                $popup_message .= " Goodies distributed successfully!";
            } else {
                $popup_message .= " Error distributing goodies: " . $dist_stmt->error;
                $popup_type = "warning";
            }
            $dist_stmt->close();
        }
        
        // Refresh the visitor data
        $fetch_query = mysqli_query($conn, "SELECT * FROM vms_visitors WHERE id='$visitor_id'");
        $row = mysqli_fetch_assoc($fetch_query);
    } else {
        $popup_message = "Error updating visitor: ". $stmt->error;
        $popup_type = "danger";
    }
    $stmt->close();
}
?>
<?php include __DIR__ . '/../includes/header.php';?>
<div id="wrapper">
<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Edit Visitor</a></li>
        </ol>
        
        <div class="card mb-3">
            <div class="card-header"><i class="fa fa-info-circle"></i> Edit Details</div>
            
            <?php if($popup_message && $popup_type === 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i>
                <?php echo $popup_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php elseif($popup_message && $popup_type === 'danger'): ?>
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                <?php echo $popup_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
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
                                    $selected = ($row['department'] == $dept['department'])? 'selected' : '';
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
                                <option value="Male" <?php echo ($row['gender'] == 'Male')? 'selected' : '';?>>Male</option>
                                <option value="Female" <?php echo ($row['gender'] == 'Female')? 'selected' : '';?>>Female</option>
                                <option value="Other" <?php echo ($row['gender'] == 'Other')? 'selected' : '';?>>Other</option>
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
                                    $selected = ($row['year_of_graduation'] == $y)? 'selected' : '';
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
                    <!-- Check In/Out Status -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Check In/Out Status</label>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Check In Time</label>
                                    <input type="datetime-local" name="in_time" class="form-control"
                                        value="<?php echo $row['in_time'] ? date('Y-m-d\TH:i', strtotime($row['in_time'])) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Check Out Time</label>
                                    <input type="datetime-local" name="out_time" class="form-control"
                                        value="<?php echo $row['out_time'] ? date('Y-m-d\TH:i', strtotime($row['out_time'])) : ''; ?>">
                                </div>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="auto_checkout" id="auto_checkout" checked>
                                <label class="form-check-label" for="auto_checkout">
                                    Auto-set checkout time when status is "Out"
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Current Status</label>
                        <div class="col-lg-6">
                            <select name="status" class="form-control" required>
                                <option value="1" <?php echo ($row['status'] == 1)? 'selected' : '';?>>Checked In</option>
                                <option value="0" <?php echo ($row['status'] == 0)? 'selected' : '';?>>Checked Out</option>
                            </select>
                        </div>
                    </div>
                    <!-- Goodies Distribution Section -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Goodies Distribution</label>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="distribute_goodies" id="distribute_goodies" value="1">
                                    <label class="form-check-label" for="distribute_goodies">
                                        Distribute Goodies to this visitor
                                    </label>
                                </div>
                            </div>
                            <div id="goodies_details" style="display: none;">
                                <div class="row">
                                    <div class="col-md-8 mb-2">
                                        <input type="text" name="goodie_name" class="form-control" placeholder="Enter goodies name (e.g., T-shirt, Bag, Pen)" maxlength="100">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <input type="number" name="goodie_quantity" class="form-control" placeholder="Qty" min="1" max="100" value="1">
                                    </div>
                                </div>
                                <small class="form-text text-muted">Specify the goodies name and quantity to distribute</small>
                            </div>
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

        <!-- Goodies Distribution History -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa-solid fa-gift text-primary"></i> Goodies Distribution History
            </div>
            <div class="card-body">
                <?php
                $goodies_query = mysqli_query($conn, "SELECT * FROM vms_goodies_distribution WHERE visitor_id='$visitor_id' ORDER BY distribution_time DESC");
                if (mysqli_num_rows($goodies_query) > 0) {
                ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Goodie Name</th>
                                <th>Quantity</th>
                                <th>Distribution Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($goodie = mysqli_fetch_assoc($goodies_query)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($goodie['goodie_name']); ?></td>
                                <td><?php echo (int)$goodie['quantity']; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($goodie['distribution_time'])); ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } else { ?>
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-gift fa-2x mb-2"></i>
                    <p>No goodies distributed to this visitor yet.</p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<!-- Popup -->
<?php if($popup_message!= '') {?>
<div class="modal fade" id="visitorPopup" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
        <h5 class="modal-title"><?php echo ucfirst($popup_type ?: 'Info'); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-<?php echo $popup_type ?: 'dark'; ?>">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-<?php echo $popup_type === 'success' ? 'check-circle' : ($popup_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> fa-2x me-3 text-<?php echo $popup_type ?: 'primary'; ?>"></i>
          <span><?php echo $popup_message;?></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Editing</button>
        <a href="member_manage_visitors.php" class="btn btn-<?php echo $popup_type ?: 'primary'; ?>">Back to Visitors</a>
      </div>
    </div>
  </div>
</div>
<script>
    var popupModal = new bootstrap.Modal(document.getElementById('visitorPopup'));
    popupModal.show();
</script>
<?php }?>

<script>
    // Goodies distribution toggle
    document.getElementById('distribute_goodies').addEventListener('change', function() {
        const goodiesDetails = document.getElementById('goodies_details');
        if (this.checked) {
            goodiesDetails.style.display = 'block';
        } else {
            goodiesDetails.style.display = 'none';
        }
    });
</script>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>
<?php include __DIR__ . '/../includes/footer.php';?>
