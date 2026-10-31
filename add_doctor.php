
<?php
session_start();
include 'connection.php';

// Allow only admin
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: users.php");
    exit();
}

$message = "";
$messageType = "";

if (isset($_POST['submit'])) {

    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $department = $_POST['department'];
    $status = $_POST['is_active'];

    // Validate password
    if (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "error";
    } else {

        // Check whether email or username already exists
        $checkSql = "SELECT id FROM users
                     WHERE email = '$email'
                     OR username = '$username'
                     LIMIT 1";

        $checkResult = mysqli_query($conn, $checkSql);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $message = "Email or username already exists.";
            $messageType = "error";
        } else {

            $fname = mysqli_real_escape_string($conn, $fname);
            $lname = mysqli_real_escape_string($conn, $lname);
            $email = mysqli_real_escape_string($conn, $email);
            $username = mysqli_real_escape_string($conn, $username);
            $password = mysqli_real_escape_string($conn, $password);
            $department = mysqli_real_escape_string($conn, $department);
            $status = mysqli_real_escape_string($conn, $status);

            $sql = "INSERT INTO users
                    (fname, lname, email, username, password,
                     department, role, is_active)
                    VALUES
                    ('$fname', '$lname', '$email', '$username',
                     '$password', '$department', 'doctor', '$status')";

            $result = mysqli_query($conn, $sql);

            if ($result) {
                $message = "Doctor added successfully!";
                $messageType = "success";
            } else {
                $message = "Unable to add doctor. " . mysqli_error($conn);
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QueueLess | Add Doctor</title>
    <link rel="stylesheet" href="css/add_doctor.css">
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
        <span>Add Doctor</span>
    </div>

    <section class="page-heading">
        <div>
            <p class="eyebrow">DOCTOR MANAGEMENT</p>
            <h1>Add New Doctor</h1>
            <p class="subtitle">
                Register a doctor and configure their department and account status.
            </p>
        </div>

        <a href="manage_doctors.php" class="back-button">
            &larr; Back to Doctors
        </a>
    </section>

    <?php if ($message !== "") { ?>
        <div class="alert <?php echo $messageType; ?>" role="status">
            <span class="alert-icon">
                <?php echo $messageType === "success" ? "✓" : "!"; ?>
            </span>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php } ?>

    <section class="form-card">

        <div class="form-card-heading">
            <div class="heading-icon">+</div>
            <div>
                <h2>Doctor Information</h2>
                <p>Enter the details below to create a doctor account.</p>
            </div>
        </div>

        <div class="form-divider"></div>

        <form action="add_doctor.php" method="post">

            <div class="form-section-title">Personal Information</div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="fname">First Name <span>*</span></label>
                    <input type="text" id="fname" name="fname"
                           placeholder="Enter first name"
                           value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="lname">Last Name <span>*</span></label>
                    <input type="text" id="lname" name="lname"
                           placeholder="Enter last name"
                           value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span>*</span></label>
                    <input type="email" id="email" name="email"
                           placeholder="doctor@example.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="username">Username <span>*</span></label>
                    <input type="text" id="username" name="username"
                           placeholder="Create a username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required>
                </div>

                <div class="form-group full-width">
                    <label for="password">Password <span>*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password"
                               placeholder="Minimum 8 characters"
                               minlength="8" required>
                        <button type="button" class="toggle-password"
                                onclick="togglePassword()">Show</button>
                    </div>
                    <small>Use at least 8 characters for the password.</small>
                </div>

            </div>

            <div class="form-divider section-divider"></div>

            <div class="form-section-title">Professional Information</div>

            <div class="form-grid">

                <div class="form-group">
                    <label for="department">Department <span>*</span></label>
                    <select name="department" id="department" required>
                        <option value="">Select department</option>
                        <?php
                        $departments = [
                            "General Medicine",
                            "Orthopaedics",
                            "Paediatrics",
                            "Cardiology"
                        ];

                        foreach ($departments as $dept) {
                            $selected = (
                                isset($_POST['department']) &&
                                $_POST['department'] === $dept
                            ) ? "selected" : "";

                            echo '<option value="' .
                                 htmlspecialchars($dept) . '" ' .
                                 $selected . '>' .
                                 htmlspecialchars($dept) .
                                 '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_active">Account Status <span>*</span></label>
                    <select name="is_active" id="is_active" required>
                        <option value="Active"
                            <?php echo (!isset($_POST['is_active']) || $_POST['is_active'] === 'Active') ? 'selected' : ''; ?>>
                            Active
                        </option>
                        <option value="Inactive"
                            <?php echo (isset($_POST['is_active']) && $_POST['is_active'] === 'Inactive') ? 'selected' : ''; ?>>
                            Inactive
                        </option>
                    </select>
                    <small>Inactive doctors cannot log in.</small>
                </div>

            </div>

            <div class="form-actions">
                <a href="manage_doctors.php" class="cancel-button">Cancel</a>
                <button type="submit" name="submit" class="submit-button">
                    <span>+</span> Add Doctor
                </button>
            </div>

        </form>
    </section>

    <p class="security-note">
        <span>🔒</span>
        Doctor accounts are managed securely through the administrator dashboard.
    </p>

</main>

<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> QueueLess. Hospital queue management made simpler.</p>
</footer>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const button = document.querySelector(".toggle-password");

    if (password.type === "password") {
        password.type = "text";
        button.textContent = "Hide";
    } else {
        password.type = "password";
        button.textContent = "Show";
    }
}
</script>

</body>
</html>
