<?php
session_start();
include 'connection.php';
include 'include/guard_member.php';

// Redirect if not logged in
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

// ---------------------- CSV IMPORT ----------------------
if (isset($_POST['import'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    if (($handle = fopen($file, "r")) !== FALSE) {
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if ($row == 0) { $row++; continue; } // skip header
            if (count($data) < 2) continue; // skip invalid rows

            // Truncate full_name and phone to match DB limits
            $full_name = mysqli_real_escape_string($conn, substr($data[0] ?? '', 0, 120));
            $email = mysqli_real_escape_string($conn, $data[1] ?? '');
            $phone = mysqli_real_escape_string($conn, substr($data[2] ?? '', 0, 30));
            $department = mysqli_real_escape_string($conn, $data[3] ?? '');
            $gender = in_array($data[4] ?? '', ['Male','Female','Other']) ? $data[4] : NULL;
            $year = is_numeric($data[5] ?? null) ? $data[5] : NULL;
            $goodies_taken = is_numeric($data[6] ?? null) ? $data[6] : 0;

            $sql = "INSERT INTO tbl_visitors 
                (event_id, full_name, email, phone, department, gender, year_of_graduation, goodies_taken, created_at) 
                VALUES 
                ($event_id, '$full_name', '$email', '$phone', '$department', " . ($gender ? "'$gender'" : "NULL") . ", " . ($year ? "'$year'" : "NULL") . ", $goodies_taken, NOW())";

            mysqli_query($conn, $sql);
            $row++;
        }
        fclose($handle);
    }

    $_SESSION['success'] = "CSV imported successfully!";
    header("Location: nostalgia.php");
    exit();
}

// ---------------------- FETCH VISITORS ----------------------
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE event_id=$event_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nostalgia — Visitor Registration</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
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

  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success py-2 mb-3"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <div class="card-lite mb-3">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-user-plus text-primary"></i>
        <span class="fw-semibold">Register Visitor</span>
      </div>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Full Name<span class="text-danger"> *</span></label>
          <input type="text" name="name" class="form-control" maxlength="120" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" maxlength="30">
        </div>
        <div class="col-md-4">
          <label class="form-label">Department</label>
          <input type="text" name="department" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">Select</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Year of Graduation</label>
          <input type="number" name="year_of_graduation" class="form-control" min="1900" max="2099">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary" name="register">Register</button>
        </div>
      </form>

      <hr>

      <form action="" method="post" enctype="multipart/form-data" class="mt-2">
        <label>Select CSV file:</label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="import" class="btn btn-sm btn-outline-primary">Import CSV</button>
      </form>
    </div>
  </div>

  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-users text-success"></i>
        <span class="fw-semibold">Registered Visitors</span>
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
              <th>Gender</th>
              <th>Year</th>
              <th>Goodies Taken</th>
              <th>In</th>
              <th>Out</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($visitors)) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['full_name']); ?></td>
              <td><?php echo htmlspecialchars($row['email'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['phone'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['department'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['gender'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['year_of_graduation'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['goodies_taken'] ?? 0); ?></td>
              <td><?php echo htmlspecialchars($row['in_time'] ?: '—'); ?></td>
              <td><?php echo htmlspecialchars($row['out_time'] ?: '—'); ?></td>
              <td>
                <span class="badge rounded-pill <?php echo $row['out_time'] ? 'text-bg-success' : 'text-bg-primary'; ?>">
                  <?php echo $row['out_time'] ? 'Checked Out' : ($row['in_time'] ? 'Checked In' : 'New'); ?>
                </span>
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
