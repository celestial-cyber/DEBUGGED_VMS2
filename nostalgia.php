<?php
session_start();
include 'connection.php';
include 'include/guard_member.php';

// PDF Export for Nostalgia visitors
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require('fpdf/fpdf.php'); // Include FPDF library

    $pdf = new FPDF('L','mm','A4'); // Landscape, mm, A4
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Nostalgia Visitors List',0,1,'C');
    $pdf->Ln(5);

    // Table Header
    $pdf->SetFont('Arial','B',11);
    $pdf->Cell(50,10,'Name',1);
    $pdf->Cell(50,10,'Email',1);
    $pdf->Cell(30,10,'Phone',1);
    $pdf->Cell(40,10,'Department',1);
    $pdf->Cell(30,10,'Roll No.',1);
    $pdf->Cell(25,10,'Year',1);    
    $pdf->Cell(30,10,'In Time',1);
    $pdf->Cell(30,10,'Out Time',1);
    $pdf->Cell(30,10,'Status',1);
    $pdf->Ln();

    // Fetch visitors for Nostalgia
    $visitors_pdf = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE event_id=$event_id ORDER BY created_at DESC");

    $pdf->SetFont('Arial','',10);
    while($row = mysqli_fetch_assoc($visitors_pdf)) {
        $pdf->Cell(50,8,$row['name'],1);
        $pdf->Cell(50,8,$row['email'] ?: '-',1);
        $pdf->Cell(30,8,$row['phone'] ?: '-',1);
        $pdf->Cell(40,8,$row['department'] ?: '-',1);
        $pdf->Cell(30,8,$row['roll_number'] ?: '-',1);
        $pdf->Cell(25,8,$row['year_of_graduation'] ?: '-',1);
        $pdf->Cell(30,8,$row['in_time'] ?: '-',1);
        $pdf->Cell(30,8,$row['out_time'] ?: '-',1);
        $status = $row['out_time'] ? 'Checked Out' : ($row['in_time'] ? 'Checked In' : 'New');
        $pdf->Cell(30,8,$status,1);
        $pdf->Ln();
    }

    $pdf->Output('D','nostalgia_visitors.pdf'); // Force download
    exit;
}




if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Ensure 'Nostalgia' event exists and get its id
$nostalgia_name = 'Nostalgia';
$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT event_id FROM tbl_events WHERE event_name='" . mysqli_real_escape_string($conn, $nostalgia_name) . "' LIMIT 1"));
if (!$event) {
    mysqli_query($conn, "INSERT INTO tbl_events (event_name, event_date) VALUES ('" . mysqli_real_escape_string($conn, $nostalgia_name) . "', NULL)");
    $event_id = mysqli_insert_id($conn);
} else {
    $event_id = (int)$event['event_id'];
}

// Helper to detect if a column is generated (so we must not set it explicitly)
function isGeneratedColumn(mysqli $conn, string $table, string $column): bool {
    $dbRes = mysqli_query($conn, "SELECT DATABASE() as db");
    $dbRow = mysqli_fetch_assoc($dbRes);
    $db = $dbRow['db'];
    $q = sprintf(
        "SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='%s' AND COLUMN_NAME='%s' LIMIT 1",
        mysqli_real_escape_string($conn, $db),
        mysqli_real_escape_string($conn, $table),
        mysqli_real_escape_string($conn, $column)
    );
    $res = mysqli_query($conn, $q);
    if (!$res) return false;
    $row = mysqli_fetch_assoc($res);
    if (!$row) return false;
    return stripos($row['EXTRA'] ?? '', 'GENERATED') !== false;
}

// Handle actions: create/update/checkin/checkout
$errors = [];
$success = '';

function clean($s) { return trim($s ?? ''); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = mysqli_real_escape_string($conn, clean($_POST['name']));
    $email = mysqli_real_escape_string($conn, clean($_POST['email']));
    $phone = mysqli_real_escape_string($conn, clean($_POST['phone']));
    $department = mysqli_real_escape_string($conn, clean($_POST['department']));
    $roll_number = mysqli_real_escape_string($conn, clean($_POST['roll_number']));
    $year_of_graduation = mysqli_real_escape_string($conn, clean($_POST['year_of_graduation']));


    if ($name === '') { $errors[] = 'Name is required.'; }

    if (!$errors) {
        if ($edit_id > 0) {
            $sets = [];
            if (!isGeneratedColumn($conn, 'tbl_visitors', 'name')) { $sets[] = "name='$name'"; }
            // also update full_name if exists
            $hasFullName = false;
            $probeFull = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_visitors' AND COLUMN_NAME='full_name' LIMIT 1");
            if ($probeFull && mysqli_num_rows($probeFull) > 0) { $hasFullName = true; }
            if ($hasFullName) { $sets[] = "full_name='$name'"; }
            $sets[] = "email='$email'";
            // schema uses 'mobile' in setup.sql; use both to be safe
            $probeMobile = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_visitors' AND COLUMN_NAME='mobile' LIMIT 1");
            if ($probeMobile && mysqli_num_rows($probeMobile) > 0) {
                $sets[] = "mobile='$phone'";
            } else {
                $sets[] = "phone='$phone'";
            }
            $sets[] = "department='$department'";
            $sets[] = "roll_number='$roll_number'";
            $sets[] = "year_of_graduation='$year_of_graduation'";

            $sql = "UPDATE tbl_visitors SET " . implode(',', $sets) . " WHERE id=$edit_id AND event_id=$event_id";
            if (mysqli_query($conn, $sql)) { $success = 'Visitor updated.'; }
            else { $errors[] = 'Update failed.'; }
        } else {
            $columns = ["event_id","email","department","created_at"];
            $values  = ["$event_id","'$email'","'$department'","NOW()"];

            $columns[] = "roll_number";
            $values[]  = "'$roll_number'";
            $columns[] = "year_of_graduation";
            $values[]  = "'$year_of_graduation'";

            if (!isGeneratedColumn($conn, 'tbl_visitors', 'name')) { $columns[] = 'name'; $values[] = "'$name'"; }
            // include full_name if present
            $probeFull = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_visitors' AND COLUMN_NAME='full_name' LIMIT 1");
            if ($probeFull && mysqli_num_rows($probeFull) > 0) { $columns[] = 'full_name'; $values[] = "'$name'"; }
            // handle phone/mobile
            $hasMobile = false;
            $probe = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='tbl_visitors' AND COLUMN_NAME='mobile' LIMIT 1");
            if ($probe && mysqli_num_rows($probe) > 0) { $hasMobile = true; }
            if ($hasMobile) { $columns[] = 'mobile'; $values[] = "'$phone'"; }
            else { $columns[] = 'phone'; $values[] = "'$phone'"; }
            $sql = "INSERT INTO tbl_visitors (".implode(',', $columns).") VALUES (".implode(',', $values).")";
            if (mysqli_query($conn, $sql)) { $success = 'Visitor registered.'; }
            else { $errors[] = 'Registration failed.'; }
        }
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $vid = (int)$_GET['id'];
    if ($_GET['action'] === 'checkin') {
        mysqli_query($conn, "UPDATE tbl_visitors SET in_time=IFNULL(in_time, NOW()), out_time=NULL WHERE id=$vid AND event_id=$event_id");
        $success = 'Checked in.';
    } elseif ($_GET['action'] === 'checkout') {
        mysqli_query($conn, "UPDATE tbl_visitors SET out_time=NOW() WHERE id=$vid AND event_id=$event_id AND in_time IS NOT NULL");
        $success = 'Checked out.';
    } elseif ($_GET['action'] === 'edit') {
        // handled below by prefill
    }
}

// Prefill for edit
$editing = null;
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $eid = (int)$_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE id=$eid AND event_id=$event_id LIMIT 1");
    $editing = mysqli_fetch_assoc($res) ?: null;
}

// Load visitors for Nostalgia
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE event_id=$event_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Nostalgia — Visitor Registration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <style>
    body{background:#f6f7fb}
    .container-narrow{max-width:1100px}
    .card-lite{background:#fff; border:1px solid #e5e7eb; border-radius:14px}
    .card-head{padding:16px 18px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between}
    .card-body{padding:16px 18px}
    .badge-soft{background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff}
  </style>
  </head>
<body>

<div class="container container-narrow py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="m-0">Nostalgia — Visitor Management</h3>
    <a href="member_dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-chevron-left me-1"></i>Back</a>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger py-2 mb-3">
      <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
    </div>
  <?php elseif ($success): ?>
    <div class="alert alert-success py-2 mb-3"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <div class="card-lite mb-3">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-user-plus text-primary"></i>
        <span class="fw-semibold"><?php echo $editing ? 'Edit Visitor' : 'Register Visitor'; ?></span>
      </div>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>"><?php endif; ?>
        <div class="col-md-4">
          <label class="form-label">Full Name<span class="text-danger"> *</span></label>
          <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($editing['name'] ?? ''); ?>">
        </div>

        <!-- ADD BELOW FULL NAME FIELD -->
<div class="col-md-4">
  <label class="form-label">Roll Number</label>
  <input type="text" name="roll_number" class="form-control" 
         value="<?php echo htmlspecialchars($editing['roll_number'] ?? ''); ?>">
</div>

<div class="col-md-4">
  <label class="form-label">Year of Graduation</label>
  <input type="number" name="year_of_graduation" class="form-control" 
         placeholder="e.g. 2022"
         min="1980" max="<?php echo date('Y') + 1; ?>"
         value="<?php echo htmlspecialchars($editing['year_of_graduation'] ?? ''); ?>">
</div>
<!-- END -->

        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editing['email'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editing['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($editing['department'] ?? ''); ?>">
        </div>


        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i><?php echo $editing ? 'Save Changes' : 'Register'; ?></button>
          <?php if ($editing): ?>
            <a href="nostalgia.php" class="btn btn-outline-secondary">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-users text-success"></i>
        <span class="fw-semibold">Registered Visitors</span>
      </div>
      <div class="d-flex gap-2">
        <a href="export_visitors.php?event=Nostalgia" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-csv me-1"></i>Export CSV</a>
        <a href="export_visitors_excel.php?event=Nostalgia" class="btn btn-sm btn-outline-success"><i class="fa-regular fa-file-excel me-1"></i>Export Excel</a>
       <a href="export_visitors_pdf.php?event=Nostalgia&department=<?php echo urlencode($dept ?? ''); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
  <i class="fa-regular fa-file-pdf me-1"></i>Export PDF
</a>

      </div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Department</th>
              <th>RollNo.</th>
              <th>Year</th>
              <th>In</th>
              <th>Out</th>
              <th>Status</th>
              <th style="width:180px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($visitors)) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><?php echo htmlspecialchars($row['email'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['department'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['roll_number'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['year_of_graduation'] ?: '—'); ?></td>

              <td><?php echo htmlspecialchars($row['in_time'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['out_time'] ?: '—'); ?></td>
              <td>
                <span class="badge rounded-pill <?php echo $row['out_time'] ? 'text-bg-success' : 'text-bg-primary'; ?>">
                  <?php echo $row['out_time'] ? 'Checked Out' : ($row['in_time'] ? 'Checked In' : 'New'); ?>
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a class="btn btn-outline-secondary" href="nostalgia.php?action=edit&id=<?php echo (int)$row['id']; ?>"><i class="fa-regular fa-pen-to-square"></i></a>
                  <?php if (!$row['in_time']): ?>
                    <a class="btn btn-outline-primary" href="nostalgia.php?action=checkin&id=<?php echo (int)$row['id']; ?>"><i class="fa-solid fa-door-open"></i></a>
                  <?php elseif (!$row['out_time']): ?>
                    <a class="btn btn-outline-success" href="nostalgia.php?action=checkout&id=<?php echo (int)$row['id']; ?>"><i class="fa-solid fa-door-closed"></i></a>
                  <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled><i class="fa-regular fa-circle-check"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>