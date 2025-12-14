<?php
// Include the connection file
include 'connection.php';

try {
    // Execute the ALTER TABLE command
    $conn->query("ALTER TABLE vms_visitors ADD COLUMN added_by INT NULL COMMENT 'User ID who added this visitor'");

    echo "Column 'added_by' added successfully to vms_visitors.\n";
} catch (mysqli_sql_exception $e) {
    error_log("Error adding column 'added_by' to vms_visitors: " . $e->getMessage());
    die("Error adding column 'added_by' to vms_visitors. Please try again later.");
} finally {
    $conn->close();
}
?>