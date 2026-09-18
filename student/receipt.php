<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('student');

$student_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit();
}

// Fetch booking & successful transaction log
$stmt = $conn->prepare("SELECT b.*, h.name as hostel_name, h.location, r.room_no, u.name as student_name, u.email as student_email,
                        t.transaction_ref, t.network, t.phone_number, t.amount, t.remaining_balance, t.created_at as payment_time
                        FROM bookings b
                        JOIN hostels h ON b.hostel_id = h.id
                        JOIN rooms r ON b.room_id = r.id
                        JOIN users u ON b.student_id = u.id
                        LEFT JOIN transactions t ON (t.booking_id = b.id AND t.status = 'SUCCESSFUL')
                        WHERE b.id = ? AND b.student_id = ? LIMIT 1");
$stmt->bind_param("ii", $booking_id, $student_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data || $data['payment_status'] !== 'SUCCESSFUL') {
    header("Location: my_bookings.php");
    exit();
}

$page_title = "Official Simulated Payment Receipt";
require_once '../includes/header.php';
?>

<div class="container py-5" style="max-width: 680px;">
    <!-- Print Button Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <a href="my_bookings.php" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Back to My Bookings
        </a>
        <button onclick="window.print();" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-printer me-2"></i> Print Official Receipt
        </button>
    </div>

    <!-- Official Receipt Card -->
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white">
        <!-- Header -->
        <div class="bg-dark text-white p-4 text-center position-relative">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <i class="bi bi-building-check text-warning fs-2"></i>
                <h3 class="fw-bold mb-0 text-white">HostelFinder Bushenyi</h3>
            </div>
            <span class="badge bg-warning text-dark font-monospace px-3 py-1.5 rounded-pill uppercase tracking-wide">SIMULATED PAYMENT RECEIPT</span>
        </div>

        <!-- Watermark Banner -->
        <div class="bg-warning-subtle text-dark p-2 text-center font-monospace fw-bold small border-bottom">
            ⚠️ SIMULATED TRANSACTION – NO REAL MONEY TRANSFERRED
        </div>

        <!-- Receipt Body -->
        <div class="card-body p-4 p-md-5">
            
            <div class="row g-3 mb-4 text-sm">
                <div class="col-6">
                    <span class="text-muted d-block small">Receipt Date & Time:</span>
                    <strong class="text-dark"><?php echo date('d M Y, h:i A', strtotime($data['payment_time'] ?? $data['booking_date'])); ?></strong>
                </div>
                <div class="col-6 text-end">
                    <span class="text-muted d-block small">Booking Reference:</span>
                    <strong class="font-monospace text-primary">#BK-<?php echo str_pad($data['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                </div>
            </div>

            <div class="border rounded-3 p-3 bg-light mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Demo Transaction ID:</span>
                    <span class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($data['transaction_ref'] ?? 'DEMO-TX-000000'); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Payment Network:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($data['payment_method']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Student Name:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($data['student_name']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Student Test Phone:</span>
                    <span class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($data['payment_phone']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Hostel Name:</span>
                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($data['hostel_name']); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Assigned Room Number:</span>
                    <span class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($data['room_no']); ?> (<?php echo htmlspecialchars($data['location']); ?>)</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Payment Status:</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace fw-bold">SUCCESSFUL</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark">Simulated Amount Paid:</span>
                    <span class="fs-4 fw-extrabold text-success font-monospace">UGX <?php echo number_format($data['amount']); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Remaining Demo Balance:</span>
                    <span class="fw-bold font-monospace text-primary">UGX <?php echo number_format($data['remaining_balance'] ?? 0); ?></span>
                </div>
            </div>

            <!-- Verification Footer -->
            <div class="text-center pt-2">
                <div class="d-inline-block border border-2 border-dark rounded-3 px-3 py-1 font-monospace text-uppercase fw-bold text-muted small mb-2">
                    AUTHENTICATED DEMO TRANSACTION
                </div>
                <p class="text-muted small mb-0">Generated by HostelFinder Bushenyi Demonstration Environment.</p>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
