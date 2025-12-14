<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'config/connection.php'; // Make sure this file does NOT echo anything

$login_error = false;
$alumni_error = false;
$alumni_success = false;

// Handle login form submission
if (isset($_POST['login_btn'])) {
    $email = trim($_POST['email']);
    $pwd   = $_POST['pwd']; // Get plain text password
    $role  = $_POST['role'];

    // Debug: Log the attempt
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 4px solid #007cba;">';
        echo '<strong>DEBUG LOGIN ATTEMPT:</strong><br>';
        echo 'Email: ' . htmlspecialchars($email) . '<br>';
        echo 'Password length: ' . strlen($pwd) . '<br>';
        echo 'Role: ' . htmlspecialchars($role) . '<br>';
        echo 'Session status: ' . session_status() . '<br>';
        echo '</div>';
    }

    if ($role === "admin") {
        $stmt = mysqli_prepare($conn, "SELECT id, user_name, password FROM vms_admin WHERE emailid = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, member_name, password FROM vms_members WHERE emailid = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
    }

    mysqli_stmt_execute($stmt);
    $select_query = mysqli_stmt_get_result($stmt);

    // Debug: Check query result
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo '<div style="background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid #4caf50;">';
        echo '<strong>DEBUG QUERY RESULT:</strong><br>';
        echo 'Rows found: ' . mysqli_num_rows($select_query) . '<br>';
        if (mysqli_num_rows($select_query) > 0) {
            $user_data = mysqli_fetch_assoc($select_query);
            echo 'User ID: ' . $user_data['id'] . '<br>';
            echo 'User Name: ' . ($role === "admin" ? $user_data['user_name'] : $user_data['member_name']) . '<br>';
            echo 'Stored Hash: ' . $user_data['password'] . '<br>';
            echo 'Entered MD5: ' . md5(trim($pwd)) . '<br>';
            echo 'Match: ' . (md5(trim($pwd)) === $user_data['password'] ? 'YES' : 'NO') . '<br>';
            // Reset pointer for actual processing
            mysqli_data_seek($select_query, 0);
        }
        echo '</div>';
    }

   if (mysqli_num_rows($select_query) > 0) {

    $user_data = mysqli_fetch_assoc($select_query);

    // Convert entered password to MD5
    $entered_pwd = md5(trim($pwd));

    // Compare MD5 hashes
    if ($entered_pwd === $user_data['password']) {

        // Debug: Successful login
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo '<div style="background: #c8e6c9; padding: 10px; margin: 10px 0; border-left: 4px solid #2e7d32;">';
            echo '<strong>DEBUG: LOGIN SUCCESSFUL</strong><br>';
            echo 'Setting session variables...<br>';
            echo 'Redirecting to: ' . $dashboard_link . '<br>';
            echo '</div>';
        }

        $_SESSION['id']   = $user_data['id'];
        $_SESSION['name'] = ($role === "admin")
            ? $user_data['user_name']
            : $user_data['member_name'];
        $_SESSION['role'] = $role;

        // Redirect to dashboard
        $dashboard_link = ($role === 'admin')
            ? "admin/dashboard.php"
            : "member/dashboard.php";

        header("Location: $dashboard_link");
        exit();

    } else {
        // Password mismatch
        $login_error = true;

        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            echo '<div class="error-message">';
            echo '<strong>Debug:</strong><br>';
            echo 'Email found, but password mismatch.<br>';
            echo 'Entered MD5: ' . htmlspecialchars($entered_pwd) . '<br>';
            echo 'DB MD5: ' . htmlspecialchars($user_data['password']) . '<br>';
            echo '</div>';
        }
    }

} else {
    // Email not found
    $login_error = true;

    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        echo '<div class="error-message">';
        echo '<strong>Debug:</strong><br>';
        echo 'Email not found in database.<br>';
        echo 'Tried email: ' . htmlspecialchars($email) . '<br>';
        echo 'Role: ' . htmlspecialchars($role) . '<br>';
        echo '</div>';
    }
}
}

// Handle alumni registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alumni_submit'])) {
    $name = isset($_POST['alumni_name']) ? trim($_POST['alumni_name']) : '';
    $roll_number = isset($_POST['alumni_roll']) ? trim($_POST['alumni_roll']) : '';
    $batch = isset($_POST['alumni_batch']) ? trim($_POST['alumni_batch']) : '';
    $department = isset($_POST['alumni_dept']) ? trim($_POST['alumni_dept']) : '';
    $email = isset($_POST['alumni_email']) ? filter_var(trim($_POST['alumni_email']), FILTER_SANITIZE_EMAIL) : '';
    $organization = isset($_POST['alumni_org']) ? trim($_POST['alumni_org']) : '';
    $designation = isset($_POST['alumni_desig']) ? trim($_POST['alumni_desig']) : '';
    $call = isset($_POST['alumni_call']) ? trim($_POST['alumni_call']) : '';
    $whatsapp = isset($_POST['alumni_whatsapp']) ? trim($_POST['alumni_whatsapp']) : '';
    $message = isset($_POST['alumni_msg']) ? trim($_POST['alumni_msg']) : '';

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($roll_number)) $errors[] = 'Roll number is required';
    if (empty($batch)) $errors[] = 'Graduation year is required';
    if (empty($department)) $errors[] = 'Department is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($organization)) $errors[] = 'Organization is required';
    if (empty($designation)) $errors[] = 'Designation is required';
    if (empty($call) || strlen($call) < 10) $errors[] = 'Valid phone number (10+ digits) required';

    if (count($errors) === 0) {
        $conn->begin_transaction();
        try {
            // Insert into alumni_registrations
            $stmt = $conn->prepare(
                "INSERT INTO alumni_registrations 
                (name, roll_number, passed_out_batch, department, email, current_organization, 
                 current_designation, call_number, whatsapp_number, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $status_param = 'Pending';
            $stmt->bind_param(
                'sssssssssss',
                $name, $roll_number, $batch, $department, $email,
                $organization, $designation, $call, $whatsapp, $message, $status_param
            );
            $stmt->execute();
            $alumni_id = $stmt->insert_id;
            $stmt->close();

            // Insert into vms_visitors
            $stmt = $conn->prepare(
                "INSERT INTO vms_visitors 
                (name, email, phone, department, roll_number, added_by, status, registration_type, visitor_type, event_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $added_by = null;
            $registration_type = 'regular';
            $visitor_status = 1;
            $event_id = 1;
            $stmt->bind_param(
                'ssssssissi',
                $name, $email, $call, $department, $roll_number,
                $added_by, $visitor_status, $registration_type, $registration_type, $event_id
            );
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $alumni_success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $alumni_error = $e->getMessage();
        }
    } else {
        $alumni_error = implode('; ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VMS - Alumni Registration & Staff Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 650px;
        }

        .banner {
            text-align: center;
            margin-bottom: 30px;
        }

        .banner h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .banner p {
            color: #999;
            font-size: 14px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .tab-button {
            flex: 1;
            padding: 12px;
            border: none;
            background: none;
            color: #999;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-message, .error-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .success-message i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .success-message h3 {
            margin: 10px 0;
        }

        .success-message p {
            font-size: 14px;
            margin-bottom: 15px;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }

        .error-message i {
            margin-right: 8px;
        }

        .alumni-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row.full-width {
            grid-template-columns: 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-container input {
            flex: 1;
            padding-right: 40px;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .role-btn {
            padding: 12px;
            border: 2px solid #ddd;
            background: white;
            color: #333;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .role-btn:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .role-btn.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn-submit {
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary {
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary:hover {
            background: #45a049;
        }

        footer {
            text-align: center;
            padding: 20px 0;
            color: #999;
            font-size: 12px;
            margin-top: 40px;
            border-top: 1px solid #eee;
        }

        @media (max-width: 600px) {
            .main-container {
                padding: 25px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .banner h1 {
                font-size: 22px;
            }

            .tabs {
                flex-direction: column;
            }

            .role-selection {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="banner">
            <h1>Visitor Management System</h1>
            <p>Alumni Registration & Staff Portal</p>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button type="button" class="tab-button active" onclick="switchTab('alumni')">Alumni Registration</button>
            <button type="button" class="tab-button" onclick="switchTab('login')">Staff Login</button>
        </div>

        <!-- Alumni Registration Tab -->
        <div id="alumni-tab" class="tab-content active">
            <?php if ($alumni_success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Registration Successful!</h3>
                    <p>Thank you for registering. Your information has been recorded.</p>
                    <button onclick="resetAlumniForm()" class="btn-primary">Register Another</button>
                </div>
            <?php else: ?>
                <?php if ($alumni_error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?php echo htmlspecialchars($alumni_error ?? ''); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="alumni-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="alumni_name" placeholder="Enter your full name" required>
                        </div>
                        <div class="form-group">
                            <label>Roll Number *</label>
                            <input type="text" name="alumni_roll" placeholder="e.g., 2021-EC-001" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Graduation Year *</label>
                            <input type="number" name="alumni_batch" placeholder="e.g., 2021" required>
                        </div>
                        <div class="form-group">
                            <label>Department *</label>
                            <input type="text" name="alumni_dept" placeholder="e.g., Electronics" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="alumni_email" placeholder="your@email.com" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="alumni_call" placeholder="10-digit mobile number" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>WhatsApp Number</label>
                            <input type="tel" name="alumni_whatsapp" placeholder="WhatsApp number (optional)">
                        </div>
                        <div class="form-group">
                            <label>Current Organization *</label>
                            <input type="text" name="alumni_org" placeholder="e.g., Company Name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Designation *</label>
                            <input type="text" name="alumni_desig" placeholder="e.g., Software Engineer" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Message (Optional)</label>
                        <textarea name="alumni_msg" placeholder="Share your thoughts or message..." rows="4"></textarea>
                    </div>

                    <button type="submit" name="alumni_submit" class="btn-submit">Register Now</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Staff Login Tab -->
        <div id="login-tab" class="tab-content">
            <div class="role-selection">
                <button type="button" class="role-btn admin-btn"
        onclick="setRole('admin', this)">Admin Login</button>

<button type="button" class="role-btn member-btn"
        onclick="setRole('member', this)">Member Login</button>

            </div>

            <?php if ($login_error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Invalid email or password. Please try again.</p>
                    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; text-align: left; font-size: 12px;">
                            <strong>Debug Info:</strong><br>
                            Last attempted login details will appear here after submission.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="login-form" class="login-form">
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <div class="password-container">
                        <input type="password" name="pwd" id="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" id="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="role" id="role" value="admin">
                <button type="submit" name="login_btn" class="btn-submit">Login</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Visitor Management System. All rights reserved.</p>
    </footer>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }

       function setRole(role, el) {
    document.getElementById('role').value = role;

    document.querySelectorAll('.role-btn').forEach(btn => {
        btn.classList.remove('selected');
    });

    el.classList.add('selected');
}


        function resetAlumniForm() {
            location.reload();
        }

        // Set default role selection
        window.addEventListener('load', function() {
            const adminBtn = document.querySelector('.admin-btn');
            if (adminBtn) {
                adminBtn.classList.add('selected');
            }
        });

        // Password visibility toggle
        document.getElementById('password-toggle').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
