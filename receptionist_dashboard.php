
<?php
session_start();
include 'connection.php';

// Check receptionist login
if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'receptionist'
) {
    header("Location: users.php");
    exit();
}

$username = $_SESSION['user'];

// Fetch receptionist profile
$username_safe = mysqli_real_escape_string($conn, $username);

$query = "SELECT id, fname, lname, email, username, role,
                 department, is_active
          FROM users
          WHERE username = '$username_safe'
          AND role = 'receptionist'
          LIMIT 1";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $profile = mysqli_fetch_assoc($result);

    // Prevent inactive receptionists from continuing
    if ($profile['is_active'] !== 'Active') {
        session_unset();
        session_destroy();
        header("Location: users.php");
        exit();
    }
} else {
    session_unset();
    session_destroy();
    header("Location: users.php");
    exit();
}

$full_name = trim($profile['fname'] . ' ' . $profile['lname']);
$first_name = $profile['fname'];
$initials = strtoupper(
    substr($profile['fname'], 0, 1) .
    substr($profile['lname'], 0, 1)
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Receptionist Dashboard | QueueLess</title>

    <link rel="stylesheet" href="css/receptionist_dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">
</head>

<body>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">

        <a href="index.html" class="brand">
            <span class="brand-icon">✚</span>
            <span>Queue<span class="brand-highlight">Less</span></span>
        </a>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="sidebar-nav">

            <a href="receptionist_dashboard.php" class="nav-link active">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="register_patient.php" class="nav-link">
                <span class="nav-icon">＋</span>
                <span>Register Patient</span>
            </a>

            <a href="view_appointments.php" class="nav-link">
                <span class="nav-icon">▤</span>
                <span>Appointments</span>
            </a>

            <a href="view_appointments.php" class="nav-link">
                <span class="nav-icon">☷</span>
                <span>Queue Overview</span>
            </a>

        </nav>

        <div class="sidebar-bottom">
            <div class="help-card">
                <div class="help-icon">✦</div>
                <h4>Need assistance?</h4>
                <p>Contact your administrator if you need help using QueueLess.</p>
            </div>

            <a href="logout.php" class="logout-link">
                <span class="nav-icon">↪</span>
                <span>Log out</span>
            </a>
        </div>

    </aside>

    <!-- Main area -->
    <main class="main-content">

        <!-- Topbar -->
        <header class="topbar">

            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle"
                        type="button" aria-label="Toggle navigation"
                        aria-expanded="false">☰</button>

                <div>
                    <p class="breadcrumb">Workspace / <strong>Dashboard</strong></p>
                </div>
            </div>

            <div class="topbar-right">

                <div class="date-display">
                    <span>▦</span>
                    <span><?php echo date('d M Y'); ?></span>
                </div>

                <div class="profile-area">
                    <button type="button" class="profile-button"
                            id="profileButton" aria-expanded="false">
                        <span class="avatar">
                            <?php echo htmlspecialchars($initials); ?>
                        </span>

                        <span class="profile-text">
                            <strong><?php echo htmlspecialchars($full_name); ?></strong>
                            <small>Receptionist</small>
                        </span>

                        <span class="chevron">⌄</span>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">

                        <div class="dropdown-heading">Your profile</div>

                        <p>
                            <span>Name</span>
                            <strong><?php echo htmlspecialchars($full_name); ?></strong>
                        </p>

                        <p>
                            <span>Email</span>
                            <strong><?php echo htmlspecialchars($profile['email']); ?></strong>
                        </p>

                        <p>
                            <span>Username</span>
                            <strong><?php echo htmlspecialchars($profile['username']); ?></strong>
                        </p>

                        <p>
                            <span>Department</span>
                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    !empty($profile['department'])
                                        ? $profile['department']
                                        : 'Not assigned'
                                );
                                ?>
                            </strong>
                        </p>

                        <a href="logout.php" class="dropdown-logout">
                            ↪ &nbsp; Log out
                        </a>
                    </div>
                </div>

            </div>

        </header>

        <!-- Dashboard content -->
        <section class="page-content">

            <!-- Welcome banner -->
            <section class="welcome-banner">

                <div class="welcome-copy">
                    <span class="welcome-tag">
                        <span class="status-dot"></span>
                        RECEPTION WORKSPACE
                    </span>

                    <h1>Good to see you, <?php echo htmlspecialchars($first_name); ?>!</h1>

                    <p>
                        Manage patient registrations and appointments
                        efficiently. Let's keep the hospital moving smoothly.
                    </p>

                    <a href="register_patient.php" class="primary-button">
                        <span>＋</span> Register a patient
                    </a>
                </div>

                <div class="welcome-art" aria-hidden="true">
                    <div class="art-circle circle-one"></div>
                    <div class="art-circle circle-two"></div>

                    <div class="medical-card">
                        <div class="medical-cross">+</div>
                        <div class="medical-line line-one"></div>
                        <div class="medical-line line-two"></div>
                        <div class="medical-line line-three"></div>
                        <div class="medical-check">✓</div>
                    </div>

                    <div class="floating-chip chip-one">✓ Patient care</div>
                    <div class="floating-chip chip-two">♡ QueueLess</div>
                </div>

            </section>

            <!-- Page heading -->
            <div class="section-heading">
                <div>
                    <h2>Your workspace</h2>
                    <p>Quick access to your daily receptionist tasks.</p>
                </div>

                <span class="today-label">Today · <?php echo date('l'); ?></span>
            </div>

            <!-- Quick action cards -->
            <div class="action-grid">

                <article class="action-card registration-card">
                    <div class="card-top">
                        <div class="action-icon icon-blue">＋</div>
                        <span class="card-arrow">↗</span>
                    </div>

                    <span class="card-eyebrow">PATIENT SERVICES</span>
                    <h3>Register Patient</h3>

                    <p>
                        Register new or returning patients and generate
                        their daily queue tokens.
                    </p>

                    <a href="register_patient.php" class="card-link">
                        Start registration <span>→</span>
                    </a>
                </article>

                <article class="action-card appointment-card">
                    <div class="card-top">
                        <div class="action-icon icon-purple">▤</div>
                        <span class="card-arrow">↗</span>
                    </div>

                    <span class="card-eyebrow">PATIENT RECORDS</span>
                    <h3>Appointments</h3>

                    <p>
                        View registered patients, search records,
                        and review appointment details.
                    </p>

                    <a href="view_appointments.php" class="card-link">
                        View appointments <span>→</span>
                    </a>
                </article>

                <article class="action-card queue-card">
                    <div class="card-top">
                        <div class="action-icon icon-green">☷</div>
                        <span class="card-arrow">↗</span>
                    </div>

                    <span class="card-eyebrow">QUEUE TRACKING</span>
                    <h3>Queue Overview</h3>

                    <p>
                        Open the appointment list to check patient tokens,
                        doctors, and queue statuses.
                    </p>

                    <a href="view_appointments.php" class="card-link">
                        View queue records <span>→</span>
                    </a>
                </article>

            </div>

            <!-- Workflow panel -->
            <section class="workflow-panel">

                <div class="workflow-heading">
                    <div class="workflow-icon">✦</div>
                    <div>
                        <h2>Your daily workflow</h2>
                        <p>Follow these steps to keep registrations organised.</p>
                    </div>
                </div>

                <div class="workflow-steps">

                    <div class="workflow-step">
                        <span class="step-number">01</span>
                        <div>
                            <h3>Register the patient</h3>
                            <p>Enter patient details and select the department and doctor.</p>
                        </div>
                    </div>

                    <div class="workflow-step">
                        <span class="step-number">02</span>
                        <div>
                            <h3>Generate a token</h3>
                            <p>Complete registration and provide the patient with their token.</p>
                        </div>
                    </div>

                    <div class="workflow-step">
                        <span class="step-number">03</span>
                        <div>
                            <h3>Review appointments</h3>
                            <p>Check patient records and token statuses when needed.</p>
                        </div>
                    </div>

                </div>

                <a href="register_patient.php" class="secondary-button">
                    Get started <span>→</span>
                </a>

            </section>

            <!-- Footer -->
            <footer class="dashboard-footer">
                <span>© <?php echo date('Y'); ?> QueueLess</span>
                <span>Making hospital visits simpler.</span>
            </footer>

        </section>

    </main>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Profile dropdown
    profileButton.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = profileDropdown.classList.toggle('show');
        profileButton.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', function (event) {
        if (!profileDropdown.contains(event.target) &&
            !profileButton.contains(event.target)) {
            profileDropdown.classList.remove('show');
            profileButton.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            profileDropdown.classList.remove('show');
            profileButton.setAttribute('aria-expanded', 'false');
            sidebar.classList.remove('sidebar-open');
            sidebarOverlay.classList.remove('visible');
            menuToggle.setAttribute('aria-expanded', 'false');
        }
    });

    // Mobile sidebar
    function toggleSidebar() {
        const isOpen = sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('visible', isOpen);
        menuToggle.setAttribute('aria-expanded', String(isOpen));
    }

    menuToggle.addEventListener('click', toggleSidebar);

    sidebarOverlay.addEventListener('click', function () {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('visible');
        menuToggle.setAttribute('aria-expanded', 'false');
    });
</script>

</body>
</html>
