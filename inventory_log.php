<?php
session_start();
include 'connection.php';
include 'include/guard_admin.php';
$logs = mysqli_query($conn, "SELECT * FROM tbl_inventory_log ORDER BY created_at DESC");
?>
<?php include('include/header.php'); ?>
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-regular fa-file-lines text-primary"></i> Inventory Log</span>
      <h2>Inventory Changes</h2>
      <span class="badge">Admin</span>
    </div>
  </div>
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-clipboard-list text-primary"></i>
        <span class="fw-semibold">Recent Changes</span>
      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Time</th>
              <th>Item</th>
              <th>Delta</th>
              <th>Action</th>
              <th>User</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r = mysqli_fetch_assoc($logs)) { ?>
            <tr>
              <td><?php echo htmlspecialchars($r['created_at']); ?></td>
              <td><?php echo htmlspecialchars($r['item_name'] ?: ('#'.$r['item_id'])); ?></td>
              <td><?php echo (int)$r['delta']; ?></td>
              <td><?php echo htmlspecialchars($r['action']); ?></td>
              <td><?php echo (int)$r['user_id']; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include('include/footer.php'); ?>





