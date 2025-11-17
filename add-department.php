<?php
session_start();
include ('connection.php');
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id))
{
    header("Location: index.php"); 
}
if(isset($_REQUEST['sbt-dpt']))
{
  $deptname = $_POST['deptname'];
  $status = $_POST['status'];
  
  $insert_department = mysqli_query($conn,"insert into tbl_department set department='$deptname', status='$status'");

if($insert_department > 0)
{
  ?>
<script type="text/javascript">
    alert("Department added successfully.")
</script>
<?php
}
}
?>
<?php include('include/header.php'); ?>
<div id="wrapper">
<?php include('include/side-bar.php'); ?>

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
 <?php include('include/footer.php'); ?>