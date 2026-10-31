
<?php
session_start();
include 'connection.php';

// Only admin can access this page
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

// Get doctor ID from POST
if (isset($_POST['doctor_id'])) {
    $_SESSION['doctor_id'] = (int) $_POST['doctor_id'];
}

// Retrieve doctor ID from session
if (!isset($_SESSION['doctor_id'])) {
    header("Location: view_doctors.php");
    exit();
}

$doctor_id = (int) $_SESSION['doctor_id'];

// Fetch doctor details
$stmt = mysqli_prepare(
    $conn,
    "SELECT fname, lname, email, username, department, role, is_active
     FROM users
     WHERE id = ? AND role = 'doctor'
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to retrieve doctor details.");
}

mysqli_stmt_bind_param($stmt, "i", $doctor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {

    $doctor = mysqli_fetch_assoc($result);

    $fname = $doctor['fname'];
    $lname = $doctor['lname'];
    $email = $doctor['email'];
    $username = $doctor['username'];
    $department = $doctor['department'];
    $role = $doctor['role'];

    // Database stores 'Active' or 'Inactive'
    $isActive = (strtolower((string) $doctor['is_active']) === 'active');
    $status = $isActive ? 'Active' : 'Inactive';

} else {
    unset($_SESSION['doctor_id']);
    mysqli_stmt_close($stmt);
    header("Location: view_doctors.php");
    exit();
}

mysqli_stmt_close($stmt);

$fullName = trim($fname . ' ' . $lname);
$initial = strtoupper(substr($fname, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QueueLess | Doctor Details</title>
    <link rel="stylesheet" href="css/view_doctor.css">
</head>

<body>

<header class="topbar">
    <a href="admin_dashboard.php" class="brand">
        <span class="brand-icon">✚</span>
        <span>Queue<span class="brand-highlight">Less</span></span>
    </a>

    <div class="topbar-right">
        <span class="admin-label">Administrator</span>
        <span class="admin-avatar">A</span>
    </div>
</header>

<main class="page-container">

    <div class="breadcrumb">
        <a href="admin_dashboard.php">Dashboard</a>
        <span>/</span>
        <a href="manage_doctors.php">Manage Doctors</a>
        <span>/</span>
        <a href="view_doctors.php">Doctor Directory</a>
        <span>/</span>
        <span>Doctor Details</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">DOCTOR MANAGEMENT</p>
            <h1>Doctor Details</h1>
            <p class="subtitle">
                View professional information and manage this doctor's account.
            </p>
        </div>

        <a href="view_doctors.php" class="back-button">
            &larr; Back to Directory
        </a>
    </section>

    <section class="profile-banner">
        <div class="profile-avatar">
            <?php echo htmlspecialchars($initial); ?>
        </div>

        <div class="profile-info">
            <span class="profile-label">DOCTOR PROFILE</span>
            <h2>Dr. <?php echo htmlspecialchars($fullName); ?></h2>
            <p><?php echo htmlspecialchars($department); ?></p>

            <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                <span class="status-dot"></span>
                <?php echo htmlspecialchars($status); ?> Account
            </span>
        </div>

        <div class="profile-id">
            <span>DOCTOR ID</span>
            <strong>#<?php echo $doctor_id; ?></strong>
        </div>
    </section>

    <section class="details-card">
        <div class="card-heading">
            <div>
                <h2>Personal & Account Information</h2>
                <p>Registered information for this doctor.</p>
            </div>
            <span class="role-badge">Doctor</span>
        </div>

        <div class="details-grid">

            <div class="detail-item">
                <span class="detail-label">First Name</span>
                <p><?php echo htmlspecialchars($fname); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Last Name</span>
                <p><?php echo htmlspecialchars($lname); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Email Address</span>
                <p><?php echo htmlspecialchars($email); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Username</span>
                <p><?php echo htmlspecialchars($username); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Department</span>
                <p><?php echo htmlspecialchars($department); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Account Status</span>
                <p class="<?php echo $isActive ? 'active-text' : 'inactive-text'; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </p>
            </div>

            <div class="detail-item">
                <span class="detail-label">System Role</span>
                <p><?php echo htmlspecialchars($role); ?></p>
            </div>

        </div>
    </section>

    <section class="actions-card">
        <div class="card-heading">
            <div>
                <h2>Account Actions</h2>
                <p>Choose an action to manage this doctor's account.</p>
            </div>
        </div>

        <div class="action-list">

            <div class="action-item">
                <div class="action-icon edit-icon">✎</div>
                <div class="action-description">
                    <h3>Edit Doctor Details</h3>
                    <p>Update the doctor's name, email, department, or other details.</p>
                </div>

                <form method="post" action="edit_doctor.php">
                    <input type="hidden" name="doctor_id"
                           value="<?php echo $doctor_id; ?>">
                    <button type="submit" class="edit-button">Edit Doctor</button>
                </form>
            </div>

            <div class="action-item">
                <div class="action-icon <?php echo $isActive ? 'deactivate-icon' : 'activate-icon'; ?>">
                    <?php echo $isActive ? '−' : '✓'; ?>
                </div>

                <div class="action-description">
                    <h3><?php echo $isActive ? 'Deactivate Account' : 'Activate Account'; ?></h3>
                    <p>
                        <?php
                        echo $isActive
                            ? 'Prevent this doctor from logging in while preserving their records.'
                            : 'Allow this doctor to log in and manage their queue again.';
                        ?>
                    </p>
                </div>

                <?php if ($isActive) { ?>
                    <form method="post" action="deactivate_doctor.php"
                          onsubmit="return confirm('Deactivate this doctor account?');">
                        <input type="hidden" name="doctor_id"
                               value="<?php echo $doctor_id; ?>">
                        <button type="submit" class="deactivate-button">
                            Deactivate
                        </button>
                    </form>
                <?php } else { ?>
                    <form method="post" action="activate_doctor.php"
                          onsubmit="return confirm('Activate this doctor account?');">
                        <input type="hidden" name="doctor_id"
                               value="<?php echo $doctor_id; ?>">
                        <button type="submit" class="activate-button">
                            Activate
                        </button>
                    </form>
                <?php } ?>
            </div>

        </div>
    </section>

    <a href="view_doctors.php" class="bottom-link">
        &larr; Return to Doctor Directory
    </a>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

</body>
</html>
