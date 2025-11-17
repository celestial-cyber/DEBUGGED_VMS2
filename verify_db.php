<?php
require_once 'connection.php';

$tables = array(
    'tbl_admin',
    'tbl_members',
    'tbl_events',
    'tbl_department',
    'tbl_visitors',
    'event_registrations',
    'tbl_inventory',
    'tbl_goodies_distribution',
    'tbl_event_participation',
    'tbl_coordinator_notes'
);

$allTablesExist = true;

foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT 1 FROM $table LIMIT 1");
    if ($result === FALSE) {
        echo "Table '$table' does not exist or is not accessible.\n";
        $allTablesExist = false;
    } else {
        echo "Table '$table' exists and is accessible.\n";
        
        // Check if there's any data
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
        $count = mysqli_fetch_assoc($countResult)['count'];
        echo "Records in $table: $count\n";
    }
}

if ($allTablesExist) {
    echo "\nAll tables are set up correctly!";
} else {
    echo "\nSome tables are missing or inaccessible.";
}

mysqli_close($conn);
?>