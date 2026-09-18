<?php
require_once 'db.php';
require_once 'includes/auth_check.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if ($role === 'admin') header("Location: admin/dashboard.php");
    elseif ($role === 'owner') header("Location: owner/dashboard.php");
    else header("Location: student/dashboard.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
    $success = "Registration successful! Please login below.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($user = $res->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] === 'owner') {
                    header("Location: owner/dashboard.php");
                } else {
                    header("Location: student/dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No user found with that email address.";
        }
        $stmt->close();
    }
}

$page_title = "Sign In";
require_once 'includes/header.php';
?>

<div class="container auth-wrapper py-5">
    <div class="card auth-card border-0 shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary d-inline-flex p-3 rounded-circle mb-3">
                    <i class="bi bi-shield-lock-fill fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Welcome Back</h3>
                <p class="text-muted small">Sign in to manage bookings & hostel services</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-theme w-100 py-2.5 fs-6 shadow-sm mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Log In
                </button>
            </form>

            <div class="bg-light p-3 rounded-3 text-center mb-3">
                <span class="text-muted small d-block mb-1">Demo Credentials:</span>
                <span class="badge bg-secondary me-1">Student: student@gmail.com</span>
                <span class="badge bg-info text-dark me-1">Owner: owner@gmail.com</span>
                <span class="badge bg-dark">Admin: admin@gmail.com</span>
                <span class="d-block text-muted small mt-1">Default password: <code>student123</code> / <code>owner123</code> / <code>admin123</code></span>
            </div>

            <div class="text-center">
                <p class="text-muted small mb-0">Don't have an account yet? 
                    <a href="register.php" class="text-primary fw-bold text-decoration-none">Register Here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
