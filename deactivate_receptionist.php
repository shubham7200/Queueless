<?php
session_start();

// Only admin can deactivate doctors
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: users.php");
    exit();
}

include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receptionist_id'])) {
    $receptionist_id = (int) $_POST['receptionist_id'];

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET is_active = 'Inactive' WHERE id = ? AND role = 'receptionist'");
    mysqli_stmt_bind_param($stmt, "i", $receptionist_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['receptionist_id'] = $receptionist_id;
        header("Location: view_receptionist.php");
        exit();
    } else {
        echo "Error deactivating receptionist: " . mysqli_error($conn);
    }
} else {
    header("Location: view_receptionists.php");
    exit();
}
?>