
<?php
session_start();
include 'connection.php';

$patient = null;
$doctor = null;
$current_patient = null;
$patients_ahead = 0;
$error = "";

$mobile_number = "";
$patient_token = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $patient_token = strtoupper(trim($_POST['patient_token'] ?? ''));

    if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
        $error = "Please enter a valid 10-digit mobile number.";
    } elseif ($patient_token === "") {
        $error = "Please enter your token number.";
    } else {

        $mobile_safe = mysqli_real_escape_string($conn, $mobile_number);
        $token_safe = mysqli_real_escape_string($conn, $patient_token);

        $sql = "SELECT *
                FROM patients
                WHERE mobile_number = '$mobile_safe'
                AND patient_token = '$token_safe'
                LIMIT 1";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {

            $patient = mysqli_fetch_assoc($result);

            $doctor_id = (int)$patient['doctor_id'];
            $registration_date = mysqli_real_escape_string(
                $conn,
                $patient['registration_date']
            );

            // Fetch doctor details and notice
            $doctor_sql = "SELECT fname, lname, department,
                                  availability_message
                           FROM users
                           WHERE id = $doctor_id
                           AND role = 'doctor'
                           LIMIT 1";

            $doctor_result = mysqli_query($conn, $doctor_sql);

            if ($doctor_result && mysqli_num_rows($doctor_result) > 0) {
                $doctor = mysqli_fetch_assoc($doctor_result);
            }

            // Find the currently serving patient for this doctor and date
            $current_sql = "SELECT patient_token
                            FROM patients
                            WHERE doctor_id = $doctor_id
                            AND registration_date = '$registration_date'
                            AND status = 'Serving'
                            ORDER BY token_number ASC, id ASC
                            LIMIT 1";

            $current_result = mysqli_query($conn, $current_sql);

            if ($current_result && mysqli_num_rows($current_result) > 0) {
                $current_patient = mysqli_fetch_assoc($current_result);
            }

            // Count patients ahead in the queue
            if ($patient['status'] === 'Waiting') {

                $token_number = (int)$patient['token_number'];

                $ahead_sql = "SELECT COUNT(*) AS total
                              FROM patients
                              WHERE doctor_id = $doctor_id
                              AND registration_date = '$registration_date'
                              AND token_number < $token_number
                              AND status IN ('Waiting', 'Serving')";

                $ahead_result = mysqli_query($conn, $ahead_sql);

                if ($ahead_result) {
                    $ahead_row = mysqli_fetch_assoc($ahead_result);
                    $patients_ahead = (int)$ahead_row['total'];
                }
            }

        } else {
            $error = "No patient found with this mobile number and token number. Please check your details.";
        }
    }
}

function safe($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$patient_name = '';

if ($patient !== null) {
    $patient_name = trim(
        ($patient['fname'] ?? '') . ' ' .
        ($patient['mname'] ?? '') . ' ' .
        ($patient['lname'] ?? '')
    );
}

$status = $patient['status'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Check Queue Status | QueueLess</title>

    <link rel="stylesheet" href="css/customer.css">
</head>

<body>

<header class="site-header">
    <a href="index.html" class="brand">
        <span class="brand-icon">✚</span>
        <span>Queue<span class="brand-light">Less</span></span>
    </a>

    <a href="index.html" class="home-link">
        <span aria-hidden="true">←</span>
        <span>Back to Home</span>
    </a>
</header>

<main class="page-container">

    <section class="page-intro">
        <div class="intro-badge">
            <span class="live-dot"></span>
            PATIENT PORTAL
        </div>

        <h1>Your care journey,<br><span>without the wait.</span></h1>

        <p>
            Check your appointment status, track your queue position,
            and stay informed before your consultation.
        </p>
    </section>

    <section class="lookup-card">

        <div class="card-heading">
            <div class="heading-icon" aria-hidden="true">⌕</div>

            <div>
                <h2>Track your appointment</h2>
                <p>Enter the details provided during registration.</p>
            </div>
        </div>

        <?php if ($error !== "") { ?>
            <div class="error-message" role="alert">
                <span class="error-icon">!</span>
                <span><?php echo safe($error); ?></span>
            </div>
        <?php } ?>

        <form method="POST" action="Customer.php" class="lookup-form">

            <div class="form-group">
                <label for="mobile_number">Registered mobile number</label>

                <div class="input-wrap">
                    <span class="field-icon" aria-hidden="true">☎</span>

                    <span class="country-code">+91</span>

                    <input
                        type="tel"
                        id="mobile_number"
                        name="mobile_number"
                        placeholder="10-digit mobile number"
                        pattern="[0-9]{10}"
                        minlength="10"
                        maxlength="10"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        value="<?php echo safe($mobile_number); ?>"
                        required
                    >
                </div>

                <small>Use the number registered by the receptionist.</small>
            </div>

            <div class="form-group">
                <label for="patient_token">Your token number</label>

                <div class="input-wrap">
                    <span class="field-icon token-icon" aria-hidden="true">#</span>

                    <input
                        type="text"
                        id="patient_token"
                        name="patient_token"
                        placeholder="Example: CAR001"
                        maxlength="20"
                        autocomplete="off"
                        autocapitalize="characters"
                        value="<?php echo safe($patient_token); ?>"
                        required
                    >
                </div>

                <small>Enter the token printed on your registration slip.</small>
            </div>

            <button type="submit" class="primary-button">
                Check Queue Status
                <span aria-hidden="true">→</span>
            </button>

        </form>

        <div class="privacy-note">
            <span aria-hidden="true">✓</span>
            Your appointment details are verified securely.
        </div>

    </section>

    <?php if ($patient !== null) { ?>

        <section class="results-section" aria-live="polite">

            <div class="result-heading">
                <div>
                    <span class="section-eyebrow">APPOINTMENT DETAILS</span>
                    <h2>Your queue status</h2>
                </div>

                <span class="status status-<?php echo safe($status); ?>">
                    <span class="status-dot"></span>
                    <?php echo safe($status); ?>
                </span>
            </div>

            <div class="token-card">

                <div class="token-card-top">
                    <div>
                        <span class="token-label">YOUR TOKEN NUMBER</span>
                        <div class="token-number">
                            <?php echo safe($patient['patient_token']); ?>
                        </div>
                    </div>

                    <div class="token-symbol" aria-hidden="true">#</div>
                </div>

                <div class="token-divider"></div>

                <div class="token-meta">
                    <div>
                        <span>Department</span>
                        <strong><?php echo safe($patient['department']); ?></strong>
                    </div>

                    <div>
                        <span>Registration date</span>
                        <strong><?php echo safe($patient['registration_date']); ?></strong>
                    </div>
                </div>

            </div>

            <div class="details-card">
                <h3>Patient information</h3>

                <div class="detail-row">
                    <span class="detail-label">Patient name</span>
                    <strong><?php echo safe($patient_name); ?></strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Doctor</span>
                    <strong>
                        <?php
                        if ($doctor !== null) {
                            echo 'Dr. ' . safe(
                                $doctor['fname'] . ' ' . $doctor['lname']
                            );
                        } else {
                            echo 'Not available';
                        }
                        ?>
                    </strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Department</span>
                    <strong><?php echo safe($patient['department']); ?></strong>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Appointment status</span>
                    <strong><?php echo safe($status); ?></strong>
                </div>
            </div>

            <?php if ($status === 'Waiting') { ?>

                <div class="queue-card">
                    <div class="queue-card-icon" aria-hidden="true">◷</div>

                    <div class="queue-card-content">
                        <span class="section-eyebrow">YOUR QUEUE POSITION</span>

                        <h3>
                            <?php echo $patients_ahead; ?>
                            <?php echo $patients_ahead === 1 ? 'patient' : 'patients'; ?>
                            ahead of you
                        </h3>

                        <p>
                            <?php
                            if ($patients_ahead === 0) {
                                echo 'You are next in the queue. Please stay ready.';
                            } else {
                                echo 'Please wait for your turn. Your queue position may change.';
                            }
                            ?>
                        </p>
                    </div>
                </div>

            <?php } elseif ($status === 'Serving') { ?>

                <div class="state-card state-serving">
                    <div class="state-icon" aria-hidden="true">✓</div>
                    <div>
                        <h3>It's your turn!</h3>
                        <p>Please proceed to the doctor's room.</p>
                    </div>
                </div>

            <?php } elseif ($status === 'Completed') { ?>

                <div class="state-card state-completed">
                    <div class="state-icon" aria-hidden="true">✓</div>
                    <div>
                        <h3>Consultation completed</h3>
                        <p>Your consultation has been marked as completed.</p>
                    </div>
                </div>

            <?php } elseif ($status === 'Skipped') { ?>

                <div class="state-card state-skipped">
                    <div class="state-icon" aria-hidden="true">!</div>
                    <div>
                        <h3>Token skipped</h3>
                        <p>Please contact the receptionist for assistance.</p>
                    </div>
                </div>

            <?php } ?>

            <?php
            if (
                $doctor !== null &&
                !empty(trim($doctor['availability_message'] ?? ''))
            ) {
            ?>
                <div class="notice-card">
                    <div class="notice-icon" aria-hidden="true">i</div>

                    <div>
                        <h3>Doctor's notice</h3>
                        <p><?php echo nl2br(safe($doctor['availability_message'])); ?></p>
                    </div>
                </div>
            <?php } ?>

            <div class="current-serving-card">
                <div class="serving-heading">
                    <div class="serving-icon" aria-hidden="true">↗</div>
                    <div>
                        <h3>Currently serving</h3>
                        <p>For your doctor's queue today</p>
                    </div>
                </div>

                <?php if ($current_patient !== null) { ?>

                    <div class="serving-token">
                        <span>Now serving token</span>
                        <strong><?php echo safe($current_patient['patient_token']); ?></strong>
                    </div>

                <?php } else { ?>

                    <p class="empty-serving">
                        No patient is currently being served.
                    </p>

                <?php } ?>
            </div>

            <a href="Customer.php" class="check-again">
                Check another appointment
            </a>

        </section>

    <?php } ?>

</main>

<footer class="site-footer">
    <div class="footer-brand">
        <span class="brand-icon footer-brand-icon">Q</span>
        <span>Queue<span class="brand-light">Less</span></span>
    </div>

    <p>Making healthcare queues simpler, one visit at a time.</p>

    <span class="copyright">
        &copy; <?php echo date('Y'); ?> QueueLess. All rights reserved.
    </span>
</footer>

</body>
</html>
