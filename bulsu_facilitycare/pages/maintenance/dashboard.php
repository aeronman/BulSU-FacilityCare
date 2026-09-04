<?php
/**
 * Maintenance Personnel Dashboard
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isMaintenance()) {
    redirect('/login');
}

$stats = $func->getDashboardStats($currentUser['id'], $currentUser['role_name']);
$assignedReports = $func->getAssignedReports($currentUser['id'], null);
$priorityStats = $func->getPriorityStats();
$maintenancePerformance = $func->getMaintenancePerformance($currentUser['id'], 5);

$assignedCount = count($assignedReports);
$ongoingCount = $func->getReportsCount(['assigned_to' => $currentUser['id'], 'status' => STATUS_ONGOING]);

$pageTitle = 'Maintenance Dashboard';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Maintenance Dashboard</h1>
                <p class="page-subtitle text-muted">
                    Welcome, <?php echo htmlspecialchars($currentUser['full_name']); ?> |
                    Assigned: <?php echo $assignedCount; ?> reports
                </p>
            </div>
            <div>
                <span class="badge bg-gold text-bulsumaroon px-3 py-2 fs-6">
                    <?php echo $currentUser['role_display']; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="stats-grid mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(13, 110, 253, 0.1);">
                    <i class="fas fa-user-helmet-safety fa-2x text-primary"></i>
                </div>
                <div class="stat-value text-primary"><?php echo $assignedCount; ?></div>
                <div class="stat-label">Assigned Reports</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(255, 193, 7, 0.1);">
                    <i class="fas fa-cog fa-2x text-warning"></i>
                </div>
                <div class="stat-value text-warning"><?php echo $ongoingCount; ?></div>
                <div class="stat-label">Ongoing</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(25, 135, 84, 0.1);">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
                <div class="stat-value text-success"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(220, 53, 69, 0.1);">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                </div>
                <div class="stat-value text-danger"><?php echo $stats['high_priority'] ?? 0; ?></div>
                <div class="stat-label">High Priority</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(139, 0, 21, 0.1);">
                    <i class="fas fa-shield-alt fa-2x text-bulsumaroon"></i>
                </div>
                <div class="stat-value text-bulsumaroon"><?php echo $stats['safety_risks'] ?? 0; ?></div>
                <div class="stat-label">Safety Risk</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-user-helmet-safety me-2"></i> My Assigned Reports
                    </h3>
                </div>
                <div class="card-body p-0">
                    <?php if ($assignedCount > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Report #</th>
                                        <th>Issue</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Submitted</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignedReports as $i => $report): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td class="fw-medium"><?php echo htmlspecialchars($report['report_number']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($report['title']); ?>
                                                <div>
                                                    <?php echo getPriorityBadge($report['priority_level']); ?>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($report['category_name']); ?></span></td>
                                            <td><?php echo getReportStatusBadge($report['status_code']); ?></td>
                                            <td><?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?></td>
                                            <td class="text-muted small"><?php echo timeAgo($report['created_at']); ?></td>
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
                    <?php else: ?>
                        <div class="card-body text-center py-4">
                            <i class="fas fa-check-circle fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No reports currently assigned to you.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-chart-pie me-2"></i>Priority Distribution
                    </h3>
                </div>
                <div class="card-body text-center">
                    <?php
                    $priorityMap = [];
                    foreach ($priorityStats as $p) $priorityMap[$p['priority_level']] = $p['count'];
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-danger">High</span>
                            <strong><?php echo $priorityMap['high'] ?? 0; ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-danger" style="width: 100%"></div></div>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="text-warning">Medium</span>
                            <strong><?php echo $priorityMap['medium'] ?? 0; ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 100%"></div></div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span class="text-success">Low</span>
                            <strong><?php echo $priorityMap['low'] ?? 0; ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: 100%"></div></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($maintenancePerformance)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-trophy me-2"></i> My Performance
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <?php foreach ($maintenancePerformance as $perf): ?>
                            <div class="mb-2">
                                <div class="fw-bold"><?php echo $perf['reports_handled']; ?></div>
                                <small class="text-muted">Reports Handled</small>
                            </div>
                            <div class="mb-2">
                                <div class="fw-bold"><?php echo $perf['updates_count']; ?></div>
                                <small class="text-muted">Updates Logged</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
