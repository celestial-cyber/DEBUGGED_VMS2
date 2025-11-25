<?php
include '../connection.php';

if(isset($_POST['dept_name']) && !empty(trim($_POST['dept_name']))) {
    $dept_name = trim($_POST['dept_name']);

    // Check if department already exists using prepared statement
    $check_stmt = $conn->prepare("SELECT id FROM vms_department WHERE department = ? AND status = 1");
    if ($check_stmt) {
        $check_stmt->bind_param("s", $dept_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if($result->num_rows > 0){
            echo "exists";
            $check_stmt->close();
            exit;
        }
        $check_stmt->close();
    }

    // Insert new department using prepared statement
    $insert_stmt = $conn->prepare("INSERT INTO vms_department (department, status, created_at) VALUES (?, 1, NOW())");
    if ($insert_stmt) {
        $insert_stmt->bind_param("s", $dept_name);
        
        if($insert_stmt->execute()){
            echo "success";
        } else {
            echo "error";
        }
        $insert_stmt->close();
    } else {
        echo "error";
    }
} else {
    echo "error";
}
?>
