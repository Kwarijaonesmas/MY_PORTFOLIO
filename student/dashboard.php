<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('student');

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];

// Fetch stats for student
$stmt_b = $conn->prepare("SELECT COUNT(*) as total_bookings, 
                          SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                          SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings
                          FROM bookings WHERE student_id = ?");
$stmt_b->bind_param("i", $student_id);
$stmt_b->execute();
$stats = $stmt_b->get_result()->fetch_assoc();
$stmt_b->close();

// Fetch recent bookings
$stmt_rec = $conn->prepare("SELECT b.*, h.name as hostel_name, h.location, r.room_no, h.price 
                            FROM bookings b
                            JOIN hostels h ON b.hostel_id = h.id
                            JOIN rooms r ON b.room_id = r.id
                            WHERE b.student_id = ?
                            ORDER BY b.booking_date DESC LIMIT 5");
$stmt_rec->bind_param("i", $student_id);
$stmt_rec->execute();
$recent_bookings = $stmt_rec->get_result();

// Fetch student wallet balance
$stmt_w = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
$stmt_w->bind_param("i", $student_id);
$stmt_w->execute();
$st_w = $stmt_w->get_result()->fetch_assoc();
$wallet_balance = (int)($st_w['wallet_balance'] ?? 500000);
$stmt_w->close();

$page_title = "Student Dashboard";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Header -->
    <div class="card border-0 bg-primary text-white rounded-4 p-4 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-primary rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">STUDENT PORTAL</span>
                <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($student_name); ?>!</h2>
                <p class="mb-0 opacity-75">Browse hostels, check booking status, and complete your MTN MoMo payments easily.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-light rounded-pill font-semibold shadow-sm">
                    <i class="bi bi-search me-1"></i> Search Hostels
                </a>
                <a href="my_bookings.php" class="btn btn-outline-light rounded-pill font-semibold">
                    <i class="bi bi-journal-text me-1"></i> My Bookings
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10 text-dark">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-0 font-monospace">UGX <?php echo number_format($wallet_balance); ?></h4>
                    <span class="text-muted small fw-semibold">MoMo Wallet Balance</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $stats['total_bookings'] ?? 0; ?></h3>
                    <span class="text-muted small fw-semibold">Total Bookings</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $stats['pending_payments'] ?? 0; ?></h3>
                    <span class="text-muted small fw-semibold">Pending MoMo Payments</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $stats['paid_bookings'] ?? 0; ?></h3>
                    <span class="text-muted small fw-semibold">Confirmed Bookings</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Room Bookings</h5>
            <a href="my_bookings.php" class="btn btn-sm btn-outline-primary rounded-pill">View All Bookings</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hostel</th>
                            <th>Location</th>
                            <th>Room No</th>
                            <th>Booking Date</th>
                            <th>Price / Sem</th>
                            <th>Payment Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_bookings->num_rows > 0): ?>
                            <?php while ($b = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($b['hostel_name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($b['location']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($b['room_no']); ?></span></td>
                                    <td class="small text-muted"><?php echo date('M d, Y - h:i A', strtotime($b['booking_date'])); ?></td>
                                    <td class="fw-bold text-primary">UGX <?php echo number_format($b['price']); ?></td>
                                    <td>
                                        <?php if ($b['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                                <i class="bi bi-clock-fill me-1"></i> Pending Payment
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['payment_status'] === 'pending'): ?>
                                            <a href="pay.php?booking_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-warning font-semibold text-dark rounded-pill px-3 shadow-sm">
                                                <i class="bi bi-phone-fill me-1"></i> Pay MoMo
                                            </a>
                                        <?php else: ?>
                                            <a href="my_bookings.php" class="btn btn-sm btn-light border rounded-pill text-muted">View Receipt</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    You have not booked any rooms yet. <a href="../index.php" class="text-primary fw-bold">Explore Available Hostels</a>
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
$stmt_rec->close();
require_once '../includes/footer.php'; 
?>
