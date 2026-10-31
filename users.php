
<?php
session_start();
include 'connection.php';

$message = "";

if (isset($_POST['submit'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department = $_POST['department'] ?? '';

    if ($role === 'admin') {

        $sql = "SELECT * FROM admin
                WHERE username = '$username'
                AND password = '$password'";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $_SESSION['user'] = $username;
            $_SESSION['role'] = 'admin';

            header("Location: admin_dashboard.php");
            exit();
        } else {
            $message = "Invalid username or password.";
        }

    } elseif ($role === 'doctor') {

        $sql = "SELECT * FROM users
                WHERE username = '$username'
                AND password = '$password'
                AND role = 'doctor'
                AND department = '$department'
                AND is_active = 'Active'";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $_SESSION['user'] = $username;
            $_SESSION['role'] = 'doctor';
            $_SESSION['department'] = $department;

            header("Location: doctor_dashboard.php");
            exit();
        } else {
            $message = "Invalid username, password, or department.";
        }

    } elseif ($role === 'receptionist') {

        $sql = "SELECT * FROM users
                WHERE username = '$username'
                AND password = '$password'
                AND role = 'receptionist'
                AND department = '$department'
                AND is_active = 'Active'";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $_SESSION['user'] = $username;
            $_SESSION['role'] = 'receptionist';

            header("Location: receptionist_dashboard.php");
            exit();
        } else {
            $message = "Invalid username, password, or department.";
        }

    } else {
        $message = "Please select a valid role.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sign In | QueueLess</title>

    <link rel="stylesheet" href="css/users.css">
</head>
<body>

<div class="login-page">

    <div class="login-visual">
        <a href="index.html" class="brand">
            <span class="brand-icon">✚</span>
            <span>Queue<span class="brand-light">Less</span></span>
        </a>

        <div class="visual-content">
            <span class="eyebrow">SMARTER HEALTHCARE</span>

            <h1>Less waiting.<br>More caring.</h1>

            <p>
                A simpler way to manage hospital queues,
                coordinate patient visits, and deliver a
                better healthcare experience.
            </p>

            <div class="visual-card">
                <div class="visual-card-icon">✓</div>
                <div>
                    <strong>Care without the chaos</strong>
                    <span>Organized queues. Better experiences.</span>
                </div>
            </div>
        </div>

        <div class="visual-footer">
            <span class="status-dot"></span>
            A smarter way to manage patient flow
        </div>
    </div>

    <main class="login-main">
        <div class="login-card">

            <a href="index.html" class="mobile-brand">
                <span class="brand-icon">Q</span>
                QueueLess
            </a>

            <div class="form-heading">
                <span class="welcome-label">WELCOME BACK</span>
                <h2 id="formTitle">Sign in to QueueLess</h2>
                <p>Enter your credentials to access your account.</p>
            </div>

            <?php if ($message !== ""): ?>
                <div class="error-message" role="alert">
                    <span class="error-icon">!</span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="users.php" method="post">

                <div class="form-group">
                    <label for="role">Sign in as</label>

                    <div class="input-wrapper">
                        <span class="input-icon">◎</span>
                        <select name="role" id="role" required>
                            <option value="">Select your role</option>
                            <option value="admin"
                                <?php if (($_POST['role'] ?? '') === 'admin') echo 'selected'; ?>>
                                Admin
                            </option>
                            <option value="doctor"
                                <?php if (($_POST['role'] ?? '') === 'doctor') echo 'selected'; ?>>
                                Doctor
                            </option>
                            <option value="receptionist"
                                <?php if (($_POST['role'] ?? '') === 'receptionist') echo 'selected'; ?>>
                                Receptionist
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="departmentGroup">
                    <label for="department">Department</label>

                    <div class="input-wrapper">
                        <span class="input-icon">✚</span>
                        <select name="department" id="department">
                            <option value="">Select department</option>
                            <option value="General Medicine">General Medicine</option>
                            <option value="Orthopaedics">Orthopaedics</option>
                            <option value="Paediatrics">Paediatrics</option>
                            <option value="Cardiology">Cardiology</option>
                        </select>
                    </div>
                    <small class="field-hint">Required for doctors and receptionists.</small>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>

                    <div class="input-wrapper">
                        <span class="input-icon">♙</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-wrapper">
                        <span class="input-icon">⌑</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle"
                                id="togglePassword" aria-label="Show password">
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" name="submit" class="login-submit">
                    Sign In
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <a href="index.html" class="back-link">
                <span aria-hidden="true">←</span> Back to home
            </a>

            <p class="security-note">
                <span aria-hidden="true">▣</span>
                Secure access for authorized hospital staff
            </p>

        </div>

        <footer class="page-footer">
            © 2026 QueueLess. Healthcare, made simpler.
        </footer>
    </main>

</div>

<script>
const roleSelect = document.getElementById('role');
const departmentGroup = document.getElementById('departmentGroup');
const departmentSelect = document.getElementById('department');
const formTitle = document.getElementById('formTitle');

function updateRoleFields() {
    const role = roleSelect.value;
    const needsDepartment = role === 'doctor' ;

    departmentGroup.hidden = !needsDepartment;
    departmentSelect.required = needsDepartment;

    if (!needsDepartment) {
        departmentSelect.value = '';
    }

    if (role === 'admin') {
        formTitle.textContent = 'Admin sign in';
    } else if (role === 'doctor') {
        formTitle.textContent = 'Doctor sign in';
    } else if (role === 'receptionist') {
        formTitle.textContent = 'Receptionist sign in';
    } else {
        formTitle.textContent = 'Sign in to QueueLess';
    }
}

roleSelect.addEventListener('change', updateRoleFields);
updateRoleFields();

const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

togglePassword.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';

    passwordInput.type = isPassword ? 'text' : 'password';
    togglePassword.textContent = isPassword ? 'Hide' : 'Show';
    togglePassword.setAttribute(
        'aria-label',
        isPassword ? 'Hide password' : 'Show password'
    );
});
</script>

</body>
</html>
