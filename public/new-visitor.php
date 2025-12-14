<?php
session_start();

// Include the connection file to establish the database connection
include __DIR__ . '/../config/connection.php';

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch departments
$departments = $conn->query("SELECT department FROM vms_department WHERE status = 1");

// Fetch events
$events = $conn->query("SELECT event_id, event_name FROM vms_events");

// Initialize variables
$popup_message = '';
$popup_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbt-vstr'])) {
    
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $popup_message = 'Invalid CSRF token. Please refresh the page and try again.';
        $popup_type = 'danger';
    } else {
        // Retrieve and sanitize form data (null-coalescing to avoid passing null to htmlspecialchars)
        $first_name = htmlspecialchars($_POST['first_name'] ?? '');
        $last_name = htmlspecialchars($_POST['last_name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($_POST['phone'] ?? '');
        $address = htmlspecialchars($_POST['address'] ?? '');
        $department = htmlspecialchars($_POST['department'] ?? '');
        $gender = htmlspecialchars($_POST['gender'] ?? '');
        $year = htmlspecialchars($_POST['year_of_graduation'] ?? '');
        $event_id = (int)($_POST['event_id'] ?? 0);

        // Merge first + last name into full_name
        $full_name = trim($first_name . ' ' . $last_name);

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($phone)) {
            $popup_message = 'Please fill in all required fields.';
            $popup_type = 'warning';
        } else {
            // ✅ Insert into correct columns
            $stmt = $conn->prepare("INSERT INTO vms_visitors 
                (event_id, name, email, mobile, address, department, gender, year_of_graduation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param(
                    "isssssss",
                    $event_id,
                    $full_name,
                    $email,
                    $phone,
                    $address,
                    $department,
                    $gender,
                    $year
                );

                if ($stmt->execute()) {
                    $popup_message = "Visitor registered successfully!";
                    $popup_type = "success";
                    $_POST = array(); // clear form
                } else {
                    $popup_message = "Error saving visitor: " . $stmt->error;
                    $popup_type = "danger";
                }

                $stmt->close();
            } else {
                $popup_message = "Error preparing statement: " . $conn->error;
                $popup_type = "danger";
            }
        }
    }
}


// Log any errors to error.log
if (!empty($popup_message) && $popup_type === 'danger') {
    error_log($popup_message);
}

// Ensure a CSRF token exists for the form (generate if missing)
if (empty($_SESSION['csrf_token'])) {
    // Use random_bytes when available for secure tokens
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        // Fallback to less-preferred but available method
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
}

?>
<!-- HTML Form -->
<div class="container">
    <!-- Add CSS link here -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/sb-admin.min.css">
    <link rel="stylesheet" href="plugins/animate.min.css">
    <link rel="stylesheet" href="plugins/metisMenu.min.css">
    <link rel="stylesheet" href="plugins/bootstrap-select.min.css">
    
    <div class="row g-4">
        <div class="col-12">
            <h2 class="text-primary mb-4">Visitor Registration</h2>
            
            <?php if ($popup_message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($popup_type ?? 'info'); ?> mt-3">
                    <?php echo htmlspecialchars($popup_message ?? ''); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="post" class="needs-validation" novalidate>
    <!-- Hidden CSRF token -->
    <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">
        
        <!-- Form Fields -->
        <div class="row g-4">
            <!-- First Name -->
            <div class="col-12 col-md-6">
                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" id="first_name" class="form-control" required>
            </div>
            
            <!-- Last Name -->
            <div class="col-12 col-md-6">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" name="last_name" id="last_name" class="form-control">
            </div>
            
            <!-- Email -->
            <div class="col-12 col-md-6">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            
            <!-- Phone -->
            <div class="col-12 col-md-6">
                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                <input type="tel" name="phone" id="phone" class="form-control" placeholder="+91 9876543210" required>
            </div>
            
            <!-- Address -->
            <div class="col-12">
                <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                <textarea name="address" id="address" class="form-control" required></textarea>
            </div>
            
            <!-- Department -->
            <div class="col-12 col-md-6">
                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                <select name="department" id="department" class="form-control" required>
                    <?php 
                    // Reset the departments result pointer
                    mysqli_data_seek($departments, 0);
                    while ($dept = mysqli_fetch_assoc($departments)) { 
                    ?>
                        <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Event -->
            <div class="col-12 col-md-6">
                <label for="event_id" class="form-label">Event <span class="text-danger">*</span></label>
                <select name="event_id" id="event_id" class="form-control" required>
                    <?php 
                    // Reset the events result pointer
                    mysqli_data_seek($events, 0);
                    while ($event = mysqli_fetch_assoc($events)) { 
                    ?>
                        <option value="<?php echo $event['event_id']; ?>"><?php echo $event['event_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Gender -->
            <div class="col-12 col-md-6">
                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                <select name="gender" id="gender" class="form-control" required>
                    <?php $genders = ['Male', 'Female', 'Other']; ?>
                    <?php foreach ($genders as $gender_option) { ?>
                        <option value="<?php echo $gender_option; ?>"><?php echo $gender_option; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Year of Graduation -->
            <div class="col-12 col-md-6">
                <label for="year_of_graduation" class="form-label">Year of Graduation <span class="text-danger">*</span></label>
                <select name="year_of_graduation" id="year_of_graduation" class="form-control" required>
                    <?php $years = range(date('Y') - 5, date('Y') + 5); ?>
                    <?php foreach ($years as $year_option) { ?>
                        <option value="<?php echo $year_option; ?>"><?php echo $year_option; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        
        <!-- Submit Buttons -->
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="sbt-vstr" class="btn btn-primary">Register Visitor</button>
                <button type="reset" class="btn btn-outline-secondary me-2">Clear Form</button>
            </div>
        </div>
    </form>
</div>
