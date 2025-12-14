<?php
require_once 'config/connection.php';

$sql = file_get_contents('migrations/20250125_create_alumni_registrations_table.sql');
if ($sql === false) {
    die('Could not read migration file');
}

$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        if (mysqli_query($conn, $statement)) {
            echo "Executed successfully: " . substr($statement, 0, 50) . "...\n";
        } else {
            echo "Error: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "Migration completed.\n";
?>