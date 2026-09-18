<?php
// Calculate root path relative prefix dynamically if not already set
if (!isset($root_path)) {
    $script_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $root_dir = dirname(__DIR__);
    if (realpath($script_dir) === realpath($root_dir)) {
        $root_path = "./";
    } else {
        $root_path = "../";
    }
}
?>
<footer class="py-4 mt-5">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-center text-md-start">
                <h6 class="fw-bold text-white mb-1"><i class="bi bi-building-check text-primary"></i> Bushenyi Hostel Booking & Management System</h6>
                <p class="small text-muted mb-0">&copy; <?php echo date('Y'); ?> Serving KIU Western Campus & Bushenyi District Students.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="<?php echo $root_path; ?>index.php" class="text-decoration-none text-muted me-3 small"><i class="bi bi-house me-1"></i> Home</a>
                <a href="<?php echo $root_path; ?>seed.php" class="text-decoration-none text-muted me-3 small"><i class="bi bi-database-fill-gear me-1"></i> Reset/Seed DB</a>
                <a href="<?php echo $root_path; ?>login.php" class="text-decoration-none text-muted small"><i class="bi bi-shield-lock me-1"></i> Portal Access</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo $root_path; ?>js/script.js"></script>
</body>
</html>
