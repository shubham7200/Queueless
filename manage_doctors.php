
<?php
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

$admin_name = htmlspecialchars(
    $_SESSION['user'],
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Doctors | QueueLess</title>

    <link rel="stylesheet" href="css/manage_doctors.css">
</head>
<body>

<div class="page-layout">

    <header class="topbar">
        <a href="admin_dashboard.php" class="brand">
            <span class="brand-icon">✚</span>
            <span>Queue<span class="brand-light">Less</span></span>
        </a>

        <div class="topbar-right">
            <a href="admin_dashboard.php" class="back-button">
                <span aria-hidden="true">←</span>
                Dashboard
            </a>

            <div class="admin-avatar">A</div>

            <div class="admin-info">
                <strong><?php echo $admin_name; ?></strong>
                <span>Administrator</span>
            </div>
        </div>
    </header>

    <main class="main-content">

        <div class="breadcrumb">
            <a href="admin_dashboard.php">Dashboard</a>
            <span>/</span>
            <span>Manage Doctors</span>
        </div>

        <section class="page-heading">
            <div class="heading-icon">✚</div>

            <div>
                <span class="eyebrow">MEDICAL TEAM MANAGEMENT</span>
                <h1>Manage Doctors</h1>
                <p>
                    Add new doctors and manage your hospital's
                    medical staff records.
                </p>
            </div>
        </section>

        <section class="welcome-panel">
            <div class="welcome-content">
                <span class="panel-label">DOCTOR ADMINISTRATION</span>
                <h2>Your medical team, organized.</h2>
                <p>
                    Keep doctor information up to date and make it
                    easier to manage your hospital's staff.
                </p>
            </div>

            <div class="medical-art" aria-hidden="true">
                <div class="art-ring ring-one"></div>
                <div class="art-ring ring-two"></div>
                <div class="medical-symbol">+</div>
            </div>
        </section>

        <section class="section-heading">
            <div>
                <h2>Doctor management</h2>
                <p>Choose an action to continue.</p>
            </div>
        </section>

        <section class="action-grid">

            <a href="add_doctor.php" class="action-card">

                <div class="card-top">
                    <div class="action-icon add-icon">+</div>
                    <span class="card-arrow" aria-hidden="true">↗</span>
                </div>

                <span class="card-label">CREATE RECORD</span>

                <h3>Add Doctor</h3>

                <p>
                    Register a new doctor by entering their
                    personal, professional, and department details.
                </p>

                <div class="card-divider"></div>

                <div class="card-action">
                    <span>Add a new doctor</span>
                    <span aria-hidden="true">→</span>
                </div>

            </a>

            <a href="view_doctors.php" class="action-card">

                <div class="card-top">
                    <div class="action-icon view-icon">☷</div>
                    <span class="card-arrow" aria-hidden="true">↗</span>
                </div>

                <span class="card-label">STAFF DIRECTORY</span>

                <h3>View Doctors</h3>

                <p>
                    View existing doctor records and access
                    available management actions.
                </p>

                <div class="card-divider"></div>

                <div class="card-action">
                    <span>View doctor records</span>
                    <span aria-hidden="true">→</span>
                </div>

            </a>

        </section>

        <a href="admin_dashboard.php" class="bottom-link">
            <span aria-hidden="true">←</span>
            Return to Admin Dashboard
        </a>

    </main>

    <footer class="page-footer">
        <span>© <?php echo date('Y'); ?> QueueLess</span>
        <span>Healthcare management, made simpler.</span>
    </footer>

</div>

</body>
</html>
