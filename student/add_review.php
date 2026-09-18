<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $hostel_id = isset($_POST['hostel_id']) ? (int)$_POST['hostel_id'] : 0;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    $comment = trim($_POST['comment'] ?? '');

    if ($hostel_id > 0 && !empty($comment)) {
        // Ensure rating is bounded 1 to 5
        $rating = max(1, min(5, $rating));

        $stmt = $conn->prepare("INSERT INTO reviews (student_id, hostel_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $student_id, $hostel_id, $rating, $comment);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: my_bookings.php?msg=review_submitted");
exit();
?>
