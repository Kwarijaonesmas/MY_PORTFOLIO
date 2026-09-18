<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('admin');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_admin_id = $_SESSION['user_id'];

if ($user_id > 0 && $user_id !== $current_admin_id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php?msg=user_deleted");
exit();
?>
