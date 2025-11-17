<?php
session_start();
include 'connection.php';
include 'include/guard_member.php';

// Redirect if not logged in
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Update Goodies Given
if(isset($_POST['update_goodies'])){
    $new_count = intval($_POST['goodies_count']);

    // Remove all previous entries (so counter exactly matches user input)
    mysqli_query($conn, "DELETE FROM tbl_goodies_distribution");

    // Insert new value
    mysqli_query($conn, "INSERT INTO tbl_goodies_distribution(quantity, created_at) VALUES ($new_count, NOW())");

    // Refresh page
    header("Location: member_dashboard.php");
    exit();
}


// Member info
$id = $_SESSION['id'];
$name = 'User';
//$name = $_SESSION['name'];
$user_role = $_SESSION['role'] ?? 'member';
$member_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tbl_members WHERE id='$id'"));

// Dashboard Stats
$total_visitors = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_visitors"))[0];
$checked_in = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_visitors WHERE status=1 AND in_time IS NOT NULL"))[0];
$goodies_given = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(quantity),0) FROM tbl_goodies_distribution"))[0];
$event_participants = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(participant_count),0) FROM tbl_event_participation"))[0];

// Fetch recent visitors
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors ORDER BY created_at DESC LIMIT 10");

//add inventory 
// Handle Add Inventory
if(isset($_POST['add_inventory'])){
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $total_stock = intval($_POST['total_stock']);

    $insert = mysqli_query($conn, "INSERT INTO tbl_inventory (item_name, total_stock, used_count, status, created_at)
                                   VALUES ('$item_name', $total_stock, 0, 1, NOW())");
    if($insert){
        // Refresh page to show newly added inventory
        header("Location: member_dashboard.php");
        exit();
    } else {
        echo "Error adding inventory: " . mysqli_error($conn);
    }
}

// Handle Delete Inventory
if(isset($_POST['delete_inventory'])){
    $delete = mysqli_query($conn, "DELETE FROM tbl_inventory");
    if($delete){
        // Optional: reset auto-increment
        mysqli_query($conn, "ALTER TABLE tbl_inventory AUTO_INCREMENT = 1");
        // Refresh page to show empty inventory
        header("Location: member_dashboard.php");
        exit();
    } else {
        echo "Error deleting inventory: " . mysqli_error($conn);
    }
}

// Handle Mark Used
if(isset($_POST['mark_used'])){
    $item_id = intval($_POST['item_id']);
    $used_qty = intval($_POST['used_quantity']);

    // Get current used_count and total_stock
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_stock, used_count FROM tbl_inventory WHERE id=$item_id"));
    if($item){
        $new_used = $item['used_count'] + $used_qty;
        if($new_used > $item['total_stock']){
            $new_used = $item['total_stock']; // Cannot exceed total stock
        }

        mysqli_query($conn, "UPDATE tbl_inventory SET used_count=$new_used WHERE id=$item_id");
    }

    // Refresh page to reflect changes
    header("Location: member_dashboard.php");
    exit();
}



// Fetch inventory
$inventory = mysqli_query($conn, "SELECT * FROM tbl_inventory ORDER BY status, item_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Member Dashboard - VMS Console</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#f6f7fb; --card:#fff; --ink:#1f2937; --ink-soft:#6b7280; --edge:#e5e7eb; 
  --brand:#2563eb; --accent:#10b981; --warn:#f59e0b; --danger:#ef4444;
}
body{margin:0; font-family:'Poppins',sans-serif; background:var(--bg); color:var(--ink);}
.sidebar{position:fixed; inset:0 auto 0 0; width:260px; background:#f3e8ff; border-right:1px solid #e9d5ff; display:flex; flex-direction:column;}
.brand{padding:18px 20px; border-bottom:1px solid #e9d5ff; display:flex; align-items:center; gap:10px; font-weight:600;}
.brand .logo{width:34px; height:34px; border-radius:8px; background:#7c3aed; display:flex; align-items:center; justify-content:center;}
.nav{list-style:none; padding:10px 10px 24px; margin:0; overflow:auto;}
.nav a{display:flex; gap:12px; align-items:center; padding:10px 12px; margin:4px 0; color:#5b21b6; text-decoration:none; border-radius:8px; transition:all .15s;}
.nav a:hover{background:#ede9fe; color:#3b0764;}
.nav .section-label{font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#7c3aed; padding:12px 12px 6px;}
.nav .is-active{background:#e9d5ff; color:#3b0764;}
.main{margin-left:260px; padding:22px; min-height:100vh;}
.card-lite{background:var(--card); border:1px solid var(--edge); border-radius:14px; box-shadow:0 2px 10px rgba(16,24,40,.04);}
.card-head{padding:16px 18px; border-bottom:1px solid var(--edge); display:flex; align-items:center; justify-content:space-between; gap:12px;}
.card-body{padding:16px 18px;}
.chip{display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:20px; font-size:12px; border:1px solid var(--edge); background:#fff;}
.chip i{opacity:.85;}
.table thead th{color:#6b7280; font-weight:600; background:#fafafa;}
.table td, .table th{vertical-align:middle;}
.stat{display:flex; align-items:center; gap:10px; padding:14px; border-radius:14px; background:var(--card); border:1px solid var(--edge);}
.stat i{font-size:18px;}
.muted{color:var(--ink-soft);}
.btn-soft{border:1px solid var(--edge); background:#fff; color:var(--ink);}
.btn-soft:hover{border-color:#cbd5e1; background:#f9fafb;}
.topbar{display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;}
.crumbs{display:flex; align-items:center; gap:8px; color:var(--ink-soft); font-size:13px;}
.crumbs a{ color:var(--ink-soft); text-decoration:none;}
.crumbs a:hover{text-decoration:underline;}
.title-row{display:flex; align-items:center; gap:12px;}
.title-row h2{margin:0; font-weight:600; font-size:22px;}
.title-row .badge{background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff; padding:6px 10px; border-radius:10px; font-weight:500;}
.nav ul.dropdown-menu{list-style:none; padding-left:0; margin:0; display:none; background:#ede9fe !important; border:1px solid #e9d5ff !important; border-radius:8px;}
.nav ul.dropdown-menu li a{display:flex; gap:10px; padding:8px 20px 8px 40px; color:#5b21b6 !important; text-decoration:none; border-radius:6px; transition:.15s;}
.nav ul.dropdown-menu li a:hover{background:#e9d5ff !important; color:#3b0764 !important;}
@media (max-width: 992px){.sidebar{transform:translateX(-100%); transition:transform .2s ease}.sidebar.show{transform:translateX(0)}.main{margin-left:0}.topbar .toggle{display:inline-flex}}
@media (min-width: 993px){.topbar .toggle{display:none}}
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="Images/SALogo.png" alt="Logo" style="width:50px;height:50px;border-radius:8px;object-fit:cover;margin-left:-15px;">
    <span class="brand-text">SPECANCIENS VMS</span>
  </div>
  <ul class="nav">
    <li><a href="member_dashboard.php" class="is-active"><i class="fa-solid fa-house me-2"></i>Member Dashboard</a></li>
    <li class="has-dropdown">
      <a href="#" onclick="toggleDropdown(event)"><i class="fa-solid fa-edit me-2"></i>Manage Visitors <i class="fa-solid fa-chevron-right ms-auto toggle-icon"></i></a>
      <ul class="dropdown-menu">
        <li><a href="member_new-visitor.php"><i class="fa-solid fa-user-plus me-2"></i>Add Visitor</a></li>
        <li><a href="member_manage_visitors.php"><i class="fa-solid fa-pen me-2"></i>Edit Visitor</a></li>
      </ul>
    </li>
    <li class="has-dropdown">
      <a href="#" onclick="toggleDropdown(event)"><i class="fa-solid fa-calendar-days me-2"></i>Manage Events <i class="fa-solid fa-chevron-right ms-auto toggle-icon"></i></a>
      <ul class="dropdown-menu">
        <li><a href="nostalgia.php"><i class="fa-solid fa-scroll me-2"></i>Nostalgia</a></li>
      </ul>
    </li>
  </ul>
</aside>

<!-- Main -->
<main class="main">
  <!-- Topbar -->
  <div class="topbar">
    <button class="btn btn-soft toggle" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
    <div class="crumbs"><a href="member_dashboard.php">Dashboard</a> › <span>Member</span></div>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted">Welcome, <?php echo htmlspecialchars($name); ?></span>
      <a href="logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="stat"><i class="fa-solid fa-users text-primary"></i><div><div class="fw-semibold">Total Visitors</div><div class="muted"><?php echo $total_visitors; ?></div></div></div></div>
    <div class="col-6 col-md-3"><div class="stat"><i class="fa-solid fa-door-open text-success"></i><div><div class="fw-semibold">Checked In</div><div class="muted"><?php echo $checked_in; ?></div></div></div></div>
   <div class="col-6 col-md-3">
  <div class="stat">
    <i class="fa-solid fa-gift text-warning"></i>
    <div>
      <div class="fw-semibold">Goodies Given</div>
      <form method="POST" class="d-flex align-items-center gap-2 mt-1">
        <input type="number" name="goodies_count" class="form-control form-control-sm" style="width:70px;" min="0" value="<?php echo $goodies_given; ?>" required>
        <button type="submit" name="update_goodies" class="btn btn-sm btn-primary">
          <i class="fa-solid fa-floppy-disk me-1"></i>Save
        </button>
      </form>
      
    </div>
  </div>
</div>

    
  </div>

  <!-- Visitors Table -->
  <div class="row g-3">
    <div class="col-12 col-lg-7">
      <div class="card-lite">
        <div class="card-head"><i class="fa-solid fa-right-left text-primary me-2"></i><span>Visitor In/Out</span></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>Name</th><th>Dept</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
              <tbody>
                <?php while($row=mysqli_fetch_assoc($visitors)){ ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['in_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['out_time'] ?: '—'); ?></td>
                    <td><span class="badge <?php echo $row['out_time']?'text-bg-success':'text-bg-primary'; ?>"><?php echo $row['out_time']?'Checked Out':'Checked In'; ?></span></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
          <a href="member_manage_visitors.php" class="btn btn-sm btn-outline-primary mt-2">View All</a>
        </div>
      </div>
    </div>

    <!-- Inventory -->
    <div class="col-12 col-lg-5">
      <div class="card-lite">
        <div class="card-head"><i class="fa-solid fa-boxes-stacked text-success me-2"></i>Inventory</div>
        <div class="card-body">
         <div class="d-flex justify-content-between mb-2">
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addInventoryModal"><i class="fa-solid fa-plus me-1"></i>Add Item</button>
    <form method="POST" style="display:inline;">
        <button type="submit" name="delete_inventory" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete all inventory?');">
            <i class="fa-solid fa-trash me-1"></i>Delete All
        </button>
    </form>
    <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-1"></i>Refresh</button>
</div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>Item</th><th>Total</th><th>Used</th><th>Remaining</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
                <?php while($item=mysqli_fetch_assoc($inventory)){
                  $total=intval($item['total_stock']); $used=intval($item['used_count']);
                  $remaining=max(0,$total-$used);
                  if($remaining<=0){$badge_class='text-bg-danger';}elseif($remaining<=10){$badge_class='text-bg-warning';}else{$badge_class='text-bg-success';}
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                  <td><?php echo $total; ?></td>
                  <td><?php echo $used; ?></td>
                  <td><?php echo $remaining; ?></td>
                  <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                  <td>
                    <?php if($remaining>0){ ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="openMarkUsedModal('<?php echo $item['id']; ?>','<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>')"><i class="fa-solid fa-box-open me-1"></i>Mark Used</button>
                    <?php }else{ echo '<span class="text-muted">N/A</span>'; } ?>
                  </td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modals -->
<!-- Add Inventory -->
<div class="modal fade" id="addInventoryModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST">
    <div class="modal-header"><h5 class="modal-title">Add Inventory</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-3"><label>Item Name</label><input type="text" name="item_name" class="form-control" required></div>
      <div class="mb-3"><label>Total Stock</label><input type="number" name="total_stock" class="form-control" min="0" required></div>
    </div>
    <div class="modal-footer"><button type="submit" name="add_inventory" class="btn btn-primary">Save</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
  </form>
</div></div></div>

<!-- Mark Used -->
<div class="modal fade" id="markUsedModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST">
    <div class="modal-header"><h5 class="modal-title">Mark Item Used</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <input type="hidden" name="item_id" id="item_id">
      <div class="mb-3"><label>Item Name</label><input type="text" class="form-control" id="item_name" readonly></div>
      <div class="mb-3"><label>Quantity Used</label><input type="number" name="used_quantity" class="form-control" min="1" required></div>
    </div>
    <div class="modal-footer"><button type="submit" name="mark_used" class="btn btn-primary">Save</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button></div>
  </form>
</div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
document.getElementById('toggleSidebar')?.addEventListener('click', e => {sidebar.classList.toggle('show'); e.stopPropagation();});
document.addEventListener('click', e => {if(!sidebar.contains(e.target)) sidebar.classList.remove('show');});

// Dropdowns
function toggleDropdown(e){e.preventDefault(); const menu=e.currentTarget.nextElementSibling; menu.style.display=(menu.style.display==='block')?'none':'block';}

// Mark used modal
function openMarkUsedModal(id,name){
  document.getElementById('item_id').value=id;
  document.getElementById('item_name').value=name;
  new bootstrap.Modal(document.getElementById('markUsedModal')).show();
}
</script>
</body>
</html>
