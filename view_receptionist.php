
<?php
session_start();
include 'connection.php';

// Only admin can access this page
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

// Get receptionist ID from POST if provided
if (isset($_POST['receptionist_id'])) {
    $_SESSION['receptionist_id'] = (int) $_POST['receptionist_id'];
}

// Retrieve receptionist ID from session
if (!isset($_SESSION['receptionist_id'])) {
    header("Location: view_receptionists.php");
    exit();
}

$receptionist_id = (int) $_SESSION['receptionist_id'];

// Fetch receptionist details
$stmt = mysqli_prepare(
    $conn,
    "SELECT fname, lname, email, username, role, is_active
     FROM users
     WHERE id = ? AND role = 'receptionist'
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to retrieve receptionist details.");
}

mysqli_stmt_bind_param($stmt, "i", $receptionist_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $receptionist = mysqli_fetch_assoc($result);

    $fname = $receptionist['fname'];
    $lname = $receptionist['lname'];
    $email = $receptionist['email'];
    $username = $receptionist['username'];
    $role = $receptionist['role'];

    // Database stores 'Active' or 'Inactive'
    $isActive = (
        strtolower((string) $receptionist['is_active']) === 'active'
    );

    $status = $isActive ? 'Active' : 'Inactive';

} else {
    unset($_SESSION['receptionist_id']);
    mysqli_stmt_close($stmt);
    header("Location: view_receptionists.php");
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
    <title>QueueLess | Receptionist Details</title>
    <link rel="stylesheet" href="css/view_receptionist.css">
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
        <a href="manage_receptionists.php">Manage Receptionists</a>
        <span>/</span>
        <a href="view_receptionists.php">Receptionist Directory</a>
        <span>/</span>
        <span>Staff Details</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">STAFF MANAGEMENT</p>
            <h1>Receptionist Details</h1>
            <p class="subtitle">
                View account information and manage this receptionist's access.
            </p>
        </div>

        <a href="view_receptionists.php" class="back-button">
            &larr; Back to Directory
        </a>
    </section>

    <section class="profile-banner">
        <div class="profile-avatar">
            <?php echo htmlspecialchars($initial); ?>
        </div>

        <div class="profile-info">
            <span class="profile-label">RECEPTIONIST PROFILE</span>
            <h2><?php echo htmlspecialchars($fullName); ?></h2>
            <p>Hospital Front Desk</p>

            <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                <span class="status-dot"></span>
                <?php echo htmlspecialchars($status); ?> Account
            </span>
        </div>

        <div class="profile-id">
            <span>STAFF ID</span>
            <strong>#<?php echo $receptionist_id; ?></strong>
        </div>
    </section>

    <section class="details-card">
        <div class="card-heading">
            <div>
                <h2>Personal & Account Information</h2>
                <p>Registered details for this receptionist.</p>
            </div>

            <span class="role-badge">Receptionist</span>
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
                <span class="detail-label">System Role</span>
                <p><?php echo htmlspecialchars($role); ?></p>
            </div>

            <div class="detail-item">
                <span class="detail-label">Account Status</span>
                <p class="<?php echo $isActive ? 'active-text' : 'inactive-text'; ?>">
                    <?php echo htmlspecialchars($status); ?>
                </p>
            </div>

        </div>
    </section>

    <section class="actions-card">
        <div class="card-heading">
            <div>
                <h2>Account Actions</h2>
                <p>Manage this receptionist's account.</p>
            </div>
        </div>

        <div class="action-list">

            <div class="action-item">
                <div class="action-icon edit-icon">✎</div>

                <div class="action-description">
                    <h3>Edit Receptionist Details</h3>
                    <p>Update the receptionist's account information.</p>
                </div>

                <form method="post" action="edit_receptionist.php">
                    <input type="hidden" name="receptionist_id"
                           value="<?php echo $receptionist_id; ?>">

                    <button type="submit" class="edit-button">
                        Edit Details
                    </button>
                </form>
            </div>

            <div class="action-item">
                <div class="action-icon <?php echo $isActive ? 'deactivate-icon' : 'activate-icon'; ?>">
                    <?php echo $isActive ? '−' : '✓'; ?>
                </div>

                <div class="action-description">
                    <h3>
                        <?php echo $isActive ? 'Deactivate Account' : 'Activate Account'; ?>
                    </h3>

                    <p>
                        <?php
                        echo $isActive
                            ? 'Prevent this receptionist from logging in while preserving existing records.'
                            : 'Restore this receptionist’s ability to log in.';
                        ?>
                    </p>
                </div>

                <?php if ($isActive) { ?>

                    <form method="post" action="deactivate_receptionist.php"
                          onsubmit="return confirm('Deactivate this receptionist account?');">

                        <input type="hidden" name="receptionist_id"
                               value="<?php echo $receptionist_id; ?>">

                        <button type="submit" class="deactivate-button">
                            Deactivate
                        </button>
                    </form>

                <?php } else { ?>

                    <form method="post" action="activate_receptionist.php"
                          onsubmit="return confirm('Activate this receptionist account?');">

                        <input type="hidden" name="receptionist_id"
                               value="<?php echo $receptionist_id; ?>">

                        <button type="submit" class="activate-button">
                            Activate
                        </button>
                    </form>

                <?php } ?>
            </div>

        </div>
    </section>

    <a href="view_receptionists.php" class="bottom-link">
        &larr; Return to Receptionist Directory
    </a>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

</body>
</html>
