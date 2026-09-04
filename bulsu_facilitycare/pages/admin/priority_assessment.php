<?php
/**
 * Admin - Priority Assessment
 * Multi-Criteria Risk Prioritization Framework
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->canAssessPriority()) {
    redirect('/admin/dashboard');
}

$reportId = $_GET['report'] ?? null;
$report = $reportId ? $func->getReportById($reportId) : null;

$reportsNeedingPriority = $func->getReports(
    ['status' => STATUS_VALIDATED], 8, 0
);

$priority = new Priority();
$criteria = $priority->getCriteriaDescriptions();

$pageTitle = 'Priority Assessment';
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
                        <li class="breadcrumb-item active">Priority Assessment</li>
                    </ol>
                </nav>
                <h1 class="page-title">Priority Assessment</h1>
                <p class="page-subtitle text-muted">
                    Multi-Criteria Risk Prioritization Framework
                </p>
            </div>
        </div>
    </div>

    <?php if ($report): ?>
        <?php
        $priorityResult = $priority->calculatePriority($report['id']);
        $existingScore = $func->getPriorityScore($report['id']);
        ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        Assess Priority: #<?php echo htmlspecialchars($report['report_number']); ?>
                    </h3>
                    <?php echo getPriorityBadge($priorityResult['priority_level'], $priorityResult['total_score']); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <h5 class="fw-bold text-bulsumaroon"><?php echo htmlspecialchars($report['title']); ?></h5>
                                <p class="text-muted small mb-1">
                                    <?php echo getReportStatusBadge($report['status_code']); ?>
                                </p>
                                <p class="text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars($report['building'] . ' - ' . $report['location_name']); ?>
                                </p>
                                <p class="text-muted small">
                                    <i class="fas fa-folder me-1"></i>
                                    <?php echo htmlspecialchars($report['category_name']); ?>
                                </p>
                                <p class="text-muted small">
                                    <i class="fas fa-user me-1"></i>
                                    Reported by: <?php echo htmlspecialchars($report['reporter_name']); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="text-center mb-4">
                            <div class="d-inline-block">
                                <div class="priority-indicator mb-2" style="width: 300px;">
                                    <div class="priority-indicator-fill <?php echo $priorityResult['priority_level']; ?>"
                                         style="width: <?php echo min(100, ($priorityResult['total_score'] / 20) * 100); ?>%"></div>
                                </div>
                                <h1 class="priority-score <?php echo 'priority-' . $priorityResult['priority_level']; ?>">
                                    <?php echo $priorityResult['total_score']; ?>
                                </h1>
                                <?php echo getPriorityBadge($priorityResult['priority_level'], $priorityResult['total_score']); ?>
                            </div>
                        </div>

                        <div class="card bg-light">
                            <div class="card-header bg-white">
                                <h4 class="h6 mb-0 text-bulsumaroon fw-bold">Criteria Breakdown</h4>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Criterion</th>
                                            <th>Description</th>
                                            <th>Value</th>
                                            <th class="text-end">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Safety Risk</strong></td>
                                            <td><?php echo $criteria['safety_risk']['description']; ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($priorityResult['criteria']['safety_risk'] ?? 'N/A')); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['safety_risk']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Severity of Damage</strong></td>
                                            <td><?php echo $criteria['severity']['description']; ?></td>
                                            <td><?php echo htmlspecialchars($report['severity']); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['severity']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Urgency</strong></td>
                                            <td><?php echo $criteria['urgency']['description']; ?></td>
                                            <td><?php echo htmlspecialchars($report['urgency']); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['urgency']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Location Criticality</strong></td>
                                            <td><?php echo $criteria['location']['description']; ?> (Weight: <?php echo $report['facility_weight']; ?>)</td>
                                            <td><?php echo htmlspecialchars($report['location_name']); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['location']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Impact on Operations</strong></td>
                                            <td><?php echo $criteria['operations']['description']; ?></td>
                                            <td><?php echo htmlspecialchars($report['affected_users'] ?: 'N/A'); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['operations']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Report Frequency</strong></td>
                                            <td><?php echo $criteria['frequency']['description']; ?></td>
                                            <td>-</td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['frequency']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Issue Category</strong></td>
                                            <td><?php echo $criteria['category']['description']; ?> (Weight: <?php echo $report['category_weight']; ?>)</td>
                                            <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['breakdown']['category']; ?></td>
                                        </tr>
                                        <tr class="border-top">
                                            <td colspan="3" class="fw-bold">TOTAL SCORE</td>
                                            <td class="text-end fw-bold"><?php echo $priorityResult['total_score']; ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="alert mt-3
                            <?php echo $priorityResult['priority_level'] === 'high' ? 'alert-danger' :
                                 ($priorityResult['priority_level'] === 'medium' ? 'alert-warning' : 'alert-success'); ?>
                            alert-bulsu">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Priority Level: <?php echo getPriorityLabel($priorityResult['priority_level']); ?></strong>
                            <?php
                            $highThreshold = getSetting('priority_high_threshold', 7.5);
                            $medThreshold = getSetting('priority_medium_threshold', 4.0);
                            ?>
                            (High >= <?php echo $highThreshold; ?>, Medium >= <?php echo $medThreshold; ?>, Low < <?php echo $medThreshold; ?>)
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-0 text-end">
                <a href="/admin/priority" class="btn btn-outline-gold btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0 text-bulsumaroon fw-bold">Reports Needing Priority Assessment</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reportsNeedingPriority)): ?>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="text-muted">All validated reports have been assessed.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Report #</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Safety Risk</th>
                                    <th>Urgency</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportsNeedingPriority as $r): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($r['report_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($r['category_name']); ?></span></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($r['location_name'] . ', ' . $r['building']); ?></td>
                                        <td><?php echo getSafetyRiskBadge($r['safety_risk']); ?></td>
                                        <td><?php echo getUrgencyBadge($r['urgency']); ?></td>
                                        <td class="text-end">
                                            <a href="/admin/priority?report=<?php echo $r['id']; ?>"
                                               class="btn btn-sm btn-outline-gold">
                                                <i class="fas fa-gauge-high"></i> Assess
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
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
