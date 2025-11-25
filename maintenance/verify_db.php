<?php
require_once __DIR__ . '/../config/connection.php';

$tables = array(
    'vms_admin',
    'vms_members',
    'vms_events',
    'vms_department',
    'vms_visitors',
    'event_registrations',
    'vms_excel_imports',
    'vms_inventory',
    'vms_inventory_log',
    'vms_goodies_distribution',
    'vms_event_participation',
    'vms_coordinator_notes'
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