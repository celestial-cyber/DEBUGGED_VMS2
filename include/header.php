<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Ensure a CSRF token exists for forms
if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  } catch (Exception $e) {
    // fallback
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
  }
}
$role = $_SESSION['role'] ?? 'member';
$name = $_SESSION['name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Visitors Management System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>

  <style>
    :root{
      --bg:#f6f7fb;
      --card:#ffffff;
      --ink:#1f2937;
      --ink-soft:#6b7280;
      --edge:#e5e7eb;
      --brand:#2563eb;
      --accent:#10b981;
      --warn:#f59e0b;
      --danger:#ef4444;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      background:var(--bg);
      color:var(--ink);
      font-family:'Poppins',sans-serif;
      font-size:15px;
    }

    /* Sidebar (default admin dark) */
    .sidebar{
      position:fixed; inset:0 auto 0 0;
      width:260px; background:#0f172a; color:#fff;
      border-right:1px solid rgba(255,255,255,0.06);
      display:flex; flex-direction:column;
    }
    .brand{
      padding:18px 20px; border-bottom:1px solid rgba(255,255,255,0.08);
      display:flex; align-items:center; gap:10px; font-weight:600;
    }
    .brand .logo{display:inline-flex; width:34px; height:34px; border-radius:8px; background:#1d4ed8; align-items:center; justify-content:center}
    .nav{ list-style:none; padding:10px 10px 24px; margin:0; overflow:auto; }
    .nav a{
      display:flex; gap:12px; align-items:center;
      padding:10px 12px; margin:4px 0; color:#cbd5e1; text-decoration:none;
      border-radius:8px; transition:all .15s ease;
    }
    .nav a:hover{background:rgba(255,255,255,0.06); color:#fff}
    .nav .section-label{ font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8; padding:12px 12px 6px; margin-top:6px; }
    .nav .is-active{background:#1e293b; color:#fff}

    /* Member theme (light purple) */
    .member-theme .sidebar{ background:#f3e8ff; color:#3b0764; border-right:1px solid #e9d5ff; }
    .member-theme .brand{ border-bottom:1px solid #e9d5ff; }
    .member-theme .brand .logo{ background:#7c3aed; }
    .member-theme .nav a{ color:#5b21b6; }
    .member-theme .nav a:hover{ background:#ede9fe; color:#3b0764; }
    .member-theme .nav .section-label{ color:#7c3aed; }
    .member-theme .nav .is-active{ background:#e9d5ff; color:#3b0764; }

    /* Main */
    .main{
      margin-left:260px; padding:22px;
      min-height:100vh;
    }

    /* Cards */
    .card-lite{
      background:var(--card); border:1px solid var(--edge);
      border-radius:14px; box-shadow:0 2px 10px rgba(16,24,40,.04);
    }
    .card-head{
      padding:16px 18px; border-bottom:1px solid var(--edge);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .card-body{ padding:16px 18px; }
    .chip{
      display:inline-flex; align-items:center; gap:8px;
      padding:6px 10px; border-radius:20px; font-size:12px; border:1px solid var(--edge); background:#fff;
    }
    .chip i{opacity:.85}

    /* Tables */
    .table thead th{color:#6b7280; font-weight:600; background:#fafafa}
    .table td, .table th{vertical-align:middle}
    .stat{
      display:flex; align-items:center; gap:10px;
      padding:14px; border-radius:14px; background:var(--card);
      border:1px solid var(--edge);
    }
    .stat i{font-size:18px}
    .muted{color:var(--ink-soft)}
    .btn-soft{
      border:1px solid var(--edge); background:#fff; color:var(--ink);
    }
    .btn-soft:hover{border-color:#cbd5e1; background:#f9fafb}
    .btn-primary{background:var(--brand); border-color:var(--brand)}
    .btn-primary:hover{background:#1d4ed8; border-color:#1d4ed8}

    /* Topbar */
    .topbar{
      display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;
    }
    .crumbs{
      display:flex; align-items:center; gap:8px; color:var(--ink-soft); font-size:13px;
    }
    .crumbs a{ color:var(--ink-soft); text-decoration:none }
    .crumbs a:hover{ text-decoration:underline }
    .title-row{ display:flex; align-items:center; gap:12px; }
    .title-row h2{ margin:0; font-weight:600; font-size:22px; }
    .title-row .badge{
      background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff;
      padding:6px 10px; border-radius:10px; font-weight:500;
    }

    /* Responsive */
    @media (max-width: 992px){
      .sidebar{transform:translateX(-100%); transition:transform .2s ease}
      .sidebar.show{transform:translateX(0)}
      .main{margin-left:0}
      .topbar .toggle{display:inline-flex}
    }
    @media (min-width: 993px){
      .topbar .toggle{display:none}
    }
  </style>
</head>
<body class="<?php echo ($role==='member') ? 'member-theme' : ''; ?>">

  <!-- Sidebar -->
  <?php include('side-bar.php'); ?>






  <!-- Main -->
  <main class="main">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-soft toggle" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
      <div class="crumbs">
        <a href="<?php echo ($role==='member') ? 'member_dashboard.php' : 'admin_dashboard.php'; ?>">Dashboard</a>
        <span>›</span>
        <span><?php echo basename($_SERVER['PHP_SELF'], '.php'); ?></span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted">Welcome, <?php echo htmlspecialchars($name); ?></span>
        <button class="btn btn-soft"><i class="fa-regular fa-bell"></i></button>
        <button class="btn btn-soft"><i class="fa-regular fa-circle-question"></i></button>
        <a href="logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i></a>
      </div>
    </div>
