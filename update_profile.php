<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$user_id = $_SESSION['id'];
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$department = $_POST['department'] ?? '';
$graduation_year = $_POST['graduation_year'] ?? '';
$linkedin = $_POST['linkedin'] ?? '';
$instagram = $_POST['instagram'] ?? '';
$whatsapp = $_POST['whatsapp'] ?? '';

// Validate required fields
if (empty($full_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
    exit();
}

// Check if email already exists for another user
$email_check = mysqli_query($conn, "SELECT id FROM tbl_members WHERE emailid='$email' AND id != '$user_id'");
if (mysqli_num_rows($email_check) > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists for another user']);
    exit();
}

// Update member profile
$update_query = mysqli_query($conn, "UPDATE tbl_members SET 
    member_name = '$full_name',
    emailid = '$email',
    department = '$department',
    graduation_year = '$graduation_year',
    linkedin = '$linkedin',
    instagram = '$instagram',
    whatsapp = '$whatsapp'
    WHERE id = '$user_id'");

if ($update_query) {
    // Update session name if changed
    $_SESSION['name'] = $full_name;
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . mysqli_error($conn)]);
}
?>