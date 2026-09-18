<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('admin');

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') $msg = "Hostel approved successfully! It is now live on the index search page.";
    elseif ($_GET['msg'] === 'user_deleted') $msg = "User account removed.";
}

// Fetch Admin Stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$total_hostels = $conn->query("SELECT COUNT(*) as count FROM hostels")->fetch_assoc()['count'] ?? 0;
$pending_hostels = $conn->query("SELECT COUNT(*) as count FROM hostels WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
$total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'] ?? 0;
$total_paid = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'paid'")->fetch_assoc()['count'] ?? 0;

// Fetch Pending Hostels
$pending_query = $conn->query("SELECT h.*, u.name as owner_name, u.email as owner_email 
                               FROM hostels h 
                               JOIN users u ON h.owner_id = u.id 
                               WHERE h.status = 'pending' 
                               ORDER BY h.id DESC");

// Fetch All Hostels
$all_hostels_query = $conn->query("SELECT h.*, u.name as owner_name 
                                   FROM hostels h 
                                   JOIN users u ON h.owner_id = u.id 
                                   ORDER BY h.id DESC");

// Fetch Users List
$users_query = $conn->query("SELECT * FROM users ORDER BY id DESC");

$page_title = "Admin Portal & Control Center";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="card border-0 bg-dark text-white rounded-4 p-4 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-danger text-white rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">SYSTEM ADMIN PANEL</span>
                <h2 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2"></i> Bushenyi Hostels Overview</h2>
                <p class="mb-0 opacity-75">Platform analytics, pending hostel approvals, and system user management.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="manage_momo_accounts.php" class="btn btn-warning font-semibold text-dark rounded-pill px-4">
                    <i class="bi bi-device-ssd me-1"></i> MoMo Demo Accounts & Audit Logs
                </a>
                <a href="../index.php" class="btn btn-outline-light rounded-pill px-4">
                    <i class="bi bi-globe me-1"></i> Public Site View
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Metric Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2.4 col-sm-6">
            <div class="stat-card p-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_users; ?></h3>
                    <span class="text-muted small fw-semibold">Total Users</span>
                </div>
            </div>
        </div>
        <div class="col-md-2.4 col-sm-6">
            <div class="stat-card p-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_hostels; ?></h3>
                    <span class="text-muted small fw-semibold">Total Hostels</span>
                </div>
            </div>
        </div>
        <div class="col-md-2.4 col-sm-6">
            <div class="stat-card p-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $pending_hostels; ?></h3>
                    <span class="text-muted small fw-semibold">Pending Approvals</span>
                </div>
            </div>
        </div>
        <div class="col-md-2.4 col-sm-6">
            <div class="stat-card p-3">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-journal-bookmark"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_bookings; ?></h3>
                    <span class="text-muted small fw-semibold">Total Bookings</span>
                </div>
            </div>
        </div>
        <div class="col-md-2.4 col-sm-6">
            <div class="stat-card p-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-cash-check"></i>
                </div>
                <div>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_paid; ?></h3>
                    <span class="text-muted small fw-semibold">Paid MoMo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Pending Hostels Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-warning bg-opacity-10 py-3 border-bottom border-warning-subtle d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-exclamation-circle-fill text-warning me-2"></i> Pending Hostel Approvals</h5>
            <span class="badge bg-warning text-dark rounded-pill"><?php echo $pending_hostels; ?> Hostels Pending</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Hostel Name</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Semester Price</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pending_query->num_rows > 0): ?>
                            <?php while ($ph = $pending_query->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($ph['name']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($ph['owner_name']); ?></strong><br>
                                        <span class="small text-muted"><?php echo htmlspecialchars($ph['owner_email']); ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ph['location']); ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ph['type']); ?></span></td>
                                    <td class="fw-bold text-primary">UGX <?php echo number_format($ph['price']); ?></td>
                                    <td class="pe-4 text-end">
                                        <a href="approve.php?id=<?php echo $ph['id']; ?>" class="btn btn-sm btn-success rounded-pill px-4 shadow-sm fw-bold">
                                            <i class="bi bi-check-lg me-1"></i> Approve Hostel
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-all text-success fs-4 me-1"></i> No pending hostels requiring approval.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. All Hostels Directory Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-building me-2 text-primary"></i> All Registered Hostels</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Hostel Name</th>
                            <th>Owner</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ah = $all_hostels_query->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($ah['name']); ?></td>
                                <td><?php echo htmlspecialchars($ah['owner_name']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ah['location']); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ah['type']); ?></span></td>
                                <td class="fw-bold text-primary">UGX <?php echo number_format($ah['price']); ?></td>
                                <td>
                                    <?php if ($ah['status'] === 'approved'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. System User Management Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-gear me-2 text-primary"></i> User Management Directory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">User ID</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>System Role</th>
                            <th>Registered Date</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usr = $users_query->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 font-monospace text-muted">#USR-<?php echo str_pad($usr['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($usr['name']); ?></td>
                                <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                <td>
                                    <?php if ($usr['role'] === 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php elseif ($usr['role'] === 'owner'): ?>
                                        <span class="badge bg-primary">Owner</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo date('d M Y', strtotime($usr['created_at'])); ?></td>
                                <td class="pe-4 text-end">
                                    <?php if ($usr['id'] != $_SESSION['user_id']): ?>
                                        <a href="delete_user.php?id=<?php echo $usr['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 confirm-action" data-confirm="Are you sure you want to delete user '<?php echo htmlspecialchars($usr['name']); ?>'?">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">You (Current Admin)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
