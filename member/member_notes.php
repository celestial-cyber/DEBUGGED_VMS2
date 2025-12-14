<?php
session_start();
include '../config/connection.php';
include __DIR__ . '/../includes/guard_member.php';

// Handle Add Note
$popup_message = '';
$popup_type = '';
if(isset($_POST['add_note'])) {
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $note_type = mysqli_real_escape_string($conn, $_POST['note_type']);

    if (empty($content) || empty($note_type)) {
        $popup_message = "Content and note type are required";
        $popup_type = "danger";
    } else if (!in_array($note_type, ['LOG', 'ACTION_ITEM'])) {
        $popup_message = "Invalid note type";
        $popup_type = "danger";
    } else {
        $member_id = $_SESSION['id'];
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

        $sql = "INSERT INTO vms_coordinator_notes (event_id, note_type, creator_type, member_id, content, created_at)
            VALUES (" . ($event_id > 0 ? $event_id : 'NULL') . ", '$note_type', 'member', $member_id, '$content', NOW())";

        if (mysqli_query($conn, $sql)) {
            $popup_message = "Note added successfully!";
            $popup_type = "success";
        } else {
            $popup_message = "Error adding note: " . mysqli_error($conn);
            $popup_type = "danger";
        }
    }
}

// Fetch all notes (both coordinator and member)
$notes = mysqli_query($conn, "SELECT n.*, m.member_name,
    CASE
        WHEN n.creator_type = 'coordinator' THEN 'Coordinator'
        WHEN n.creator_type = 'member' THEN COALESCE(m.member_name, 'Member')
        ELSE 'Unknown'
    END as creator_name
    FROM vms_coordinator_notes n
    LEFT JOIN vms_members m ON n.member_id = m.id
    ORDER BY n.created_at DESC");

// Fetch events for selection
$events = mysqli_query($conn, "SELECT event_id, event_name FROM vms_events ORDER BY event_date DESC");
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Popup Message -->
<?php if (!empty($popup_message)): ?>
<div class="alert alert-<?php echo $popup_type; ?> alert-dismissible fade show" role="alert">
  <?php echo htmlspecialchars($popup_message); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-regular fa-note-sticky text-primary"></i> Notes</span>
      <h2>All Notes</h2>
      <span class="badge text-bg-success">Read & Write</span>
    </div>
  </div>

  <!-- Add Note Form -->
  <div class="card-lite mb-4">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-plus text-primary"></i>
        <span class="fw-semibold">Add New Note</span>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="note_type" class="form-label">Note Type</label>
            <select class="form-select" id="note_type" name="note_type" required>
              <option value="">Select Type</option>
              <option value="LOG">Log Entry</option>
              <option value="ACTION_ITEM">Action Item</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="event_id" class="form-label">Related Event (Optional)</label>
            <select class="form-select" id="event_id" name="event_id">
              <option value="">No specific event</option>
              <?php while ($event = mysqli_fetch_assoc($events)) { ?>
              <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-12">
            <label for="content" class="form-label">Note Content</label>
            <textarea class="form-control" id="content" name="content" rows="3" placeholder="Enter your note here..." required></textarea>
          </div>
          <div class="col-12">
            <button type="submit" name="add_note" class="btn btn-primary">
              <i class="fa-solid fa-plus me-2"></i>Add Note
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Notes List -->
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-clipboard-list text-primary"></i>
        <span class="fw-semibold">All Notes</span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Type</th>
              <th>Creator</th>
              <th>Content</th>
              <th>Event</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($n = mysqli_fetch_assoc($notes)) { ?>
            <tr>
              <td><span class="badge rounded-pill <?php echo $n['note_type']==='LOG'?'text-bg-secondary':'text-bg-warning'; ?>"><?php echo htmlspecialchars($n['note_type']); ?></span></td>
              <td>
                <span class="badge rounded-pill <?php echo $n['creator_type']==='coordinator'?'text-bg-primary':'text-bg-info'; ?>">
                  <?php echo htmlspecialchars($n['creator_name']); ?>
                </span>
              </td>
              <td><?php echo nl2br(htmlspecialchars($n['content'])); ?></td>
              <td>
                <?php
                if ($n['event_id']) {
                  $event_query = mysqli_query($conn, "SELECT event_name FROM vms_events WHERE event_id = " . $n['event_id']);
                  $event = mysqli_fetch_assoc($event_query);
                  echo htmlspecialchars($event['event_name'] ?? 'Unknown Event');
                } else {
                  echo '-';
                }
                ?>
              </td>
              <td><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

