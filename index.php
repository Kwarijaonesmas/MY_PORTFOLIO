<?php
require_once 'db.php';
$page_title = "Explore Hostels around Bushenyi & KIU";
require_once 'includes/header.php';

// Handle Search and Filter Query
$search_location = isset($_GET['location']) ? trim($_GET['location']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int)$_GET['max_price'] : 2000000;
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';

// Base query for approved hostels
$sql = "SELECT h.*, u.name as owner_name, 
        (SELECT COUNT(*) FROM rooms r WHERE r.hostel_id = h.id AND r.status = 'available') as available_rooms_count,
        (SELECT AVG(rating) FROM reviews rev WHERE rev.hostel_id = h.id) as avg_rating,
        (SELECT COUNT(*) FROM reviews rev WHERE rev.hostel_id = h.id) as review_count
        FROM hostels h
        JOIN users u ON h.owner_id = u.id
        WHERE h.status = 'approved'";

$params = [];
$types = "";

if (!empty($search_location)) {
    $sql .= " AND h.location LIKE ?";
    $params[] = "%" . $search_location . "%";
    $types .= "s";
}

if ($max_price > 0) {
    $sql .= " AND h.price <= ?";
    $params[] = $max_price;
    $types .= "i";
}

if (!empty($type_filter)) {
    $sql .= " AND h.type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

$sql .= " ORDER BY h.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Hero Section -->
<div class="hero-header text-center">
    <div class="container py-4">
        <span class="badge bg-primary bg-opacity-25 text-primary-light px-3 py-2 rounded-pill fw-semibold mb-3">
            <i class="bi bi-geo-alt-fill text-warning me-1"></i> KIU Western Campus & Bushenyi District
        </span>
        <h1 class="display-4 fw-extrabold mb-3">Find Your Ideal Student Hostel</h1>
        <p class="lead text-light opacity-75 max-w-75 mx-auto">
            Browse verified, safe, and comfortable student accommodation in Ishaka, Katungu, Kashenyi, and along KIU Road with instant MTN Mobile Money booking.
        </p>
    </div>
</div>

<!-- Search & Filter Card -->
<div class="container mb-5">
    <div class="search-box-card p-4">
        <form action="index.php" method="GET" class="row g-3 align-items-end">
            <!-- Location Filter -->
            <div class="col-lg-4 col-md-6">
                <label for="location" class="form-label fw-bold text-dark"><i class="bi bi-pin-map text-danger me-1"></i> Location / Area</label>
                <select name="location" id="location" class="form-select">
                    <option value="">-- All Locations in Bushenyi --</option>
                    <option value="Ishaka Town" <?php echo ($search_location === 'Ishaka Town') ? 'selected' : ''; ?>>Ishaka Town</option>
                    <option value="KIU Road" <?php echo ($search_location === 'KIU Road') ? 'selected' : ''; ?>>KIU Road (Near Campus Gate)</option>
                    <option value="Katungu" <?php echo ($search_location === 'Katungu') ? 'selected' : ''; ?>>Katungu Area</option>
                    <option value="Kashenyi" <?php echo ($search_location === 'Kashenyi') ? 'selected' : ''; ?>>Kashenyi Area</option>
                </select>
            </div>

            <!-- Room Type Filter -->
            <div class="col-lg-3 col-md-6">
                <label for="type" class="form-label fw-bold text-dark"><i class="bi bi-door-open text-primary me-1"></i> Room Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">-- Any Room Type --</option>
                    <option value="Single" <?php echo ($type_filter === 'Single') ? 'selected' : ''; ?>>Single Room</option>
                    <option value="Double" <?php echo ($type_filter === 'Double') ? 'selected' : ''; ?>>Double Room</option>
                    <option value="Self-contained" <?php echo ($type_filter === 'Self-contained') ? 'selected' : ''; ?>>Self-contained Suite</option>
                </select>
            </div>

            <!-- Price Slider Filter -->
            <div class="col-lg-3 col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="price_range" class="form-label fw-bold text-dark mb-0"><i class="bi bi-cash-stack text-success me-1"></i> Max Price / Semester</label>
                    <span id="price_value_display" class="badge bg-primary">UGX <?php echo number_format($max_price); ?></span>
                </div>
                <input type="range" class="form-range" min="300000" max="1500000" step="50000" id="price_range" name="max_price" value="<?php echo $max_price; ?>">
            </div>

            <!-- Search Button -->
            <div class="col-lg-2 col-md-6">
                <button type="submit" class="btn btn-primary-theme w-100 py-2 shadow-sm">
                    <i class="bi bi-search me-1"></i> Filter Hostels
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Main Hostels Display Grid -->
<div class="container py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Available Hostels</h3>
            <p class="text-muted small mb-0">Showing approved hostels in Bushenyi region matching your criteria</p>
        </div>
        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-6 fw-semibold">
            <i class="bi bi-house-check text-success me-1"></i> <?php echo $result->num_rows; ?> Hostels Found
        </span>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $facilities_arr = array_map('trim', explode(',', $row['facilities']));
                    $photo_path = (!empty($row['photo']) && file_exists(__DIR__ . '/uploads/' . $row['photo'])) 
                        ? 'uploads/' . htmlspecialchars($row['photo']) 
                        : 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=800&q=80';
                    $avg_rating = round($row['avg_rating'] ?? 5, 1);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="hostel-card card h-100">
                        <div class="hostel-img-wrapper">
                            <img src="<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" loading="lazy">
                            <span class="badge-type"><i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($row['type']); ?></span>
                            <span class="badge-location"><i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($row['location']); ?></span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <div class="text-warning small fw-bold d-flex align-items-center gap-1">
                                    <i class="bi bi-star-fill"></i> <?php echo $avg_rating; ?>
                                </div>
                            </div>

                            <p class="text-muted small mb-3">
                                <i class="bi bi-person-badge text-primary me-1"></i> Managed by: <strong><?php echo htmlspecialchars($row['owner_name']); ?></strong>
                            </p>

                            <!-- Facilities -->
                            <div class="mb-3">
                                <?php foreach (array_slice($facilities_arr, 0, 4) as $facility): ?>
                                    <span class="facility-pill"><i class="bi bi-check2-circle me-1"></i><?php echo htmlspecialchars($facility); ?></span>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small d-block">Semester Fee</span>
                                    <span class="fw-extrabold fs-5 text-primary">UGX <?php echo number_format($row['price']); ?></span>
                                </div>

                                <div class="text-end">
                                    <span class="badge <?php echo ($row['available_rooms_count'] > 0) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill mb-1 d-inline-block px-2 py-1">
                                        <i class="bi bi-door-closed me-1"></i> <?php echo $row['available_rooms_count']; ?> Rooms Available
                                    </span>
                                    <br>
                                    <a href="student/hostel_view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary-theme rounded-pill px-3">
                                        View & Book <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm my-4">
            <i class="bi bi-search text-muted display-1"></i>
            <h4 class="fw-bold mt-3 text-dark">No Hostels Match Your Criteria</h4>
            <p class="text-muted">Try adjusting your location filter or price budget range.</p>
            <a href="index.php" class="btn btn-outline-primary rounded-pill px-4 mt-2">Reset Filters</a>
        </div>
    <?php endif; ?>
    <?php $stmt->close(); ?>
</div>

<?php require_once 'includes/footer.php'; ?>
