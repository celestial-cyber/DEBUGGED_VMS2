<?php
// side-bar.php
// ensure this file is included where $conn is available, or include connection.php here:
if (!isset($conn)) {
    include 'connection.php';
}
?>

<!-- Sidebar -->
<ul class="sidebar navbar-nav">
  <!-- Admin Dashboard -->
  <li class="nav-item active">
    <a class="nav-link" href="admin_dashboard.php">
      <i class="fas fa-fw fa-tachometer-alt"></i>
      <span>Admin Dashboard</span>
    </a>
  </li>

  <!-- New Visitors -->
  <li class="nav-item">
    <a class="nav-link" href="new-visitor.php" style="color: white;">
      <i class="fa fa-user"></i>
      <span>New Visitors</span>
    </a>  
  </li>

  <!-- Manage Visitors -->
  <li class="nav-item">
    <a class="nav-link" href="manage-visitors.php" style="color: white;">
      <i class="fa fa-list-alt"></i>
      <span>Manage Visitors</span>
    </a>
  </li>

  <!-- Manage Events (AdminLTE-style treeview) -->
  <?php
    // fetch events
    $events_res = mysqli_query($conn, "SELECT event_id, event_name FROM tbl_events ORDER BY event_date DESC");
    $has_events = ($events_res && mysqli_num_rows($events_res) > 0);
    // If you want the menu auto-expanded when there are events, add 'menu-open' class here:
    // $tree_class = $has_events ? 'menu-open' : '';
    $tree_class = ''; // keep collapsed by default
  ?>
  <li class="nav-item has-treeview <?php echo $tree_class; ?>">
    <a href="#" class="nav-link">
      <i class="nav-icon fa fa-calendar-alt"></i>
      <p>
        Manage Events
        <i class="right fas fa-angle-left"></i>
      </p>
    </a>

    <ul class="nav nav-treeview">
      <!-- Add new event always visible -->
      <li class="nav-item">
        <a href="add-event.php" class="nav-link">
          <i class="far fa-circle nav-icon"></i>
          <p>Add New Event</p>
        </a>
      </li>

      <?php
      // Header / separator line inside treeview (optional)
      echo '<li class="nav-item"><a class="nav-link" style="cursor:default;"><i class="far fa-circle nav-icon"></i><p class="mb-0">Events</p></a></li>';

      if ($has_events) {
          // output each event as a nav link to view_event.php
          while ($ev = mysqli_fetch_assoc($events_res)) {
              $id = (int)$ev['event_id'];
              $name = htmlspecialchars($ev['event_name']);
              echo "<li class='nav-item'>
                      <a class='nav-link' href='view_event.php?event_id={$id}'>
                        <i class='far fa-dot-circle nav-icon'></i>
                        <p>{$name}</p>
                      </a>
                    </li>";
          }
      } else {
          // show a disabled/greyed entry so user knows there are no events yet
          echo "<li class='nav-item'>
                  <a class='nav-link disabled' href='#' tabindex='-1' aria-disabled='true'>
                    <i class='far fa-circle nav-icon'></i>
                    <p class='text-muted mb-0'>No events available</p>
                  </a>
                </li>";
      }
      ?>
    </ul>
  </li>
</ul>
