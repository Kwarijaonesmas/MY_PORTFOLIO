<?php
// Seed script to initialize database, create tables, and populate demo accounts & hostels
// Accessible directly via web browser or CLI PHP

$db_host = "localhost";
$db_user = "root";
$db_pass = "";

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Database Seeder - Bushenyi Hostels Mobile Money Simulator</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'>
</head>
<body class='bg-light py-5'>
<div class='container' style='max-width: 850px;'>
    <div class='card shadow-lg border-0 rounded-4'>
        <div class='card-body p-4 p-md-5'>
            <div class='d-flex align-items-center justify-content-between mb-3'>
                <h2 class='fw-bold text-primary mb-0'><i class='bi bi-hdd-stack-fill me-2'></i> Bushenyi Hostels Seeder</h2>
                <span class='badge bg-warning text-dark font-monospace px-3 py-2 rounded-pill'>DEMO SIMULATOR MODE</span>
            </div>
            <p class='text-muted small'>Initializes database schema, seeds sample hostels, rooms, and fictional test Mobile Money accounts.</p>
            <hr>";

$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) {
    die("<div class='alert alert-danger'>Connection to MySQL server failed: " . htmlspecialchars($conn->connect_error) . "</div></div></div></div></body></html>");
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<div class='alert alert-success py-2'>✓ Database <strong>mydb</strong> checked/created successfully.</div>";
} else {
    echo "<div class='alert alert-danger'>Error creating database: " . $conn->error . "</div>";
}

$conn->select_db("mydb");

// Create upload directory
$upload_dir = __DIR__ . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<div class='alert alert-success py-2'>✓ Created directory <strong>/uploads/</strong>.</div>";
}

// Create Tables
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('student', 'owner', 'admin') NOT NULL DEFAULT 'student',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "hostels" => "CREATE TABLE IF NOT EXISTS `hostels` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `owner_id` INT NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `location` VARCHAR(100) NOT NULL,
        `price` INT NOT NULL,
        `type` ENUM('Single', 'Double', 'Self-contained') NOT NULL,
        `facilities` TEXT NOT NULL,
        `photo` VARCHAR(255) DEFAULT 'default_hostel.jpg',
        `status` ENUM('pending', 'approved') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "rooms" => "CREATE TABLE IF NOT EXISTS `rooms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `hostel_id` INT NOT NULL,
        `room_no` VARCHAR(50) NOT NULL,
        `status` ENUM('available', 'booked') DEFAULT 'available',
        FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "mobile_money_accounts" => "CREATE TABLE IF NOT EXISTS `mobile_money_accounts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `network` ENUM('MTN', 'Airtel') NOT NULL,
        `phone_number` VARCHAR(20) NOT NULL UNIQUE,
        `account_name` VARCHAR(100) NOT NULL,
        `simulated_balance` INT NOT NULL DEFAULT 500000,
        `test_pin_hash` VARCHAR(255) NOT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "bookings" => "CREATE TABLE IF NOT EXISTS `bookings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `room_id` INT NOT NULL,
        `hostel_id` INT NOT NULL,
        `booking_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `payment_status` ENUM('PENDING', 'PROCESSING', 'SUCCESSFUL', 'FAILED', 'INSUFFICIENT_BALANCE', 'INVALID_PIN', 'CANCELLED') DEFAULT 'PENDING',
        `payment_method` VARCHAR(50) DEFAULT 'MTN Mobile Money Demo',
        `payment_phone` VARCHAR(20) DEFAULT NULL,
        FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "transactions" => "CREATE TABLE IF NOT EXISTS `transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `booking_id` INT NOT NULL,
        `student_id` INT NOT NULL,
        `account_id` INT DEFAULT NULL,
        `network` VARCHAR(50) NOT NULL,
        `phone_number` VARCHAR(20) NOT NULL,
        `amount` INT NOT NULL,
        `transaction_ref` VARCHAR(50) NOT NULL UNIQUE,
        `status` ENUM('PENDING', 'PROCESSING', 'SUCCESSFUL', 'FAILED', 'INSUFFICIENT_BALANCE', 'INVALID_PIN', 'CANCELLED') NOT NULL,
        `message` TEXT,
        `remaining_balance` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "reviews" => "CREATE TABLE IF NOT EXISTS `reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `student_id` INT NOT NULL,
        `hostel_id` INT NOT NULL,
        `rating` INT NOT NULL DEFAULT 5,
        `comment` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($tables as $t_name => $t_sql) {
    if ($conn->query($t_sql) === TRUE) {
        echo "<div class='alert alert-success py-1 my-1'>✓ Table <strong>$t_name</strong> verified/created.</div>";
    } else {
        echo "<div class='alert alert-danger py-1 my-1'>Error creating $t_name: " . $conn->error . "</div>";
    }
}

// Seed Users
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$owner_pass = password_hash('owner123', PASSWORD_BCRYPT);
$student_pass = password_hash('student123', PASSWORD_BCRYPT);

$users = [
    ['System Administrator', 'admin@gmail.com', $admin_pass, 'admin'],
    ['Kato Peter (Hostel Owner)', 'owner@gmail.com', $owner_pass, 'owner'],
    ['Mugisha Denis (Hostel Owner)', 'denis@gmail.com', $owner_pass, 'owner'],
    ['Namubiru Sarah (Student)', 'student@gmail.com', $student_pass, 'student'],
    ['Tumusiime Ivan (Student)', 'ivan@gmail.com', $student_pass, 'student']
];

$stmt = $conn->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `password` = VALUES(`password`)");
foreach ($users as $u) {
    $stmt->bind_param("ssss", $u[0], $u[1], $u[2], $u[3]);
    $stmt->execute();
}
$stmt->close();
echo "<div class='alert alert-success py-2 mt-2'>✓ Default users seeded successfully.</div>";

// Seed Fictional Mobile Money Simulator Accounts
$momo_accounts = [
    ['MTN', '0770000001', 'Namubiru Sarah (MTN Demo)', 500000, password_hash('1234', PASSWORD_BCRYPT), 'active'],
    ['Airtel', '0750000001', 'Namubiru Sarah (Airtel Demo)', 400000, password_hash('5678', PASSWORD_BCRYPT), 'active'],
    ['MTN', '0770000002', 'Tumusiime Ivan (Low Balance Demo)', 100000, password_hash('1234', PASSWORD_BCRYPT), 'active'],
    ['Airtel', '0700000002', 'Tumusiime Ivan (High Balance Demo)', 1500000, password_hash('4321', PASSWORD_BCRYPT), 'active']
];

$stmt_m = $conn->prepare("INSERT IGNORE INTO `mobile_money_accounts` (`network`, `phone_number`, `account_name`, `simulated_balance`, `test_pin_hash`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($momo_accounts as $ma) {
    $stmt_m->bind_param("sssiss", $ma[0], $ma[1], $ma[2], $ma[3], $ma[4], $ma[5]);
    $stmt_m->execute();
}
$stmt_m->close();
echo "<div class='alert alert-success py-2 mt-2'>✓ 4 Fictional Test Mobile Money Accounts seeded (MTN: 0770000001 / PIN: 1234, Airtel: 0750000001 / PIN: 5678).</div>";

// Fetch dynamic user IDs for foreign key safety
$owners_res = $conn->query("SELECT id FROM users WHERE role = 'owner' ORDER BY id ASC");
$owner_ids = [];
while ($row = $owners_res->fetch_assoc()) {
    $owner_ids[] = $row['id'];
}
$owner1 = $owner_ids[0] ?? 1;
$owner2 = $owner_ids[1] ?? $owner1;

$students_res = $conn->query("SELECT id FROM users WHERE role = 'student' ORDER BY id ASC");
$student_ids = [];
while ($row = $students_res->fetch_assoc()) {
    $student_ids[] = $row['id'];
}
$student1 = $student_ids[0] ?? 1;
$student2 = $student_ids[1] ?? $student1;

// Seed Hostels
$check_h = $conn->query("SELECT COUNT(*) as count FROM hostels");
$row_h = $check_h->fetch_assoc();
if ($row_h['count'] == 0) {
    $hostels = [
        [$owner1, 'Crane Paradise Hostel', 'KIU Road', 850000, 'Self-contained', 'WiFi, Running Water, 24/7 Security, Electricity, Study Room', 'crane_paradise.jpg', 'approved'],
        [$owner1, 'Pearl Student Residence', 'Ishaka Town', 450000, 'Single', 'Water, Electricity, Perimeter Wall Security, DSTV Lounge', 'pearl_residence.jpg', 'approved'],
        [$owner1, 'KIU Heights Hostel', 'KIU Road', 600000, 'Double', 'WiFi, Water, Electricity, Kitchen Area, Security Guards', 'kiu_heights.jpg', 'approved'],
        [$owner2, 'Kashenyi View Hostel', 'Kashenyi', 750000, 'Self-contained', 'Balcony, Water, Electricity, Standby Generator, WiFi', 'kashenyi_view.jpg', 'approved'],
        [$owner2, 'Katungu Executive Suites', 'Katungu', 500000, 'Single', 'Water, Electricity, Quiet Environment, Security', 'katungu_suites.jpg', 'approved'],
        [$owner2, 'Valley View Student Hostel', 'Ishaka Town', 550000, 'Double', 'WiFi, Solar Water Heater, Shuttle Service, Security', 'valley_view.jpg', 'pending']
    ];

    $stmt_h = $conn->prepare("INSERT INTO `hostels` (`owner_id`, `name`, `location`, `price`, `type`, `facilities`, `photo`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($hostels as $h) {
        $stmt_h->bind_param("ississss", $h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $h[6], $h[7]);
        $stmt_h->execute();
        $h_id = $stmt_h->insert_id;

        // Add 5 rooms per hostel
        $stmt_r = $conn->prepare("INSERT INTO `rooms` (`hostel_id`, `room_no`, `status`) VALUES (?, ?, 'available')");
        for ($i = 1; $i <= 5; $i++) {
            $r_no = "Block A-10" . $i;
            $stmt_r->bind_param("is", $h_id, $r_no);
            $stmt_r->execute();
        }
        $stmt_r->close();
    }
    $stmt_h->close();
    echo "<div class='alert alert-success py-2'>✓ 6 Bushenyi Hostels & 30 Rooms seeded!</div>";
} else {
    echo "<div class='alert alert-info py-2'>ℹ Hostels already seeded.</div>";
}

echo "  <hr class='my-4'>
        <div class='bg-light p-3 rounded border mb-4'>
            <h5 class='fw-bold text-dark mb-2'>Default Demo Credentials & Test Accounts:</h5>
            <ul class='mb-2 small'>
                <li><strong>Admin:</strong> admin@gmail.com | Password: <code>admin123</code></li>
                <li><strong>Hostel Owner:</strong> owner@gmail.com | Password: <code>owner123</code></li>
                <li><strong>Student:</strong> student@gmail.com | Password: <code>student123</code></li>
            </ul>
            <hr class='my-2'>
            <h6 class='fw-bold text-dark mb-1'>Simulated Mobile Money Test Accounts:</h6>
            <ul class='mb-0 small font-monospace'>
                <li><strong class='text-warning bg-dark px-1 rounded'>MTN Demo:</strong> Phone <code>0770000001</code> | Balance UGX 500,000 | PIN <code>1234</code></li>
                <li><strong class='text-danger'>Airtel Demo:</strong> Phone <code>0750000001</code> | Balance UGX 400,000 | PIN <code>5678</code></li>
                <li><strong class='text-secondary'>MTN Low Bal:</strong> Phone <code>0770000002</code> | Balance UGX 100,000 | PIN <code>1234</code></li>
            </ul>
        </div>
        <div class='text-center'>
            <a href='index.php' class='btn btn-primary btn-lg rounded-pill px-5 shadow'><i class='bi bi-house-door-fill me-2'></i> Go to Homepage</a>
        </div>
    </div>
</div>
</div>
</body>
</html>";
?>
