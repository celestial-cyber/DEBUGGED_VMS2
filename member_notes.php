<?php
session_start();
include 'connection.php';
include 'include/guard_member.php';

$notes = mysqli_query($conn, "SELECT * FROM tbl_coordinator_notes ORDER BY created_at DESC");
?>
<?php include('include/header.php'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-regular fa-note-sticky text-primary"></i> Notes</span>
      <h2>Coordinator Notes</h2>
      <span class="badge">Read-only</span>
    </div>
  </div>
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-clipboard-list text-primary"></i>
        <span class="fw-semibold">Recent Notes</span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Type</th>
              <th>Content</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($n = mysqli_fetch_assoc($notes)) { ?>
            <tr>
              <td><span class="badge rounded-pill <?php echo $n['note_type']==='LOG'?'text-bg-secondary':'text-bg-warning'; ?>"><?php echo htmlspecialchars($n['note_type']); ?></span></td>
              <td><?php echo nl2br(htmlspecialchars($n['content'])); ?></td>
              <td><?php echo date('Y-m-d H:i', strtotime($n['created_at'])); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include('include/footer.php'); ?>

