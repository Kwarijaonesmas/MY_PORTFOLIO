<?php
require_once '../db.php';
require_once '../includes/auth_check.php';
require_role('owner');

$owner_id = $_SESSION['user_id'];
$msg = '';
$err = '';

// Fetch all hostels belonging to this owner
$stmt_h = $conn->prepare("SELECT id, name, location FROM hostels WHERE owner_id = ? ORDER BY name ASC");
$stmt_h->bind_param("i", $owner_id);
$stmt_h->execute();
$hostels_result = $stmt_h->get_result();

$my_hostels = [];
while ($row = $hostels_result->fetch_assoc()) {
    $my_hostels[] = $row;
}
$stmt_h->close();

$selected_hostel_id = isset($_GET['hostel_id']) ? (int)$_GET['hostel_id'] : ($my_hostels[0]['id'] ?? 0);

// Handle Delete Room Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['room_id'])) {
    $del_room_id = (int)$_GET['room_id'];
    $stmt_del = $conn->prepare("DELETE r FROM rooms r JOIN hostels h ON r.hostel_id = h.id WHERE r.id = ? AND h.owner_id = ?");
    $stmt_del->bind_param("ii", $del_room_id, $owner_id);
    if ($stmt_del->execute()) {
        $msg = "Room deleted successfully.";
    } else {
        $err = "Could not delete room.";
    }
    $stmt_del->close();
}

// Handle Add Room Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $target_hostel_id = (int)$_POST['hostel_id'];
    $room_no = trim($_POST['room_no'] ?? '');

    if ($target_hostel_id <= 0 || empty($room_no)) {
        $err = "Please select a hostel and enter a valid room number.";
    } else {
        // Verify owner owns this hostel
        $stmt_v = $conn->prepare("SELECT id FROM hostels WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt_v->bind_param("ii", $target_hostel_id, $owner_id);
        $stmt_v->execute();
        if ($stmt_v->get_result()->num_rows > 0) {
            $stmt_v->close();

            // Insert room
            $stmt_add = $conn->prepare("INSERT INTO rooms (hostel_id, room_no, status) VALUES (?, ?, 'available')");
            $stmt_add->bind_param("is", $target_hostel_id, $room_no);
            if ($stmt_add->execute()) {
                $msg = "Room '$room_no' added successfully!";
                $selected_hostel_id = $target_hostel_id;
            } else {
                $err = "Error adding room.";
            }
            $stmt_add->close();
        } else {
            $err = "Unauthorized hostel selection.";
            $stmt_v->close();
        }
    }
}

// Fetch rooms for currently selected hostel
$rooms = [];
if ($selected_hostel_id > 0) {
    $stmt_r = $conn->prepare("SELECT r.* FROM rooms r JOIN hostels h ON r.hostel_id = h.id WHERE r.hostel_id = ? AND h.owner_id = ? ORDER BY r.room_no ASC");
    $stmt_r->bind_param("ii", $selected_hostel_id, $owner_id);
    $stmt_r->execute();
    $rooms_res = $stmt_r->get_result();
    while ($r = $rooms_res->fetch_assoc()) {
        $rooms[] = $r;
    }
    $stmt_r->close();
}

$page_title = "Manage Rooms";
require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-door-open text-primary me-2"></i> Manage Hostel Rooms</h2>
            <p class="text-muted small mb-0">Add new rooms to your hostels, view current status, and delete rooms</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
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

    <?php if (empty($my_hostels)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-building-slash display-2 text-muted mb-3"></i>
            <h4 class="fw-bold">No Hostels Registered Yet</h4>
            <p class="text-muted">You must register a hostel first before managing rooms.</p>
            <a href="add_hostel.php" class="btn btn-primary-theme rounded-pill px-4 align-self-center">Add Hostel Now</a>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <!-- Select & Add Room Card -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-plus-circle-fill text-primary me-2"></i> Add New Room</h5>
                    
                    <form action="manage_rooms.php?hostel_id=<?php echo $selected_hostel_id; ?>" method="POST">
                        <input type="hidden" name="add_room" value="1">
                        
                        <div class="mb-3">
                            <label for="hostel_id" class="form-label fw-bold">Select Hostel</label>
                            <select name="hostel_id" id="hostel_id" class="form-select" onchange="window.location.href='manage_rooms.php?hostel_id=' + this.value;">
                                <?php foreach ($my_hostels as $h): ?>
                                    <option value="<?php echo $h['id']; ?>" <?php echo ($h['id'] == $selected_hostel_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($h['name']); ?> (<?php echo htmlspecialchars($h['location']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="room_no" class="form-label fw-bold">Room Number / Identification</label>
                            <input type="text" name="room_no" id="room_no" class="form-control" placeholder="e.g. Room 101, Block B-12" required>
                        </div>

                        <button type="submit" class="btn btn-primary-theme w-100 rounded-pill py-2.5 shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> Add Room to Inventory
                        </button>
                    </form>
                </div>
            </div>

            <!-- Rooms List Table -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-task me-2 text-primary"></i> Inventory Rooms Listing</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Room No</th>
                                        <th>Status</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($rooms)): ?>
                                        <?php foreach ($rooms as $rm): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-dark font-monospace"><?php echo htmlspecialchars($rm['room_no']); ?></td>
                                                <td>
                                                    <?php if ($rm['status'] === 'available'): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                                                            Available
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill">
                                                            Booked / Occupied
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-end">
                                                    <a href="manage_rooms.php?hostel_id=<?php echo $selected_hostel_id; ?>&action=delete&room_id=<?php echo $rm['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger rounded-pill px-3 confirm-action" 
                                                       data-confirm="Are you sure you want to delete room <?php echo htmlspecialchars($rm['room_no']); ?>?">
                                                        <i class="bi bi-trash me-1"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">
                                                No rooms added to this hostel yet. Use the form on the left to add rooms.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
