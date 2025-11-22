<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include ('connection.php');
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id))
{
    header("Location: index.php");
    exit();
}

// ----- POST handlers (centralized) -----
// Handle visitor deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_visitor_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $del_id = intval($_POST['delete_visitor_id']);
    if ($del_id > 0) {
        $dstmt = $conn->prepare("DELETE FROM vms_visitors WHERE id = ?");
        if ($dstmt) {
            $dstmt->bind_param('i', $del_id);
            $dstmt->execute();
            $dstmt->close();
        }
    }
    header('Location: manage-visitors.php');
    exit();
}

// Handle registration deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reg_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $reg_id = intval($_POST['delete_reg_id']);
    if ($reg_id > 0) {
        $rstmt = $conn->prepare("DELETE FROM event_registrations WHERE id = ?");
        if ($rstmt) {
            $rstmt->bind_param('i', $reg_id);
            $rstmt->execute();
            $rstmt->close();
        }
    }
    header('Location: manage-visitors.php');
    exit();
}

// Pagination defaults
$perPage = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
?>
<?php include('include/header.php'); ?>

<!-- Content -->
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="title-row">
            <span class="chip"><i class="fa-solid fa-users-gear text-primary"></i> Visitors</span>
            <h2>👥 Manage Visitors</h2>
            <span class="badge">Live Data</span>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="location.href='new-visitor.php'"><i class="fa-solid fa-plus me-2"></i>Add Visitor</button>
            <button class="btn btn-success" onclick="location.href='excel-import.php'"><i class="fa-solid fa-file-excel me-2"></i>Import Excel</button>
            <button class="btn btn-info" onclick="location.href='search-excel.php'"><i class="fa-solid fa-search me-2"></i>Search Excel</button>
            <button class="btn btn-outline-primary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Refresh</button>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card-lite mb-3">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-filter text-primary"></i>
                <span class="fw-semibold">Filter Visitors</span>
            </div>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Roll Number</label>
                    <input type="text" class="form-control" id="roll_number" name="roll_number">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Department</label>
                    <select class="form-control" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php
                        $fetch_department = mysqli_query($conn, "select * from vms_department");
                        while($row = mysqli_fetch_array($fetch_department)){
                        ?>
                            <option value="<?php echo $row['department']; ?>"><?php echo $row['department']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Name (Partial Match)</label>
                    <input type="text" class="form-control" id="name" name="name">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Year of Graduation</label>
                    <input type="number" class="form-control" id="year" name="year_of_graduation">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Registration Type</label>
                    <select class="form-control" id="registration_type" name="registration_type">
                        <option value="">All Types</option>
                        <option value="beforehand">Beforehand</option>
                        <option value="spot">Spot</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <button type="submit" name="srh-btn" class="btn btn-primary w-100"><i class="fa-solid fa-search me-2"></i>Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Visitors Table -->
    <div class="card-lite">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-users text-primary"></i>
                <span class="fw-semibold">Visitors List</span>
            </div>
            <span class="text-muted"><?php echo isset($search_query) ? 'Filtered Results' : 'All Visitors'; ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(isset($_REQUEST['srh-btn']))
                        {
                            // Build WHERE clause and params
                            $where = " WHERE 1=1";
                            $params = [];
                            $types = '';

                            $dept = $_POST['department'] ?? '';
                            $roll_number = $_POST['roll_number'] ?? '';
                            $name = $_POST['name'] ?? '';
                            $year = $_POST['year_of_graduation'] ?? '';
                            $registration_type = $_POST['registration_type'] ?? '';

                            // normalize registration type values from UI to stored values
                            if ($registration_type === 'beforehand') { $registration_type = 'regular'; }
                            elseif ($registration_type === 'spot') { $registration_type = 'spot_entry'; }

                            if (!empty($dept)) { $where .= " AND v.department = ?"; $params[] = $dept; $types .= 's'; }
                            if (!empty($roll_number)) { $where .= " AND v.roll_number = ?"; $params[] = $roll_number; $types .= 's'; }
                            if (!empty($name)) { $where .= " AND v.name LIKE ?"; $params[] = '%' . $name . '%'; $types .= 's'; }
                            if (!empty($year)) { $where .= " AND v.year_of_graduation = ?"; $params[] = $year; $types .= 'i'; }
                            if (!empty($registration_type)) { $where .= " AND (v.visitor_type = ? OR v.registration_type = ?)"; $params[] = $registration_type; $params[] = $registration_type; $types .= 'ss'; }

                            // Count total rows for pagination
                            $countSql = "SELECT COUNT(*) as cnt FROM vms_visitors v LEFT JOIN vms_admin a ON v.added_by = a.id LEFT JOIN vms_members m ON v.added_by = m.id" . $where;
                            $totalRows = 0;
                            $cstmt = $conn->prepare($countSql);
                            if ($cstmt) {
                                if (!empty($params)) { $cstmt->bind_param($types, ...$params); }
                                $cstmt->execute();
                                $cres = $cstmt->get_result();
                                if ($cres) { $crow = $cres->fetch_assoc(); $totalRows = (int)$crow['cnt']; }
                                $cstmt->close();
                            }

                            // Main select with ordering and limit
                            $sql = "SELECT v.*, COALESCE(a.user_name, m.member_name) AS added_by_name FROM vms_visitors v LEFT JOIN vms_admin a ON v.added_by = a.id LEFT JOIN vms_members m ON v.added_by = m.id" . $where . " ORDER BY v.roll_number ASC, v.created_at DESC LIMIT $offset,$perPage";

                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                echo "<tr><td colspan=9 class='text-danger'>Query prepare failed: " . htmlspecialchars($conn->error) . "</td></tr>";
                            } else {
                                if (!empty($params)) { $stmt->bind_param($types, ...$params); }
                                $stmt->execute();
                                $search_query = $stmt->get_result();

                                $sn = $offset + 1;
                                while($row = mysqli_fetch_array($search_query))
                                { ?>
                                <tr>
                                    <td><?php echo $sn; ?></td>
                                    <td><?php echo htmlspecialchars($row['roll_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status']==1 ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-danger-subtle text-danger border border-danger'; ?>">
                                            <?php echo $row['status']==1 ? 'In' : 'Out'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit-visitor.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-pencil me-1"></i><?php echo $row['status']==1 ? 'Edit' : 'View'; ?>
                                            </a>
                                            <form method="POST" action="manage-visitors.php" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="delete_visitor_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                            <?php // deletion is handled centrally at top of page ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php $sn++; }
                            }
                            $stmt->close();
                        } else {
                            if(isset($_POST['delete_visitor_id'])){
                                $id_to_delete = $_POST['delete_visitor_id'];
                                $stmt = $conn->prepare("DELETE FROM vms_visitors WHERE id=?");
                                $stmt->bind_param("i", $id_to_delete);
                                if ($stmt->execute()) {
                                    echo "<script>alert('Visitor deleted successfully');</script>";
                                    echo "<script>window.location.href='manage-visitors.php';</script>";
                                } else {
                                    echo "<script>alert('Error deleting visitor: " . $stmt->error . "');</script>";
                                }
                                $stmt->close();
                            }
                            // Default (non-filtered) listing with pagination
                            $countRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM vms_visitors");
                            $totalRows = 0;
                            if ($countRes) {
                                $crow = mysqli_fetch_assoc($countRes);
                                $totalRows = (int)$crow['cnt'];
                            }
                            $totalPages = max(1, (int)ceil($totalRows / $perPage));

                            $select_query = mysqli_query($conn, "SELECT v.*, COALESCE(a.user_name, m.member_name) AS added_by_name FROM vms_visitors v LEFT JOIN vms_admin a ON v.added_by = a.id LEFT JOIN vms_members m ON v.added_by = m.id ORDER BY v.roll_number ASC, v.created_at DESC LIMIT $offset,$perPage");
                            $sn = $offset + 1;
                            while($row = mysqli_fetch_array($select_query))
                            {
                            ?>
                                <tr>
                                    <td><?php echo $sn; ?></td>
                                    <td><?php echo htmlspecialchars($row['roll_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo isset($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status']==1 ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-danger-subtle text-danger border border-danger'; ?>">
                                            <?php echo $row['status']==1 ? 'In' : 'Out'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo isset($row['added_by_name']) ? htmlspecialchars($row['added_by_name']) : 'N/A'; ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="edit-visitor.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-pencil me-1"></i><?php echo $row['status']==1 ? 'Edit' : 'View'; ?>
                                            </a>
                                            <form method="POST" action="manage-visitors.php" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="delete_visitor_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php $sn++; } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="muted">
                    Showing <?php if (!empty($totalRows)) { echo ($offset+1) . ' - ' . min($offset+$perPage, $totalRows) . ' of ' . $totalRows; } else { echo '0 visitors'; } ?>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Export</button>
                    <?php if (!isset($_REQUEST['srh-btn']) && !empty($totalPages) && $totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Registrations Table -->
    <div class="card-lite mt-4">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-calendar-check text-primary"></i>
                <span class="fw-semibold">Event Registrations</span>
            </div>
            <span class="text-muted">All Event Registrations</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Event</th>
                            <th>Event Date</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $select_registrations = mysqli_query($conn, "SELECT * FROM event_registrations ORDER BY created_at DESC");
                        $sn_reg = 1;
                        while($row_reg = mysqli_fetch_array($select_registrations))
                        {
                        ?>
                            <tr>
                                <td><?php echo $sn_reg; ?></td>
                                <td><?php echo htmlspecialchars($row_reg['name']); ?></td>
                                <td><?php echo htmlspecialchars($row_reg['email']); ?></td>
                                <td><?php echo htmlspecialchars($row_reg['event']); ?></td>
                                <td><?php echo !empty($row_reg['event_date']) ? date('M j, Y', strtotime($row_reg['event_date'])) : 'N/A'; ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($row_reg['created_at'])); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="POST" action="manage-visitors.php" onsubmit="return confirmDeleteRegistration()">
                                            <input type="hidden" name="delete_reg_id" value="<?php echo $row_reg['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fa-solid fa-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php $sn_reg++; }
                        if($sn_reg == 1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fa-solid fa-calendar-plus fa-2x mb-2"></i>
                                    <p>No event registrations found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Handle registration deletion
if(isset($_POST['delete_reg_id'])){
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $reg_id = filter_input(INPUT_POST, 'delete_reg_id', FILTER_VALIDATE_INT);
    if (!$reg_id) {
        die("Invalid registration ID");
    }
    $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id=?");
    $stmt->bind_param("i", $reg_id);
    if($stmt->execute()){
        echo "<script>alert('Registration deleted successfully');</script>";
        echo "<script>window.location.href='manage-visitors.php';</script>";
    } else {
        echo "<script>alert('Error deleting registration: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<?php include('include/footer.php'); ?>
<script>
function confirmDelete(){
    return confirm('Are you sure want to delete this Visitor?');
}
function confirmDeleteRegistration(){
    return confirm('Are you sure want to delete this event registration?');
}
</script>