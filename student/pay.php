<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('student');

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['user_name'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id <= 0) {
    header("Location: my_bookings.php");
    exit();
}

$err = '';
$err_type = '';
$receipt = null;

// Fetch Booking & Room Details (Price retrieved strictly from DB, never trusted from client)
$stmt = $conn->prepare("SELECT b.*, h.name as hostel_name, h.location, h.price as room_price, r.room_no, r.status as room_status 
                        FROM bookings b 
                        JOIN hostels h ON b.hostel_id = h.id 
                        JOIN rooms r ON b.room_id = r.id 
                        WHERE b.id = ? AND b.student_id = ? LIMIT 1");
$stmt->bind_param("ii", $booking_id, $student_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Handle Simulated Mobile Money Payment Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_network'])) {
    $network = trim($_POST['payment_network'] ?? 'MTN');
    $phone = trim($_POST['payment_phone'] ?? '');
    $test_pin = trim($_POST['test_pin'] ?? '');

    $network_val = ($network === 'Airtel') ? 'Airtel' : 'MTN';
    $method_label = ($network_val === 'Airtel') ? 'Airtel Money Demo' : 'MTN Mobile Money Demo';

    if (empty($phone) || empty($test_pin)) {
        $err = "Simulated Payment Error: Please provide both the test phone number and test PIN.";
        $err_type = "INVALID_INPUT";
    } elseif (strlen($test_pin) !== 4 || !is_numeric($test_pin)) {
        $err = "Simulated Payment Error: Test PIN must be a 4-digit number (e.g., 1234 or 5678).";
        $err_type = "INVALID_PIN";
    } else {
        // BEGIN ATOMIC DATABASE TRANSACTION (Concurrency & ACID Security)
        $conn->begin_transaction();

        try {
            // 1. Verify Room is still available using row lock (FOR UPDATE)
            $stmt_room_check = $conn->prepare("SELECT r.status, h.price FROM rooms r JOIN hostels h ON r.hostel_id = h.id WHERE r.id = ? FOR UPDATE");
            $stmt_room_check->bind_param("i", $booking['room_id']);
            $stmt_room_check->execute();
            $room_data = $stmt_room_check->get_result()->fetch_assoc();
            $stmt_room_check->close();

            if (!$room_data || $room_data['status'] !== 'available') {
                // Room already booked by another user -> Abort
                $conn->rollback();
                $tx_ref = 'DEMO-FAIL-' . strtoupper(substr(md5(time() . rand()), 0, 8));
                
                // Log failed transaction outside rollback
                $stmt_log = $conn->prepare("INSERT INTO transactions (booking_id, student_id, network, phone_number, amount, transaction_ref, status, message) VALUES (?, ?, ?, ?, ?, ?, 'FAILED', 'Room is no longer available. Another student booked this room.')");
                $stmt_log->bind_param("iissis", $booking_id, $student_id, $network_val, $phone, $booking['room_price'], $tx_ref);
                $stmt_log->execute();
                $stmt_log->close();

                $stmt_b_up = $conn->prepare("UPDATE bookings SET payment_status = 'FAILED' WHERE id = ?");
                $stmt_b_up->bind_param("i", $booking_id);
                $stmt_b_up->execute();
                $stmt_b_up->close();

                $err = "Payment Failed: Room " . htmlspecialchars($booking['room_no']) . " is no longer available. Another student completed booking for this room.";
                $err_type = "ROOM_UNAVAILABLE";
            } else {
                $actual_room_price = (int)$room_data['price'];

                // 2. Lookup Simulated Mobile Money Account in Database
                $stmt_acc = $conn->prepare("SELECT * FROM mobile_money_accounts WHERE phone_number = ? AND network = ? AND status = 'active' FOR UPDATE");
                $stmt_acc->bind_param("ss", $phone, $network_val);
                $stmt_acc->execute();
                $acc = $stmt_acc->get_result()->fetch_assoc();
                $stmt_acc->close();

                if (!$acc) {
                    // Test account not found or inactive
                    $conn->rollback();
                    $tx_ref = 'DEMO-FAIL-' . strtoupper(substr(md5(time() . rand()), 0, 8));
                    
                    $stmt_log = $conn->prepare("INSERT INTO transactions (booking_id, student_id, network, phone_number, amount, transaction_ref, status, message) VALUES (?, ?, ?, ?, ?, ?, 'FAILED', 'Simulated Mobile Money account not found or inactive.')");
                    $stmt_log->bind_param("iissis", $booking_id, $student_id, $network_val, $phone, $actual_room_price, $tx_ref);
                    $stmt_log->execute();
                    $stmt_log->close();

                    $stmt_b_up = $conn->prepare("UPDATE bookings SET payment_status = 'FAILED' WHERE id = ?");
                    $stmt_b_up->bind_param("i", $booking_id);
                    $stmt_b_up->execute();
                    $stmt_b_up->close();

                    $err = "Payment Failed: Simulated Mobile Money account for phone number " . htmlspecialchars($phone) . " on " . htmlspecialchars($network_val) . " network was not found. Please use a valid test account (e.g. 0770000001 or 0750000001).";
                    $err_type = "ACCOUNT_NOT_FOUND";
                } elseif (!password_verify($test_pin, $acc['test_pin_hash'])) {
                    // 3. Verify Test PIN
                    $conn->rollback();
                    $tx_ref = 'DEMO-PINFAIL-' . strtoupper(substr(md5(time() . rand()), 0, 8));
                    
                    $stmt_log = $conn->prepare("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message) VALUES (?, ?, ?, ?, ?, ?, ?, 'INVALID_PIN', 'Invalid test PIN entered for simulated Mobile Money account.')");
                    $stmt_log->bind_param("iiissis", $booking_id, $student_id, $acc['id'], $network_val, $phone, $actual_room_price, $tx_ref);
                    $stmt_log->execute();
                    $stmt_log->close();

                    $stmt_b_up = $conn->prepare("UPDATE bookings SET payment_status = 'INVALID_PIN' WHERE id = ?");
                    $stmt_b_up->bind_param("i", $booking_id);
                    $stmt_b_up->execute();
                    $stmt_b_up->close();

                    $err = "Payment Failed: Invalid test PIN entered for test account " . htmlspecialchars($phone) . ". No money was deducted, and the room remains available.";
                    $err_type = "INVALID_PIN";
                } elseif ((int)$acc['simulated_balance'] < $actual_room_price) {
                    // 4. Verify Simulated Balance
                    $conn->rollback();
                    $current_bal = (int)$acc['simulated_balance'];
                    $shortage = $actual_room_price - $current_bal;
                    $tx_ref = 'DEMO-BALFAIL-' . strtoupper(substr(md5(time() . rand()), 0, 8));

                    $msg_fail = "Insufficient simulated balance. Room Price: UGX " . number_format($actual_room_price) . ", Available Demo Balance: UGX " . number_format($current_bal) . ", Shortage: UGX " . number_format($shortage) . ". No money was deducted. The room remains available.";

                    $stmt_log = $conn->prepare("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message, remaining_balance) VALUES (?, ?, ?, ?, ?, ?, ?, 'INSUFFICIENT_BALANCE', ?, ?)");
                    $stmt_log->bind_param("iiississi", $booking_id, $student_id, $acc['id'], $network_val, $phone, $actual_room_price, $tx_ref, $msg_fail, $current_bal);
                    $stmt_log->execute();
                    $stmt_log->close();

                    $stmt_b_up = $conn->prepare("UPDATE bookings SET payment_status = 'INSUFFICIENT_BALANCE' WHERE id = ?");
                    $stmt_b_up->bind_param("i", $booking_id);
                    $stmt_b_up->execute();
                    $stmt_b_up->close();

                    $err = "Payment Failed\n\nInsufficient simulated balance.\n\nRoom Price:\nUGX " . number_format($actual_room_price) . "\n\nAvailable Demo Balance:\nUGX " . number_format($current_bal) . "\n\nShortage:\nUGX " . number_format($shortage) . "\n\nNo money was deducted.\nThe room remains available.";
                    $err_type = "INSUFFICIENT_BALANCE";
                } else {
                    // 5. SUCCESSFUL SIMULATED TRANSACTION -> Execute Atomic Deductions & Lock Room
                    $new_balance = (int)$acc['simulated_balance'] - $actual_room_price;

                    // A. Deduct simulated balance
                    $stmt_bal = $conn->prepare("UPDATE mobile_money_accounts SET simulated_balance = ? WHERE id = ?");
                    $stmt_bal->bind_param("ii", $new_balance, $acc['id']);
                    $stmt_bal->execute();
                    $stmt_bal->close();

                    // B. Change room status to booked
                    $stmt_room_up = $conn->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?");
                    $stmt_room_up->bind_param("i", $booking['room_id']);
                    $stmt_room_up->execute();
                    $stmt_room_up->close();

                    // C. Update booking payment status
                    $stmt_b_ok = $conn->prepare("UPDATE bookings SET payment_status = 'SUCCESSFUL', payment_method = ?, payment_phone = ? WHERE id = ?");
                    $stmt_b_ok->bind_param("ssi", $method_label, $phone, $booking_id);
                    $stmt_b_ok->execute();
                    $stmt_b_ok->close();

                    // D. Create transaction record
                    $tx_prefix = ($network_val === 'Airtel') ? 'DEMO-ATL-' : 'DEMO-MTN-';
                    $tx_ref = $tx_prefix . strtoupper(substr(md5(time() . $booking_id . rand()), 0, 8));

                    $msg_ok = "Simulated Mobile Money payment completed successfully.";
                    $stmt_tx = $conn->prepare("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message, remaining_balance) VALUES (?, ?, ?, ?, ?, ?, ?, 'SUCCESSFUL', ?, ?)");
                    $stmt_tx->bind_param("iiississi", $booking_id, $student_id, $acc['id'], $network_val, $phone, $actual_room_price, $tx_ref, $msg_ok, $new_balance);
                    $stmt_tx->execute();
                    $stmt_tx->close();

                    // Commit Transaction
                    $conn->commit();

                    $receipt = [
                        'tx_id' => $tx_ref,
                        'network' => $method_label,
                        'phone' => $phone,
                        'account_name' => $acc['account_name'],
                        'amount' => $actual_room_price,
                        'remaining_balance' => $new_balance,
                        'date' => date('d M Y, h:i A'),
                        'booking_id' => $booking_id,
                        'hostel_name' => $booking['hostel_name'],
                        'room_no' => $booking['room_no'],
                        'student_name' => $student_name
                    ];
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = "System Error: Transaction could not be completed. " . $e->getMessage();
            $err_type = "SYSTEM_ERROR";
        }
    }
}

// Fetch available Test Mobile Money Accounts for helper display
$demo_accounts_res = $conn->query("SELECT * FROM mobile_money_accounts WHERE status = 'active' ORDER BY network DESC, id ASC");

$page_title = "Simulated Mobile Money Payment Gateway";
require_once '../includes/header.php';
?>

<div class="container py-4" style="max-width: 760px;">

    <!-- Prominent Demo Disclaimer Banner -->
    <div class="alert alert-warning border-start border-warning border-4 shadow-sm rounded-4 p-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-warning text-dark p-2.5 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-info-circle-fill"></i>
            </div>
            <div>
                <strong class="d-block text-dark font-monospace text-uppercase tracking-wide">DEMO / SIMULATION MODE – NO REAL MONEY IS TRANSFERRED</strong>
                <span class="small text-muted">This environment operates strictly on fictional test accounts and simulated balances for academic demonstration purposes.</span>
            </div>
        </div>
    </div>

    <?php if ($receipt): ?>
        <!-- Successful Payment Receipt -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
            <div class="<?php echo (strpos($receipt['network'], 'Airtel') !== false) ? 'bg-danger' : 'bg-warning text-dark'; ?> text-white p-4 text-center position-relative">
                <span class="badge bg-dark text-warning position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill font-monospace small">SIMULATION MODE</span>
                <div class="bg-white <?php echo (strpos($receipt['network'], 'Airtel') !== false) ? 'text-danger' : 'text-dark'; ?> d-inline-flex p-3 rounded-circle mb-3 shadow">
                    <i class="bi bi-check-lg display-4"></i>
                </div>
                <h2 class="fw-bold mb-1 text-white">Payment Successful</h2>
                <p class="mb-0 opacity-90">Transaction processed via <?php echo htmlspecialchars($receipt['network']); ?></p>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="alert alert-secondary border-0 rounded-3 mb-4 text-center font-monospace fw-bold text-uppercase small">
                    SIMULATED TRANSACTION – NO REAL MONEY TRANSFERRED
                </div>

                <div class="border rounded-3 p-3 bg-light mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Demo Transaction Ref ID:</span>
                        <span class="font-monospace fw-bold text-dark"><?php echo $receipt['tx_id']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Network Provider:</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($receipt['network']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Test Phone Number:</span>
                        <span class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($receipt['phone']); ?> (<?php echo htmlspecialchars($receipt['account_name']); ?>)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Hostel & Reserved Room:</span>
                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($receipt['hostel_name']); ?> (Room <?php echo htmlspecialchars($receipt['room_no']); ?>)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Date & Time:</span>
                        <span class="small text-dark"><?php echo $receipt['date']; ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark">Simulated Amount Paid:</span>
                        <span class="fs-4 fw-extrabold text-success">UGX <?php echo number_format($receipt['amount']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Remaining Demo Balance:</span>
                        <span class="fw-bold font-monospace text-primary">UGX <?php echo number_format($receipt['remaining_balance']); ?></span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="receipt.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary-theme btn-lg rounded-pill fw-bold">
                        <i class="bi bi-file-earmark-pdf me-2"></i> View & Print Digital Receipt
                    </a>
                    <a href="my_bookings.php" class="btn btn-outline-secondary rounded-pill fw-bold">
                        <i class="bi bi-journal-check me-2"></i> Return to My Bookings
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>

        <?php if ($booking['payment_status'] === 'SUCCESSFUL' || $booking['room_status'] === 'booked'): ?>
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden p-4 text-center bg-white">
                <i class="bi bi-check-circle-fill display-3 d-block text-success mb-2"></i>
                <h4 class="fw-bold text-dark">This Room Booking is Already Confirmed & Paid!</h4>
                <p class="mb-1 text-muted">Payment status: <span class="badge bg-success font-monospace">SUCCESSFUL</span></p>
                <p class="mb-4">Paid via: <strong><?php echo htmlspecialchars($booking['payment_method'] ?? 'Mobile Money Demo'); ?></strong> (<?php echo htmlspecialchars($booking['payment_phone']); ?>)</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="receipt.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-primary rounded-pill px-4">View Receipt</a>
                    <a href="my_bookings.php" class="btn btn-primary-theme rounded-pill px-4">My Bookings</a>
                </div>
            </div>
        <?php else: ?>

            <!-- Error Banner -->
            <?php if (!empty($err)): ?>
                <div class="card border-danger border-2 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-danger text-white py-3 fw-bold d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle-fill fs-5"></i>
                        <span>Payment Failed</span>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <pre class="mb-0 text-dark font-sans" style="white-space: pre-wrap; font-family: inherit; font-size: 1rem; line-height: 1.6;"><?php echo htmlspecialchars($err); ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Test Accounts Helper Drawer -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-card-checklist me-2 text-primary"></i> Available Test Mobile Money Accounts</h5>
                    <p class="text-muted small mb-3">Select any pre-configured test account to simulate different payment outcomes (Success, Low Balance, etc.):</p>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Network</th>
                                    <th>Test Phone Number</th>
                                    <th>Account Name</th>
                                    <th>Simulated Balance</th>
                                    <th>Test PIN</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($demo_accounts_res && $demo_accounts_res->num_rows > 0): ?>
                                    <?php while ($d_acc = $demo_accounts_res->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo ($d_acc['network'] === 'Airtel') ? 'bg-danger' : 'bg-warning text-dark'; ?> font-monospace">
                                                    <?php echo htmlspecialchars($d_acc['network']); ?> Demo
                                                </span>
                                            </td>
                                            <td class="font-monospace fw-bold"><?php echo htmlspecialchars($d_acc['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($d_acc['account_name']); ?></td>
                                            <td class="fw-bold font-monospace <?php echo ($d_acc['simulated_balance'] < $booking['room_price']) ? 'text-danger' : 'text-success'; ?>">
                                                UGX <?php echo number_format($d_acc['simulated_balance']); ?>
                                            </td>
                                            <td><code class="fw-bold fs-6">1234 / 5678</code></td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 small" 
                                                        onclick="selectTestAccount('<?php echo $d_acc['network']; ?>', '<?php echo $d_acc['phone_number']; ?>')">
                                                    Use Account
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Network Selection Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Room Details Card -->
                    <div class="alert alert-light border p-3 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-1">Hostel Room Checkout</span>
                                <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($booking['hostel_name']); ?></h4>
                                <span class="text-muted small">Room No: <strong><?php echo htmlspecialchars($booking['room_no']); ?></strong> (<?php echo htmlspecialchars($booking['location']); ?>)</span>
                            </div>
                            <div class="text-end">
                                <span class="small text-muted d-block">Semester Fee (From Database)</span>
                                <span class="fs-3 fw-extrabold text-dark font-monospace">UGX <?php echo number_format($booking['room_price']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Provider Tabs -->
                    <ul class="nav nav-pills nav-fill bg-light p-2 rounded-4 mb-4 border" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-3 rounded-3 d-flex align-items-center justify-content-center gap-2" id="tab-mtn" data-bs-toggle="pill" data-bs-target="#pane-mtn" type="button" role="tab" onclick="setFormNetwork('MTN')">
                                <span class="bg-warning text-dark px-2 py-0.5 rounded font-monospace fw-bold">MTN</span>
                                <span>MTN Mobile Money Simulator</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 rounded-3 d-flex align-items-center justify-content-center gap-2 text-danger" id="tab-airtel" data-bs-toggle="pill" data-bs-target="#pane-airtel" type="button" role="tab" onclick="setFormNetwork('Airtel')">
                                <span class="bg-danger text-white px-2 py-0.5 rounded font-monospace fw-bold">airtel</span>
                                <span>Airtel Money Simulator</span>
                            </button>
                        </li>
                    </ul>

                    <form action="pay.php?booking_id=<?php echo $booking_id; ?>" method="POST" id="mainPaymentForm">
                        <input type="hidden" name="payment_network" id="payment_network_input" value="MTN">
                        
                        <div class="mb-3">
                            <label for="payment_phone_input" class="form-label fw-bold">Test Phone Number</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-dark text-warning fw-bold font-monospace">+256</span>
                                <input type="tel" name="payment_phone" id="payment_phone_input" class="form-control font-monospace fw-bold" placeholder="0770000001" required value="0770000001">
                            </div>
                            <span class="form-text text-muted">Enter registered demo subscriber number (e.g. <code>0770000001</code> for MTN or <code>0750000001</code> for Airtel).</span>
                        </div>

                        <input type="hidden" name="test_pin" id="hidden_test_pin" value="1234">

                        <button type="button" onclick="openSimulatedModal()" class="btn btn-warning btn-lg w-100 fw-bold text-dark py-3 shadow rounded-3">
                            <i class="bi bi-device-ssd me-2"></i> OPEN SIMULATED PAYMENT PROMPT (UGX <?php echo number_format($booking['room_price']); ?>)
                        </button>
                    </form>

                </div>
            </div>

        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Professional Simulated Mobile Money Prompt Modal -->
<div class="modal fade" id="momoDemoModal" tabindex="-1" aria-labelledby="momoDemoModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            
            <div id="modalHeaderBg" class="p-4 text-white text-center position-relative bg-dark">
                <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill font-monospace small">DEMO MODE</span>
                <div class="bg-white text-dark d-inline-flex p-3 rounded-circle mb-2 shadow">
                    <i class="bi bi-phone-vibrate-fill fs-2 text-warning" id="modalIcon"></i>
                </div>
                <h4 class="fw-bold mb-0 text-white" id="modalNetworkTitle">MOBILE MONEY DEMO</h4>
                <p class="small text-white-50 mb-0 font-monospace">Simulated Payment Authorization Prompt</p>
            </div>
            
            <div class="modal-body p-4">
                <div class="alert alert-secondary border-0 rounded-3 p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Target Merchant:</span>
                        <strong class="text-dark">Bushenyi Hostels System</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Hostel & Room:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($booking['hostel_name']); ?> (Room <?php echo htmlspecialchars($booking['room_no']); ?>)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Selected Network:</span>
                        <strong id="modalNetworkDisplay" class="font-monospace text-dark">MTN Mobile Money Simulator</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Test Phone Number:</span>
                        <strong id="modalPhoneDisplay" class="font-monospace text-dark">0770000001</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Payment Amount:</span>
                        <span class="fs-4 fw-extrabold text-primary font-monospace">UGX <?php echo number_format($booking['room_price']); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="modal_pin_input" class="form-label fw-bold text-dark d-block text-center">Enter TEST PIN:</label>
                    <input type="password" id="modal_pin_input" maxlength="4" class="form-control form-control-lg font-monospace text-center fs-2" placeholder="••••" value="1234">
                    <span class="form-text text-muted text-center d-block">Test PINs: <code>1234</code> (MTN) or <code>5678</code> (Airtel)</span>
                </div>

                <div class="alert alert-warning border-0 rounded-3 p-2 text-center small mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>DEMONSTRATION ONLY</strong><br>No real money will be transferred.
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">
                    [ Cancel ]
                </button>
                <button type="button" id="confirmPaymentBtn" class="btn btn-success rounded-pill px-4 fw-bold">
                    [ Confirm Payment ]
                </button>
            </div>

        </div>
    </div>
</div>

<script>
let currentNetwork = 'MTN';

function setFormNetwork(net) {
    currentNetwork = net;
    document.getElementById('payment_network_input').value = net;
    const phoneInput = document.getElementById('payment_phone_input');
    if (net === 'Airtel' && phoneInput.value === '0770000001') {
        phoneInput.value = '0750000001';
    } else if (net === 'MTN' && phoneInput.value === '0750000001') {
        phoneInput.value = '0770000001';
    }
}

function selectTestAccount(network, phone) {
    if (network === 'Airtel') {
        const tabAirtel = new bootstrap.Tab(document.getElementById('tab-airtel'));
        tabAirtel.show();
        setFormNetwork('Airtel');
    } else {
        const tabMtn = new bootstrap.Tab(document.getElementById('tab-mtn'));
        tabMtn.show();
        setFormNetwork('MTN');
    }
    document.getElementById('payment_phone_input').value = phone;
}

function openSimulatedModal() {
    const phoneVal = document.getElementById('payment_phone_input').value;
    document.getElementById('modalNetworkDisplay').innerText = currentNetwork + ' Mobile Money Simulator';
    document.getElementById('modalPhoneDisplay').innerText = phoneVal;

    const modalHeader = document.getElementById('modalHeaderBg');
    const modalIcon = document.getElementById('modalIcon');
    const modalNetworkTitle = document.getElementById('modalNetworkTitle');

    if (currentNetwork === 'Airtel') {
        modalHeader.className = 'p-4 text-white text-center position-relative bg-danger';
        modalIcon.className = 'bi bi-phone-vibrate-fill fs-2 text-danger';
        modalNetworkTitle.innerText = 'AIRTEL MONEY DEMO';
        document.getElementById('modal_pin_input').value = '5678';
    } else {
        modalHeader.className = 'p-4 text-white text-center position-relative bg-warning text-dark';
        modalIcon.className = 'bi bi-phone-vibrate-fill fs-2 text-dark';
        modalNetworkTitle.innerText = 'MTN MOBILE MONEY DEMO';
        document.getElementById('modal_pin_input').value = '1234';
    }

    const demoModal = new bootstrap.Modal(document.getElementById('momoDemoModal'));
    demoModal.show();
}

document.getElementById('confirmPaymentBtn').addEventListener('click', function() {
    const pinVal = document.getElementById('modal_pin_input').value;
    document.getElementById('hidden_test_pin').value = pinVal;
    document.getElementById('mainPaymentForm').submit();
});
</script>

<?php require_once '../includes/footer.php'; ?>
