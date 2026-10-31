
<?php
session_start();
include 'connection.php';

// Check receptionist login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receptionist') {
    header("Location: users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header("Location: register_patient_form.php");
    exit();
}

// Get form data
$fname = trim($_POST['fname'] ?? '');
$mname = trim($_POST['mname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$gender = $_POST['gender'] ?? '';
$age = $_POST['age'] ?? '';
$mobile_number = trim($_POST['mobile_number'] ?? '');
$blood_group = $_POST['blood_group'] ?? '';
$patient_type = $_POST['patient_type'] ?? '';
$department = $_POST['department'] ?? '';
$doctor_id = $_POST['doctor_id'] ?? '';

// Basic validation
if (
    $fname === '' ||
    $lname === '' ||
    !in_array($gender, ['Male', 'Female', 'Other'], true) ||
    !filter_var($age, FILTER_VALIDATE_INT) ||
    (int)$age < 0 ||
    (int)$age > 130 ||
    !preg_match('/^[0-9]{10}$/', $mobile_number) ||
    !in_array($blood_group, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'], true) ||
    !in_array($patient_type, ['Regular', 'First time'], true) ||
    !in_array($department, ['General Medicine', 'Orthopaedics', 'Paediatrics', 'Cardiology'], true) ||
    !filter_var($doctor_id, FILTER_VALIDATE_INT) ||
    (int)$doctor_id < 1
) {
    die("Invalid patient details. Please go back and check the form.");
}

$age = (int)$age;
$doctor_id = (int)$doctor_id;
$registration_date = date('Y-m-d');

// Start transaction
mysqli_begin_transaction($conn);

try {

    // Verify doctor exists, is active, and belongs to selected department
    $doctorQuery = "SELECT id
                    FROM users
                    WHERE id = $doctor_id
                    AND role = 'doctor'
                    AND is_active = 'Active'
                    AND department = '" .
                    mysqli_real_escape_string($conn, $department) . "'
                    FOR UPDATE";

    $doctorResult = mysqli_query($conn, $doctorQuery);

    if (!$doctorResult || mysqli_num_rows($doctorResult) !== 1) {
        throw new Exception("The selected doctor is unavailable for this department.");
    }

    /*
       Lock this doctor's queue for today's registrations.
       The patients table must use InnoDB for transaction locking.
    */
    $lockQuery = "SELECT id
                  FROM patients
                  WHERE doctor_id = $doctor_id
                  AND registration_date = '$registration_date'
                  ORDER BY token_number DESC
                  LIMIT 1
                  FOR UPDATE";

    $lockResult = mysqli_query($conn, $lockQuery);

    if (!$lockResult) {
        throw new Exception("Unable to check the current queue.");
    }

    // Find the next daily number for this doctor
    $numberQuery = "SELECT COALESCE(MAX(token_number), 0) + 1 AS next_number
                    FROM patients
                    WHERE doctor_id = $doctor_id
                    AND registration_date = '$registration_date'";

    $numberResult = mysqli_query($conn, $numberQuery);

    if (!$numberResult) {
        throw new Exception("Unable to generate the patient token.");
    }

    $numberRow = mysqli_fetch_assoc($numberResult);
    $token_number = (int)$numberRow['next_number'];

    // Generate department prefix
    $prefixes = [
        'General Medicine' => 'GEN',
        'Orthopaedics' => 'ORT',
        'Paediatrics' => 'PAE',
        'Cardiology' => 'CAR'
    ];

    $patient_token = $prefixes[$department] .
                     str_pad($token_number, 3, '0', STR_PAD_LEFT);

    // Escape string values
    $fname = mysqli_real_escape_string($conn, $fname);
    $mname = mysqli_real_escape_string($conn, $mname);
    $lname = mysqli_real_escape_string($conn, $lname);
    $gender = mysqli_real_escape_string($conn, $gender);
    $blood_group = mysqli_real_escape_string($conn, $blood_group);
    $patient_type = mysqli_real_escape_string($conn, $patient_type);
    $department = mysqli_real_escape_string($conn, $department);
    $mobile_number = mysqli_real_escape_string($conn, $mobile_number);
    $patient_token = mysqli_real_escape_string($conn, $patient_token);

    // Save patient registration
    $insertQuery = "INSERT INTO patients
                    (fname, mname, lname, gender, age, mobile_number,
                     blood_group, patient_type, department, doctor_id,
                     token_number, patient_token, registration_date, status)
                    VALUES
                    ('$fname', '$mname', '$lname', '$gender', $age,
                     '$mobile_number', '$blood_group', '$patient_type',
                     '$department', $doctor_id, $token_number,
                     '$patient_token', '$registration_date', 'Waiting')";

    if (!mysqli_query($conn, $insertQuery)) {
        throw new Exception("Unable to save patient registration.");
    }

    $patient_id = mysqli_insert_id($conn);

    mysqli_commit($conn);

    // Store registration details for confirmation page
    $_SESSION['registered_patient'] = [
        'id' => $patient_id,
        'name' => trim($fname . ' ' . $mname . ' ' . $lname),
        'token' => $patient_token,
        'department' => $department,
        'doctor_id' => $doctor_id,
        'date' => $registration_date
    ];

    header("Location: patient_registration_success.php");
    exit();

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo "Registration failed: " .
         htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
?>
