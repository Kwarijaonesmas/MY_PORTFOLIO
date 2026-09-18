<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate root path relative prefix dynamically
$script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
$root_dir = dirname(__DIR__);
if (realpath($script_dir) === realpath($root_dir)) {
    $root_path = "./";
} else {
    $root_path = "../";
}

$user = isset($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'name' => $_SESSION['user_name'] ?? 'User',
    'role' => $_SESSION['user_role'] ?? 'student'
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . " - HostelFinder Bushenyi" : "HostelFinder Bushenyi - Find & Book Student Hostels"; ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $root_path; ?>css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $root_path; ?>index.php">
            <i class="bi bi-building-check text-primary fs-3"></i>
            <span>HostelFinder <span class="badge bg-primary fs-6 fw-bold">Bushenyi</span></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="<?php echo $root_path; ?>index.php"><i class="bi bi-search me-1"></i> Explore Hostels</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'student'): ?>
                        <a href="<?php echo $root_path; ?>student/dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold me-1">
                            <i class="bi bi-speedometer2 me-1"></i> Student Dashboard
                        </a>
                        <a href="<?php echo $root_path; ?>student/my_bookings.php" class="btn btn-primary-theme btn-sm rounded-pill">
                            <i class="bi bi-journal-check me-1"></i> My Bookings
                        </a>
                    <?php elseif ($user['role'] === 'owner'): ?>
                        <a href="<?php echo $root_path; ?>owner/dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold me-1">
                            <i class="bi bi-speedometer2 me-1"></i> Owner Dashboard
                        </a>
                        <a href="<?php echo $root_path; ?>owner/add_hostel.php" class="btn btn-primary-theme btn-sm rounded-pill">
                            <i class="bi bi-plus-circle me-1"></i> Add Hostel
                        </a>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <a href="<?php echo $root_path; ?>admin/dashboard.php" class="btn btn-dark btn-sm rounded-pill fw-semibold me-1">
                            <i class="bi bi-shield-lock me-1"></i> Admin Dashboard
                        </a>
                    <?php endif; ?>

                    <div class="dropdown ms-2">
                        <button class="btn btn-light btn-sm rounded-pill dropdown-toggle fw-semibold border px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle text-primary me-1"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li><span class="dropdown-item-text text-muted small">Role: <strong><?php echo ucfirst($user['role']); ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-semibold" href="<?php echo $root_path; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $root_path; ?>login.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                    <a href="<?php echo $root_path; ?>register.php" class="btn btn-primary-theme btn-sm rounded-pill px-3">
                        <i class="bi bi-person-plus me-1"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
