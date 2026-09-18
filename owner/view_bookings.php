<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('owner');

$owner_id = $_SESSION['user_id'];
$hostel_filter = isset($_GET['hostel_id']) ? (int)$_GET['hostel_id'] : 0;

// Fetch owner's hostels for dropdown filter
$stmt_h = $conn->prepare("SELECT id, name FROM hostels WHERE owner_id = ? ORDER BY name ASC");
$stmt_h->bind_param("i", $owner_id);
$stmt_h->execute();
$hostel_options = $stmt_h->get_result();

// Build query for bookings
$sql = "SELECT b.*, u.name as student_name, u.email as student_email, h.name as hostel_name, r.room_no, h.price
        FROM bookings b
        JOIN users u ON b.student_id = u.id
        JOIN hostels h ON b.hostel_id = h.id
        JOIN rooms r ON b.room_id = r.id
        WHERE h.owner_id = ?";

$params = [$owner_id];
$types = "i";

if ($hostel_filter > 0) {
    $sql .= " AND b.hostel_id = ?";
    $params[] = $hostel_filter;
    $types .= "i";
}

$sql .= " ORDER BY b.booking_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = "Student Bookings Received";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-people text-primary me-2"></i> Student Bookings Tracker</h2>
            <p class="text-muted small mb-0">Track student reservations and verify MTN MoMo mobile payments</p>
        </div>
        
        <!-- Filter dropdown -->
        <form action="view_bookings.php" method="GET" class="d-flex align-items-center gap-2">
            <select name="hostel_id" class="form-select" onchange="this.form.submit()">
                <option value="0">-- All My Hostels --</option>
                <?php while ($ho = $hostel_options->fetch_assoc()): ?>
                    <option value="<?php echo $ho['id']; ?>" <?php echo ($ho['id'] == $hostel_filter) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ho['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Student Name</th>
                            <th>Email Contact</th>
                            <th>Hostel</th>
                            <th>Room No</th>
                            <th>Fee</th>
                            <th>Booking Date</th>
                            <th>MoMo Phone</th>
                            <th class="pe-4">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($bookings->num_rows > 0): ?>
                            <?php while ($b = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><i class="bi bi-person-fill text-primary me-1"></i><?php echo htmlspecialchars($b['student_name']); ?></td>
                                    <td class="small text-muted"><a href="mailto:<?php echo htmlspecialchars($b['student_email']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($b['student_email']); ?></a></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($b['hostel_name']); ?></td>
                                    <td><span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($b['room_no']); ?></span></td>
                                    <td class="fw-bold text-primary">UGX <?php echo number_format($b['price']); ?></td>
                                    <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($b['booking_date'])); ?></td>
                                    <td class="font-monospace fw-semibold">
                                        <?php if (!empty($b['payment_phone'])): ?>
                                            <?php echo htmlspecialchars($b['payment_phone']); ?><br>
                                            <span class="badge <?php echo (($b['payment_method'] ?? '') === 'Airtel Money') ? 'bg-danger' : 'bg-warning text-dark'; ?> font-monospace me-1">
                                                <?php echo htmlspecialchars($b['payment_method'] ?? 'MTN MoMo'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4">
                                        <?php if ($b['payment_status'] === 'paid'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill">
                                                <i class="bi bi-clock me-1"></i> Pending Payment
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    No student bookings found for the selected criteria.
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
$stmt_h->close();
$stmt->close();
require_once '../includes/footer.php'; 
?>
