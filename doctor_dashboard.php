<?php
session_start();
require_once 'connection.php';

/* Doctor-only access */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor' || empty($_SESSION['user'])) {
    header("Location: users.php");
    exit();
}

$username = mysqli_real_escape_string($conn, $_SESSION['user']);

$doctor_result = mysqli_query(
    $conn,
    "SELECT id, fname, lname, department, is_active, availability_message
     FROM users
     WHERE username = '$username' AND role = 'doctor'
     LIMIT 1"
);

if (!$doctor_result || mysqli_num_rows($doctor_result) !== 1) {
    session_destroy();
    header("Location: users.php");
    exit();
}

$doctor = mysqli_fetch_assoc($doctor_result);

if ($doctor['is_active'] !== 'Active') {
    session_destroy();
    echo "Your account is inactive. Please contact the administrator.";
    exit();
}

$doctor_id = (int)$doctor['id'];
$doctor_name = trim($doctor['fname'] . ' ' . $doctor['lname']);
$department = $doctor['department'] ?? '';
$today = date('Y-m-d');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* Small helper for safe HTML output */
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Handle forms and redirect after POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!is_string($submitted_token) || !hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        $_SESSION['dashboard_message'] = 'Invalid request. Please refresh the page and try again.';
        header('Location: doctor_dashboard.php');
        exit();
    }

    $action = $_POST['action'] ?? '';
    $message = '';

    if ($action === 'save_notice') {
        $notice = trim($_POST['availability_message'] ?? '');

        if (strlen($notice) > 1000) {
            $message = 'Notice cannot exceed 1000 characters.';
        } else {
            $notice_sql_value = ($notice === '')
                ? 'NULL'
                : "'" . mysqli_real_escape_string($conn, $notice) . "'";

            $sql = "UPDATE users
                    SET availability_message = $notice_sql_value
                    WHERE id = $doctor_id AND role = 'doctor'";

            $message = mysqli_query($conn, $sql)
                ? ($notice === '' ? 'Patient notice cleared.' : 'Patient notice published.')
                : 'Unable to save the notice.';
        }

    } elseif ($action === 'call_next') {
        $serving_sql = "SELECT id FROM patients
                        WHERE doctor_id = $doctor_id
                          AND registration_date = '$today'
                          AND status = 'Serving'
                        LIMIT 1";
        $serving_result = mysqli_query($conn, $serving_sql);

        if (!$serving_result) {
            $message = 'Unable to check the current queue.';
        } elseif (mysqli_num_rows($serving_result) > 0) {
            $message = 'Complete or skip the current patient first.';
        } else {
            $next_sql = "SELECT id FROM patients
                         WHERE doctor_id = $doctor_id
                           AND registration_date = '$today'
                           AND status = 'Waiting'
                         ORDER BY token_number ASC, id ASC
                         LIMIT 1";
            $next_result = mysqli_query($conn, $next_sql);

            if (!$next_result) {
                $message = 'Unable to retrieve the next patient.';
            } elseif (mysqli_num_rows($next_result) === 0) {
                $message = 'There are no waiting patients.';
            } else {
                $next_row = mysqli_fetch_assoc($next_result);
                $next_id = (int)$next_row['id'];
                $requested_id = (int)($_POST['patient_id'] ?? 0);

                if ($requested_id !== $next_id) {
                    $message = 'The queue changed. Please refresh and call the next token.';
                } else {
                    $update_sql = "UPDATE patients
                                   SET status = 'Serving'
                                   WHERE id = $next_id
                                     AND doctor_id = $doctor_id
                                     AND registration_date = '$today'
                                     AND status = 'Waiting'";
                    if (mysqli_query($conn, $update_sql) && mysqli_affected_rows($conn) === 1) {
                        $message = 'Next patient called successfully.';
                    } else {
                        $message = 'Unable to call the patient. Please refresh.';
                    }
                }
            }
        }

    } elseif ($action === 'complete' || $action === 'skip') {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $new_status = ($action === 'complete') ? 'Completed' : 'Skipped';

        $update_sql = "UPDATE patients
                       SET status = '$new_status'
                       WHERE id = $patient_id
                         AND doctor_id = $doctor_id
                         AND registration_date = '$today'
                         AND status = 'Serving'";

        if (mysqli_query($conn, $update_sql) && mysqli_affected_rows($conn) === 1) {
            $message = ($action === 'complete')
                ? 'Consultation completed successfully.'
                : 'Current patient skipped successfully.';
        } else {
            $message = 'Unable to update the patient. Please refresh.';
        }
    } else {
        $message = 'Invalid action.';
    }

    $_SESSION['dashboard_message'] = $message;
    header('Location: doctor_dashboard.php');
    exit();
}

$dashboard_message = $_SESSION['dashboard_message'] ?? '';
unset($_SESSION['dashboard_message']);

/* Count queue records for this doctor and date */
function queueCount($conn, $doctor_id, $date, $status = null) {
    $doctor_id = (int)$doctor_id;
    $date = mysqli_real_escape_string($conn, $date);
    $sql = "SELECT COUNT(*) AS total
            FROM patients
            WHERE doctor_id = $doctor_id AND registration_date = '$date'";

    if ($status !== null) {
        $status = mysqli_real_escape_string($conn, $status);
        $sql .= " AND status = '$status'";
    }

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

$total_patients = queueCount($conn, $doctor_id, $today);
$waiting_patients = queueCount($conn, $doctor_id, $today, 'Waiting');
$serving_patients = queueCount($conn, $doctor_id, $today, 'Serving');
$completed_patients = queueCount($conn, $doctor_id, $today, 'Completed');
$skipped_patients = queueCount($conn, $doctor_id, $today, 'Skipped');

/* Patient search scoped to the signed-in doctor */
$search_phone = trim($_GET['mobile_number'] ?? '');
$search_patient = null;
$search_error = '';

if (isset($_GET['search_patient'])) {
    if (!preg_match('/^[0-9]{10}$/', $search_phone)) {
        $search_error = 'Enter a valid 10-digit mobile number.';
    } else {
        $phone_safe = mysqli_real_escape_string($conn, $search_phone);
        $search_sql = "SELECT id, fname, mname, lname, gender, age, mobile_number,
                              blood_group, patient_type, department, patient_token,
                              registration_date, status
                       FROM patients
                       WHERE mobile_number = '$phone_safe'
                         AND doctor_id = $doctor_id
                       ORDER BY registration_date DESC, id DESC
                       LIMIT 1";
        $search_result = mysqli_query($conn, $search_sql);

        if ($search_result && mysqli_num_rows($search_result) > 0) {
            $search_patient = mysqli_fetch_assoc($search_result);
        } else {
            $search_error = 'No matching patient found in your records.';
        }
    }
}

/* Date-wise report */
$report_date = $_GET['report_date'] ?? $today;
$date_object = DateTime::createFromFormat('!Y-m-d', $report_date);
$date_valid = $date_object &&
              $date_object->format('Y-m-d') === $report_date &&
              $report_date <= $today;

if (!$date_valid) {
    $report_date = $today;
}

$report_total = queueCount($conn, $doctor_id, $report_date);
$report_waiting = queueCount($conn, $doctor_id, $report_date, 'Waiting');
$report_serving = queueCount($conn, $doctor_id, $report_date, 'Serving');
$report_completed = queueCount($conn, $doctor_id, $report_date, 'Completed');
$report_skipped = queueCount($conn, $doctor_id, $report_date, 'Skipped');

/* Current serving patient */
$serving_sql = "SELECT id, fname, mname, lname, patient_token, department
                FROM patients
                WHERE doctor_id = $doctor_id
                  AND registration_date = '$today'
                  AND status = 'Serving'
                ORDER BY token_number ASC, id ASC
                LIMIT 1";
$serving_result = mysqli_query($conn, $serving_sql);
$current_patient = ($serving_result) ? mysqli_fetch_assoc($serving_result) : null;

/* Next patient in token order */
$next_sql = "SELECT id, fname, mname, lname, patient_token, token_number
             FROM patients
             WHERE doctor_id = $doctor_id
               AND registration_date = '$today'
               AND status = 'Waiting'
             ORDER BY token_number ASC, id ASC
             LIMIT 1";
$next_result = mysqli_query($conn, $next_sql);
$next_patient = ($next_result) ? mysqli_fetch_assoc($next_result) : null;

$current_notice = $doctor['availability_message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | QueueLess</title>
    <link rel="stylesheet" href="css/doctor_dashboard.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="doctor_dashboard.php" aria-label="QueueLess dashboard">
            <span class="brand-mark">✚</span>
            <span>Queue<span class="brand-light">Less</span><small>DOCTOR PORTAL</small></span>
        </a>

        <div class="sidebar-label">WORKSPACE</div>
        <nav class="side-nav">
            <a class="nav-link active" href="doctor_dashboard.php"><span class="nav-icon">⌂</span>Dashboard</a>
            <a class="nav-link" href="manage_queue.php"><span class="nav-icon">▤</span>My Queue</a>
            <a class="nav-link" href="#patient-search"><span class="nav-icon">⌕</span>Search Patient</a>
            <a class="nav-link" href="#patient-notice"><span class="nav-icon">◉</span>Patient Notice</a>
            <a class="nav-link" href="#patient-reports"><span class="nav-icon">▥</span>Reports</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <span class="help-icon">✦</span>
                <strong>Keep your queue moving</strong>
                <p>Update each patient's status after consultation.</p>
            </div>
            <a class="nav-link logout-link" href="logout.php"><span class="nav-icon">↪</span>Sign out</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="mobile-brand"><span class="brand-mark">Q</span> QueueLess</div>
            <div class="page-heading">
                <p class="eyebrow">DOCTOR WORKSPACE</p>
                <h1>Dashboard</h1>
                <p class="subheading">Here's what's happening with your queue today.</p>
            </div>
            <div class="doctor-profile">
                <div class="avatar"><?php echo h(strtoupper(substr($doctor['fname'], 0, 1) . substr($doctor['lname'], 0, 1))); ?></div>
                <div><strong>Dr. <?php echo h($doctor_name); ?></strong><span><?php echo h($department); ?></span></div>
            </div>
        </header>

        <section class="welcome-banner">
            <div class="welcome-copy">
                <span class="live-pill"><span></span> YOUR DAILY OVERVIEW</span>
                <h2>Good to see you, Dr. <?php echo h($doctor['fname']); ?>.</h2>
                <p>Manage your patients, keep an eye on waiting times, and keep your clinic running smoothly.</p>
                <div class="banner-date">▦ &nbsp; <?php echo date('l, d F Y'); ?></div>
            </div>
            <div class="banner-art" aria-hidden="true">
                <div class="art-circle circle-one"></div><div class="art-circle circle-two"></div>
                <div class="art-card"><span class="art-cross">+</span><span class="art-line line-a"></span><span class="art-line line-b"></span><span class="art-line line-c"></span><span class="art-check">✓</span></div>
            </div>
        </section>

        <?php if ($dashboard_message !== ''): ?>
            <div class="flash-message" role="status"><span>✓</span><?php echo h($dashboard_message); ?></div>
        <?php endif; ?>

        <section class="section-block">
            <div class="section-title-row"><div><p class="eyebrow">AT A GLANCE</p><h2>Today's queue</h2></div><a class="text-link" href="manage_queue.php">View full queue <span>→</span></a></div>
            <div class="stats-grid">
                <article class="stat-card stat-blue"><div class="stat-top"><span>Total patients</span><span class="stat-icon">▣</span></div><div class="stat-value"><?php echo $total_patients; ?></div><div class="stat-foot">Registered today</div></article>
                <article class="stat-card stat-amber"><div class="stat-top"><span>Waiting</span><span class="stat-icon">◷</span></div><div class="stat-value"><?php echo $waiting_patients; ?></div><div class="stat-foot">Patients in line</div></article>
                <article class="stat-card stat-violet"><div class="stat-top"><span>In consultation</span><span class="stat-icon">✚</span></div><div class="stat-value"><?php echo $serving_patients; ?></div><div class="stat-foot">Currently being seen</div></article>
                <article class="stat-card stat-green"><div class="stat-top"><span>Completed</span><span class="stat-icon">✓</span></div><div class="stat-value"><?php echo $completed_patients; ?></div><div class="stat-foot">Consultations finished</div></article>
            </div>
        </section>

        <section class="queue-grid section-block">
            <article class="surface-card queue-panel">
                <div class="card-heading"><div><p class="eyebrow">LIVE QUEUE</p><h2>Currently serving</h2></div><span class="status-chip status-serving">Serving</span></div>
                <?php if ($current_patient): ?>
                    <div class="patient-highlight">
                        <div class="patient-avatar"><?php echo h(strtoupper(substr($current_patient['fname'], 0, 1))); ?></div>
                        <div class="patient-summary"><strong><?php echo h(trim($current_patient['fname'] . ' ' . ($current_patient['mname'] ?? '') . ' ' . $current_patient['lname'])); ?></strong><span><?php echo h($current_patient['department']); ?></span></div>
                        <div class="token-badge"><?php echo h($current_patient['patient_token']); ?></div>
                    </div>
                    <div class="queue-note">Finish the consultation or skip this patient to continue the queue.</div>
                    <div class="button-row">
                        <form method="POST" action="doctor_dashboard.php" onsubmit="return confirm('Mark this consultation as completed?');">
                            <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="patient_id" value="<?php echo (int)$current_patient['id']; ?>">
                            <button class="btn btn-success" type="submit" name="action" value="complete">✓ &nbsp; Complete</button>
                        </form>
                        <form method="POST" action="doctor_dashboard.php" onsubmit="return confirm('Skip the current patient?');">
                            <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="patient_id" value="<?php echo (int)$current_patient['id']; ?>">
                            <button class="btn btn-outline-danger" type="submit" name="action" value="skip">Skip patient</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">♡</div><strong>No active consultation</strong><p>Call the next patient when you're ready to begin.</p></div>
                <?php endif; ?>
            </article>

            <article class="surface-card queue-panel">
                <div class="card-heading"><div><p class="eyebrow">UP NEXT</p><h2>Next patient</h2></div><span class="status-chip status-waiting">Waiting</span></div>
                <?php if ($next_patient): ?>
                    <div class="patient-highlight">
                        <div class="patient-avatar patient-avatar-soft"><?php echo h(strtoupper(substr($next_patient['fname'], 0, 1))); ?></div>
                        <div class="patient-summary"><strong><?php echo h(trim($next_patient['fname'] . ' ' . ($next_patient['mname'] ?? '') . ' ' . $next_patient['lname'])); ?></strong><span>Next in token order</span></div>
                        <div class="token-badge"><?php echo h($next_patient['patient_token']); ?></div>
                    </div>
                    <div class="queue-note">Call this patient when the current consultation is complete.</div>
                    <form method="POST" action="doctor_dashboard.php" onsubmit="return confirm('Call the next patient?');">
                        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="patient_id" value="<?php echo (int)$next_patient['id']; ?>">
                        <button class="btn btn-primary btn-wide" type="submit" name="action" value="call_next" <?php echo $current_patient ? 'disabled' : ''; ?>>Call next patient <span>→</span></button>
                    </form>
                    <?php if ($current_patient): ?><p class="inline-hint">Complete or skip the current patient before calling the next one.</p><?php endif; ?>
                <?php else: ?>
                    <div class="empty-state"><div class="empty-icon">✓</div><strong>Queue is clear</strong><p>There are no waiting patients right now.</p></div>
                <?php endif; ?>
            </article>
        </section>

        <section class="surface-card section-block" id="patient-search">
            <div class="card-heading"><div><p class="eyebrow">PATIENT RECORDS</p><h2>Find a patient</h2><p class="section-description">Search by mobile number for a patient registered under your care.</p></div><span class="large-card-icon">⌕</span></div>
            <form class="search-form" method="GET" action="doctor_dashboard.php">
                <div class="form-field"><label for="mobile_number">Mobile number</label><input id="mobile_number" type="tel" name="mobile_number" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="Enter 10-digit mobile number" value="<?php echo h($search_phone); ?>" required></div>
                <button class="btn btn-primary" type="submit" name="search_patient" value="1">Search patient <span>→</span></button>
            </form>
            <?php if ($search_error !== ''): ?><div class="inline-alert"><?php echo h($search_error); ?></div><?php endif; ?>
            <?php if ($search_patient): ?>
                <div class="patient-record">
                    <div class="record-header"><div><p class="eyebrow">PATIENT DETAILS</p><h3><?php echo h(trim($search_patient['fname'] . ' ' . ($search_patient['mname'] ?? '') . ' ' . $search_patient['lname'])); ?></h3></div><span class="token-badge"><?php echo h($search_patient['patient_token']); ?></span></div>
                    <div class="record-grid">
                        <div><span>Patient ID</span><strong><?php echo (int)$search_patient['id']; ?></strong></div>
                        <div><span>Mobile</span><strong><?php echo h($search_patient['mobile_number']); ?></strong></div>
                        <div><span>Gender / Age</span><strong><?php echo h($search_patient['gender'] ?? 'N/A'); ?> / <?php echo h($search_patient['age'] ?? 'N/A'); ?></strong></div>
                        <div><span>Blood group</span><strong><?php echo h($search_patient['blood_group'] ?? 'N/A'); ?></strong></div>
                        <div><span>Department</span><strong><?php echo h($search_patient['department']); ?></strong></div>
                        <div><span>Patient type</span><strong><?php echo h($search_patient['patient_type'] ?? 'N/A'); ?></strong></div>
                        <div><span>Registration date</span><strong><?php echo h($search_patient['registration_date']); ?></strong></div>
                        <div><span>Visit status</span><strong><?php echo h($search_patient['status']); ?></strong></div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="surface-card section-block" id="patient-notice">
            <div class="card-heading"><div><p class="eyebrow">COMMUNICATION</p><h2>Patient notice</h2><p class="section-description">Share a delay or availability update with patients.</p></div><span class="large-card-icon">◉</span></div>
            <form method="POST" action="doctor_dashboard.php" class="notice-form">
                <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="save_notice">
                <div class="form-field"><label for="availability_message">Notice message</label><textarea id="availability_message" name="availability_message" maxlength="1000" rows="4" placeholder="Example: Consultations will resume at 12:30 PM."><?php echo h($current_notice); ?></textarea><small>Maximum 1000 characters. Leave blank and publish to clear the notice.</small></div>
                <div class="button-row"><button class="btn btn-primary" type="submit">Publish notice</button><?php if (trim($current_notice) !== ''): ?><button class="btn btn-outline-danger" type="submit" name="clear_notice" value="1" onclick="if(!confirm('Clear the published notice?')) return false; this.form.availability_message.value='';">Clear notice</button><?php endif; ?></div>
            </form>
            <?php if (trim($current_notice) !== ''): ?><div class="published-notice"><span class="published-dot"></span><div><strong>Currently published</strong><p><?php echo nl2br(h($current_notice)); ?></p></div></div><?php endif; ?>
        </section>

        <section class="section-block" id="patient-reports">
            <div class="section-title-row"><div><p class="eyebrow">PERFORMANCE SUMMARY</p><h2>Queue reports</h2><p class="section-description">Review the queue totals for a previous date.</p></div></div>
            <div class="surface-card report-card">
                <form method="GET" action="doctor_dashboard.php" class="report-filter">
                    <div class="form-field"><label for="report_date">Report date</label><input type="date" id="report_date" name="report_date" max="<?php echo h($today); ?>" value="<?php echo h($report_date); ?>" required></div>
                    <button class="btn btn-primary" type="submit">Generate report</button>
                </form>
                <div class="report-date-label">Report for <?php echo h(date('d M Y', strtotime($report_date))); ?></div>
                <div class="report-grid">
                    <div><span>Total</span><strong><?php echo $report_total; ?></strong></div>
                    <div><span>Waiting</span><strong><?php echo $report_waiting; ?></strong></div>
                    <div><span>Serving</span><strong><?php echo $report_serving; ?></strong></div>
                    <div><span>Completed</span><strong><?php echo $report_completed; ?></strong></div>
                    <div><span>Skipped</span><strong><?php echo $report_skipped; ?></strong></div>
                </div>
                <p class="report-footnote">Counts show registrations for the selected date and each patient's current status.</p>
            </div>
        </section>

        <footer class="page-footer"><span>© <?php echo date('Y'); ?> QueueLess</span><span>Better flow. Better care.</span><a href="logout.php">Sign out →</a></footer>
    </main>
</div>
</body>
</html>
