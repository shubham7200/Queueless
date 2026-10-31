
<?php
session_start();
include 'connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'receptionist') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Receptionist login required.'
    ]);
    exit();
}

$mobile_number = trim($_GET['mobile_number'] ?? '');

if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mobile number.'
    ]);
    exit();
}

$mobile_number = mysqli_real_escape_string($conn, $mobile_number);

$sql = "SELECT fname, mname, lname, gender, age,
               mobile_number, blood_group, patient_type
        FROM patients
        WHERE mobile_number = '$mobile_number'
        ORDER BY registration_date DESC, id DESC
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database query failed: ' . mysqli_error($conn)
    ]);
    exit();
}

if (mysqli_num_rows($result) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Patient found.',
        'patient' => mysqli_fetch_assoc($result)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No patient found with this mobile number.'
    ]);
}
?>
