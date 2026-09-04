<?php
/**
 * Admin - All Reports
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($categoryFilter) $filters['category_id'] = $categoryFilter;
if ($priorityFilter) $filters['priority'] = $priorityFilter;
if ($search) $filters['search'] = $search;

$reports = $func->getReports($filters, $perPage, $offset);
$totalReports = $func->getReportsCount($filters);
$totalPages = ceil($totalReports / $perPage);

$categories = $func->getAllCategories();
$statuses = $func->getReportStatuses();

$pageTitle = 'All Reports';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active">All Reports</li>
                    </ol>
                </nav>
                <h1 class="page-title">All Reports</h1>
                <p class="page-subtitle text-muted">
                    <?php echo $totalReports; ?> reports found.
                </p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Status</label>
                    <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo $s['code']; ?>" <?php echo $statusFilter == $s['code'] ? 'selected' : ''; ?>>
                                <?php echo $s['label']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-bulsumaroon fw-semibold small">Category</label>
                    <select class="form-select form-select-sm" name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $categoryFilter == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
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
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $i => $report): ?>
                                <tr>
                                    <td><?php echo $offset + $i + 1; ?></td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($report['report_number']); ?></td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($report['title']); ?></div>
                                        <?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($report['category_name']); ?></span></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($report['location_name'] . ', ' . $report['building']); ?></td>
                                    <td><?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?></td>
                                    <td><?php echo htmlspecialchars($report['assignee_name'] ?? 'Not assigned'); ?></td>
                                    <td><?php echo getReportStatusBadge($report['status_code']); ?></td>
                                    <td class="text-muted small"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a href="/report/<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-gold">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/validate?report=<?php echo $report['id']; ?>"
                                           class="btn btn-sm btn-bulsu btn-success">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
                    <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-light border-0">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <?php $queryParams = array_filter(['status' => $statusFilter, 'category' => $categoryFilter, 'priority' => $priorityFilter, 'search' => $search]); ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++):
                                $params = array_merge($queryParams, ['page' => $i]);
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query($params); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
