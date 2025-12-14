<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$event_id = $_GET['event_id'] ?? 0;

// Fetch event name
$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT event_name FROM tbl_events WHERE event_id = $event_id"));
$event_name = $event['event_name'] ?? "Unknown Event";

// Fetch visitors
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE event_id = $event_id");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Visitors for <?php echo htmlspecialchars($event_name); ?></title>
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <?php include 'side-nav.php'; ?>

  <div class="content-wrapper p-4">
    <h3>Visitors for: <?php echo htmlspecialchars($event_name); ?></h3>

    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Visitor Name</th>
          <th>Email</th>
          <th>Phone</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 1;
        while ($row = mysqli_fetch_assoc($visitors)) {
            echo "<tr>
                    <td>{$i}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['phone']}</td>
                  </tr>";
            $i++;
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
