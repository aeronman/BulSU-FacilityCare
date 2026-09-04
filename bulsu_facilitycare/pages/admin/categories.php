<?php
/**
 * Admin - Categories Management
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$categories = $func->getAllCategories();

$pageTitle = 'Categories';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Categories</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Issue Categories</h1>
                <p class="page-subtitle text-muted">
                    Manage issue categories and their criticality weights for priority assessment.
                </p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="fas fa-plus me-2"></i>Add Category
            </button>
        </div>
    </div>

    <div class="row">
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0 text-bulsumaroon"><?php echo htmlspecialchars($cat['name']); ?></h5>
                            <span class="badge bg-gold text-bulsumaroon">Weight: <?php echo $cat['criticality_weight']; ?></span>
                        </div>
                        <p class="text-muted small"><?php echo htmlspecialchars($cat['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $cat['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-gold"
                                        onclick="editCategory(<?php echo $cat['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/categories/store">
                <div class="modal-content">
                    <div class="bulsu-modal-header">
                        <h5 class="modal-title">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo CSRF::tokenField(); ?>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Category Name *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Criticality Weight</label>
                            <input type="number" class="form-control" name="criticality_weight" step="0.01" min="0.5" max="3.0" value="1.00">
                            <small class="text-muted">Weight multiplier for priority score</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" checked>
                                <label class="form-label form-check-label text-bulsumaroon fw-semibold">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<script>
function editCategory(id) {
    alert('Edit category ' + id + ' - Feature coming soon!');
}
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
