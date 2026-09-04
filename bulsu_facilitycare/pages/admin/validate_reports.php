<?php
/**
 * Admin - Report Validation
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->canValidate()) {
    redirect('/admin/dashboard');
}

$reportId = $_GET['report'] ?? null;
$report = null;

if ($reportId) {
    $report = $func->getReportById($reportId);
}

$reports = $func->getReports(['status' => STATUS_SUBMITTED], 15, 0);

$pageTitle = 'Report Validation';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Report Validation</li>
            </ol>
        </nav>
        <h1 class="page-title">Report Validation</h1>
        <p class="page-subtitle text-muted">
            Review and validate submitted reports. <?php echo count($reports); ?> reports pending validation.
        </p>
    </div>

    <?php if ($report): ?>
        <?php $priority = new Priority(); ?>
        <?php $priorityResult = $priority->calculatePriority($report['id']); ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        Validate Report #<?php echo htmlspecialchars($report['report_number']); ?>
                    </h3>
                    <span class="badge bg-warning"><?php echo getReportStatusBadge($report['status_code']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="h6 text-bulsumaroon fw-bold">Issue Details</h4>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="fw-medium">Title</td>
                                <td><?php echo htmlspecialchars($report['title']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Category</td>
                                <td><?php echo htmlspecialchars($report['category_name']); ?>
                                    <span class="badge bg-light text-dark small">Weight: <?php echo $report['category_weight']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Location</td>
                                <td><?php echo htmlspecialchars($report['building'] . ' - ' . $report['location_name'] . ' (' . $report['room_number'] . ')'); ?>
                                    <span class="badge bg-light text-dark small">Weight: <?php echo $report['facility_weight']; ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Description</td>
                                <td><?php echo nl2br(htmlspecialchars($report['description'])); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Safety Risk</td>
                                <td><?php echo getSafetyRiskBadge($report['safety_risk']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Severity</td>
                                <td><?php echo getSeverityBadge($report['severity']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Urgency</td>
                                <td><?php echo getUrgencyBadge($report['urgency']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">Affected Users</td>
                                <td><?php echo htmlspecialchars($report['affected_users'] ?? 'N/A'); ?></td>
                            </tr>
                        </table>
                        <?php if ($report['photo_path']): ?>
                            <a href="/assets/img/uploads/<?php echo htmlspecialchars($report['photo_path']); ?>" target="_blank">
                                <img src="/assets/img/uploads/<?php echo htmlspecialchars($report['photo_path']); ?>"
                                     class="img-fluid rounded border" style="max-height: 200px;" alt="Photo Evidence">
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 bg-light">
                            <div class="card-header bg-white">
                                <h4 class="h6 mb-0 text-bulsumaroon fw-bold">Priority Assessment</h4>
                            </div>
                            <div class="card-body text-center">
                                <div class="priority-indicator mb-2">
                                    <div class="priority-indicator-fill <?php echo $priorityResult['priority_level']; ?>"
                                         style="width: <?php echo min(100, ($priorityResult['total_score'] / 20) * 100); ?>%"></div>
                                </div>
                                <h2 class="priority-score <?php echo 'priority-' . $priorityResult['priority_level']; ?>">
                                    <?php echo $priorityResult['total_score']; ?>
                                </h2>
                                <?php echo getPriorityBadge($priorityResult['priority_level']); ?>
                                <table class="table table-sm table-borderless mt-3">
                                    <tbody>
                                        <?php foreach ($priorityResult['breakdown'] as $key => $score): ?>
                                            <tr>
                                                <td class="small"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></td>
                                                <td class="text-end fw-bold small"><?php echo $score; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <form method="POST" action="/admin/validate/<?php echo $report['id']; ?>" class="mt-3">
                            <?php echo CSRF::tokenField(); ?>
                            <div class="mb-3">
                                <label class="form-label text-bulsumaroon fw-semibold">Assign To</label>
                                <select class="form-select form-select-sm" name="assign_to">
                                    <option value="">Auto-assign (by category)</option>
                                    <?php
                                    $maintainers = $func->getUsersByRole(ROLE_MAINTENANCE);
                                    foreach ($maintainers as $m): ?>
                                        <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-bulsumaroon fw-semibold">Notes</label>
                                <textarea class="form-control form-control-sm" name="notes" rows="2"
                                          placeholder="Validation notes..."></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="validate"
                                        class="btn btn-success btn-sm w-50">
                                    <i class="fas fa-check me-1"></i> Validate
                                </button>
                                <button type="submit" name="action" value="reject"
                                        class="btn btn-danger btn-sm w-50">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                            </div>
                        </form>

                        <?php
                        $duplicates = $func->searchSimilarReports(
                            $report['id'], $report['title'], $report['description'],
                            $report['category_id'], $report['facility_id']
                        );
                        if (!empty($duplicates)): ?>
                            <div class="alert alert-info alert-bulsu mt-3">
                                <i class="fas fa-search me-2"></i><strong>Potential Duplicate(s) Found!</strong>
                                <ul class="mb-0 mt-2 small">
                                    <?php foreach ($duplicates as $dup): ?>
                                        <li>
                                            Report <?php echo htmlspecialchars($dup['report_number']); ?>
                                            (Similarity: <?php echo round($dup['similarity_score'] * 100); ?>%)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-0 text-end">
                <a href="/admin/validate" class="btn btn-outline-gold btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Validation Queue
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php if (empty($reports)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clip-check fa-3x text-muted mb-3"></i>
                    <h4 class="h5 text-bulsumaroon">No Reports to Validate</h4>
                    <p class="text-muted">All submitted reports have been processed.</p>
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
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Safety</th>
                                    <th>Urgency</th>
                                    <th>Submitted</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars($r['report_number']); ?></td>
                                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($r['category_name']); ?></span></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($r['location_name'] . ', ' . $r['building']); ?></td>
                                        <td><?php echo getSafetyRiskBadge($r['safety_risk']); ?></td>
                                        <td><?php echo getUrgencyBadge($r['urgency']); ?></td>
                                        <td class="text-muted small"><?php echo timeAgo($r['created_at']); ?></td>
                                        <td class="text-end">
                                            <a href="/admin/validate?report=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-gold">
                                                <i class="fas fa-eye"></i> Review
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
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
