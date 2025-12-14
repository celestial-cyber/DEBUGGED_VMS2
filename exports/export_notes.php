<?php
session_start();
include '../config/connection.php';

if(empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="coordinator_notes_export_'.date('Y-m-d').'.csv"');

// Create CSV header
$output = fopen('php://output', 'w');
fputcsv($output, array('Note Type', 'Content', 'Event Name', 'Created At'));

// Build the query
$sql = "SELECT n.*, e.event_name FROM vms_coordinator_notes n LEFT JOIN vms_events e ON n.event_id = e.event_id ORDER BY n.created_at DESC";

$result = mysqli_query($conn, $sql);

// Write data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, array(
        $row['note_type'],
        $row['content'],
        $row['event_name'] ?? 'N/A',
        date('Y-m-d H:i:s', strtotime($row['created_at']))
    ));
}

// Close the file handle
fclose($output);
exit();
?>