<?php
/**
 * Admin - Maintenance Monitoring
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->canUpdateReport()) {
    redirect('/admin/dashboard');
}

$statusFilter = $_GET['status'] ?? 'ongoing';
$filters = $statusFilter ? ['status' => $statusFilter] : [];
$reports = $func->getReports($filters, 20, 0);

$timeStats = $func->getTimeStats();
$resolutionTimes = $func->getReportResolutionTime();
$maintenancePerformance = $func->getMaintenancePerformance(null, 10);
$unresolvedHigh = $func->getUnresolvedHighPriority(10);

$pageTitle = 'Maintenance Monitoring';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Maintenance Monitoring</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Maintenance Monitoring</h1>
                <p class="page-subtitle text-muted">Monitor ongoing maintenance activities and performance metrics.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="?status=all" class="btn btn-outline-gold btn-sm <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                    All
                </a>
                <a href="?status=ongoing" class="btn btn-outline-gold btn-sm <?php echo $statusFilter === 'ongoing' ? 'active' : ''; ?>">
                    Ongoing
                </a>
                <a href="?status=assigned" class="btn btn-outline-gold btn-sm <?php echo $statusFilter === 'assigned' ? 'active' : ''; ?>">
                    Assigned
                </a>
                <a href="?status=resolved" class="btn btn-outline-gold btn-sm <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">
                    Resolved
                </a>
            </div>
        </div>
    </div>

    <div class="stats-grid mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(255, 193, 7, 0.1);">
                    <i class="fas fa-cog fa-2x text-warning"></i>
                </div>
                <div class="stat-value text-warning"><?php echo count($func->getAssignedReports(null, STATUS_ONGOING)); ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(13, 110, 253, 0.1);">
                    <i class="fas fa-user-check fa-2x text-primary"></i>
                </div>
                <div class="stat-value text-primary"><?php echo count($func->getAssignedReports(null, STATUS_ASSIGNED)); ?></div>
                <div class="stat-label">Assigned</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(25, 135, 84, 0.1);">
                    <i class="fas fa-check-double fa-2x text-success"></i>
                </div>
                <div class="stat-value text-success"><?php echo $func->getDashboardStats()['resolved'] ?? 0; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(220, 53, 69, 0.1);">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                </div>
                <div class="stat-value text-danger"><?php echo count($unresolvedHigh); ?></div>
                <div class="stat-label">High Priority</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-chart-line me-2"></i>Resolution Time by Category
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($resolutionTimes)): ?>
                        <?php foreach ($resolutionTimes as $rt): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-medium"><?php echo htmlspecialchars($rt['category_name']); ?></span>
                                    <span class="text-muted"><?php echo round($rt['avg_days'], 1); ?> days avg (<?php echo $rt['count']; ?> reports)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-gold" role="progressbar"
                                         style="width: <?php echo min(100, ($rt['avg_days'] / 14) * 100); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
<p class="text-muted text-center">No resolved reports available for analysis.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-trophy me-2"></i>Maintenance Performance
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($maintenancePerformance)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Personnel</th>
                                        <th class="text-end">Reports</th>
                                        <th class="text-end">Updates</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenancePerformance as $perf): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($perf['full_name']); ?></td>
                                            <td class="text-end"><?php echo $perf['reports_handled']; ?></td>
                                            <td class="text-end"><?php echo $perf['updates_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No maintenance performance data.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                <i class="fas fa-list me-2"></i>
                <?php echo ucfirst($statusFilter ?: 'All'); ?> Reports
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($reports)): ?>
                <div class="card-body text-center py-4">
                    <i class="fas fa-clipboard-list fa-2x text-muted"></i>
                    <p class="text-muted mt-2">No reports found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Report #</th>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($r['report_number']); ?></td>
                                    <td><?php echo htmlspecialchars($r['title']); ?></td>
                                    <td><?php echo htmlspecialchars($r['assignee_name'] ?? 'Not assigned'); ?></td>
                                    <td><?php echo getPriorityBadge($r['priority_level']); ?></td>
                                    <td><?php echo getReportStatusBadge($r['status_code']); ?></td>
                                    <td class="text-muted small"><?php echo timeAgo($r['updated_at']); ?></td>
                                    <td class="text-end">
                                        <a href="/report/<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-gold">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
