<?php
include 'connection.php';

// get event id from URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// fetch event name
$event_name = "Unknown Event";
if ($event_id > 0) {
    $res = mysqli_query($conn, "SELECT event_name FROM tbl_events WHERE event_id = $event_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $event_name = $row['event_name'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Event Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="p-4">
  <h2>Event: <?php echo htmlspecialchars($event_name); ?></h2>
  <p>Here you can display the list of visitors for this event.</p>
</body>
</html>
