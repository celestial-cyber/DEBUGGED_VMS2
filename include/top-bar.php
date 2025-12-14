<?php
// top-bar.php - Top navigation bar for consistent UI across pages
// Ensure $breadcrumbs is set before including this file
if (!isset($breadcrumbs)) {
    // Default breadcrumbs if not set
    $breadcrumbs = [
        ['url' => 'admin_dashboard.php', 'text' => 'Dashboard'],
        ['text' => 'Current Page']
    ];
}
?>
<div class="topbar">
    <button class="btn btn-soft toggle" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
    <div class="crumbs">
        <?php
        $last_index = count($breadcrumbs) - 1;
        foreach ($breadcrumbs as $index => $crumb) {
            if (isset($crumb['url'])) {
                echo '<a href="' . $crumb['url'] . '">' . $crumb['text'] . '</a>';
            } else {
                echo '<span>' . $crumb['text'] . '</span>';
            }
            if ($index < $last_index) {
                echo '<span>›</span>';
            }
        }
        ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted">Welcome, <?php echo $name; ?></span>
        <button class="btn btn-soft"><i class="fa-regular fa-bell"></i></button>
        <button class="btn btn-soft"><i class="fa-regular fa-circle-question"></i></button>
        <a href="logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
</div>