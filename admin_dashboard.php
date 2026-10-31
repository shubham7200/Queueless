
<?php
session_start();

// Check whether the user is logged in as admin
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

    <title>Admin Dashboard | QueueLess</title>

    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">

        <a href="admin_dashboard.php" class="brand">
            <span class="brand-icon">✚</span>
            <span>Queue<span class="brand-light">Less</span></span>
        </a>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-link active">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="manage_doctors.php" class="nav-link">
                <span class="nav-icon">✚</span>
                <span>Manage Doctors</span>
            </a>

            <a href="manage_receptionists.php" class="nav-link">
                <span class="nav-icon">♙</span>
                <span>Manage Receptionists</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="support-card">
                <div class="support-icon">i</div>
                <strong>QueueLess Admin</strong>
                <p>Manage your hospital team from one place.</p>
            </div>

            <a href="logout.php" class="logout-link">
                <span class="nav-icon">↪</span>
                <span>Logout</span>
            </a>
        </div>

        <div class="sidebar-footer">
            QueueLess Administration
        </div>

    </aside>

    <!-- Main dashboard -->
    <main class="main-content">

        <!-- Top bar -->
        <header class="topbar">

            <div class="topbar-left">
                <button type="button" class="menu-toggle"
                        id="menuToggle" aria-label="Toggle sidebar"
                        aria-expanded="false">
                    ☰
                </button>

                <div>
                    <p class="breadcrumb">Workspace / Dashboard</p>
                    <h2>Admin Dashboard</h2>
                </div>
            </div>

            <div class="topbar-right">
                <div class="admin-avatar">A</div>

                <div class="admin-info">
                    <strong><?php echo $admin_name; ?></strong>
                    <span>Administrator</span>
                </div>
            </div>

        </header>

        <div class="content-area">

            <!-- Welcome section -->
            <section class="welcome-banner">

                <div class="welcome-content">
                    <span class="welcome-label">
                        <span class="welcome-dot"></span>
                        ADMINISTRATOR WORKSPACE
                    </span>

                    <h1>Welcome back, <?php echo $admin_name; ?>!</h1>

                    <p>
                        Manage your hospital team, keep staff information
                        organized, and oversee your QueueLess workspace.
                    </p>

                    <a href="manage_doctors.php" class="welcome-button">
                        Manage your team
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="welcome-art" aria-hidden="true">
                    <div class="art-circle art-circle-one"></div>
                    <div class="art-circle art-circle-two"></div>

                    <div class="art-card">
                        <div class="art-cross">+</div>
                        <div class="art-lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="art-check">✓</div>
                    </div>
                </div>

            </section>

            <!-- Page introduction -->
            <section class="section-heading">
                <div>
                    <span class="section-eyebrow">OVERVIEW</span>
                    <h2>Manage your workspace</h2>
                    <p>Choose a section to manage your hospital staff.</p>
                </div>
            </section>

            <!-- Management cards -->
            <section class="management-grid">

                <a href="manage_doctors.php" class="management-card">

                    <div class="card-top">
                        <div class="management-icon doctor-icon">
                            <span aria-hidden="true">✚</span>
                        </div>

                        <span class="card-arrow" aria-hidden="true">↗</span>
                    </div>

                    <span class="card-category">MEDICAL TEAM</span>

                    <h3>Manage Doctors</h3>

                    <p>
                        Manage doctor accounts and maintain your
                        hospital's medical staff information.
                    </p>

                    <div class="card-divider"></div>

                    <div class="card-action">
                        <span>Open doctor management</span>
                        <span aria-hidden="true">→</span>
                    </div>

                </a>

                <a href="manage_receptionists.php" class="management-card">

                    <div class="card-top">
                        <div class="management-icon receptionist-icon">
                            <span aria-hidden="true">♙</span>
                        </div>

                        <span class="card-arrow" aria-hidden="true">↗</span>
                    </div>

                    <span class="card-category">FRONT DESK TEAM</span>

                    <h3>Manage Receptionists</h3>

                    <p>
                        Manage receptionist accounts and keep
                        your front desk team organized.
                    </p>

                    <div class="card-divider"></div>

                    <div class="card-action">
                        <span>Open receptionist management</span>
                        <span aria-hidden="true">→</span>
                    </div>

                </a>

            </section>

            <!-- Quick access -->
            <section class="quick-access">

                <div class="quick-access-heading">
                    <div class="quick-access-icon">⌘</div>

                    <div>
                        <h3>Quick access</h3>
                        <p>Jump directly to a management section.</p>
                    </div>
                </div>

                <div class="quick-links">

                    <a href="manage_doctors.php" class="quick-link">
                        <span class="quick-link-icon">✚</span>
                        <span>Doctors</span>
                        <span class="quick-arrow">→</span>
                    </a>

                    <a href="manage_receptionists.php" class="quick-link">
                        <span class="quick-link-icon">♙</span>
                        <span>Receptionists</span>
                        <span class="quick-arrow">→</span>
                    </a>

                </div>

            </section>

            <!-- Footer -->
            <footer class="content-footer">
                <span>© <?php echo date('Y'); ?> QueueLess</span>
                <span>Healthcare management, made simpler.</span>
            </footer>

        </div>

    </main>

</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function closeSidebar() {
    sidebar.classList.remove('sidebar-open');
    sidebarOverlay.classList.remove('overlay-visible');
    menuToggle.setAttribute('aria-expanded', 'false');
}

menuToggle.addEventListener('click', function () {
    const isOpen = sidebar.classList.toggle('sidebar-open');

    sidebarOverlay.classList.toggle('overlay-visible', isOpen);
    menuToggle.setAttribute('aria-expanded', String(isOpen));
});

sidebarOverlay.addEventListener('click', closeSidebar);

document.querySelectorAll('.sidebar-nav a, .logout-link').forEach(function (link) {
    link.addEventListener('click', closeSidebar);
});
</script>

</body>
</html>
