<?php
session_start();

// Only admin can deactivate doctors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: users.php");
    exit();
}

include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctor_id'])) {
    $doctor_id = (int) $_POST['doctor_id'];

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET is_active = 'Inactive' WHERE id = ? AND role = 'doctor'");
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['doctor_id'] = $doctor_id;
        header("Location: view_doctor.php");
        exit();
    } else {
        echo "Error deactivating doctor: " . mysqli_error($conn);
    }
} else {
    header("Location: view_doctors.php");
    exit();
}
?>