```php
<?php
session_start();
include 'connection.php';

// Check receptionist login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: users.php");
    exit();
}

// Check registration details
if (!isset($_SESSION['registered_patient'])) {
    header("Location: register_patient.php");
    exit();
}

$patient = $_SESSION['registered_patient'];

$patient_id = (int)$patient['id'];
$patient_name = $patient['name'];
$patient_token = $patient['token'];
$department = $patient['department'];
$doctor_id = (int)$patient['doctor_id'];
$registration_date = $patient['date'];

// Fetch doctor details
$sql = "SELECT fname, lname
        FROM users
        WHERE id = $doctor_id
        AND role = 'doctor'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $doctor = mysqli_fetch_assoc($result);
    $doctor_name = "Dr. " . $doctor['fname'] . " " . $doctor['lname'];
} else {
    $doctor_name = "Doctor information unavailable";
}

// Escape output
$patient_name = htmlspecialchars($patient_name, ENT_QUOTES, 'UTF-8');
$patient_token = htmlspecialchars($patient_token, ENT_QUOTES, 'UTF-8');
$department = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');
$doctor_name = htmlspecialchars($doctor_name, ENT_QUOTES, 'UTF-8');
$registration_date = htmlspecialchars($registration_date, ENT_QUOTES, 'UTF-8');

// Prevent reuse after refresh
unset($_SESSION['registered_patient']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful | QueueLess</title>
    <link rel="stylesheet" href="css/patient_registration_success.css">
</head>
<body>

<div class="page-wrapper">

    <header class="brand-header">
        <a href="receptionist_dashboard.php" class="brand">
            <span class="brand-icon">✚</span>
            <span>QueueLess</span>
        </a>

        <span class="header-label">Patient Registration</span>
    </header>

    <main class="success-card">

        <div class="success-icon">&#10003;</div>

        <p class="eyebrow">REGISTRATION COMPLETE</p>

        <h1>Patient registered successfully!</h1>

        <p class="subtitle">
            The patient has been added to the queue.
            Please share the token details with them.
        </p>

        <section class="token-box">
            <span class="token-label">PATIENT QUEUE TOKEN</span>

            <div class="token">
                <?php echo $patient_token; ?>
            </div>

            <span class="token-help">
                Keep this token to check your queue status.
            </span>

            <div class="queue-status">
                <span class="status-dot"></span>
                Waiting for consultation
            </div>
        </section>

        <section class="details-section">
            <h2>Registration details</h2>

            <div class="detail-row">
                <span>Patient ID</span>
                <strong><?php echo $patient_id; ?></strong>
            </div>

            <div class="detail-row">
                <span>Patient name</span>
                <strong><?php echo $patient_name; ?></strong>
            </div>

            <div class="detail-row">
                <span>Department</span>
                <strong><?php echo $department; ?></strong>
            </div>

            <div class="detail-row">
                <span>Assigned doctor</span>
                <strong><?php echo $doctor_name; ?></strong>
            </div>

            <div class="detail-row">
                <span>Registration date</span>
                <strong><?php echo $registration_date; ?></strong>
            </div>

            <div class="detail-row last-row">
                <span>Current status</span>
                <strong class="waiting-text">Waiting</strong>
            </div>
        </section>

        <div class="button-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                &#128438; Print Token
            </button>

            <a href="register_patient.php" class="btn btn-secondary">
                + Register Another Patient
            </a>

            <a href="receptionist_dashboard.php" class="dashboard-link">
                Back to Dashboard &rarr;
            </a>
        </div>

        <p class="footer-note">
            QueueLess &mdash; Making hospital visits simpler.
        </p>

    </main>

</div>

</body>
</html>
```