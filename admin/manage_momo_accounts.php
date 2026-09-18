<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('admin');

$msg = '';
$err = '';

// 1. Handle New Demo Account Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_account') {
    $network = trim($_POST['network'] ?? 'MTN');
    $phone = trim($_POST['phone_number'] ?? '');
    $name = trim($_POST['account_name'] ?? '');
    $balance = (int)($_POST['simulated_balance'] ?? 500000);
    $pin = trim($_POST['test_pin'] ?? '1234');

    if (empty($phone) || empty($name) || strlen($pin) !== 4) {
        $err = "Failed: All fields are required and Test PIN must be 4 digits.";
    } else {
        $pin_hash = password_hash($pin, PASSWORD_BCRYPT);
        $stmt_add = $conn->prepare("INSERT INTO mobile_money_accounts (network, phone_number, account_name, simulated_balance, test_pin_hash, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt_add->bind_param("sssiss", $network, $phone, $name, $balance, $pin_hash, $status);
        if ($stmt_add->execute()) {
            $msg = "New simulated Mobile Money test account for " . htmlspecialchars($phone) . " (" . htmlspecialchars($network) . ") created successfully!";
        } else {
            $err = "Error: Phone number may already be registered. " . $conn->error;
        }
        $stmt_add->close();
    }
}

// 2. Handle Balance Reset / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_balance') {
    $acc_id = (int)$_POST['account_id'];
    $new_bal = (int)$_POST['simulated_balance'];

    $stmt_u = $conn->prepare("UPDATE mobile_money_accounts SET simulated_balance = ? WHERE id = ?");
    $stmt_u->bind_param("ii", $new_bal, $acc_id);
    if ($stmt_u->execute()) {
        $msg = "Simulated Mobile Money balance updated to UGX " . number_format($new_bal) . ".";
    }
    $stmt_u->close();
}

// 3. Handle Status Toggle (Activate/Deactivate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $acc_id = (int)$_GET['id'];
    $new_status = ($_GET['toggle_status'] === 'active') ? 'active' : 'inactive';
    $stmt_t = $conn->prepare("UPDATE mobile_money_accounts SET status = ? WHERE id = ?");
    $stmt_t->bind_param("si", $new_status, $acc_id);
    if ($stmt_t->execute()) {
        $msg = "Demo account status changed to " . strtoupper($new_status) . ".";
    }
    $stmt_t->close();
}

// Fetch All Demo Accounts
$accounts = $conn->query("SELECT * FROM mobile_money_accounts ORDER BY network DESC, id ASC");

// Fetch All Transaction Logs
$filter_status = $_GET['filter_status'] ?? 'ALL';
$where_clause = "";
if ($filter_status !== 'ALL') {
    $safe_status = $conn->real_escape_string($filter_status);
    $where_clause = "WHERE t.status = '$safe_status'";
}

$transactions = $conn->query("SELECT t.*, u.name as student_name, b.id as booking_ref, h.name as hostel_name, r.room_no
                             FROM transactions t
                             JOIN users u ON t.student_id = u.id
                             JOIN bookings b ON t.booking_id = b.id
                             JOIN hostels h ON b.hostel_id = h.id
                             JOIN rooms r ON b.room_id = r.id
                             $where_clause
                             ORDER BY t.created_at DESC LIMIT 50");

$page_title = "Manage Demo Mobile Money Accounts";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <!-- Header -->
    <div class="card border-0 bg-dark text-white rounded-4 p-4 shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 mb-2 font-monospace fw-bold">ADMINISTRATION CENTER</span>
                <h2 class="fw-bold mb-1"><i class="bi bi-device-ssd me-2"></i> Demo Mobile Money Accounts & Audit Logs</h2>
                <p class="mb-0 opacity-75">Create test accounts, edit simulated balances, toggle status, and monitor real-time transaction logs.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Admin Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($err); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <!-- Add Account Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-person-plus-fill me-2 text-primary"></i> Create Test Account</h5>
                </div>
                <div class="card-body p-4">
                    <form action="manage_momo_accounts.php" method="POST">
                        <input type="hidden" name="action" value="create_account">
                        
                        <div class="mb-3">
                            <label for="network" class="form-label fw-bold small">Network Provider</label>
                            <select name="network" class="form-select font-monospace" required>
                                <option value="MTN">MTN Mobile Money Demo</option>
                                <option value="Airtel">Airtel Money Demo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="phone_number" class="form-label fw-bold small">Test Phone Number</label>
                            <input type="text" name="phone_number" class="form-control font-monospace" placeholder="0770000003" required>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label fw-bold small">Account Holder Name</label>
                            <input type="text" name="account_name" class="form-control" placeholder="Test User (Demo)" required>
                        </div>

                        <div class="mb-3">
                            <label for="simulated_balance" class="form-label fw-bold small">Initial Simulated Balance (UGX)</label>
                            <input type="number" name="simulated_balance" class="form-control font-monospace" value="500000" required>
                        </div>

                        <div class="mb-4">
                            <label for="test_pin" class="form-label fw-bold small">Test PIN Code (4 Digits)</label>
                            <input type="password" name="test_pin" maxlength="4" class="form-control font-monospace text-center fs-5" value="1234" required>
                        </div>

                        <button type="submit" class="btn btn-primary-theme w-100 rounded-pill fw-bold py-2.5">
                            <i class="bi bi-plus-circle me-1"></i> Register Test Account
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Accounts List Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-wallet2 me-2 text-primary"></i> Registered Test Accounts</h5>
                    <span class="badge bg-secondary font-monospace"><?php echo $accounts->num_rows; ?> Accounts</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th class="ps-3">Network</th>
                                    <th>Phone Number</th>
                                    <th>Account Name</th>
                                    <th>Demo Balance</th>
                                    <th>Status</th>
                                    <th class="pe-3 text-end">Manage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($accounts->num_rows > 0): ?>
                                    <?php while ($acc = $accounts->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <span class="badge <?php echo ($acc['network'] === 'Airtel') ? 'bg-danger' : 'bg-warning text-dark'; ?> font-monospace">
                                                    <?php echo $acc['network']; ?>
                                                </span>
                                            </td>
                                            <td class="font-monospace fw-bold text-dark"><?php echo htmlspecialchars($acc['phone_number']); ?></td>
                                            <td class="small fw-semibold"><?php echo htmlspecialchars($acc['account_name']); ?></td>
                                            <td class="font-monospace fw-bold text-success">UGX <?php echo number_format($acc['simulated_balance']); ?></td>
                                            <td>
                                                <?php if ($acc['status'] === 'active'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-3 text-end">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <!-- Edit Balance Modal Trigger -->
                                                    <button class="btn btn-xs btn-outline-primary rounded-pill px-2" data-bs-toggle="modal" data-bs-target="#editBalModal<?php echo $acc['id']; ?>">
                                                        <i class="bi bi-pencil me-1"></i> Balance
                                                    </button>

                                                    <?php if ($acc['status'] === 'active'): ?>
                                                        <a href="manage_momo_accounts.php?toggle_status=inactive&id=<?php echo $acc['id']; ?>" class="btn btn-xs btn-outline-warning rounded-pill px-2">Deactivate</a>
                                                    <?php else: ?>
                                                        <a href="manage_momo_accounts.php?toggle_status=active&id=<?php echo $acc['id']; ?>" class="btn btn-xs btn-outline-success rounded-pill px-2">Activate</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Edit Balance Modal -->
                                        <div class="modal fade" id="editBalModal<?php echo $acc['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header bg-dark text-white rounded-top-4 py-2">
                                                        <h6 class="modal-title fw-bold">Update Demo Balance</h6>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="manage_momo_accounts.php" method="POST">
                                                        <div class="modal-body p-3">
                                                            <input type="hidden" name="action" value="update_balance">
                                                            <input type="hidden" name="account_id" value="<?php echo $acc['id']; ?>">
                                                            <div class="mb-2">
                                                                <span class="small text-muted d-block"><?php echo htmlspecialchars($acc['account_name']); ?></span>
                                                                <strong class="font-monospace text-dark"><?php echo htmlspecialchars($acc['phone_number']); ?></strong>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="simulated_balance" class="form-label small fw-bold">New Demo Balance (UGX)</label>
                                                                <input type="number" name="simulated_balance" class="form-control font-monospace" value="<?php echo $acc['simulated_balance']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer p-2 bg-light">
                                                            <button type="button" class="btn btn-xs btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-xs btn-primary rounded-pill px-3">Save Balance</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Transaction Logs Section -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-journal-text me-2 text-primary"></i> Simulated Payment Activity Audit Logs</h5>
            
            <!-- Filter Dropdown -->
            <div class="d-flex align-items-center gap-2">
                <span class="small text-muted fw-semibold">Filter Status:</span>
                <select class="form-select form-select-sm font-monospace" style="width: 220px;" onchange="location = 'manage_momo_accounts.php?filter_status=' + this.value;">
                    <option value="ALL" <?php if ($filter_status === 'ALL') echo 'selected'; ?>>ALL TRANSACTIONS</option>
                    <option value="SUCCESSFUL" <?php if ($filter_status === 'SUCCESSFUL') echo 'selected'; ?>>SUCCESSFUL</option>
                    <option value="INSUFFICIENT_BALANCE" <?php if ($filter_status === 'INSUFFICIENT_BALANCE') echo 'selected'; ?>>INSUFFICIENT_BALANCE</option>
                    <option value="INVALID_PIN" <?php if ($filter_status === 'INVALID_PIN') echo 'selected'; ?>>INVALID_PIN</option>
                    <option value="FAILED" <?php if ($filter_status === 'FAILED') echo 'selected'; ?>>FAILED</option>
                </select>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light text-uppercase">
                        <tr>
                            <th class="ps-3">Ref ID</th>
                            <th>Student</th>
                            <th>Network & Phone</th>
                            <th>Hostel & Room</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Timestamp</th>
                            <th class="pe-3">Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions && $transactions->num_rows > 0): ?>
                            <?php while ($tx = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-dark"><?php echo htmlspecialchars($tx['transaction_ref']); ?></td>
                                    <td><?php echo htmlspecialchars($tx['student_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo (strpos($tx['network'], 'Airtel') !== false) ? 'bg-danger' : 'bg-warning text-dark'; ?> font-monospace">
                                            <?php echo htmlspecialchars($tx['network']); ?>
                                        </span>
                                        <span class="font-monospace ms-1"><?php echo htmlspecialchars($tx['phone_number']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($tx['hostel_name']); ?> (Room <?php echo htmlspecialchars($tx['room_no']); ?>)</td>
                                    <td class="fw-bold text-dark font-monospace">UGX <?php echo number_format($tx['amount']); ?></td>
                                    <td>
                                        <?php if ($tx['status'] === 'SUCCESSFUL'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">SUCCESSFUL</span>
                                        <?php elseif ($tx['status'] === 'INSUFFICIENT_BALANCE'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">INSUFFICIENT BALANCE</span>
                                        <?php elseif ($tx['status'] === 'INVALID_PIN'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">INVALID PIN</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?php echo $tx['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?php echo date('d M Y, h:i:s A', strtotime($tx['created_at'])); ?></td>
                                    <td class="pe-3 text-muted" style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?php echo htmlspecialchars($tx['message']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No transaction logs recorded matching the selected filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
