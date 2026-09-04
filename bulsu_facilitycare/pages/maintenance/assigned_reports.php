<?php
/**
 * Maintenance - Assigned Reports
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isMaintenance()) {
    redirect('/login');
}

$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$filters = ['assigned_to' => $currentUser['id']];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($search) $filters['search'] = $search;

$reports = $func->getReports($filters, 25, 0);

$statusLabels = [
    STATUS_SUBMITTED => 'Submitted', STATUS_UNDER_REVIEW => 'Under Review',
    STATUS_VALIDATED => 'Validated', STATUS_ASSIGNED => 'Assigned',
    STATUS_ONGOING => 'Ongoing', STATUS_RESOLVED => 'Resolved',
    STATUS_CLOSED => 'Closed', STATUS_REJECTED => 'Rejected',
];

$pageTitle = 'Assigned Reports';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Assigned Reports</h1>
                <p class="page-subtitle text-muted">Reports assigned to you for resolution.</p>
            </div>
            <a href="/dashboard" class="btn btn-outline-gold">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-bulsumaroon fw-semibold small">Filter by Status</label>
                    <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusLabels as $code => $label): ?>
                            <option value="<?php echo $code; ?>" <?php echo $statusFilter == $code ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label text-bulsumaroon fw-semibold small">Search</label>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="search" name="search" class="form-control"
                               placeholder="Search by report number or title..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-outline-gold btn-sm">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($reports)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h4 class="h5 text-bulsumaroon">No reports found</h4>
                <p class="text-muted">No reports match your current search criteria.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Report #</th>
                                <th>Issue</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr class="report-card <?php echo 'priority-' . $report['priority_level']; ?>">
                                    <td class="fw-medium"><?php echo htmlspecialchars($report['report_number']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($report['title']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars(substr($report['description'], 0, 60)); ?>...</div>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($report['category_name']); ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($report['location_name'] . ', ' . $report['building']); ?></td>
                                    <td><?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?></td>
                                    <td><?php echo getReportStatusBadge($report['status_code']); ?></td>
                                    <td class="text-muted small"><?php echo timeAgo($report['updated_at']); ?></td>
                                    <td class="text-end">
                                        <a href="/report/<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-gold">
                                            <i class="fas fa-eye"></i>
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
