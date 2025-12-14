<?php
include '../config/connection.php';

$sql = file_get_contents('../migrations/20251213_add_creator_columns_to_notes.sql');

if (mysqli_multi_query($conn, $sql)) {
    echo 'Migration completed successfully';
} else {
    echo 'Migration failed: ' . mysqli_error($conn);
}
?>