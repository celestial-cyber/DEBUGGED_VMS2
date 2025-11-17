<?php
include 'connection.php';
$response = ['success'=>false];

if($_SERVER['REQUEST_METHOD']=='POST'){
  $name = $_POST['name'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $email = $_POST['email'] ?? '';
  $visit_date = $_POST['visit_date'] ?? '';
  $purpose = $_POST['purpose'] ?? '';
  $department = $_POST['department'] ?? '';
  $member_id = $_POST['member_id'] ?? '';

  $stmt = $conn->prepare("INSERT INTO tbl_visitors (member_id,name,phone,email,visit_date,purpose,department,status) VALUES (?,?,?,?,?,?,?,'Pending')");
  $stmt->bind_param("issssss",$member_id,$name,$phone,$email,$visit_date,$purpose,$department);
  if($stmt->execute()) $response['success']=true;
  else $response['message']=$stmt->error;
}
echo json_encode($response);
?>
