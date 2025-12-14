<?php
session_start();
include '../config/connection.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id)) {
    header("Location: index.php");
    exit();
}

// Initialize popup message
$popup_message = '';
$popup_type = '';

// Handle Add Note
if(isset($_POST['sbt-note'])) {
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $note_type = mysqli_real_escape_string($conn, $_POST['note_type']);
    
    if (empty($content) || empty($note_type)) {
        $popup_message = "Content and note type are required";
        $popup_type = "danger";
    } else if (!in_array($note_type, ['LOG', 'ACTION_ITEM'])) {
        $popup_message = "Invalid note type";
        $popup_type = "danger";
  } else {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $sql = "INSERT INTO vms_coordinator_notes (event_id, content, note_type, creator_type, created_at)
        VALUES (" . ($event_id > 0 ? $event_id : 'NULL') . ", '$content', '$note_type', 'coordinator', NOW())";
        
    if (mysqli_query($conn, $sql)) {
      $popup_message = "Note added successfully!";
      $popup_type = "success";
    } else {
      $popup_message = "Error adding note: " . mysqli_error($conn);
      $popup_type = "danger";
    }
  }
}

// Fetch events for selection
$events = mysqli_query($conn, "SELECT event_id, event_name FROM vms_events ORDER BY event_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>VMS - Add Note</title>
  <?php include __DIR__ . '/../includes/header.php'; ?>
</head>
<body id="page-top">
  <?php
  $breadcrumbs = [
      ['url' => 'admin/dashboard.php', 'text' => 'Dashboard'],
      ['url' => 'manage-notes.php', 'text' => 'Notes'],
      ['text' => 'Add Note']
  ];
  include __DIR__ . '/../includes/top-bar.php';
  ?>
  <div id="wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <!-- Content -->
    <div class="container-fluid">
      <!-- Header -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="title-row">
          <span class="chip"><i class="fa-solid fa-sticky-note text-primary"></i> Notes</span>
          <h2>📝 Add Coordinator Note</h2>
          <span class="badge">Live Form</span>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary" onclick="location.href='manage-notes.php'"><i class="fa-solid fa-clipboard-list me-2"></i>View All</button>
          <button class="btn btn-outline-secondary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Reset</button>
        </div>
      </div>

      <!-- Note Form -->
      <div class="card-lite">
        <div class="card-head">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-note-sticky text-primary"></i>
            <span class="fw-semibold">Note Details</span>
          </div>
        </div>
        <div class="card-body">
          <form method="post" class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Event <span class="text-danger">*</span></label>
              <select name="event_id" class="form-control">
                <option value="">(Global) Select Event (optional)</option>
                <?php mysqli_data_seek($events, 0); while($ev = mysqli_fetch_assoc($events)) { ?>
                  <option value="<?php echo $ev['event_id']; ?>"><?php echo htmlspecialchars($ev['event_name']); ?></option>
                <?php } ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Note Type <span class="text-danger">*</span></label>
              <select name="note_type" class="form-control" required>
                <option value="">Select Note Type</option>
                <option value="LOG">Log Entry</option>
                <option value="ACTION_ITEM">Action Item</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Content <span class="text-danger">*</span></label>
              <textarea name="content" class="form-control" placeholder="Enter note content..." rows="4" required></textarea>
            </div>

            <div class="col-12">
              <div class="d-flex gap-2">
                <button type="submit" name="sbt-note" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Add Note</button>
                <button type="reset" class="btn btn-outline-secondary"><i class="fa-solid fa-eraser me-2"></i>Clear Form</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Popup Modal -->
  <div class="modal fade" id="notePopup" tabindex="-1" aria-labelledby="notePopupLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-<?php echo $popup_type ?: 'primary'; ?> text-white">
          <h5 class="modal-title" id="notePopupLabel"><?php echo ucfirst($popup_type ?: 'Info'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php echo $popup_message; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-<?php echo $popup_type ?: 'primary'; ?>" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <?php if($popup_message): ?>
  <script>
    var popupModal = new bootstrap.Modal(document.getElementById('notePopup'));
    popupModal.show();
  </script>
  <?php endif; ?>
</body>
</html>