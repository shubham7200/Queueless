
<?php
session_start();
include 'connection.php';

// Only admin can view/manage doctors
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

$message = "";
$messageType = "";

// View doctor details
if (isset($_POST['view_id'])) {
    $_SESSION['doctor_id'] = (int) $_POST['view_id'];
    header("Location: view_doctor.php");
    exit();
}

// Delete doctor
if (isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ? AND role = 'doctor'"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: view_doctors.php");
            exit();
        } else {
            $message = "Error deleting doctor: " . mysqli_error($conn);
            $messageType = "error";
        }

        mysqli_stmt_close($stmt);
    } else {
        $message = "Unable to prepare the delete operation.";
        $messageType = "error";
    }
}

// Fetch doctors
$sql = "SELECT id, fname, lname, email, username, department, is_active
        FROM users
        WHERE role = 'doctor'
        ORDER BY fname ASC";

$result = mysqli_query($conn, $sql);
$totalDoctors = $result ? mysqli_num_rows($result) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QueueLess | View Doctors</title>
    <link rel="stylesheet" href="css/view_doctors.css">
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
        <span>Doctor List</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">DOCTOR MANAGEMENT</p>
            <h1>Doctor Directory</h1>
            <p class="subtitle">
                View doctor information, check account status, and manage doctor records.
            </p>
        </div>

        <a href="add_doctor.php" class="add-button">
            <span>+</span> Add Doctor
        </a>
    </section>

    <?php if ($message !== "") { ?>
        <div class="alert error">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <section class="stats-card">
        <div class="stats-icon">+</div>
        <div>
            <p>Total Registered Doctors</p>
            <h2><?php echo $totalDoctors; ?></h2>
        </div>
    </section>

    <section class="table-card">

        <div class="table-heading">
            <div>
                <h2>All Doctors</h2>
                <p>Manage doctors registered in QueueLess.</p>
            </div>

            <span class="count-badge">
                <?php echo $totalDoctors; ?>
                <?php echo $totalDoctors === 1 ? 'Doctor' : 'Doctors'; ?>
            </span>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>DOCTOR</th>
                        <th>DEPARTMENT</th>
                        <th>USERNAME</th>
                        <th>STATUS</th>
                        <th class="actions-heading">ACTIONS</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                        <tr>
                            <td>
                                <div class="doctor-cell">
                                    <div class="doctor-avatar">
                                        <?php
                                        echo strtoupper(
                                            substr($row['fname'], 0, 1)
                                        );
                                        ?>
                                    </div>

                                    <div class="doctor-info">
                                        <strong>
                                            Dr. <?php
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
                                <span class="department-text">
                                    <?php echo htmlspecialchars($row['department']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="username-text">
                                    <?php echo htmlspecialchars($row['username']); ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                $active = $row['is_active'] === 'Active';
                                ?>
                                <span class="status-badge <?php echo $active ? 'active' : 'inactive'; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo $active ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <form method="post" action="view_doctors.php">
                                        <input type="hidden" name="view_id"
                                               value="<?php echo (int) $row['id']; ?>">

                                        <button type="submit" class="view-button">
                                            View
                                        </button>
                                    </form>

                                    <form method="post" action="view_doctors.php"
                                          onsubmit="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.');">

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
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-icon">+</div>
                                <h3>No doctors found</h3>
                                <p>Add a doctor to see their information here.</p>
                                <a href="add_doctor.php" class="add-button">
                                    + Add First Doctor
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
                Showing <?php echo $totalDoctors; ?>
                registered <?php echo $totalDoctors === 1 ? 'doctor' : 'doctors'; ?>
            </span>

            <a href="manage_doctors.php">Back to Manage Doctors &rarr;</a>
        </div>

    </section>

    <a href="admin_dashboard.php" class="dashboard-link">
        &larr; Back to Admin Dashboard
    </a>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

</body>
</html>
