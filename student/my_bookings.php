<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('student');

$student_id = $_SESSION['user_id'];
$msg = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'booked_success') $msg = "Room booked successfully! Please complete your MTN MoMo payment below.";
    elseif ($_GET['msg'] === 'paid_success') $msg = "Payment confirmed! Your hostel room booking is now officially locked in.";
}

// Fetch all bookings for this student
$stmt = $conn->prepare("SELECT b.*, h.name as hostel_name, h.location, h.price, r.room_no, u.name as owner_name, u.email as owner_email
                       FROM bookings b
                       JOIN hostels h ON b.hostel_id = h.id
                       JOIN rooms r ON b.room_id = r.id
                       JOIN users u ON h.owner_id = u.id
                       WHERE b.student_id = ?
                       ORDER BY b.booking_date DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = "My Hostel Bookings";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-journal-bookmark text-primary me-2"></i> My Hostel Bookings</h2>
            <p class="text-muted small mb-0">View your reserved rooms, payment receipts, and manager contact details</p>
        </div>
        <a href="../index.php" class="btn btn-primary-theme rounded-pill px-4">
            <i class="bi bi-search me-1"></i> Book Another Hostel
        </a>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Booking ID</th>
                            <th>Hostel Name</th>
                            <th>Location</th>
                            <th>Room No</th>
                            <th>Semester Fee</th>
                            <th>Booking Date</th>
                            <th>Payment Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 font-monospace text-muted">#BK-<?php echo str_pad($b['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold text-dark">
                                        <a href="hostel_view.php?id=<?php echo $b['hostel_id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($b['hostel_name']); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($b['location']); ?></span></td>
                                    <td><span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($b['room_no']); ?></span></td>
                                    <td class="fw-bold text-primary">UGX <?php echo number_format($b['price']); ?></td>
                                    <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($b['booking_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_val = strtoupper($b['payment_status']);
                                        if ($status_val === 'SUCCESSFUL' || $status_val === 'PAID'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill d-inline-block mb-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> SUCCESSFUL
                                            </span>
                                            <br>
                                            <span class="badge <?php echo (strpos($b['payment_method'] ?? '', 'Airtel') !== false) ? 'bg-danger' : 'bg-warning text-dark'; ?> font-monospace">
                                                <?php echo htmlspecialchars($b['payment_method'] ?? 'MTN Mobile Money Demo'); ?>
                                            </span>
                                        <?php elseif ($status_val === 'INSUFFICIENT_BALANCE'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> INSUFFICIENT BALANCE
                                            </span>
                                        <?php elseif ($status_val === 'INVALID_PIN'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-key-fill me-1"></i> INVALID PIN
                                            </span>
                                        <?php elseif ($status_val === 'FAILED'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-x-circle-fill me-1"></i> FAILED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-hourglass-split me-1"></i> PENDING PAYMENT
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <?php if ($status_val === 'SUCCESSFUL' || $status_val === 'PAID'): ?>
                                            <a href="receipt.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 me-1">
                                                <i class="bi bi-receipt me-1"></i> Digital Receipt
                                            </a>
                                            <!-- Leave Review Modal Trigger -->
                                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $b['id']; ?>">
                                                <i class="bi bi-star me-1"></i> Review
                                            </button>
                                        <?php else: ?>
                                            <a href="pay.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-warning font-semibold text-dark rounded-pill px-3 shadow-sm">
                                                <i class="bi bi-phone-fill me-1"></i> Pay Demo
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Review Modal -->
                                <?php if ($status_val === 'SUCCESSFUL' || $status_val === 'PAID'): ?>
                                    <div class="modal fade" id="reviewModal<?php echo $b['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-4">
                                                <div class="modal-header bg-primary text-white rounded-top-4">
                                                    <h5 class="modal-title fw-bold"><i class="bi bi-star-fill text-warning me-2"></i> Review <?php echo htmlspecialchars($b['hostel_name']); ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="add_review.php" method="POST">
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="hostel_id" value="<?php echo $b['hostel_id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="rating" class="form-label fw-bold">Star Rating (1 to 5)</label>
                                                            <select name="rating" class="form-select" required>
                                                                <option value="5" selected>⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                                                                <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                                                                <option value="3">⭐⭐⭐ (3 - Good)</option>
                                                                <option value="2">⭐⭐ (2 - Average)</option>
                                                                <option value="1">⭐ (1 - Poor)</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="comment" class="form-label fw-bold">Your Review Comment</label>
                                                            <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience regarding security, water, WiFi, and caretaker hospitality..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 p-4 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary-theme rounded-pill px-4">Submit Review</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x display-4 d-block text-muted mb-2"></i>
                                    <h5 class="fw-bold">No Bookings Found</h5>
                                    <p class="small mb-3">You have not reserved any hostel rooms yet.</p>
                                    <a href="../index.php" class="btn btn-primary-theme rounded-pill px-4">Explore Available Hostels</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
$stmt->close();
require_once '../includes/footer.php'; 
?>
