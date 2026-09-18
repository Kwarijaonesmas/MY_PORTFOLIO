<?php
require_once '../db.php';
require_once '../includes/auth_check.php';

$hostel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($hostel_id <= 0) {
    header("Location: ../index.php");
    exit();
}

$msg = '';
$err = '';

// Handle Booking Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room_id'])) {
    if (!is_logged_in()) {
        header("Location: ../login.php?msg=please_login_to_book");
        exit();
    }

    $student_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'] ?? '';

    if ($user_role !== 'student') {
        $err = "Only registered students can book hostel rooms.";
    } else {
        $room_id = (int)$_POST['book_room_id'];

        // Verify room is still available
        $check_r = $conn->prepare("SELECT id, room_no FROM rooms WHERE id = ? AND hostel_id = ? AND status = 'available'");
        $check_r->bind_param("ii", $room_id, $hostel_id);
        $check_r->execute();
        $r_res = $check_r->get_result();

        if ($r_res->num_rows > 0) {
            $check_r->close();

            // Perform booking transaction inside database
            $conn->begin_transaction();
            try {
                // Insert booking
                $stmt_b = $conn->prepare("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES (?, ?, ?, 'pending')");
                $stmt_b->bind_param("iii", $student_id, $room_id, $hostel_id);
                $stmt_b->execute();
                $booking_id = $stmt_b->insert_id;
                $stmt_b->close();

                // Update room status
                $stmt_u = $conn->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?");
                $stmt_u->bind_param("i", $room_id);
                $stmt_u->execute();
                $stmt_u->close();

                $conn->commit();
                header("Location: pay.php?booking_id=" . $booking_id . "&msg=booked_success");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $err = "Booking failed due to a system error. Please try again.";
            }
        } else {
            $err = "Sorry, that room has already been booked or is unavailable.";
            $check_r->close();
        }
    }
}

// Fetch Hostel Details
$stmt = $conn->prepare("SELECT h.*, u.name as owner_name, u.email as owner_email
                        FROM hostels h
                        JOIN users u ON h.owner_id = u.id
                        WHERE h.id = ? LIMIT 1");
$stmt->bind_param("i", $hostel_id);
$stmt->execute();
$hostel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hostel) {
    header("Location: ../index.php");
    exit();
}

// Fetch Rooms for this Hostel
$stmt_rooms = $conn->prepare("SELECT * FROM rooms WHERE hostel_id = ? ORDER BY room_no ASC");
$stmt_rooms->bind_param("i", $hostel_id);
$stmt_rooms->execute();
$rooms_result = $stmt_rooms->get_result();

// Fetch Reviews
$stmt_rev = $conn->prepare("SELECT r.*, u.name as student_name FROM reviews r JOIN users u ON r.student_id = u.id WHERE r.hostel_id = ? ORDER BY r.created_at DESC");
$stmt_rev->bind_param("i", $hostel_id);
$stmt_rev->execute();
$reviews_result = $stmt_rev->get_result();

$facilities_arr = array_map('trim', explode(',', $hostel['facilities']));
$photo_path = (!empty($hostel['photo']) && file_exists(__DIR__ . '/../uploads/' . $hostel['photo'])) 
    ? '../uploads/' . htmlspecialchars($hostel['photo']) 
    : 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=800&q=80';

$page_title = $hostel['name'];
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="../index.php?location=<?php echo urlencode($hostel['location']); ?>"><?php echo htmlspecialchars($hostel['location']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($hostel['name']); ?></li>
        </ol>
    </nav>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo htmlspecialchars($err); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- Hostel Details Card -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div style="height: 380px; overflow: hidden; position: relative;">
                    <img src="<?php echo $photo_path; ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo htmlspecialchars($hostel['name']); ?>">
                    <span class="badge bg-dark bg-opacity-75 text-white fs-6 px-3 py-2 rounded-pill position-absolute top-0 end-0 m-3">
                        <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($hostel['type']); ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h2 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($hostel['name']); ?></h2>
                            <span class="text-primary fw-semibold"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Location: <?php echo htmlspecialchars($hostel['location']); ?></span>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small d-block">Price / Semester</span>
                            <span class="fs-3 fw-extrabold text-primary">UGX <?php echo number_format($hostel['price']); ?></span>
                        </div>
                    </div>
                    <hr class="my-3">

                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-stars text-warning me-2"></i> Facilities & Amenities Included</h5>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ($facilities_arr as $fac): ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary fs-6 px-3 py-2 rounded-pill border border-primary-subtle">
                                <i class="bi bi-check-circle-fill text-primary me-1"></i> <?php echo htmlspecialchars($fac); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-light p-3 rounded-3 border d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small d-block">Hostel Owner / Manager</span>
                            <strong class="text-dark fs-6"><i class="bi bi-person-circle me-1 text-primary"></i> <?php echo htmlspecialchars($hostel['owner_name']); ?></strong>
                        </div>
                        <a href="mailto:<?php echo htmlspecialchars($hostel['owner_email']); ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="bi bi-envelope me-1"></i> Contact Owner
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-chat-square-quote-fill text-primary me-2"></i> Student Reviews & Ratings</h5>
                <?php if ($reviews_result->num_rows > 0): ?>
                    <?php while ($rev = $reviews_result->fetch_assoc()): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-dark"><i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($rev['student_name']); ?></strong>
                                <div class="text-warning small">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo ($i <= $rev['rating']) ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-secondary small mb-0"><?php echo htmlspecialchars($rev['comment']); ?></p>
                            <span class="text-muted text-xs" style="font-size:0.75rem;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No reviews submitted yet for this hostel.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Rooms Sidebar / Booking Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
                <div class="card-header bg-primary text-white p-3 rounded-top-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-door-open-fill me-2"></i> Select & Book a Room</h5>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">Choose an available room below to initiate instant booking and payment.</p>
                    
                    <?php if ($rooms_result->num_rows > 0): ?>
                        <div class="list-group list-group-flush mb-3">
                            <?php while ($r = $rooms_result->fetch_assoc()): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 border-bottom">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-hash text-muted"></i> <?php echo htmlspecialchars($r['room_no']); ?></h6>
                                        <span class="small text-muted">Status: 
                                            <?php if ($r['status'] === 'available'): ?>
                                                <strong class="text-success">Available</strong>
                                            <?php else: ?>
                                                <strong class="text-danger">Booked</strong>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($r['status'] === 'available'): ?>
                                            <form action="hostel_view.php?id=<?php echo $hostel_id; ?>" method="POST" class="m-0">
                                                <input type="hidden" name="book_room_id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary-theme rounded-pill px-3 shadow-sm" onclick="return confirm('Confirm booking room <?php echo htmlspecialchars($r['room_no']); ?>?');">
                                                    <i class="bi bi-bookmark-plus me-1"></i> Book Now
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary disabled rounded-pill px-3" disabled>Occupied</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center mb-0">
                            No rooms registered for this hostel yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$stmt_rooms->close();
$stmt_rev->close();
require_once '../includes/footer.php'; 
?>
