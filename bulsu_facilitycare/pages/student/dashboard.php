<?php
/**
 * Student/Faculty/Staff Dashboard
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$stats = $func->getDashboardStats($currentUser['id'], $currentUser['role_name']);
$recentReports = $func->getRecentReports(5, $currentUser['id'], $currentUser['role_name']);
$unresolvedHighPriority = $func->getUnresolvedHighPriority(5);

$pageTitle = 'Dashboard';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="page-title">Hello, <?php echo htmlspecialchars($currentUser['full_name']); ?></h1>
                <p class="page-subtitle text-muted">
                    <?php echo $currentUser['role_display']; ?> | <?php echo $currentUser['department_name'] ?? 'N/A'; ?>
                                </p>
            </div>
            <a href="/submit-report" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Submit New Report
                            </a>
        </div>
    </div>

    <div class="stats-grid mb-4">
        <div class="card dashboard-card h-100">
            <div class="card-body text-center">
                <div class="card-icon mx-auto mb-3">
                    <i class="fas fa-clipboard-list fa-2x"></i>
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
                <div class="card-icon mx-auto mb-3" style="background: rgba(13, 110, 253, 0.1);">
                    <i class="fas fa-cog fa-2x text-primary"></i>
                </div>
                <div class="stat-value text-primary"><?php echo $stats['in_progress'] ?? 0; ?></div>
                <div class="stat-label">In Progress</div>
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
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <?php if (!empty($recentReports)): ?>
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-history me-2"></i>Recent Reports
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentReports as $report): ?>
                                <div class="list-group-item border-0 py-3">
                                    <div class="row g-0">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <?php echo getReportStatusBadge($report['status_code']); ?>
                                                <?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?>
                                            </div>
                                            <h4 class="h6 mb-1 fw-semibold">
                                                <a href="/report/<?php echo $report['id']; ?>" class="text-decoration-none text-bulsumaroon">
                                                    <?php echo htmlspecialchars($report['title']); ?>
                                                </a>
                                            </h4>
                                            <p class="text-muted small mb-0">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($report['location_name'] ?? '' . ', ' . $report['building'] ?? ''); ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <div class="text-muted small">
                                                <?php echo timeAgo($report['created_at']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($report['category_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="/my-reports" class="btn btn-outline-gold btn-sm">
                            View All My Reports
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h4 class="h5 text-bulsumaroon">No reports yet</h4>
                        <p class="text-muted">You haven't submitted any facility reports.</p>
                        <a href="/submit-report" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Submit First Report
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 mb-4">
            <?php if (!empty($unresolvedHighPriority)): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-exclamation-triangle me-2"></i>High Priority Alert
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($unresolvedHighPriority as $report): ?>
                                <div class="list-group-item border-0 py-2 bg-transparent">
                                    <div class="d-flex align-items-start gap-2">
                                        <?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?>
                                        <div class="flex-grow-1">
                                            <h4 class="h6 mb-1 fw-semibold">
                                                <a href="/report/<?php echo $report['id']; ?>" class="text-decoration-none text-bulsumaroon">
                                                    <?php echo htmlspecialchars($report['title']); ?>
                                                </a>
                                            </h4>
                                            <p class="text-muted small mb-0">
                                                <?php echo htmlspecialchars($report['location_name'] ?? '' . ', ' . $report['building'] ?? ''); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-chart-pie me-2"></i>Priority Distribution
                    </h3>
                </div>
                <div class="card-body text-center">
                    <?php
                    $priorityStats = $func->getPriorityStats();
                    $priorityMap = [];
                    foreach ($priorityStats as $p) {
                        $priorityMap[$p['priority_level']] = $p['count'];
                    }
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-danger fw-medium">High</span>
                            <strong class="text-danger"><?php echo $priorityMap['high'] ?? 0; ?></strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-danger"
                                 style="width: <?php echo ($stats['high_priority'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-warning fw-medium">Medium</span>
                            <strong class="text-warning"><?php echo $priorityMap['medium'] ?? 0; ?></strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-warning"
                                 style="width: <?php echo ($priorityMap['medium'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-success fw-medium">Low</span>
                            <strong class="text-success"><?php echo $priorityMap['low'] ?? 0; ?></strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success"
                                 style="width: <?php echo ($priorityMap['low'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
