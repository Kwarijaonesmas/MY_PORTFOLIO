<?php
require_once 'db.php';
require_once 'includes/auth_check.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'student');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!in_array($role, ['student', 'owner'])) {
        $error = "Invalid role selected.";
    } else {
        // Check email uniqueness
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email address already exists.";
        } else {
            $stmt->close();
            // Hash password and insert
            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
            $insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $name, $email, $hashed_pass, $role);

            if ($insert_stmt->execute()) {
                header("Location: login.php?msg=registered");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert_stmt->close();
        }
    }
}

$page_title = "Create an Account";
require_once 'includes/header.php';
?>

<div class="container auth-wrapper py-5">
    <div class="card auth-card border-0 shadow-lg">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="bg-primary bg-opacity-10 text-primary d-inline-flex p-3 rounded-circle mb-3">
                    <i class="bi bi-person-plus-fill fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Create an Account</h3>
                <p class="text-muted small">Join HostelFinder Bushenyi today</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" id="name" class="form-control" placeholder="e.g., Mukasa David" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label fw-semibold">Register As</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-briefcase"></i></span>
                        <select name="role" id="role" class="form-select" required>
                            <option value="student" <?php echo (($_POST['role'] ?? '') === 'student') ? 'selected' : ''; ?>>Student (Searching for Hostel)</option>
                            <option value="owner" <?php echo (($_POST['role'] ?? '') === 'owner') ? 'selected' : ''; ?>>Hostel Owner (Manager/Landlord)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-theme w-100 py-2.5 fs-6 shadow-sm mb-3">
                    <i class="bi bi-check-circle me-2"></i> Register Account
                </button>
            </form>

            <div class="text-center">
                <p class="text-muted small mb-0">Already have an account? 
                    <a href="login.php" class="text-primary fw-bold text-decoration-none">Log In Here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
