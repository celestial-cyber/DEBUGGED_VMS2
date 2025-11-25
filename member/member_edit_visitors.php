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
    
    // Handle goodies distribution if selected
    $goodie_name = isset($_POST['goodie_name']) ? htmlspecialchars($_POST['goodie_name']) : '';
    $goodie_quantity = isset($_POST['goodie_quantity']) ? (int)$_POST['goodie_quantity'] : 0;

    $sql = "UPDATE vms_visitors
            SET name=?, email=?, mobile=?, address=?, department=?, gender=?, year_of_graduation=?, roll_number=?, status=?";
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
        
        // Handle goodies distribution if selected
        if (!empty($goodie_name) && $goodie_quantity > 0) {
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
            <form method="post" class="form-valide">
                <div class="card-body">
                    <!-- Name -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Name</label>
                        <div class="col-lg-6">
                            <input type="text" name="fullname" class="form-control"
                                value="<?php echo htmlspecialchars($row['name'] ?? '');?>"
                                pattern="[A-Za-z ]{3,50}"
                                title="3-50 alphabetic characters" required>
                            <div class="invalid-feedback">Please enter a valid name (3-50 letters)</div>
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
                    <!-- In Time -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">In Time</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['in_time'] ?? 'N/A');?>" readonly>
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
                                <option value="1" <?php echo ($row['status'] == 1)? 'selected' : '';?>>In</option>
                                <option value="0" <?php echo ($row['status'] == 0)? 'selected' : '';?>>Out</option>
                            </select>
                        </div>
                    </div>
                    <!-- Goodies Distribution Section -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Distribute Goodies</label>
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-md-8 mb-2">
                                    <select name="goodie_name" class="form-control">
                                        <option value="">Select Goodie</option>
                                        <?php
                                        // Fetch available inventory items
                                        $inventory_query = mysqli_query($conn, "SELECT item_name FROM vms_inventory WHERE status='Available' AND total_stock > used_count ORDER BY item_name ASC");
                                        while($item = mysqli_fetch_assoc($inventory_query)) {
                                            echo "<option value='".$item['item_name']."'>".$item['item_name']."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="number" name="goodie_quantity" class="form-control" placeholder="Qty" min="1" max="100" value="1">
                                </div>
                            </div>
                            <small class="form-text text-muted">Select a goodie and quantity to distribute</small>
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
<div class="modal fade" id="visitorPopup" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-<?php echo $popup_type;?>">
      <div class="modal-body text-<?php echo $popup_type;?>">
        <?php echo $popup_message;?>
      </div>
      <div class="modal-footer">
        <a href="member_manage_visitors.php" class="btn btn-<?php echo $popup_type;?>">OK</a>
      </div>
    </div>
  </div>
</div>
<script>
    var popup = new bootstrap.Modal(document.getElementById('visitorPopup'));
    popup.show();
</script>
<?php }?>
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>
<?php include __DIR__ . '/../includes/footer.php';?>
