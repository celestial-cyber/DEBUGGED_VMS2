<?php
require_once 'config/connection.php';

$result = mysqli_query($conn, 'SHOW TABLES LIKE "alumni_registrations"');
if (mysqli_num_rows($result) > 0) {
    echo 'alumni_registrations table exists' . PHP_EOL;
} else {
    echo 'Table does not exist' . PHP_EOL;
}

// Also check vms_visitors
$result2 = mysqli_query($conn, 'SELECT COUNT(*) as count FROM vms_visitors');
$row = mysqli_fetch_assoc($result2);
echo 'Total visitors in vms_visitors: ' . $row['count'] . PHP_EOL;
?>