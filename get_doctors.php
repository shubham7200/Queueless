
<?php
include 'connection.php';

header('Content-Type: application/json');

if (isset($_GET['department'])) {

    $department = mysqli_real_escape_string(
        $conn,
        $_GET['department']
    );

    $sql = "SELECT id, fname, lname
            FROM users
            WHERE role = 'doctor'
            AND is_active = 'Active'
            AND department = '$department'
            ORDER BY fname ASC";

    $result = mysqli_query($conn, $sql);

    $doctors = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $doctors[] = $row;
        }
    }

    echo json_encode($doctors);

} else {
    echo json_encode(array());
}
?>
