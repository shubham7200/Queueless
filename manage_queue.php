<?php
session_start();
include 'connection.php';

// Check doctor login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: users.php");
    exit();
}

$username = $_SESSION['user'] ?? '';

if ($username === '') {
    session_destroy();
    header("Location: users.php");
    exit();
}

// Fetch logged-in doctor
$username_safe = mysqli_real_escape_string($conn, $username);

$doctor_sql = "SELECT id, fname, lname, department, is_active
               FROM users
               WHERE username = '$username_safe'
               AND role = 'doctor'
               LIMIT 1";

$doctor_result = mysqli_query($conn, $doctor_sql);

if (!$doctor_result || mysqli_num_rows($doctor_result) === 0) {
    session_destroy();
    header("Location: users.php");
    exit();
}

$doctor = mysqli_fetch_assoc($doctor_result);

if ($doctor['is_active'] !== 'Active') {
    session_destroy();
    exit("Your account is inactive. Please contact the administrator.");
}

$doctor_id = (int)$doctor['id'];
$doctor_name = $doctor['fname'] . ' ' . $doctor['lname'];
$department = $doctor['department'];
$today = date('Y-m-d');

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Call Next, Complete and Skip actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['queue_message'] = "Invalid request. Please try again.";
        $_SESSION['queue_message_type'] = "error";
        header("Location: manage_queue.php");
        exit();
    }

    $patient_id = isset($_POST['patient_id'])
        ? (int)$_POST['patient_id']
        : 0;

    $action = $_POST['action'] ?? '';

    if ($patient_id <= 0) {
        $_SESSION['queue_message'] = "Invalid patient.";
        $_SESSION['queue_message_type'] = "error";
        header("Location: manage_queue.php");
        exit();
    }

    // Verify that this patient belongs to this doctor today
    $patient_sql = "SELECT id, status
                    FROM patients
                    WHERE id = $patient_id
                    AND doctor_id = $doctor_id
                    AND registration_date = '$today'
                    LIMIT 1";

    $patient_result = mysqli_query($conn, $patient_sql);

    if (!$patient_result || mysqli_num_rows($patient_result) === 0) {
        $_SESSION['queue_message'] = "Patient not found in your queue for today.";
        $_SESSION['queue_message_type'] = "error";
        header("Location: manage_queue.php");
        exit();
    }

    $patient = mysqli_fetch_assoc($patient_result);
    $current_status = $patient['status'];
    $new_status = '';
    $success_message = '';

    if ($action === 'call_next') {

        // Do not call another patient while one is being served
        $serving_sql = "SELECT id
                        FROM patients
                        WHERE doctor_id = $doctor_id
                        AND registration_date = '$today'
                        AND status = 'Serving'
                        LIMIT 1";

        $serving_result = mysqli_query($conn, $serving_sql);

        if (!$serving_result || mysqli_num_rows($serving_result) > 0) {
            $_SESSION['queue_message'] =
                "Complete or skip the currently serving patient first.";
            $_SESSION['queue_message_type'] = "error";
            header("Location: manage_queue.php");
            exit();
        }

        // Find the first waiting patient in token order
        $next_sql = "SELECT id
                     FROM patients
                     WHERE doctor_id = $doctor_id
                     AND registration_date = '$today'
                     AND status = 'Waiting'
                     ORDER BY token_number ASC, id ASC
                     LIMIT 1";

        $next_result = mysqli_query($conn, $next_sql);

        if (!$next_result || mysqli_num_rows($next_result) === 0) {
            $_SESSION['queue_message'] = "There are no waiting patients.";
            $_SESSION['queue_message_type'] = "error";
            header("Location: manage_queue.php");
            exit();
        }

        $next_row = mysqli_fetch_assoc($next_result);
        $next_patient_id = (int)$next_row['id'];

        if ($patient_id !== $next_patient_id) {
            $_SESSION['queue_message'] =
                "Please call the next patient in token order.";
            $_SESSION['queue_message_type'] = "error";
            header("Location: manage_queue.php");
            exit();
        }

        $new_status = 'Serving';
        $success_message = "Patient called successfully.";

    } elseif ($action === 'complete') {

        if ($current_status !== 'Serving') {
            $_SESSION['queue_message'] =
                "Only a currently serving patient can be completed.";
            $_SESSION['queue_message_type'] = "error";
            header("Location: manage_queue.php");
            exit();
        }

        $new_status = 'Completed';
        $success_message = "Consultation completed successfully.";

    } elseif ($action === 'skip') {

        if ($current_status !== 'Waiting' && $current_status !== 'Serving') {
            $_SESSION['queue_message'] =
                "This patient cannot be skipped in the current status.";
            $_SESSION['queue_message_type'] = "error";
            header("Location: manage_queue.php");
            exit();
        }

        $new_status = 'Skipped';
        $success_message = "Patient skipped successfully.";

    } else {
        $_SESSION['queue_message'] = "Invalid queue action.";
        $_SESSION['queue_message_type'] = "error";
        header("Location: manage_queue.php");
        exit();
    }

    // Update the patient status
    $new_status_safe = mysqli_real_escape_string($conn, $new_status);
    $old_status_safe = mysqli_real_escape_string($conn, $current_status);

    $update_sql = "UPDATE patients
                   SET status = '$new_status_safe'
                   WHERE id = $patient_id
                   AND doctor_id = $doctor_id
                   AND registration_date = '$today'
                   AND status = '$old_status_safe'";

    $update_result = mysqli_query($conn, $update_sql);

    if ($update_result && mysqli_affected_rows($conn) === 1) {
        $_SESSION['queue_message'] = $success_message;
        $_SESSION['queue_message_type'] = "success";
    } else {
        $_SESSION['queue_message'] =
            "The queue could not be updated. Refresh and try again.";
        $_SESSION['queue_message_type'] = "error";
    }

    header("Location: manage_queue.php");
    exit();
}

// Display one-time message
$message = $_SESSION['queue_message'] ?? '';
$message_type = $_SESSION['queue_message_type'] ?? '';

unset($_SESSION['queue_message']);
unset($_SESSION['queue_message_type']);

// Count today's patients
function queueCount($conn, $doctor_id, $today, $status = null) {
    $doctor_id = (int)$doctor_id;
    $today_safe = mysqli_real_escape_string($conn, $today);

    $sql = "SELECT COUNT(*) AS total
            FROM patients
            WHERE doctor_id = $doctor_id
            AND registration_date = '$today_safe'";

    if ($status !== null) {
        $status_safe = mysqli_real_escape_string($conn, $status);
        $sql .= " AND status = '$status_safe'";
    }

    $result = mysqli_query($conn, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['total'];
    }

    return 0;
}

$total_patients = queueCount($conn, $doctor_id, $today);
$waiting_patients = queueCount($conn, $doctor_id, $today, 'Waiting');
$serving_patients = queueCount($conn, $doctor_id, $today, 'Serving');
$completed_patients = queueCount($conn, $doctor_id, $today, 'Completed');
$skipped_patients = queueCount($conn, $doctor_id, $today, 'Skipped');

// Fetch today's queue
$queue_sql = "SELECT id, fname, mname, lname, gender, age,
                     mobile_number, patient_token, token_number,
                     patient_type, registration_date, status
              FROM patients
              WHERE doctor_id = $doctor_id
              AND registration_date = '$today'
              ORDER BY token_number ASC, id ASC";

$queue_result = mysqli_query($conn, $queue_sql);

$has_serving_patient = $serving_patients > 0;

// Find the next waiting patient
$next_patient_id = 0;

$next_sql = "SELECT id
             FROM patients
             WHERE doctor_id = $doctor_id
             AND registration_date = '$today'
             AND status = 'Waiting'
             ORDER BY token_number ASC, id ASC
             LIMIT 1";

$next_result = mysqli_query($conn, $next_sql);

if ($next_result && mysqli_num_rows($next_result) > 0) {
    $next_row = mysqli_fetch_assoc($next_result);
    $next_patient_id = (int)$next_row['id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Queue | QueueLess</title>
    <link rel="stylesheet" href="css/manage_queue.css">
</head>
<body>

<aside class="sidebar">
    <div class="logo">QueueLess</div>

    <a href="doctor_dashboard.php">Dashboard</a>
    <a href="manage_queue.php" class="active">Manage My Queue</a>
    <a href="view_appointments.php">Appointments</a>
    <a href="doctor_dashboard.php#patient-search">Search Patient</a>
    <a href="doctor_dashboard.php#patient-reports">Reports</a>
    <a href="logout.php">Logout</a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Manage My Queue</h1>
            <p class="muted"><?php echo date('l, d F Y'); ?></p>
        </div>

        <div class="profile">
            <div>
                <strong>Dr. <?php echo htmlspecialchars($doctor_name); ?></strong>
                <p class="muted"><?php echo htmlspecialchars($department); ?></p>
            </div>
        </div>
    </header>

    <?php if ($message !== '') { ?>
        <div class="notice <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <!-- Queue Statistics -->
    <section class="summary">

        <div class="stat-card">
            <p>Total Patients</p>
            <h2><?php echo $total_patients; ?></h2>
        </div>

        <div class="stat-card orange">
            <p>Waiting</p>
            <h2><?php echo $waiting_patients; ?></h2>
        </div>

        <div class="stat-card purple">
            <p>Currently Serving</p>
            <h2><?php echo $serving_patients; ?></h2>
        </div>

        <div class="stat-card green">
            <p>Completed</p>
            <h2><?php echo $completed_patients; ?></h2>
        </div>

        <div class="stat-card red">
            <p>Skipped</p>
            <h2><?php echo $skipped_patients; ?></h2>
        </div>

    </section>

    <!-- Queue Table -->
    <section class="panel">
        <h2>Today's Patient Queue</h2>
        <p class="muted">
            Patients are listed in token order. You can manage only your own queue.
        </p>

        <?php if ($queue_result && mysqli_num_rows($queue_result) > 0) { ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Patient Name</th>
                            <th>Mobile Number</th>
                            <th>Age / Gender</th>
                            <th>Patient Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($queue_result)) {
                        $current_patient_id = (int)$row['id'];
                        $status = $row['status'];

                        $full_name = trim(
                            $row['fname'] . ' ' .
                            ($row['mname'] ?? '') . ' ' .
                            $row['lname']
                        );

                        $status_class = 'waiting';

                        if ($status === 'Serving') {
                            $status_class = 'serving';
                        } elseif ($status === 'Completed') {
                            $status_class = 'completed';
                        } elseif ($status === 'Skipped') {
                            $status_class = 'skipped';
                        }

                        $is_next = (
                            $current_patient_id === $next_patient_id &&
                            !$has_serving_patient
                        );
                    ?>
                        <tr>
                            <td>
                                <span class="token">
                                    <?php echo htmlspecialchars($row['patient_token']); ?>
                                </span>

                                <?php if ($is_next) { ?>
                                    <p class="next-label">NEXT PATIENT</p>
                                <?php } ?>
                            </td>

                            <td><?php echo htmlspecialchars($full_name); ?></td>

                            <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>

                            <td>
                                <?php
                                echo htmlspecialchars((string)$row['age']);
                                echo ' / ';
                                echo htmlspecialchars($row['gender']);
                                ?>
                            </td>

                            <td><?php echo htmlspecialchars($row['patient_type']); ?></td>

                            <td>
                                <span class="status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>

                            <td>
                                <div class="actions">

                                    <?php if ($status === 'Waiting') { ?>

                                        <form method="POST" action="manage_queue.php">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="patient_id"
                                                   value="<?php echo $current_patient_id; ?>">

                                            <button type="submit"
                                                    name="action"
                                                    value="call_next"
                                                    class="btn btn-call"
                                                    <?php echo (!$is_next || $has_serving_patient) ? 'disabled' : ''; ?>>
                                                Call Next
                                            </button>
                                        </form>

                                        <form method="POST" action="manage_queue.php"
                                              onsubmit="return confirm('Skip this patient?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="patient_id"
                                                   value="<?php echo $current_patient_id; ?>">

                                            <button type="submit" name="action"
                                                    value="skip" class="btn btn-skip">
                                                Skip
                                            </button>
                                        </form>

                                    <?php } elseif ($status === 'Serving') { ?>

                                        <form method="POST" action="manage_queue.php"
                                              onsubmit="return confirm('Mark this consultation as completed?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="patient_id"
                                                   value="<?php echo $current_patient_id; ?>">

                                            <button type="submit" name="action"
                                                    value="complete" class="btn btn-complete">
                                                Complete
                                            </button>
                                        </form>

                                        <form method="POST" action="manage_queue.php"
                                              onsubmit="return confirm('Skip this currently serving patient?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="patient_id"
                                                   value="<?php echo $current_patient_id; ?>">

                                            <button type="submit" name="action"
                                                    value="skip" class="btn btn-skip">
                                                Skip
                                            </button>
                                        </form>

                                    <?php } else { ?>

                                        <span class="muted">No actions</span>

                                    <?php } ?>

                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>

        <?php } else { ?>

            <div class="empty">
                <h3>No Patients Today</h3>
                <p>No patients have been registered in your queue for today.</p>
            </div>

        <?php } ?>
    </section>

    <a href="doctor_dashboard.php" class="back-link">
        &larr; Back to Doctor Dashboard
    </a>

</main>
</body>
</html>