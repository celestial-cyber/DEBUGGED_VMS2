<?php
session_start();
include '../config/connection.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id))
{
    header("Location: index.php"); 
}
if(isset($_REQUEST['sbt-dpt']))
{
  $deptname = trim($_POST['deptname'] ?? '');
  $status = (int)($_POST['status'] ?? 1);
  
  // Validate input
  if (empty($deptname)) {
    $error = "Department name is required.";
  } else {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO vms_department (department, status, created_at) VALUES (?, ?, NOW())");
    if ($stmt) {
      $stmt->bind_param("si", $deptname, $status);
      if($stmt->execute()) {
        $success = "Department added successfully.";
      } else {
        $error = "Failed to add department: " . $stmt->error;
      }
      $stmt->close();
    } else {
      $error = "Database error: " . $conn->error;
    }
  }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div id="wrapper">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div id="content-wrapper">

      <div class="container-fluid">

        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#">Add Department</a>
          </li>
        </ol>

  <div class="card mb-3">
          <div class="card-header">
            <i class="fa fa-info-circle"></i>
            Submit Details</div>       
      <form method="post" class="form-valide">
      <div class="card-body">
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>
      <div class="form-group row">
      <label class="col-lg-4 col-form-label" for="department">Department Name <span class="text-danger">*</span></label>
       <div class="col-lg-6">
      <input type="text" name="deptname" id="deptname" class="form-control" placeholder="Enter Department Name" required>
       </div>
      </div> 
      <div class="form-group row">
      <label class="col-lg-4 col-form-label" for="status">Status <span class="text-danger">*</span></label>
      <div class="col-lg-6">
      <select class="form-control" id="status" name="status" required>
      <option value="">Select Status</option>
      <option value="1">Active</option>
      <option value="0">Inactive</option>
      </select>
      </div>    
      </div>                                                 
      <div class="form-group row">
      <div class="col-lg-8 ml-auto">
      <button type="submit" name="sbt-dpt" class="btn btn-primary">Submit</button>
      </div>
      </div>
      </div>
      </form>
      </div>                  
    </div>
  </div>  
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>
 <?php include __DIR__ . '/../includes/footer.php'; ?>