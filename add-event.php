<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'connection.php';

// handle form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $location   = mysqli_real_escape_string($conn, $_POST['location']);
    $description= mysqli_real_escape_string($conn, $_POST['description']);

    if (!empty($event_name) && !empty($event_date)) {
        $sql = "INSERT INTO tbl_events (event_name, event_date, location, description)
                VALUES ('$event_name', '$event_date', '$location', '$description')";
        if (mysqli_query($conn, $sql)) {
            $message = "<div class='alert alert-success'>Event added successfully!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
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
