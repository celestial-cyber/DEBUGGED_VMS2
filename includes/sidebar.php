<?php
// sidebar.php shared between admin and member views
if (!isset($conn)) {
    require_once __DIR__ . '/../config/connection.php';
}
$base_url = $base_url ?? '';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get logged-in user info
$role = $_SESSION['role'] ?? 'member'; // default to member if role not set

// Fetch counts based on role
if ($role === 'admin') {
    $visitor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_visitors WHERE status=1"))['count'];
    $inventory_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_inventory"))['count'];
    $goodies_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_goodies_distribution"))['count'];
    $participation_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_event_participation"))['count'];
    $notes_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_coordinator_notes"))['count'];
} else {
    // For member, you can limit counts or show only their relevant data
    $visitor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM vms_visitors WHERE status=1"))['count'];
}
?>
<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="<?php echo $base_url; ?>/assets/images/SALogo.png" alt="Specanciens Logo" style="width:50px; height:50px; border-radius:8px; object-fit:cover; margin-left:-15px;">
    <span class="brand-text">SPECANCIENS VMS</span>
  </div>
  <ul class="nav">
    <?php if ($role === 'admin'): ?>
        <li><a href="<?php echo $base_url; ?>/admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-house"></i><span>Admin Dashboard</span></a></li>
    <?php else: ?>
        <li><a href="<?php echo $base_url; ?>/member/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-house"></i><span>Member Dashboard</span></a></li>
    <?php endif; ?>

    <li class="section-label">Visitor Management</li>
    <?php if ($role === 'admin'): ?>
        <li><a href="<?php echo $base_url; ?>/public/new-visitor.php" class="<?php echo $current_page == 'new-visitor.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-user-plus"></i><span>New Visitor</span></a></li>
        <li><a href="<?php echo $base_url; ?>/admin/manage-visitors.php" class="<?php echo $current_page == 'manage-visitors.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-users-gear"></i><span>Manage Visitors</span><span class="badge"><?php echo $visitor_count; ?></span></a></li>
    <?php else: ?>
        <li><a href="<?php echo $base_url; ?>/member/member_manage_visitors.php" class="<?php echo $current_page == 'member_manage_visitors.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-users-gear"></i><span>Manage Visitors</span><span class="badge"><?php echo $visitor_count; ?></span></a></li>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <li class="section-label">Inventory Management</li>
    <li><a href="<?php echo $base_url; ?>/actions/add_inventory.php" class="<?php echo $current_page == 'add_inventory.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-boxes"></i><span>Add Inventory</span></a></li>
    <li><a href="<?php echo $base_url; ?>/admin/manage-inventory.php" class="<?php echo $current_page == 'manage-inventory.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-warehouse"></i><span>Manage Inventory</span><span class="badge"><?php echo $inventory_count; ?></span></a></li>

    <li class="section-label">Goodies Distribution</li>
    <li><a href="<?php echo $base_url; ?>/actions/add_goodie.php" class="<?php echo $current_page == 'add_goodie.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-gift"></i><span>Add Goodie</span></a></li>
    <li><a href="<?php echo $base_url; ?>/admin/manage-goodies.php" class="<?php echo $current_page == 'manage-goodies.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-truck"></i><span>Manage Goodies</span><span class="badge"><?php echo $goodies_count; ?></span></a></li>

    <li class="section-label">Event Participation</li>
    <li><a href="<?php echo $base_url; ?>/actions/add_participation.php" class="<?php echo $current_page == 'add_participation.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-calendar-plus"></i><span>Add Participation</span></a></li>
    <li><a href="<?php echo $base_url; ?>/admin/manage-participation.php" class="<?php echo $current_page == 'manage-participation.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-chart-line"></i><span>Manage Participation</span><span class="badge"><?php echo $participation_count; ?></span></a></li>

    <li class="section-label">Coordinator Notes</li>
    <li><a href="<?php echo $base_url; ?>/actions/add_note.php" class="<?php echo $current_page == 'add_note.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-sticky-note"></i><span>Add Note</span></a></li>
    <li><a href="<?php echo $base_url; ?>/admin/manage-notes.php" class="<?php echo $current_page == 'manage-notes.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-clipboard-list"></i><span>Manage Notes</span><span class="badge"><?php echo $notes_count; ?></span></a></li>
    <?php endif; ?>

    <li class="section-label">Event Dashboards</li>
    <li><a href="<?php echo $base_url; ?>/admin/nostalgia_dashboard.php" class="<?php echo $current_page == 'nostalgia_dashboard.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-scroll"></i><span>Nostalgia</span></a></li>
    <li><a href="<?php echo $base_url; ?>/member/member_notes.php"><i class="fa-regular fa-note-sticky"></i><span>Notes</span></a></li>
    <li><a href="#"><i class="fa-solid fa-microphone-lines"></i><span>Alumni Talks</span></a></li>
    <li><a href="#"><i class="fa-solid fa-graduation-cap"></i><span>Induction Program</span></a></li>
    <li><a href="#"><i class="fa-solid fa-briefcase"></i><span>Mock Interviews</span></a></li>
    <?php if ($role !== 'admin'): ?>
    <li class="section-label">Notes</li>
    <li><a href="<?php echo $base_url; ?>/member/member_notes.php" class="<?php echo $current_page == 'member_notes.php' ? 'is-active' : ''; ?>"><i class="fa-solid fa-note-sticky"></i><span>Coordinator Notes</span></a></li>
    <?php endif; ?>
  </ul>
</aside>
