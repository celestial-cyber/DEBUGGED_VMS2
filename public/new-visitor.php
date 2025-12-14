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
$show_success_modal = false;

// Check for success parameter in URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $popup_message = 'Visitor registered successfully!';
    $popup_type = 'success';
}

// Check for error parameter in URL
if (isset($_GET['error']) && $_GET['error'] == '1') {
    $popup_message = 'Error registering visitor. Please try again.';
    $popup_type = 'danger';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbt-vstr'])) {
    // Debug: Log that form was submitted
    error_log("Form submitted - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));
    
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $popup_message = 'Invalid CSRF token. Please refresh the page and try again.';
        $popup_type = 'danger';
        error_log("CSRF token validation failed");
    } else {
        // Retrieve and sanitize form data
        $first_name = htmlspecialchars($_POST['first_name'] ?? '');
        $last_name = htmlspecialchars($_POST['last_name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($_POST['phone'] ?? '');
        $address = htmlspecialchars($_POST['address'] ?? '');
        $department = htmlspecialchars($_POST['department'] ?? '');
        $gender = htmlspecialchars($_POST['gender'] ?? '');
        $year = htmlspecialchars($_POST['year_of_graduation'] ?? '');
        $event_id = (int)($_POST['event_id'] ?? 0);
        $roll_number = htmlspecialchars($_POST['roll_number'] ?? '');

        // Get admin ID from session
        $added_by = $_SESSION['id'] ?? null;

        // Merge first + last name into full_name
        $full_name = trim($first_name . ' ' . $last_name);

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($phone) || empty($event_id)) {
            $popup_message = 'Please fill in all required fields.';
            $popup_type = 'warning';
            error_log("Validation failed: full_name=$full_name, email=$email, phone=$phone, event_id=$event_id");
        } elseif (empty($added_by)) {
            $popup_message = 'Admin not logged in. Please login to add visitors.';
            $popup_type = 'danger';
            error_log("Admin not logged in: added_by=$added_by");
        } else {
            error_log("Validation passed, proceeding with database insertion");
            // Default values for other fields
            $in_time = date('Y-m-d H:i:s');
            $visitor_type = 'regular';
            $registration_type = 'beforehand';
            $status = 1;

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO vms_visitors 
                (event_id, name, email, mobile, address, department, gender, year_of_graduation, roll_number, added_by, in_time, visitor_type, registration_type, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param(
                    "issssssisissii",
                    $event_id,
                    $full_name,
                    $email,
                    $phone,
                    $address,
                    $department,
                    $gender,
                    $year,
                    $roll_number,
                    $added_by,
                    $in_time,
                    $visitor_type,
                    $registration_type,
                    $status
                );

                if ($stmt->execute()) {
                    $show_success_modal = true;
                    $_POST = array(); // Clear POST data
                    error_log("Database insertion successful, show_success_modal set to true");
                } else {
                    $popup_message = "Error saving visitor: " . $stmt->error;
                    $popup_type = "danger";
                    error_log("Database insertion failed: " . $stmt->error);
                }

                $stmt->close();
            } else {
                $popup_message = "Error preparing statement: " . $conn->error;
                $popup_type = "danger";
            }
        }
    }
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
    
    <style>
        /* Custom styles for visitor registration form */
        .visitor-registration-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .visitor-registration-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .visitor-registration-header h2 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .visitor-registration-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin: 0;
        }
        
        .visitor-form-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .form-section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .form-group-enhanced {
            margin-bottom: 2rem;
        }
        
        .form-label-enhanced {
            font-size: 1rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control-enhanced {
            height: 48px;
            font-size: 1rem;
            border: 2px solid #ced4da;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }
        
        .form-control-enhanced:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .form-control-enhanced:hover {
            border-color: #adb5bd;
        }
        
        textarea.form-control-enhanced {
            height: auto;
            min-height: 100px;
            resize: vertical;
        }
        
        .form-select-enhanced {
            height: 48px;
            font-size: 1rem;
            border: 2px solid #ced4da;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background-color: #ffffff;
            transition: all 0.3s ease;
        }
        
        .form-select-enhanced:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
            font-weight: bold;
        }
        
        .form-row-enhanced {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-col-enhanced {
            flex: 1;
        }
        
        .btn-enhanced {
            height: 48px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary-enhanced {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-primary-enhanced:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-secondary-enhanced {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary-enhanced:hover {
            background: #545b62;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .btn-outline-enhanced {
            background: transparent;
            border: 2px solid #6c757d;
            color: #6c757d;
        }
        
        .btn-outline-enhanced:hover {
            background: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6;
        }
        
        .form-actions-left {
            display: flex;
            gap: 1rem;
        }
        
        .form-actions-right {
            display: flex;
            gap: 1rem;
        }
        
        .alert-enhanced {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .visitor-registration-container {
                padding: 1rem 0.5rem;
            }
            
            .visitor-form-card {
                padding: 1.5rem;
                border-radius: 10px;
            }
            
            .visitor-registration-header h2 {
                font-size: 2rem;
            }
            
            .form-row-enhanced {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-actions-left,
            .form-actions-right {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Animation for form elements */
        .form-group-enhanced {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }
        
        .form-group-enhanced:nth-child(1) { animation-delay: 0.1s; }
        .form-group-enhanced:nth-child(2) { animation-delay: 0.2s; }
        .form-group-enhanced:nth-child(3) { animation-delay: 0.3s; }
        .form-group-enhanced:nth-child(4) { animation-delay: 0.4s; }
        .form-group-enhanced:nth-child(5) { animation-delay: 0.5s; }
        .form-group-enhanced:nth-child(6) { animation-delay: 0.6s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Validation states */
        .form-control-enhanced.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .form-control-enhanced.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-select-enhanced.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .form-select-enhanced.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        /* Loading spinner animation */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        /* Enhanced focus states */
        .form-control-enhanced:focus,
        .form-select-enhanced:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
        }
        
        /* Button hover effects */
        .btn-enhanced:active {
            transform: translateY(0) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Alert icon styling */
        .alert-enhanced i {
            font-size: 1.1rem;
        }
        
        /* Section title icons */
        .form-section-title i {
            color: #007bff;
        }
    </style>
    
    <div class="visitor-registration-container">
        <div class="visitor-registration-header">
            <h2>Visitor Registration</h2>
            <p>Please fill in the details below to register a new visitor</p>
        </div>
        
        <div class="visitor-form-card">
            <?php if ($popup_message): ?>
                <div class="alert alert-enhanced alert-<?php echo htmlspecialchars($popup_type ?? 'info'); ?>">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo htmlspecialchars($popup_message ?? ''); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="needs-validation" novalidate autocomplete="off">
                <!-- Hidden CSRF token -->
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">
                
                <h3 class="form-section-title">
                    <i class="fas fa-user me-2"></i>Personal Information
                </h3>
                
                <!-- Personal Information Section -->
                <div class="form-row-enhanced">
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="first_name" class="form-label-enhanced required-field">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control-enhanced" required autocomplete="given-name" placeholder="Enter first name">
                        </div>
                    </div>
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="last_name" class="form-label-enhanced">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control-enhanced" autocomplete="family-name" placeholder="Enter last name">
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <h3 class="form-section-title">
                    <i class="fas fa-address-book me-2"></i>Contact Information
                </h3>
                
                <div class="form-row-enhanced">
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="email" class="form-label-enhanced required-field">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control-enhanced" required autocomplete="email" placeholder="example@email.com">
                        </div>
                    </div>
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="phone" class="form-label-enhanced required-field">Phone Number</label>
                            <input type="tel" name="phone" id="phone" class="form-control-enhanced" placeholder="+91 9876543210" required autocomplete="tel">
                        </div>
                    </div>
                </div>
                
                <div class="form-group-enhanced">
                    <label for="address" class="form-label-enhanced required-field">Address</label>
                    <textarea name="address" id="address" class="form-control-enhanced" required autocomplete="street-address" placeholder="Enter full address"></textarea>
                </div>
                
                <!-- Academic Information Section -->
                <h3 class="form-section-title">
                    <i class="fas fa-graduation-cap me-2"></i>Academic Information
                </h3>

                <div class="form-row-enhanced">
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="roll_number" class="form-label-enhanced required-field">Roll Number</label>
                            <input type="text" name="roll_number" id="roll_number" class="form-control-enhanced" required autocomplete="off" placeholder="Enter Roll Number">
                        </div>
                    </div>
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="department" class="form-label-enhanced required-field">Department</label>
                            <select name="department" id="department" class="form-select-enhanced" required autocomplete="organization">
                                <option value="">Select Department</option>
                                <?php
                                // Reset the departments result pointer
                                mysqli_data_seek($departments, 0);
                                while ($dept = mysqli_fetch_assoc($departments)) {
                                ?>
                                    <option value="<?php echo $dept['department']; ?>"><?php echo $dept['department']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row-enhanced">
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="year_of_graduation" class="form-label-enhanced required-field">Year of Graduation</label>
                            <select name="year_of_graduation" id="year_of_graduation" class="form-select-enhanced" required autocomplete="bday-year">
                                <option value="">Select Year</option>
                                <?php $years = range(date('Y') - 5, date('Y') + 5); ?>
                                <?php foreach ($years as $year_option) { ?>
                                    <option value="<?php echo $year_option; ?>"><?php echo $year_option; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="gender" class="form-label-enhanced required-field">Gender</label>
                            <select name="gender" id="gender" class="form-select-enhanced" required autocomplete="sex">
                                <option value="">Select Gender</option>
                                <?php $genders = ['Male', 'Female', 'Other']; ?>
                                <?php foreach ($genders as $gender_option) { ?>
                                    <option value="<?php echo $gender_option; ?>"><?php echo $gender_option; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Event and Gender Section -->
                <h3 class="form-section-title">
                    <i class="fas fa-calendar-alt me-2"></i>Event & Demographics
                </h3>
                
                <div class="form-row-enhanced">
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="event_id" class="form-label-enhanced required-field">Event</label>
                            <select name="event_id" id="event_id" class="form-select-enhanced" required autocomplete="off">
                                <option value="">Select Event</option>
                                <?php 
                                // Reset the events result pointer
                                mysqli_data_seek($events, 0);
                                while ($event = mysqli_fetch_assoc($events)) { 
                                ?>
                                    <option value="<?php echo $event['event_id']; ?>"><?php echo $event['event_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col-enhanced">
                        <div class="form-group-enhanced">
                            <label for="gender" class="form-label-enhanced required-field">Gender</label>
                            <select name="gender" id="gender" class="form-select-enhanced" required autocomplete="sex">
                                <option value="">Select Gender</option>
                                <?php $genders = ['Male', 'Female', 'Other']; ?>
                                <?php foreach ($genders as $gender_option) { ?>
                                    <option value="<?php echo $gender_option; ?>"><?php echo $gender_option; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            
                <!-- Submit Buttons -->
                <div class="form-actions">
                    <div class="form-actions-left">
                        <a href="../admin/dashboard.php" class="btn btn-outline-enhanced btn-enhanced">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back to Admin Dashboard
                        </a>
                    </div>
                    <div class="form-actions-right">
                        <button type="reset" class="btn btn-secondary-enhanced btn-enhanced">
                            <i class="fas fa-eraser me-2"></i>Clear Form
                        </button>
                        <button type="submit" name="sbt-vstr" class="btn btn-primary-enhanced btn-enhanced">
                            <i class="fas fa-user-plus me-2"></i>Register Visitor
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Registration Successful!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-check text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="text-success mb-3">Visitor Registered Successfully!</h4>
                    <p class="text-muted mb-4">The visitor has been added to the system and is now available in the visitor management section.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-plus me-2"></i>Add Another Visitor
                    </button>
                    <a href="../admin/manage-visitors.php" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>View All Visitors
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
// Clear form on page load to prevent any pre-filled values
document.addEventListener('DOMContentLoaded', function() {
    // Only clear if this is not a form submission with errors and not a success redirect
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('error') && !urlParams.has('success')) {
        document.querySelector('form').reset();
    }
    
    // Add loading state to submit button
    const submitBtn = document.querySelector('button[type="submit"]');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function() {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Registering...';
        submitBtn.disabled = true;
    });
});

// Enhanced form validation with better feedback
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                    
                    // Find first invalid field and focus it
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Add input validation feedback
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control-enhanced, .form-select-enhanced');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid') && this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
});

// Show success modal if registration was successful
<?php if ($show_success_modal): ?>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Showing success modal');
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();
});
<?php else: ?>
console.log('show_success_modal is false');
<?php endif; ?>
</script>
