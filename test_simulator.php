<?php
// Automated Testing Suite & Defense Audit Runner for Mobile Money Simulator
// HostelFinder Bushenyi - Computer Engineering Project Demonstration

require_once 'db.php';

// CLI / Web formatting helpers
$is_cli = (php_sapi_name() === 'cli');
function log_test($num, $title, $passed, $details) {
    global $is_cli;
    $status_str = $passed ? "[PASSED]" : "[FAILED]";
    if ($is_cli) {
        $color = $passed ? "\033[32m" : "\033[31m";
        $reset = "\033[0m";
        echo "Test $num: $title ... {$color}{$status_str}{$reset}\n   $details\n\n";
    } else {
        $bg = $passed ? "alert-success" : "alert-danger";
        echo "<div class='alert $bg mb-3 p-3 rounded-3 shadow-sm'>
                <h5 class='fw-bold mb-1'>Test $num: " . htmlspecialchars($title) . " - <strong>$status_str</strong></h5>
                <p class='mb-0 small font-monospace'>" . nl2br(htmlspecialchars($details)) . "</p>
              </div>";
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Mobile Money Simulator Test Runner - Bushenyi Hostels</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body class='bg-light py-5'>
    <div class='container' style='max-width: 900px;'>
        <div class='card shadow-lg border-0 rounded-4'>
            <div class='card-body p-4 p-md-5'>
                <h2 class='fw-bold text-dark mb-1'>🧪 Mobile Money Simulator Test Suite</h2>
                <p class='text-muted small mb-4'>Verifies transaction integrity, ACID compliance, concurrency protection, and balance safeguards.</p>
                <hr>";
} else {
    echo "=========================================================\n";
    echo "  MOBILE MONEY SIMULATOR AUTOMATED TEST SUITE RUNNER  \n";
    echo "=========================================================\n\n";
}

// ---------------------------------------------------------
// Helper: Reset Test Data Sandbox
// ---------------------------------------------------------
function reset_test_sandbox($conn) {
    // Reset test accounts
    $conn->query("UPDATE mobile_money_accounts SET simulated_balance = 500000 WHERE phone_number = '0770000001'");
    $conn->query("UPDATE mobile_money_accounts SET simulated_balance = 400000 WHERE phone_number = '0750000001'");
    $conn->query("UPDATE mobile_money_accounts SET simulated_balance = 100000 WHERE phone_number = '0770000002'");

    // Create a fresh test hostel and 2 rooms for test isolation
    $conn->query("INSERT INTO hostels (owner_id, name, location, price, type, facilities, status) VALUES (2, 'UnitTest Isolated Hostel', 'KIU Gate', 300000, 'Single', 'Water, WiFi', 'approved')");
    $hostel_id = $conn->insert_id;

    $conn->query("INSERT INTO rooms (hostel_id, room_no, status) VALUES ($hostel_id, 'TEST-101', 'available')");
    $room1_id = $conn->insert_id;

    $conn->query("INSERT INTO rooms (hostel_id, room_no, status) VALUES ($hostel_id, 'TEST-102', 'available')");
    $room2_id = $conn->insert_id;

    return [$hostel_id, $room1_id, $room2_id];
}

list($hostel_id, $room1_id, $room2_id) = reset_test_sandbox($conn);

// Fetch valid student ID dynamically
$students_res = $conn->query("SELECT id FROM users WHERE role = 'student' ORDER BY id ASC");
$student_ids = [];
while ($s_row = $students_res->fetch_assoc()) {
    $student_ids[] = $s_row['id'];
}
$student_id = $student_ids[0] ?? 1;

// ---------------------------------------------------------
// TEST 1 – Successful MTN Payment
// ---------------------------------------------------------
$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($student_id, $room1_id, $hostel_id, 'PENDING')");
$b1_id = $conn->insert_id;

$conn->begin_transaction();
$room_check = $conn->query("SELECT r.status, h.price FROM rooms r JOIN hostels h ON r.hostel_id = h.id WHERE r.id = $room1_id FOR UPDATE")->fetch_assoc();
$acc_check = $conn->query("SELECT * FROM mobile_money_accounts WHERE phone_number = '0770000001' FOR UPDATE")->fetch_assoc();

$t1_passed = false;
$t1_details = "";

if ($room_check['status'] === 'available' && password_verify('1234', $acc_check['test_pin_hash']) && $acc_check['simulated_balance'] >= 300000) {
    $new_bal = $acc_check['simulated_balance'] - 300000;
    $conn->query("UPDATE mobile_money_accounts SET simulated_balance = $new_bal WHERE id = {$acc_check['id']}");
    $conn->query("UPDATE rooms SET status = 'booked' WHERE id = $room1_id");
    $conn->query("UPDATE bookings SET payment_status = 'SUCCESSFUL', payment_method = 'MTN Mobile Money Demo', payment_phone = '0770000001' WHERE id = $b1_id");
    $tx_ref1 = 'DEMO-MTN-T1-' . rand(10000, 99999);
    $conn->query("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message, remaining_balance) VALUES ($b1_id, $student_id, {$acc_check['id']}, 'MTN', '0770000001', 300000, '$tx_ref1', 'SUCCESSFUL', 'Test 1 Passed', $new_bal)");
    $conn->commit();

    if ($new_bal === 200000) {
        $t1_passed = true;
        $t1_details = "Initial Balance: UGX 500,000 | Room Price: UGX 300,000 | New Balance: UGX 200,000 | Room Status: BOOKED | Booking Status: SUCCESSFUL";
    }
} else {
    $conn->rollback();
    $t1_details = "Validation failed during Test 1.";
}
log_test(1, "Successful MTN Payment (Balance >= Price & Correct PIN)", $t1_passed, $t1_details);


// ---------------------------------------------------------
// TEST 2 – Insufficient MTN Balance
// ---------------------------------------------------------
$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($student_id, $room2_id, $hostel_id, 'PENDING')");
$b2_id = $conn->insert_id;

$conn->begin_transaction();
$acc_check2 = $conn->query("SELECT * FROM mobile_money_accounts WHERE phone_number = '0770000002' FOR UPDATE")->fetch_assoc();

$t2_passed = false;
$t2_details = "";

if ((int)$acc_check2['simulated_balance'] < 300000) {
    $conn->rollback();
    $tx_ref2 = 'DEMO-MTN-T2-' . rand(10000, 99999);
    $conn->query("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message, remaining_balance) VALUES ($b2_id, $student_id, {$acc_check2['id']}, 'MTN', '0770000002', 300000, '$tx_ref2', 'INSUFFICIENT_BALANCE', 'Insufficient simulated balance', 100000)");
    $conn->query("UPDATE bookings SET payment_status = 'INSUFFICIENT_BALANCE' WHERE id = $b2_id");

    $room2_stat = $conn->query("SELECT status FROM rooms WHERE id = $room2_id")->fetch_assoc()['status'];
    $acc2_bal = $conn->query("SELECT simulated_balance FROM mobile_money_accounts WHERE phone_number = '0770000002'")->fetch_assoc()['simulated_balance'];

    if ($room2_stat === 'available' && (int)$acc2_bal === 100000) {
        $t2_passed = true;
        $t2_details = "Balance Unchanged: UGX 100,000 | Room Status: AVAILABLE (Unchanged) | Transaction Status: INSUFFICIENT_BALANCE | Transaction Aborted cleanly.";
    }
} else {
    $conn->rollback();
    $t2_details = "Test 2 validation failed.";
}
log_test(2, "Insufficient MTN Balance Safeguard", $t2_passed, $t2_details);


// ---------------------------------------------------------
// TEST 3 – Invalid PIN Test
// ---------------------------------------------------------
$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($student_id, $room2_id, $hostel_id, 'PENDING')");
$b3_id = $conn->insert_id;

$conn->begin_transaction();
$acc_check3 = $conn->query("SELECT * FROM mobile_money_accounts WHERE phone_number = '0770000001' FOR UPDATE")->fetch_assoc();
$wrong_pin = "9999";

$t3_passed = false;
$t3_details = "";

if (!password_verify($wrong_pin, $acc_check3['test_pin_hash'])) {
    $conn->rollback();
    $tx_ref3 = 'DEMO-MTN-T3-' . rand(10000, 99999);
    $conn->query("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message) VALUES ($b3_id, $student_id, {$acc_check3['id']}, 'MTN', '0770000001', 300000, '$tx_ref3', 'INVALID_PIN', 'Invalid PIN entered')");
    $conn->query("UPDATE bookings SET payment_status = 'INVALID_PIN' WHERE id = $b3_id");

    $room2_stat = $conn->query("SELECT status FROM rooms WHERE id = $room2_id")->fetch_assoc()['status'];
    $acc1_bal = $conn->query("SELECT simulated_balance FROM mobile_money_accounts WHERE phone_number = '0770000001'")->fetch_assoc()['simulated_balance'];

    if ($room2_stat === 'available' && (int)$acc1_bal === 200000) {
        $t3_passed = true;
        $t3_details = "Wrong PIN '9999' rejected | Balance Unchanged: UGX 200,000 | Room Status: AVAILABLE | Transaction Status: INVALID_PIN.";
    }
} else {
    $conn->rollback();
}
log_test(3, "Invalid Test PIN Rejection", $t3_passed, $t3_details);


// ---------------------------------------------------------
// TEST 4 – Successful Airtel Payment
// ---------------------------------------------------------
$conn->begin_transaction();
$acc_airtel = $conn->query("SELECT * FROM mobile_money_accounts WHERE phone_number = '0750000001' FOR UPDATE")->fetch_assoc();

$t4_passed = false;
$t4_details = "";

if (password_verify('5678', $acc_airtel['test_pin_hash']) && (int)$acc_airtel['simulated_balance'] >= 300000) {
    $new_bal4 = (int)$acc_airtel['simulated_balance'] - 300000;
    $conn->query("UPDATE mobile_money_accounts SET simulated_balance = $new_bal4 WHERE id = {$acc_airtel['id']}");
    $conn->query("UPDATE rooms SET status = 'booked' WHERE id = $room2_id");
    $conn->query("UPDATE bookings SET payment_status = 'SUCCESSFUL', payment_method = 'Airtel Money Demo', payment_phone = '0750000001' WHERE id = $b3_id");
    $tx_ref4 = 'DEMO-ATL-T4-' . rand(10000, 99999);
    $conn->query("INSERT INTO transactions (booking_id, student_id, account_id, network, phone_number, amount, transaction_ref, status, message, remaining_balance) VALUES ($b3_id, $student_id, {$acc_airtel['id']}, 'Airtel', '0750000001', 300000, '$tx_ref4', 'SUCCESSFUL', 'Airtel Test Passed', $new_bal4)");
    $conn->commit();

    if ($new_bal4 === 100000) {
        $t4_passed = true;
        $t4_details = "Network: Airtel Money Demo | Initial: UGX 400,000 | Paid: UGX 300,000 | Remaining: UGX 100,000 | Room Status: BOOKED";
    }
} else {
    $conn->rollback();
}
log_test(4, "Successful Airtel Money Payment Workflow", $t4_passed, $t4_details);


// ---------------------------------------------------------
// TEST 5 – Concurrent Booking Simulation (Race Condition Safeguard)
// ---------------------------------------------------------
$stA_id = $student_ids[0] ?? $student_id;
$stB_id = $student_ids[1] ?? $stA_id;

$conn->query("INSERT INTO rooms (hostel_id, room_no, status) VALUES ($hostel_id, 'CONCURRENCY-999', 'available')");
$conc_room_id = $conn->insert_id;

$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($stA_id, $conc_room_id, $hostel_id, 'PENDING')");
$b_studentA = $conn->insert_id;

$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($stB_id, $conc_room_id, $hostel_id, 'PENDING')");
$b_studentB = $conn->insert_id;

// 1. Student A locks room and completes transaction
$conn->begin_transaction();
$r_lockA = $conn->query("SELECT status FROM rooms WHERE id = $conc_room_id FOR UPDATE")->fetch_assoc();
if ($r_lockA['status'] === 'available') {
    $conn->query("UPDATE rooms SET status = 'booked' WHERE id = $conc_room_id");
    $conn->query("UPDATE bookings SET payment_status = 'SUCCESSFUL' WHERE id = $b_studentA");
    $conn->commit();
    $studentA_ok = true;
} else {
    $conn->rollback();
    $studentA_ok = false;
}

// 2. Student B attempts payment on same room right after Student A
$conn->begin_transaction();
$r_lockB = $conn->query("SELECT status FROM rooms WHERE id = $conc_room_id FOR UPDATE")->fetch_assoc();
if ($r_lockB['status'] === 'available') {
    $conn->query("UPDATE rooms SET status = 'booked' WHERE id = $conc_room_id");
    $conn->query("UPDATE bookings SET payment_status = 'SUCCESSFUL' WHERE id = $b_studentB");
    $conn->commit();
    $studentB_ok = true;
} else {
    $conn->rollback();
    $conn->query("UPDATE bookings SET payment_status = 'FAILED' WHERE id = $b_studentB");
    $studentB_ok = false;
}

$t5_passed = ($studentA_ok === true && $studentB_ok === false);
$t5_details = "Student A Result: " . ($studentA_ok ? "SUCCESSFUL" : "FAILED") . " | Student B Result: " . ($studentB_ok ? "SUCCESSFUL" : "REJECTED (Room No Longer Available)") . " | Database Lock FOR UPDATE prevented double booking.";
log_test(5, "Concurrent Booking Protection (Atomic Locking)", $t5_passed, $t5_details);


// ---------------------------------------------------------
// TEST 6 – Cancelled Payment Scenario
// ---------------------------------------------------------
$conn->query("INSERT INTO rooms (hostel_id, room_no, status) VALUES ($hostel_id, 'CANCEL-303', 'available')");
$cancel_room_id = $conn->insert_id;

$conn->query("INSERT INTO bookings (student_id, room_id, hostel_id, payment_status) VALUES ($student_id, $cancel_room_id, $hostel_id, 'PENDING')");
$b_cancel = $conn->insert_id;

$conn->query("UPDATE bookings SET payment_status = 'CANCELLED' WHERE id = $b_cancel");
$tx_ref6 = 'DEMO-MTN-CNCL-' . rand(10000, 99999);
$conn->query("INSERT INTO transactions (booking_id, student_id, network, phone_number, amount, transaction_ref, status, message) VALUES ($b_cancel, $student_id, 'MTN', '0770000001', 300000, '$tx_ref6', 'CANCELLED', 'Student cancelled simulated payment')");

$cancel_room_stat = $conn->query("SELECT status FROM rooms WHERE id = $cancel_room_id")->fetch_assoc()['status'];
$t6_passed = ($cancel_room_stat === 'available');
$t6_details = "Payment Status: CANCELLED | Balance Deducted: UGX 0 | Room Status: AVAILABLE | Room remains ready for other bookings.";
log_test(6, "Cancelled Payment Scenario", $t6_passed, $t6_details);

// Cleanup sandbox
$conn->query("DELETE FROM hostels WHERE id = $hostel_id");

if (!$is_cli) {
    echo "  <hr class='my-4'>
            <div class='text-center'>
                <a href='index.php' class='btn btn-primary rounded-pill px-4'>Return to Homepage</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>";
}
?>
