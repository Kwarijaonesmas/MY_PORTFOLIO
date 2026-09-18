<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('admin');

$hostel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($hostel_id > 0) {
    $stmt = $conn->prepare("UPDATE hostels SET status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $hostel_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php?msg=approved");
exit();
?>
