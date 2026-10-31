
<?php
session_start();
include 'connection.php';

// Receptionist access check
if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'receptionist'
) {
    header("Location: users.php");
    exit();
}

// Search and filters
$search = trim($_GET['search'] ?? '');
$department = $_GET['department'] ?? '';
$doctor_id = $_GET['doctor_id'] ?? '';
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$validDepartments = [
    'General Medicine',
    'Orthopaedics',
    'Paediatrics',
    'Cardiology'
];

$validStatuses = [
    'Waiting',
    'Serving',
    'Completed',
    'Skipped'
];

// Validate date
$dateObject = DateTime::createFromFormat('!Y-m-d', $date);

if (
    !$dateObject ||
    $dateObject->format('Y-m-d') !== $date
) {
    $date = date('Y-m-d');
}

// Validate filter values
if (!in_array($department, $validDepartments, true)) {
    $department = '';
}

if (!in_array($status, $validStatuses, true)) {
    $status = '';
}

if (!ctype_digit((string)$doctor_id)) {
    $doctor_id = '';
}

// Fetch active doctors for the filter
$doctorQuery = "SELECT id, fname, lname, department
                FROM users
                WHERE role = 'doctor'
                AND is_active = 'Active'
                ORDER BY fname ASC";

$doctorResult = mysqli_query($conn, $doctorQuery);

if (!$doctorResult) {
    die("Unable to load doctors. Please check your users table.");
}

// Build appointments query
$dateSafe = mysqli_real_escape_string($conn, $date);

$sql = "SELECT p.*,
               u.fname AS doctor_fname,
               u.lname AS doctor_lname
        FROM patients p
        LEFT JOIN users u ON p.doctor_id = u.id
        WHERE p.registration_date = '$dateSafe'";

if ($department !== '') {
    $departmentSafe = mysqli_real_escape_string($conn, $department);
    $sql .= " AND p.department = '$departmentSafe'";
}

if ($doctor_id !== '') {
    $sql .= " AND p.doctor_id = " . (int)$doctor_id;
}

if ($status !== '') {
    $statusSafe = mysqli_real_escape_string($conn, $status);
    $sql .= " AND p.status = '$statusSafe'";
}

if ($search !== '') {
    $searchSafe = mysqli_real_escape_string($conn, $search);

    $sql .= " AND (
        p.fname LIKE '%$searchSafe%'
        OR p.mname LIKE '%$searchSafe%'
        OR p.lname LIKE '%$searchSafe%'
        OR p.mobile_number LIKE '%$searchSafe%'
        OR p.patient_token LIKE '%$searchSafe%'
    )";
}

$sql .= " ORDER BY p.token_number ASC, p.id ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Unable to fetch appointments. Please check your patients table.");
}

// Store results and calculate summary counts
$appointments = [];
$waiting = 0;
$serving = 0;
$completed = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $appointments[] = $row;

    switch ($row['status']) {
        case 'Waiting':
            $waiting++;
            break;

        case 'Serving':
            $serving++;
            break;

        case 'Completed':
            $completed++;
            break;

        case 'Skipped':
            $skipped++;
            break;
    }
}

$total = count($appointments);

// Reusable safe output
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Appointments | QueueLess</title>

    <link rel="stylesheet" href="css/view_appointments.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">
</head>

<body>

<header class="topbar">
    <a href="receptionist_dashboard.php" class="brand">
        <span class="brand-icon">✚</span>
        <span>Queue<span class="brand-highlight">Less</span></span>
    </a>

    <nav class="top-nav">
        <a href="receptionist_dashboard.php">Dashboard</a>
        <a href="register_patient.php">Register Patient</a>
        <a href="view_appointments.php" class="active">Appointments</a>
    </nav>

    <a href="logout.php" class="logout-link">↪ <span>Log out</span></a>
</header>

<main class="page-wrapper">

    <!-- Page heading -->
    <section class="page-heading">
        <div>
            <span class="eyebrow">PATIENT MANAGEMENT</span>
            <h1>Patient Appointments</h1>
            <p>View registrations and monitor patient queue statuses.</p>
        </div>

        <a href="register_patient.php" class="primary-button">
            <span>＋</span> Register Patient
        </a>
    </section>

    <!-- Summary cards -->
    <section class="summary-grid">

        <article class="summary-card total-card">
            <div class="summary-top">
                <span class="summary-label">Total Results</span>
                <span class="summary-icon blue-icon">▤</span>
            </div>
            <div class="summary-number"><?php echo $total; ?></div>
            <div class="summary-caption">Matching appointments</div>
        </article>

        <article class="summary-card waiting-card">
            <div class="summary-top">
                <span class="summary-label">Waiting</span>
                <span class="summary-icon amber-icon">◷</span>
            </div>
            <div class="summary-number"><?php echo $waiting; ?></div>
            <div class="summary-caption">Awaiting their turn</div>
        </article>

        <article class="summary-card serving-card">
            <div class="summary-top">
                <span class="summary-label">Currently Serving</span>
                <span class="summary-icon purple-icon">♡</span>
            </div>
            <div class="summary-number"><?php echo $serving; ?></div>
            <div class="summary-caption">Being attended</div>
        </article>

        <article class="summary-card completed-card">
            <div class="summary-top">
                <span class="summary-label">Completed</span>
                <span class="summary-icon green-icon">✓</span>
            </div>
            <div class="summary-number"><?php echo $completed; ?></div>
            <div class="summary-caption">Visits completed</div>
        </article>

        <article class="summary-card skipped-card">
            <div class="summary-top">
                <span class="summary-label">Skipped</span>
                <span class="summary-icon red-icon">↷</span>
            </div>
            <div class="summary-number"><?php echo $skipped; ?></div>
            <div class="summary-caption">Tokens skipped</div>
        </article>

    </section>

    <!-- Filters -->
    <section class="filter-card">

        <div class="filter-heading">
            <div>
                <h2>Find Appointments</h2>
                <p>Use filters to narrow down patient records.</p>
            </div>

            <span class="filter-date-label">
                <span>▦</span> <?php echo date('d M Y', strtotime($date)); ?>
            </span>
        </div>

        <form method="GET" class="filters">

            <div class="filter-field search-field">
                <label for="search">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Name, mobile or token"
                    value="<?php echo e($search); ?>"
                >
            </div>

            <div class="filter-field">
                <label for="department">Department</label>
                <select name="department" id="department">
                    <option value="">All Departments</option>

                    <?php foreach ($validDepartments as $d): ?>
                        <option value="<?php echo e($d); ?>"
                            <?php echo $department === $d ? 'selected' : ''; ?>>
                            <?php echo e($d); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="doctor_id">Doctor</label>
                <select name="doctor_id" id="doctor_id">
                    <option value="">All Doctors</option>

                    <?php while ($doc = mysqli_fetch_assoc($doctorResult)): ?>
                        <option value="<?php echo (int)$doc['id']; ?>"
                            <?php echo $doctor_id === (string)$doc['id'] ? 'selected' : ''; ?>>
                            <?php
                            echo e(
                                'Dr. ' . $doc['fname'] . ' ' .
                                $doc['lname'] . ' (' .
                                $doc['department'] . ')'
                            );
                            ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="">All Statuses</option>

                    <?php foreach ($validStatuses as $s): ?>
                        <option value="<?php echo e($s); ?>"
                            <?php echo $status === $s ? 'selected' : ''; ?>>
                            <?php echo e($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field date-field">
                <label for="date">Registration Date</label>
                <input
                    type="date"
                    name="date"
                    id="date"
                    value="<?php echo e($date); ?>"
                    required
                >
            </div>

            <div class="filter-actions">
                <button type="submit" class="apply-button">Apply Filters</button>
                <a href="view_appointments.php" class="clear-button">Clear</a>
            </div>

        </form>
    </section>

    <!-- Appointments table -->
    <section class="table-card">

        <div class="table-heading">
            <div>
                <h2>Appointment Records</h2>
                <p>
                    <?php echo $total; ?>
                    <?php echo $total === 1 ? 'appointment' : 'appointments'; ?>
                    found for <?php echo e(date('d M Y', strtotime($date))); ?>
                </p>
            </div>

            <span class="records-label">PATIENT RECORDS</span>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Token</th>
                        <th>Patient Name</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Doctor</th>
                        <th>Patient Type</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($total > 0): ?>

                        <?php foreach ($appointments as $p): ?>
                            <?php
                            $patientName = trim(
                                ($p['fname'] ?? '') . ' ' .
                                ($p['mname'] ?? '') . ' ' .
                                ($p['lname'] ?? '')
                            );

                            $statusClass = strtolower(
                                preg_replace('/[^a-zA-Z]/', '', $p['status'] ?? '')
                            );
                            ?>

                            <tr>
                                <td>
                                    <span class="patient-id">
                                        #<?php echo (int)$p['id']; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="token">
                                        <?php echo e($p['patient_token']); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="patient-name">
                                        <span class="patient-avatar">
                                            <?php
                                            echo e(
                                                strtoupper(
                                                    substr($p['fname'] ?? 'P', 0, 1)
                                                )
                                            );
                                            ?>
                                        </span>
                                        <span><?php echo e($patientName); ?></span>
                                    </div>
                                </td>

                                <td><?php echo e($p['mobile_number']); ?></td>

                                <td>
                                    <span class="department-text">
                                        <?php echo e($p['department']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    $doctorName = trim(
                                        ($p['doctor_fname'] ?? '') . ' ' .
                                        ($p['doctor_lname'] ?? '')
                                    );
                                    echo $doctorName !== ''
                                        ? 'Dr. ' . e($doctorName)
                                        : '—';
                                    ?>
                                </td>

                                <td><?php echo e($p['patient_type']); ?></td>

                                <td><?php echo e($p['registration_date']); ?></td>

                                <td>
                                    <span class="status-badge status-<?php echo e($statusClass); ?>">
                                        <span class="status-dot"></span>
                                        <?php echo e($p['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9" class="empty-cell">
                                <div class="empty-state">
                                    <span class="empty-icon">⌕</span>
                                    <h3>No appointments found</h3>
                                    <p>
                                        Try changing the date or removing some
                                        filters to find patient records.
                                    </p>
                                    <a href="view_appointments.php" class="clear-button">
                                        Clear all filters
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>QueueLess · Patient Management</span>
            <span><?php echo $total; ?> matching records</span>
        </div>

    </section>

    <footer class="page-footer">
        <span>© <?php echo date('Y'); ?> QueueLess</span>
        <span>Making hospital visits simpler.</span>
    </footer>

</main>

</body>
</html>
