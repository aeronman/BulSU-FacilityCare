<?php
/**
 * Admin Dashboard
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/login');
}

$stats = $func->getDashboardStats();
$priorityStats = $func->getPriorityStats();
$statusStats = $func->getStatusStats();
$recurringIssues = $func->getRecurringIssues();
$unresolvedHigh = $func->getUnresolvedHighPriority(5);

$recentReports = $func->getReports(['status' => null], 8, 0);
$timeStats = $func->getTimeStats();

$pageTitle = 'Admin Dashboard';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle text-muted">
                    System Overview | BulSU FacilityCare
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="/admin/validate" class="btn btn-outline-gold">
                    <i class="fas fa-clip-check me-2"></i>Validate Reports
                </a>
                <a href="/admin/reports" class="btn btn-primary">
                    <i class="fas fa-folder-open me-2"></i>All Reports
                </a>
            </div>
        </div>
    </div>

    <div class="stats-grid mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(255, 193, 7, 0.1);">
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
                <div class="stat-value text-warning"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">Pending Review</div>
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
                <div class="card-icon mx-auto mb-3" style="background: rgba(25, 135, 84, 0.1);">
                    <i class="fas fa-check-circle fa-2x text-success"></i>
                </div>
                <div class="stat-value text-success"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3" style="background: rgba(139, 0, 21, 0.1);">
                    <i class="fas fa-shield-alt fa-2x text-bulsumaroon"></i>
                </div>
                <div class="stat-value text-bulsumaroon"><?php echo $stats['safety_risks'] ?? 0; ?></div>
                <div class="stat-label">Safety Issues</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                                <i class="fas fa-chart-pie me-2"></i>Status Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($statusStats as $s): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="d-flex align-items-center">
                                        <?php echo getReportStatusBadge($s['status_code']); ?>
                                        <span class="ms-2"><?php echo htmlspecialchars($s['label']); ?></span>
                                    </span>
                                    <strong><?php echo $s['count']; ?></strong>
                                </div>
                                <div class="progress mb-3" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: <?php echo ($s['count'] / max($stats['total'], 1)) * 100; ?>%"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white">
                            <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                                <i class="fas fa-gauge-high me-2"></i>Priority Distribution
                            </h3>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-danger fw-medium">High</span>
                                    <?php
                                    $pMap = [];
                                    foreach ($priorityStats as $p) $pMap[$p["priority_level"]] = $p["count"];
                                    ?>
                                    <strong class="text-danger">high: <?php echo $pMap["high"] ?? 0; ?></strong>
                                    <strong class="text-warning">medium: <?php echo $pMap["medium"] ?? 0; ?></strong>
                                    <strong class="text-success">low: <?php echo $pMap["low"] ?? 0; ?></strong>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-danger">High</span>
                                    <strong class="text-danger"><?php echo $pMap['high'] ?? 0; ?></strong>
                                </div>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo ($pMap['high'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="text-warning">Medium</span>
                                    <strong class="text-warning"><?php echo $pMap['medium'] ?? 0; ?></strong>
                                </div>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar bg-warning" style="width: <?php echo ($pMap['medium'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-success">Low</span>
                                    <strong class="text-success"><?php echo $pMap['low'] ?? 0; ?></strong>
                                </div>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo ($pMap['low'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($unresolvedHigh)): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>Unresolved High Priority Concerns
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Report #</th>
                                        <th>Issue</th>
                                        <th>Location</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($unresolvedHigh as $r): ?>
                                        <tr>
                                            <td class="fw-medium"><?php echo htmlspecialchars($r['report_number']); ?></td>
                                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($r['location_name'] ?? '' . ', ' . $r['building'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-danger fs-6"><?php echo $r['total_score'] ?? 0; ?></span>
                                            </td>
                                            <td><?php echo getReportStatusBadge($r['status_code']); ?></td>
                                            <td class="text-end">
                                                <a href="/report/<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-gold">View</a>
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

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-history me-2"></i>Recent Reports
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentReports as $r): ?>
                            <div class="list-group-item border-0 py-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div>
                                        <?php echo getPriorityBadge($r['priority_level']); ?>
                                        <?php echo getReportStatusBadge($r['status_code']); ?>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($r['created_at']); ?></small>
                                </div>
                                <h4 class="h6 mb-0 fw-semibold">
                                    <a href="/report/<?php echo $r['id']; ?>" class="text-decoration-none text-bulsumaroon">
                                        <?php echo htmlspecialchars($r['title']); ?>
                                    </a>
                                </h4>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($r['category_name']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($recurringIssues)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-redo me-2"></i>Recurring Issues
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($recurringIssues as $issue): ?>
                            <div class="mb-2 pb-2 border-bottom">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold small"><?php echo htmlspecialchars($issue['category_name']); ?></span>
                                    <span class="badge bg-warning text-dark"><?php echo $issue['report_count']; ?>x</span>
                                </div>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($issue['location_name'] . ', ' . $issue['building']); ?></p>
                                <small class="text-muted">Since: <?php echo date('M j, Y', strtotime($issue['first_reported'])); ?></small>
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
