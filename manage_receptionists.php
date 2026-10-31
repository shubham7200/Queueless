
<?php
session_start();

// Only admin can access this page
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QueueLess | Manage Receptionists</title>
    <link rel="stylesheet" href="css/manage_receptionists.css">
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
        <span>Manage Receptionists</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">STAFF MANAGEMENT</p>
            <h1>Manage Receptionists</h1>
            <p class="subtitle">
                Create receptionist accounts and manage your hospital's front-desk staff.
            </p>
        </div>

        <a href="admin_dashboard.php" class="back-button">
            &larr; Dashboard
        </a>
    </section>

    <section class="welcome-banner">
        <div class="banner-content">
            <span class="banner-label">QUEUELESS ADMINISTRATION</span>
            <h2>Manage your front desk team</h2>
            <p>
                Give receptionists access to patient registration and queue
                management so your hospital can run smoothly.
            </p>
        </div>

        <div class="banner-art" aria-hidden="true">
            <div class="art-circle">
                <div class="art-person">
                    <div class="person-head"></div>
                    <div class="person-body"></div>
                </div>
                <div class="art-card">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="art-check">✓</div>
            </div>
        </div>
    </section>

    <section class="section-heading">
        <div>
            <h2>Receptionist Management</h2>
            <p>Choose an action to continue.</p>
        </div>
    </section>

    <div class="management-grid">

        <article class="management-card">
            <div class="card-top">
                <div class="card-icon add-icon">
                    <span>+</span>
                </div>
                <span class="card-tag">NEW ACCOUNT</span>
            </div>

            <h3>Add Receptionist</h3>
            <p>
                Register a new receptionist with login credentials and
                the required account details.
            </p>

            <div class="card-divider"></div>

            <a href="add_receptionists.php" class="card-button primary-button">
                Add Receptionist <span>&rarr;</span>
            </a>
        </article>

        <article class="management-card">
            <div class="card-top">
                <div class="card-icon view-icon">
                    <span>☷</span>
                </div>
                <span class="card-tag">STAFF DIRECTORY</span>
            </div>

            <h3>View Receptionists</h3>
            <p>
                View registered receptionists and access their
                account information and management options.
            </p>

            <div class="card-divider"></div>

            <a href="view_receptionists.php" class="card-button secondary-button">
                View Receptionists <span>&rarr;</span>
            </a>
        </article>

    </div>

    <a href="admin_dashboard.php" class="bottom-link">
        &larr; Back to Admin Dashboard
    </a>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

</body>
</html>
