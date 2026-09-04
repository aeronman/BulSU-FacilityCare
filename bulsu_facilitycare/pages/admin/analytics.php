<?php
/**
 * Admin - Analytics / Reports & Analytics
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$timeStats = $func->getTimeStats();
$categoryStats = $func->getCategoryStats();
$statusStats = $func->getStatusStats();
$resolutionTimes = $func->getReportResolutionTime();
$recurringIssues = $func->getRecurringIssues();

$statusLabels = json_encode(array_column($statusStats, 'label'));
$statusCounts = json_encode(array_column($statusStats, 'count'));
$catNames = json_encode(array_column($categoryStats, 'name'));
$catCounts = json_encode(array_column($categoryStats, 'count'));

$pageTitle = 'Analytics';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Reports & Analytics</li>
            </ol>
        </nav>
        <h1 class="page-title">Reports &amp; Analytics</h1>
        <p class="page-subtitle text-muted">System-wide analytics and performance insights.</p>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card h-100 text-center">
                <div class="card-body">
                    <div class="card-icon mx-auto mb-3">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <div class="stat-value"><?php echo $timeStats['avg_resolution_days'] ? round($timeStats['avg_resolution_days'], 1) : 0; ?></div>
                    <div class="stat-label">Avg Resolution (days)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card h-100 text-center">
                <div class="card-body">
                    <div class="card-icon mx-auto mb-3" style="background: rgba(13, 202, 240, 0.1);">
                        <i class="fas fa-file-alt fa-2x text-info"></i>
                    </div>
                    <div class="stat-value"><?php echo $func->getReportsCount([]); ?></div>
                    <div class="stat-label">Total Reports</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card h-100 text-center">
                <div class="card-body">
                    <div class="card-icon mx-auto mb-3" style="background: rgba(220, 53, 69, 0.1);">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                    <div class="stat-value text-danger">
                        <?php echo $func->getReportsCount(['priority' => 'high']); ?>
                    </div>
                    <div class="stat-label">High Priority</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card h-100 text-center">
                <div class="card-body">
                    <div class="card-icon mx-auto mb-3" style="background: rgba(25, 135, 84, 0.1);">
                        <i class="fas fa-percentage fa-2x text-success"></i>
                    </div>
                    <div class="stat-value text-success">
                        <?php
                        $resolved = $func->getReportsCount(['status' => 'resolved']) + $func->getReportsCount(['status' => 'closed']);
                        $total = max($func->getReportsCount([]), 1);
                        echo round(($resolved / $total) * 100);
                        ?>%
                    </div>
                    <div class="stat-label">Resolution Rate</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold"><i class="fas fa-chart-bar me-2"></i>Reports by Status</h3>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold"><i class="fas fa-chart-pie me-2"></i>Reports by Category</h3>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h3 class="h5 mb-0 text-bulsumaroon fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Priority Distribution</h3>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4"><div class="p-3">
                    <div class="display-6 fw-bold text-danger"><?php echo $func->getReportsCount(['priority' => 'high']); ?></div>
                    <div class="stat-label">High Priority</div>
                </div></div>
                <div class="col-4"><div class="p-3">
                    <div class="display-6 fw-bold text-warning"><?php echo $func->getReportsCount(['priority' => 'medium']); ?></div>
                    <div class="stat-label">Medium Priority</div>
                </div></div>
                <div class="col-4"><div class="p-3">
                    <div class="display-6 fw-bold text-success"><?php echo $func->getReportsCount(['priority' => 'low']); ?></div>
                    <div class="stat-label">Low Priority</div>
                </div></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold"><i class="fas fa-redo me-2"></i>Recurring Issues</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($recurringIssues)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recurringIssues as $issue): ?>
                                <div class="list-group-item border-0 py-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-medium"><?php echo htmlspecialchars($issue['category_name']); ?></span>
                                        <span class="badge bg-warning text-dark"><?php echo $issue['report_count']; ?>x</span>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        <?php echo htmlspecialchars($issue['location_name'] . ', ' . $issue['building']); ?>
                                        | Since: <?php echo date('M j, Y', strtotime($issue['first_reported'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No recurring issues found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold"><i class="fas fa-stopwatch me-2"></i>Resolution Time by Category</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($resolutionTimes)): ?>
                        <?php foreach ($resolutionTimes as $rt): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-medium"><?php echo htmlspecialchars($rt['category_name']); ?></span>
                                    <span class="text-muted small"><?php echo round($rt['avg_days'], 1); ?> days (<?php echo $rt['count']; ?>)</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-gold"
                                         style="width: <?php echo min(100, ($rt['avg_days'] / 14) * 100); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No resolution data available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo $statusLabels; ?>,
            datasets: [{
                data: <?php echo $statusCounts; ?>,
                backgroundColor: ['#0dcaf0', '#ffc107', '#0d6efd', '#198754', '#6c757d', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    var catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: <?php echo $catNames; ?>,
            datasets: [{
                label: 'Reports',
                data: <?php echo $catCounts; ?>,
                backgroundColor: 'rgba(139, 0, 21, 0.7)',
                borderColor: 'rgba(229, 169, 40, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
});
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
