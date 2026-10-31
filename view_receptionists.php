
<?php
session_start();
include 'connection.php';

// Only admin can access this page
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

$message = "";
$messageType = "";

// View receptionist details
if (isset($_POST['view_id'])) {
    $_SESSION['receptionist_id'] = (int) $_POST['view_id'];
    header("Location: view_receptionist.php");
    exit();
}

// Delete receptionist
if (isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ? AND role = 'receptionist'"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: view_receptionists.php");
            exit();
        } else {
            $message = "Unable to delete receptionist: " . mysqli_error($conn);
            $messageType = "error";
        }

        mysqli_stmt_close($stmt);
    } else {
        $message = "Unable to prepare the delete operation.";
        $messageType = "error";
    }
}

// Fetch receptionists
$sql = "SELECT id, fname, lname, email, username, is_active
        FROM users
        WHERE role = 'receptionist'
        ORDER BY fname ASC";

$result = mysqli_query($conn, $sql);
$totalReceptionists = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QueueLess | View Receptionists</title>
    <link rel="stylesheet" href="css/view_receptionists.css">
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
        <span>Receptionist Directory</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">STAFF MANAGEMENT</p>
            <h1>Receptionist Directory</h1>
            <p class="subtitle">
                View receptionist accounts and manage your hospital's front-desk team.
            </p>
        </div>

        <a href="add_receptionists.php" class="add-button">
            <span>+</span> Add Receptionist
        </a>
    </section>

    <?php if ($message !== "") { ?>
        <div class="alert error" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <section class="stats-card">
        <div class="stats-icon">♙</div>
        <div>
            <p>Total Registered Receptionists</p>
            <h2><?php echo $totalReceptionists; ?></h2>
        </div>
    </section>

    <section class="table-card">

        <div class="table-heading">
            <div>
                <h2>All Receptionists</h2>
                <p>Manage your registered front-desk staff.</p>
            </div>

            <span class="count-badge">
                <?php echo $totalReceptionists; ?>
                <?php echo $totalReceptionists === 1 ? 'Receptionist' : 'Receptionists'; ?>
            </span>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>RECEPTIONIST</th>
                        <th>USERNAME</th>
                        <th>STATUS</th>
                        <th class="actions-heading">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                        <?php
                        $isActive = (
                            strtolower((string) $row['is_active']) === 'active'
                        );
                        ?>

                        <tr>
                            <td>
                                <div class="staff-cell">
                                    <div class="staff-avatar">
                                        <?php
                                        echo htmlspecialchars(
                                            strtoupper(substr($row['fname'], 0, 1))
                                        );
                                        ?>
                                    </div>

                                    <div class="staff-info">
                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['fname'] . ' ' . $row['lname']
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            <?php echo htmlspecialchars($row['email']); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="username-text">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <form method="post" action="view_receptionists.php">
                                        <input type="hidden" name="view_id"
                                               value="<?php echo (int) $row['id']; ?>">

                                        <button type="submit" class="view-button">
                                            View
                                        </button>
                                    </form>

                                    <form method="post" action="view_receptionists.php"
                                          onsubmit="return confirm('Are you sure you want to delete this receptionist? This action cannot be undone.');">

                                        <input type="hidden" name="delete_id"
                                               value="<?php echo (int) $row['id']; ?>">

                                        <button type="submit" class="delete-button">
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">+</div>
                                <h3>No receptionists found</h3>
                                <p>Add a receptionist to see their information here.</p>
                                <a href="add_receptionists.php" class="add-button">
                                    + Add First Receptionist
                                </a>
                            </div>
                        </td>
                    </tr>

                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <span>
                Showing <?php echo $totalReceptionists; ?>
                registered
                <?php echo $totalReceptionists === 1 ? 'receptionist' : 'receptionists'; ?>
            </span>

            <a href="manage_receptionists.php">
                Back to Manage Receptionists &rarr;
            </a>
        </div>

    </section>

    <a href="admin_dashboard.php" class="bottom-link">
        &larr; Back to Admin Dashboard
    </a>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

</body>
</html>
