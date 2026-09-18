<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('owner');

$owner_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $type = trim($_POST['type'] ?? 'Single');
    $facilities_selected = isset($_POST['facilities']) ? $_POST['facilities'] : [];
    $facilities_str = implode(', ', array_map('trim', $facilities_selected));

    if (empty($name) || empty($location) || $price <= 0 || empty($type)) {
        $error = "Please fill in all required hostel details.";
    } else {
        $photo_filename = 'default_hostel.jpg';

        // Process Photo Upload if provided
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['photo']['tmp_name'];
            $file_name = $_FILES['photo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = "hostel_" . time() . "_" . rand(100, 999) . "." . $file_ext;
                $upload_target = __DIR__ . '/../uploads/' . $new_filename;

                if (!file_exists(__DIR__ . '/../uploads')) {
                    mkdir(__DIR__ . '/../uploads', 0777, true);
                }

                if (move_uploaded_file($file_tmp, $upload_target)) {
                    $photo_filename = $new_filename;
                }
            } else {
                $error = "Invalid image format. Allowed formats: JPG, PNG, WEBP.";
            }
        }

        if (empty($error)) {
            // Insert into DB with pending status
            $stmt = $conn->prepare("INSERT INTO hostels (owner_id, name, location, price, type, facilities, photo, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ississs", $owner_id, $name, $location, $price, $type, $facilities_str, $photo_filename);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?msg=hostel_created");
                exit();
            } else {
                $error = "Failed to add hostel. Database error.";
            }
        }
    }
}

$page_title = "Register New Hostel";
require_once '../includes/header.php';
?>

<div class="container py-4" style="max-width: 800px;">
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-header bg-primary text-white p-4 rounded-top-4">
            <h3 class="fw-bold mb-1"><i class="bi bi-building-add me-2"></i> Register New Hostel</h3>
            <p class="mb-0 opacity-75">Submit your hostel details for administrator approval.</p>
        </div>
        <div class="card-body p-4 p-md-5">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="add_hostel.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Hostel Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g., Crane Paradise Hostel" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="location" class="form-label fw-bold">Location / Area</label>
                        <select name="location" id="location" class="form-select" required>
                            <option value="">-- Select Bushenyi Location --</option>
                            <option value="Ishaka Town" <?php echo (($_POST['location'] ?? '') === 'Ishaka Town') ? 'selected' : ''; ?>>Ishaka Town</option>
                            <option value="KIU Road" <?php echo (($_POST['location'] ?? '') === 'KIU Road') ? 'selected' : ''; ?>>KIU Road (Near Campus Gate)</option>
                            <option value="Katungu" <?php echo (($_POST['location'] ?? '') === 'Katungu') ? 'selected' : ''; ?>>Katungu Area</option>
                            <option value="Kashenyi" <?php echo (($_POST['location'] ?? '') === 'Kashenyi') ? 'selected' : ''; ?>>Kashenyi Area</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="type" class="form-label fw-bold">Room Category / Type</label>
                        <select name="type" id="type" class="form-select" required>
                            <option value="Single" <?php echo (($_POST['type'] ?? '') === 'Single') ? 'selected' : ''; ?>>Single Room</option>
                            <option value="Double" <?php echo (($_POST['type'] ?? '') === 'Double') ? 'selected' : ''; ?>>Double Room</option>
                            <option value="Self-contained" <?php echo (($_POST['type'] ?? '') === 'Self-contained') ? 'selected' : ''; ?>>Self-contained Suite</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label fw-bold">Price / Semester (UGX)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">UGX</span>
                            <input type="number" name="price" id="price" step="10000" min="100000" class="form-control" placeholder="e.g. 750000" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="photo" class="form-label fw-bold">Hostel Cover Photo</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                        <span class="form-text text-muted">Upload clear image (JPG, PNG, WEBP)</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold d-block">Facilities & Services Provided</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="WiFi" id="fac_wifi" checked>
                                <label class="form-check-label fw-semibold ms-2" for="fac_wifi"><i class="bi bi-wifi me-1"></i> High Speed WiFi</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="Running Water" id="fac_water" checked>
                                <label class="form-check-label fw-semibold ms-2" for="fac_water"><i class="bi bi-droplet-fill me-1"></i> Running Water</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="Electricity" id="fac_elec" checked>
                                <label class="form-check-label fw-semibold ms-2" for="fac_elec"><i class="bi bi-lightning-charge-fill me-1"></i> Electricity</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="24/7 Security" id="fac_sec" checked>
                                <label class="form-check-label fw-semibold ms-2" for="fac_sec"><i class="bi bi-shield-check me-1"></i> 24/7 Guard Security</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="Standby Generator" id="fac_gen">
                                <label class="form-check-label fw-semibold ms-2" for="fac_gen"><i class="bi bi-cpu me-1"></i> Standby Generator</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check bg-light p-2 rounded border">
                                <input class="form-check-input ms-1" type="checkbox" name="facilities[]" value="Study Room" id="fac_study">
                                <label class="form-check-label fw-semibold ms-2" for="fac_study"><i class="bi bi-book me-1"></i> Quiet Study Room</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="dashboard.php" class="btn btn-light rounded-pill px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary-theme rounded-pill px-5 shadow">
                        <i class="bi bi-check-circle me-1"></i> Register Hostel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
