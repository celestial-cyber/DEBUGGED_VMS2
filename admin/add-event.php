<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../config/connection.php';

// handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = trim($_POST['event_name'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $location   = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($event_name) && !empty($event_date)) {
        // Use prepared statement to prevent SQL injection
        // Note: vms_events table may not have location/description columns, adjust as needed
        $stmt = $conn->prepare("INSERT INTO vms_events (event_name, event_date, created_at) VALUES (?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("ss", $event_name, $event_date);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Event added successfully!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-danger'>Database error: " . $conn->error . "</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Event Name and Date are required.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="mb-4">Add New Event</h2>
    <?php echo $message; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Event Name</label>
            <input type="text" name="event_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Event Date</label>
            <input type="date" name="event_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Add Event</button>
    </form>

</body>
</html>
