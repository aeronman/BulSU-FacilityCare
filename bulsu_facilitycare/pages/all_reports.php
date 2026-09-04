<?php
/**
 * All Reports - Accessible by Maintenance
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($categoryFilter) $filters['category_id'] = $categoryFilter;
if ($priorityFilter) $filters['priority'] = $priorityFilter;
if ($search) $filters['search'] = $search;

$reports = $func->getReports($filters, 25, 0);
$categories = $func->getAllCategories();

$pageTitle = 'All Reports';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">All Reports</h1>
                <p class="page-subtitle text-muted">Browse all facility maintenance reports.</p>
            </div>
            <a href="/dashboard" class="btn btn-outline-gold">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Status</label>
                    <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="submitted" <?php echo $statusFilter == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="under_review" <?php echo $statusFilter == 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="validated" <?php echo $statusFilter == 'validated' ? 'selected' : ''; ?>>Validated</option>
                        <option value="assigned" <?php echo $statusFilter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="ongoing" <?php echo $statusFilter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="resolved" <?php echo $statusFilter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $statusFilter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                        <option value="rejected" <?php echo $statusFilter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Category</label>
                    <select class="form-select form-select-sm" name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Priority</label>
                    <select class="form-select form-select-sm" name="priority" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <option value="high" <?php echo $priorityFilter == 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priorityFilter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priorityFilter == 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Search</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($reports)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h4 class="h5 text-bulsumaroon">No reports found</h4>
                <p class="text-muted">No reports match your current filters.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Report #</th>
                                <th>Issue</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $i => $report): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($report['report_number']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($report['title']); ?></div>
                                        <?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($report['category_name']); ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($report['location_name'] . ', ' . $report['building']); ?></td>
                                    <td><?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?></td>
                                    <td><?php echo getReportStatusBadge($report['status_code']); ?></td>
                                    <td class="text-muted small"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a href="/report/<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-gold">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
