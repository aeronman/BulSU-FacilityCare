<?php
/**
 * Admin - Facility/Location Management
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$facilities = $func->getAllFacilities();
$action = $_GET['action'] ?? null;
$editId = $_GET['edit'] ?? null;
$editFacility = $editId ? $func->getFacilityById($editId) : null;

$pageTitle = 'Facility Management';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Facilities</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Facility / Location Management</h1>
                <p class="page-subtitle text-muted">Manage university buildings, floors, and room locations.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#facilityModal" data-id="">
                <i class="fas fa-plus me-2"></i>Add New Facility
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-bulsu">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Building</th>
                            <th>Floor</th>
                            <th>Room</th>
                            <th>Location Name</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($facilities)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-building fa-2x mb-2"></i>
                                    <p>No facilities found. Add your first facility.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facilities as $i => $f): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($f['building']); ?></td>
                                    <td><?php echo htmlspecialchars($f['floor'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($f['room_number'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($f['location_name']); ?></td>
                                    <td><?php echo $f['criticality_weight']; ?></td>
                                    <td><?php echo $f['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-gold"
                                                onclick="editFacility(<?php echo $f['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="facilityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/facilities/store" id="facilityForm">
                <div class="modal-content">
                    <div class="bulsu-modal-header">
                        <h5 class="modal-title" id="facilityModalTitle">
                            <i class="fas fa-building me-2"></i>Add New Facility
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="id" id="facility_id" value="">
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Building *</label>
                            <input type="text" class="form-control" name="building" id="building" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Floor</label>
                            <input type="text" class="form-control" name="floor" id="floor" placeholder="e.g., Ground Floor, 1st Floor">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Room Number</label>
                            <input type="text" class="form-control" name="room_number" id="room_number" placeholder="e.g., R101">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Location Name *</label>
                            <input type="text" class="form-control" name="location_name" id="location_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Description</label>
                            <textarea class="form-control" name="description" id="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Criticality Weight</label>
                            <input type="number" class="form-control" name="criticality_weight" id="criticality_weight" step="0.01" min="0.5" max="3.0" value="1.00">
                            <small class="text-muted">Higher weight = higher priority score (0.5 to 3.0)</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                <label class="form-label form-check-label text-bulsumaroon fw-semibold" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Facility
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<script>
function editFacility(id) {
    fetch('/api/facility/' + id)
        .then(r => r.json())
        .then(data => {
            document.getElementById('facility_id').value = data.id;
            document.getElementById('building').value = data.building;
            document.getElementById('floor').value = data.floor || '';
            document.getElementById('room_number').value = data.room_number || '';
            document.getElementById('location_name').value = data.location_name;
            document.getElementById('description').value = data.description || '';
            document.getElementById('criticality_weight').value = data.criticality_weight;
            document.getElementById('is_active').checked = data.is_active == 1;
            document.getElementById('facilityModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Edit Facility';
            var modal = new bootstrap.Modal(document.getElementById('facilityModal'));
            modal.show();
        })
        .catch(err => console.error(err));
}
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
