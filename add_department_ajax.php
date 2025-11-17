<?php
include 'connection.php';

if(isset($_POST['dept_name']) && !empty(trim($_POST['dept_name']))) {
    $dept_name = mysqli_real_escape_string($conn, trim($_POST['dept_name']));

    // Check if department already exists
    $check = mysqli_query($conn, "SELECT * FROM tbl_department WHERE department='$dept_name' AND status=1");
    if(mysqli_num_rows($check) > 0){
        echo "exists";
        exit;
    }

    // Insert new department
    $insert = mysqli_query($conn, "INSERT INTO tbl_department (department, status, created_at) VALUES ('$dept_name', 1, NOW())");
    
    if($insert){
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>
