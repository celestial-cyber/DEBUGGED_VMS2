<?php
session_start();
include __DIR__ . '/../config/connection.php';
include __DIR__ . '/../includes/guard_member.php';

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
    $stmt = $conn->prepare("SELECT * FROM vms_visitors WHERE event_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $visitors_pdf = $stmt->get_result();

    $pdf->SetFont('Arial','',10);
    while($row = $visitors_pdf->fetch_assoc()) {
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
    header("Location: ../index.php");
    exit();
}

// Ensure 'Nostalgia' event exists and get its id
$nostalgia_name = 'Nostalgia';
$stmt = $conn->prepare("SELECT event_id FROM vms_events WHERE event_name = ? LIMIT 1");
$stmt->bind_param("s", $nostalgia_name);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    $stmt = $conn->prepare("INSERT INTO vms_events (event_name, event_date) VALUES (?, NULL)");
    $stmt->bind_param("s", $nostalgia_name);
    $stmt->execute();
    $event_id = $conn->insert_id;
    $stmt->close();
} else {
    $event_id = (int)$event['event_id'];
}

// Note: Removed isGeneratedColumn function as it's no longer needed with simplified schema

// Handle actions: create/update/checkin/checkout
$errors = [];
$success = '';

function clean($s) { return trim($s ?? ''); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $roll_number = clean($_POST['roll_number'] ?? '');
    $year_of_graduation = clean($_POST['year_of_graduation'] ?? '');

    if ($name === '') { 
        $errors[] = 'Name is required.'; 
    }

    if (!$errors) {
        if ($edit_id > 0) {
            // Update visitor using prepared statement
            $stmt = $conn->prepare("UPDATE vms_visitors SET name = ?, email = ?, mobile = ?, department = ?, roll_number = ?, year_of_graduation = ? WHERE id = ? AND event_id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssssii", $name, $email, $phone, $department, $roll_number, $year_of_graduation, $edit_id, $event_id);
                if ($stmt->execute()) { 
                    $success = 'Visitor updated.'; 
                } else { 
                    $errors[] = 'Update failed: ' . $stmt->error; 
                }
                $stmt->close();
            } else {
                $errors[] = 'Update failed: ' . $conn->error;
            }
        } else {
            // Insert new visitor using prepared statement
            $stmt = $conn->prepare("INSERT INTO vms_visitors (event_id, name, email, mobile, department, roll_number, year_of_graduation, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("issssss", $event_id, $name, $email, $phone, $department, $roll_number, $year_of_graduation);
                if ($stmt->execute()) { 
                    $success = 'Visitor registered.'; 
                } else { 
                    $errors[] = 'Registration failed: ' . $stmt->error; 
                }
                $stmt->close();
            } else {
                $errors[] = 'Registration failed: ' . $conn->error;
            }
        }
    }
}

if (isset($_GET['action'], $_GET['id'])) {
    $vid = (int)$_GET['id'];
    if ($_GET['action'] === 'checkin') {
        $stmt = $conn->prepare("UPDATE vms_visitors SET in_time=IFNULL(in_time, NOW()), out_time=NULL WHERE id = ? AND event_id = ?");
        $stmt->bind_param("ii", $vid, $event_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Checked in.';
    } elseif ($_GET['action'] === 'checkout') {
        $stmt = $conn->prepare("UPDATE vms_visitors SET out_time=NOW() WHERE id = ? AND event_id = ? AND in_time IS NOT NULL");
        $stmt->bind_param("ii", $vid, $event_id);
        $stmt->execute();
        $stmt->close();
        $success = 'Checked out.';
    } elseif ($_GET['action'] === 'edit') {
        // handled below by prefill
    }
}

// Prefill for edit
$editing = null;
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $eid = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM vms_visitors WHERE id = ? AND event_id = ? LIMIT 1");
    $stmt->bind_param("ii", $eid, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing = $result->fetch_assoc() ?: null;
    $stmt->close();
}

// Load visitors for Nostalgia
$stmt = $conn->prepare("SELECT * FROM vms_visitors WHERE event_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$visitors = $stmt->get_result();
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
    <a href="member/dashboard.php" class="btn btn-outline-secondary"><i class="fa-solid fa-chevron-left me-1"></i>Back</a>
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