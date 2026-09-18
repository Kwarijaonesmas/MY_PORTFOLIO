<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('owner');

$owner_id = $_SESSION['user_id'];
$owner_name = $_SESSION['user_name'];

// Fetch Stats for Owner
$stmt_h = $conn->prepare("SELECT COUNT(*) as total_hostels FROM hostels WHERE owner_id = ?");
$stmt_h->bind_param("i", $owner_id);
$stmt_h->execute();
$total_hostels = $stmt_h->get_result()->fetch_assoc()['total_hostels'] ?? 0;
$stmt_h->close();

$stmt_r = $conn->prepare("SELECT COUNT(*) as total_rooms FROM rooms r JOIN hostels h ON r.hostel_id = h.id WHERE h.owner_id = ?");
$stmt_r->bind_param("i", $owner_id);
$stmt_r->execute();
$total_rooms = $stmt_r->get_result()->fetch_assoc()['total_rooms'] ?? 0;
$stmt_r->close();

$stmt_b = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings b JOIN hostels h ON b.hostel_id = h.id WHERE h.owner_id = ?");
$stmt_b->bind_param("i", $owner_id);
$stmt_b->execute();
$total_bookings = $stmt_b->get_result()->fetch_assoc()['total_bookings'] ?? 0;
$stmt_b->close();

// Calculate Simulated Revenue for Owner
$stmt_rev = $conn->prepare("SELECT SUM(h.price) as sim_revenue FROM bookings b JOIN hostels h ON b.hostel_id = h.id WHERE h.owner_id = ? AND b.payment_status IN ('SUCCESSFUL', 'paid')");
$stmt_rev->bind_param("i", $owner_id);
$stmt_rev->execute();
$sim_revenue = (int)($stmt_rev->get_result()->fetch_assoc()['sim_revenue'] ?? 0);
$stmt_rev->close();

// Fetch Owner's Hostels
$stmt_list = $conn->prepare("SELECT h.*, 
                            (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id) as room_count,
                            (SELECT COUNT(*) FROM bookings b WHERE b.hostel_id = h.id) as booking_count
                            FROM hostels h WHERE h.owner_id = ? ORDER BY h.id DESC");
$stmt_list->bind_param("i", $owner_id);
$stmt_list->execute();
$my_hostels = $stmt_list->get_result();

$page_title = "Owner Dashboard";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header Card -->
    <div class="card border-0 bg-dark text-white rounded-4 p-4 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-primary text-white rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">HOSTEL OWNER PORTAL</span>
                <h2 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($owner_name); ?></h2>
                <p class="mb-0 opacity-75">Manage your property listings, inventory rooms, and track student bookings.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="add_hostel.php" class="btn btn-primary-theme rounded-pill font-semibold shadow">
                    <i class="bi bi-plus-circle me-1"></i> Register New Hostel
                </a>
                <a href="manage_rooms.php" class="btn btn-outline-light rounded-pill font-semibold">
                    <i class="bi bi-door-open me-1"></i> Manage Rooms
                </a>
                <a href="view_bookings.php" class="btn btn-outline-warning rounded-pill font-semibold">
                    <i class="bi bi-people me-1"></i> Student Bookings
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-0 font-monospace">UGX <?php echo number_format($sim_revenue); ?></h4>
                    <span class="text-muted small fw-semibold text-uppercase">SIMULATED REVENUE</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_hostels; ?></h3>
                    <span class="text-muted small fw-semibold">My Hostels</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-door-closed"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_rooms; ?></h3>
                    <span class="text-muted small fw-semibold">Total Inventory Rooms</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_bookings; ?></h3>
                    <span class="text-muted small fw-semibold">Bookings Received</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Hostels Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-house-door me-2 text-primary"></i> My Registered Hostels</h5>
            <a href="add_hostel.php" class="btn btn-sm btn-primary-theme rounded-pill"><i class="bi bi-plus me-1"></i> Add Hostel</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Hostel Name</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Price / Semester</th>
                            <th>Rooms</th>
                            <th>Bookings</th>
                            <th>Approval Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($my_hostels->num_rows > 0): ?>
                            <?php while ($h = $my_hostels->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($h['name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($h['location']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($h['type']); ?></span></td>
                                    <td class="fw-bold text-primary">UGX <?php echo number_format($h['price']); ?></td>
                                    <td><span class="badge bg-info text-dark rounded-pill"><?php echo $h['room_count']; ?> Rooms</span></td>
                                    <td><span class="badge bg-success rounded-pill"><?php echo $h['booking_count']; ?> Booked</span></td>
                                    <td>
                                        <?php if ($h['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                <i class="bi bi-check-circle-fill me-1"></i> Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">
                                                <i class="bi bi-clock me-1"></i> Pending Approval
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="manage_rooms.php?hostel_id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1">
                                            <i class="bi bi-gear me-1"></i> Rooms
                                        </a>
                                        <a href="view_bookings.php?hostel_id=<?php echo $h['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                            <i class="bi bi-people me-1"></i> Bookings
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    You have not registered any hostels yet. <a href="add_hostel.php" class="text-primary fw-bold">Add Your First Hostel</a>
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
$stmt_list->close();
require_once '../includes/footer.php'; 
?>
