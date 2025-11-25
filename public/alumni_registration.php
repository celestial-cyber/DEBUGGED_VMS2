<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Database connection
require_once __DIR__ . '/../config/connection.php';

$response = ['success' => false, 'message' => ''];
$form_submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $roll_number = isset($_POST['roll_number']) ? trim($_POST['roll_number']) : '';
    $batch = isset($_POST['batch']) ? trim($_POST['batch']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $organization = isset($_POST['organization']) ? trim($_POST['organization']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $call = isset($_POST['call']) ? trim($_POST['call']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $visitor_status = isset($_POST['visitor_status']) ? trim($_POST['visitor_status']) : 'Pending';

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($roll_number)) $errors[] = 'Roll number is required';
    if (empty($batch)) $errors[] = 'Graduation year is required';
    if (empty($department)) $errors[] = 'Department is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($organization)) $errors[] = 'Current organization is required';
    if (empty($designation)) $errors[] = 'Current designation is required';
    if (empty($call) || strlen($call) < 10) $errors[] = 'Valid phone number (10+ digits) is required';
    if (!empty($whatsapp) && strlen($whatsapp) < 10) $errors[] = 'Valid WhatsApp number required';
    if (!in_array($visitor_status, ['Pending', 'Verified', 'Archived'])) {
        $visitor_status = 'Pending';
    }

    if (count($errors) > 0) {
        $response['message'] = 'Validation failed: ' . implode('; ', $errors);
    } else {
        // Begin transaction for atomic insert
        $conn->begin_transaction();
        try {
            // Insert into alumni_registrations
            $stmt_alumni = $conn->prepare(
                "INSERT INTO alumni_registrations 
                (name, roll_number, passed_out_batch, department, email, current_organization, 
                 current_designation, call_number, whatsapp_number, message, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_alumni) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $status_param = 'Pending';
            $stmt_alumni->bind_param(
                'sssssssssss',
                $name, $roll_number, $batch, $department, $email,
                $organization, $designation, $call, $whatsapp, $message, $status_param
            );

            if (!$stmt_alumni->execute()) {
                throw new Exception("Alumni insert failed: " . $stmt_alumni->error);
            }

            $alumni_id = $stmt_alumni->insert_id;
            $stmt_alumni->close();

            // Insert into visitors table
            // Note: status is TINYINT(1): 1 = "In", 0 = "Out", we default new registrations to 1 (In)
            // event_id defaults to 1 (Annual Alumni Meet) for self-registered alumni
            $stmt_visitor = $conn->prepare(
                "INSERT INTO vms_visitors 
                (name, email, phone, department, roll_number, added_by, status, registration_type, visitor_type, event_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt_visitor) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $added_by = null; // NULL for self-registered alumni (no admin user assigned)
            $registration_type = 'regular';
            $visitor_status_int = 1; // 1 = "In" status for new registrations
            $event_id = 1; // Default event: "Annual Alumni Meet"
            $stmt_visitor->bind_param(
                'ssssssissi',
                $name, $email, $call, $department, $roll_number,
                $added_by, $visitor_status_int, $registration_type, $registration_type, $event_id
            );

            if (!$stmt_visitor->execute()) {
                throw new Exception("Visitor insert failed: " . $stmt_visitor->error);
            }

            $visitor_id = $stmt_visitor->insert_id;
            $stmt_visitor->close();

            // Commit transaction
            $conn->commit();
            $response['success'] = true;
            $response['message'] = 'Registration submitted successfully! Visitor added to system.';
            $response['visitor_id'] = $visitor_id;
            $form_submitted = true;

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }

    // Return JSON for AJAX or set session message
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni Registration - VMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #004aad, #5de0e6);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .side-logo {
            position: fixed;
            top: 20px;
            width: 90px;
            height: 90px;
            object-fit: cover;
            z-index: 10;
        }

        .side-logo.left { left: 30px; }
        .side-logo.right { right: 30px; }

        .container-form {
            background: #fff;
            max-width: 700px;
            margin: 80px auto;
            padding: 40px 50px;
            border-radius: 18px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.2);
        }

        h2 {
            text-align: center;
            color: #004aad;
            margin-bottom: 25px;
            font-size: 26px;
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 8px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #004aad;
            outline: none;
            box-shadow: 0 0 6px rgba(0,74,173,0.3);
        }

        .whatsapp-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
        }

        .whatsapp-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .checkbox-inline {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .checkbox-inline input[type="checkbox"] {
            transform: scale(1.1);
            accent-color: #004aad;
            cursor: pointer;
            margin: 0;
        }

        .status-row {
            display: flex;
            gap: 20px;
            margin-bottom: 18px;
        }

        .status-radio {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .status-radio input[type="radio"] {
            width: auto;
            margin: 0;
            accent-color: #004aad;
            cursor: pointer;
        }

        button {
            width: 100%;
            background: #004aad;
            color: white;
            border: none;
            padding: 14px;
            font-size: 17px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        button:hover {
            background: #003680;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .note {
            text-align: center;
            color: #777;
            font-size: 13px;
            margin-top: 10px;
            line-height: 1.5;
        }

        .thankyou-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .thankyou-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 30px;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
            animation: fadeIn 0.4s ease;
        }

        .thankyou-card h3 {
            color: #004aad;
            margin-bottom: 15px;
        }

        .thankyou-card p {
            color: #333;
            font-size: 15px;
            line-height: 1.6;
        }

        .thankyou-card .btn {
            margin-top: 20px;
            background: #004aad;
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-block;
        }

        .thankyou-card .btn:hover {
            background: #003680;
        }

        @keyframes fadeIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 700px) {
            .side-logo {
                display: none;
            }

            .container-form {
                width: 84%;
                margin: 60px auto;
                padding: 30px 25px;
            }

            h2 {
                font-size: 22px;
                margin-bottom: 20px;
            }

            label {
                font-size: 14px;
            }

            input, select, textarea {
                font-size: 14px;
                padding: 10px;
            }

            button {
                font-size: 16px;
                padding: 12px;
            }

            .thankyou-card {
                width: 85%;
                padding: 30px 20px;
            }

            .status-row {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

<!-- FIXED LOGOS -->
<img src="../assets/images/SALogo.png" alt="SPEC Logo" class="side-logo left">
<img src="../assets/images/specanciens-logo.png" alt="SPECANCIENS Logo" class="side-logo right">

<div class="container-form">
    <h2>SPECANCIENS - Alumni Registration</h2>
    <p class="note"><b>Your data will be safely stored in SPECANCIENS Alumni Records. We will not share your data with anyone without your consent.</b></p>

    <?php if (!$form_submitted && !empty($response) && !$response['success']): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> <?php echo htmlspecialchars($response['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="alumniForm" method="POST" action="">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name" required>

        <label for="roll_number">Roll Number *</label>
        <input type="text" id="roll_number" name="roll_number" required>

        <label for="batch">Graduation Year (e.g., 2019-2023) *</label>
        <input type="text" id="batch" name="batch" placeholder="e.g., 2019-2023" required>

        <label for="department">Department *</label>
        <input type="text" id="department" name="department" required>

        <label for="email">Email ID *</label>
        <input type="email" id="email" name="email" placeholder="example@domain.com" required>

        <label for="organization">Current Organization *</label>
        <input type="text" id="organization" name="organization" required>

        <label for="designation">Current Designation *</label>
        <input type="text" id="designation" name="designation" required>

        <label for="call">Mobile Number (Call) *</label>
        <input type="tel" id="call" name="call" maxlength="20" required>

        <div class="whatsapp-row">
            <div class="whatsapp-label-row">
                <label for="whatsapp">Mobile Number (WhatsApp)</label>
                <div class="checkbox-inline">
                    <input type="checkbox" id="autofill">
                    <label for="autofill" style="margin: 0;">Same as Call</label>
                </div>
            </div>
            <input type="tel" id="whatsapp" name="whatsapp" maxlength="20">
        </div>

        <label for="message">Message (optional) - Anything you'd like to share with SPECANCIENS</label>
        <textarea id="message" name="message" rows="3" placeholder="Your message here..."></textarea>

        <button type="submit">Submit Registration</button>
        <p class="note"><b>Your data will be safely stored and used only for alumni engagement and official communication. We will not share your data with anyone without consent.</b></p>
    </form>
</div>

<!-- THANK YOU POPUP -->
<div class="thankyou-overlay" id="thankyouCard">
    <div class="thankyou-card">
        <h3>🎉 Thank You!</h3>
        <p>Your registration has been submitted successfully.<br><br>
        We truly value your time and trust. Your data is <b>safe and secure</b> with SPECANCIENS — it will be used only for alumni engagement and official communication.</p>
        <div>
            <button class="btn" onclick="closeThankYou()">Close</button>
            <button class="btn" onclick="window.location.href='admin/dashboard.php'" style="margin-left: 10px;">View Dashboard</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-fill WhatsApp number from Call number
document.getElementById("autofill").addEventListener("change", function() {
    const call = document.getElementById("call");
    const whatsapp = document.getElementById("whatsapp");

    if (this.checked) {
        if (call.value.trim() === "") {
            alert("Please enter your Call Number first!");
            this.checked = false;
            return;
        }
        whatsapp.value = call.value;
        whatsapp.setAttribute("readonly", true);
    } else {
        whatsapp.value = "";
        whatsapp.removeAttribute("readonly");
    }
});

// Handle form submission
document.getElementById("alumniForm").addEventListener("submit", function(e) {
    const call = document.getElementById("call").value.trim();
    if (call.length < 10) {
        e.preventDefault();
        alert("Phone number must be at least 10 digits");
        return false;
    }
});

function closeThankYou() {
    document.getElementById("thankyouCard").style.display = "none";
}

// Show thank-you card if form submitted
<?php if ($form_submitted): ?>
    document.getElementById("thankyouCard").style.display = "flex";
<?php endif; ?>
</script>

</body>
</html>
