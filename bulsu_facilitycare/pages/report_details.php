<?php
/**
 * Report Details - View single report, add comments, track status
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$reportId = $params[0] ?? null;
if (!$reportId) {
    redirect('/my-reports');
}

$report = $func->getReportById($reportId);
if (!$report) {
    $_SESSION['error_message'] = 'Report not found.';
    redirect('/my-reports');
}

$statuses = $func->getReportStatuses();
$comments = $func->getReportComments($reportId, !$auth->canUpdateReport());
$maintenanceUpdates = $func->getMaintenanceUpdates($reportId);
$priorityScore = $func->getPriorityScore($reportId);
$canValidate = $auth->canValidate();
$canUpdate = $auth->canUpdateReport();
$canAssessPriority = $auth->canAssessPriority();
$availableTransitions = $func->getAvailableStatusTransitions($report['status_code']);
$reportStatusLabels = [
    'submitted' => 'Submitted', 'under_review' => 'Under Review', 'validated' => 'Validated',
    'assigned' => 'Assigned', 'ongoing' => 'Ongoing', 'resolved' => 'Resolved',
    'closed' => 'Closed', 'rejected' => 'Rejected',
];

$pageTitle = 'Report ' . $report['report_number'];
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <?php if ($auth->isStudentStaff()): ?>
                    <li class="breadcrumb-item"><a href="/my-reports">My Reports</a></li>
                <?php elseif ($auth->isMaintenance()): ?>
                    <li class="breadcrumb-item"><a href="/assigned-reports">Assigned Reports</a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($report['report_number']); ?></li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($report['title']); ?></h1>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <?php echo getReportStatusBadge($report['status_code']); ?>
                    <?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?>
                    <?php echo getSafetyRiskBadge($report['safety_risk']); ?>
                    <?php echo getSeverityBadge($report['severity']); ?>
                    <?php echo getUrgencyBadge($report['urgency']); ?>
                </div>
            </div>
            <div class="text-end">
                <span class="text-muted small"><?php echo '#' . htmlspecialchars($report['report_number']); ?> | <?php echo formatDate($report['created_at']); ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <ul class="nav nav-pills" id="reportTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-bulsumaroon fw-semibold" id="details-tab"
                                    data-bs-toggle="pill" data-bs-target="#details-pane" type="button">
                                <i class="fas fa-info-circle me-1"></i> Details
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-bulsumaroon fw-semibold" id="updates-tab"
                                    data-bs-toggle="pill" data-bs-target="#updates-pane" type="button">
                                <i class="fas fa-clipboard-list me-1"></i> Maintenance Updates
                            </button>
                        </li>
                        <?php if ($canUpdate): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-bulsumaroon fw-semibold" id="status-tab"
                                        data-bs-toggle="pill" data-bs-target="#status-pane" type="button">
                                    <i class="fas fa-exchange-alt me-1"></i> Update Status
                                </button>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="reportTabContent">
                        <div class="tab-pane fade show active" id="details-pane" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="h6 text-bulsumaroon fw-bold mb-3">Issue Information</h4>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="fw-medium text-dark">Category</td>
                                            <td><?php echo htmlspecialchars($report['category_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Location</td>
                                            <td><?php echo htmlspecialchars($report['building'] . ' - ' . $report['location_name'] . ' (' . $report['room_number'] . ')'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Floor</td>
                                            <td><?php echo htmlspecialchars($report['floor'] ?: 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Urgency</td>
                                            <td><?php echo getUrgencyBadge($report['urgency']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Safety Risk</td>
                                            <td><?php echo getSafetyRiskBadge($report['safety_risk']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Severity</td>
                                            <td><?php echo getSeverityBadge($report['severity']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="h6 text-bulsumaroon fw-bold mb-3">Report Information</h4>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="fw-medium text-dark">Report Number</td>
                                            <td><?php echo htmlspecialchars($report['report_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Status</td>
                                            <td><?php echo getReportStatusBadge($report['status_code']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Priority</td>
                                            <td><?php echo getPriorityBadge($report['priority_level'], $report['priority_score']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Reported By</td>
                                            <td><?php echo htmlspecialchars($report['reporter_name'] ?: 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Assigned To</td>
                                            <td><?php echo htmlspecialchars($report['assignee_name'] ?: 'Not assigned'); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-dark">Submitted</td>
                                            <td><?php echo formatDate($report['created_at']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <h4 class="h6 text-bulsumaroon fw-bold mt-4 mb-2">Description</h4>
                            <p class="text-dark"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>

                            <?php if ($report['affected_users']): ?>
                                <h4 class="h6 text-bulsumaroon fw-bold mt-3 mb-2">Affected Users</h4>
                                <p class="text-dark"><?php echo htmlspecialchars($report['affected_users']); ?></p>
                            <?php endif; ?>

                            <?php if ($report['additional_info']): ?>
                                <h4 class="h6 text-bulsumaroon fw-bold mt-3 mb-2">Additional Information</h4>
                                <p class="text-dark"><?php echo nl2br(htmlspecialchars($report['additional_info'])); ?></p>
                            <?php endif; ?>

                            <?php if ($report['photo_path']): ?>
                                <h4 class="h6 text-bulsumaroon fw-bold mt-3 mb-2">Photo Evidence</h4>
                                <a href="/assets/img/uploads/<?php echo htmlspecialchars($report['photo_path']); ?>" target="_blank">
                                    <img src="/assets/img/uploads/<?php echo htmlspecialchars($report['photo_path']); ?>"
                                         class="img-fluid rounded border" style="max-height: 300px;" alt="Photo Evidence">
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="updates-pane" role="tabpanel">
                            <?php if (!empty($maintenanceUpdates)): ?>
                                <div class="timeline">
                                    <?php foreach ($maintenanceUpdates as $update): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="h6 fw-bold mb-0"><?php echo htmlspecialchars($update['full_name']); ?></h5>
                                                        <small class="text-muted"><?php echo formatDate($update['created_at']); ?></small>
                                                    </div>
                                                    <?php if ($update['status_code']): ?>
                                                        <?php echo getReportStatusBadge($update['status_code']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($update['work_notes']): ?>
                                                    <p class="text-dark mb-2"><?php echo nl2br(htmlspecialchars($update['work_notes'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($update['materials_used']): ?>
                                                    <p class="text-muted small mb-1">
                                                        <i class="fas fa-tools me-1"></i>Materials: <?php echo htmlspecialchars($update['materials_used']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($update['time_spent']): ?>
                                                    <p class="text-muted small mb-0">
                                                        <i class="fas fa-clock me-1"></i>Time: <?php echo htmlspecialchars($update['time_spent']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($update['photo_path']): ?>
                                                    <img src="/assets/img/uploads/<?php echo htmlspecialchars($update['photo_path']); ?>"
                                                         class="img-fluid rounded mt-2" style="max-height: 150px;" alt="Update photo">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No maintenance updates yet.</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($canUpdate): ?>
                            <div class="tab-pane fade" id="status-pane" role="tabpanel">
                                <form method="POST" action="/report/<?php echo $reportId; ?>/status">
                                    <?php echo CSRF::tokenField(); ?>
                                    <div class="mb-3">
                                        <label class="form-label text-bulsumaroon fw-semibold">Change Status</label>
                                        <select class="form-select" name="status_code" required>
                                            <option value="">Select new status</option>
                                            <?php foreach ($availableTransitions as $transition): ?>
                                                <?php
                                                $lowerCode = strtolower(str_replace(' ', '_', $transition));
                                                $label = $reportStatusLabels[$lowerCode] ?? $transition;
                                                ?>
                                                <option value="<?php echo $lowerCode; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                            <?php
                                            $ownStatus = $currentUser['role_name'] === ROLE_MAINTENANCE ? 'ongoing' : 'resolved';
                                            $ownLabel = $reportStatusLabels[$ownStatus];
                                            ?>
                                            <option value="rejected" disabled>Reject Report (use admin panel)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-bulsumaroon fw-semibold">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3"
                                                  placeholder="Add notes about this status change..."></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Status
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                        <i class="fas fa-comments me-2"></i>Comments & Discussion
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($comments)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item border-0 py-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-gold text-bulsumaroon d-flex align-items-center justify-content-center"
                                                 style="width: 36px; height: 36px; border-radius: 50%;">
                                                <?php echo strtoupper(substr($comment['full_name'], 0, 1)); ?>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($comment['full_name']); ?></span>
                                                    <small class="text-muted">
                                                        <i class="fas <?php echo $comment['is_internal'] ? 'fa-lock' : 'fa-comment'; ?> me-1"></i>
                                                        <?php echo $comment['role_display']; ?>
                                                    </small>
                                                </div>
                                                <small class="text-muted"><?php echo timeAgo($comment['created_at']); ?></small>
                                            </div>
                                            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($comment['message'])); ?></p>
                                            <?php if ($comment['is_internal']): ?>
                                                <span class="badge bg-light text-muted small mt-1">Internal Note</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No comments yet.</p>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light border-0">
                    <form method="POST" action="/report/<?php echo $reportId; ?>/comment" class="d-flex gap-2">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="text" class="form-control" name="message" placeholder="Add a comment..." required>
                        <?php if ($canUpdate): ?>
                            <div class="form-check me-2 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal">
                                <label class="form-check-label" for="is_internal">Internal</label>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-outline-gold">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($reportId && $canValidate): ?>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-gauge-high me-2"></i>Priority Assessment
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($priorityScore): ?>
                            <div class="text-center mb-4">
                                <div class="priority-indicator mb-2">
                                    <div class="priority-indicator-fill <?php echo $priorityScore['priority_level']; ?>"
                                         style="width: <?php echo min(100, ($priorityScore['total_score'] / 20) * 100); ?>%"></div>
                                </div>
                                <h2 class="priority-score <?php echo 'priority-' . $priorityScore['priority_level']; ?>">
                                    <?php echo $priorityScore['total_score']; ?>
                                </h2>
                                <?php echo getPriorityBadge($priorityScore['priority_level']); ?>
                            </div>
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <td>Safety Risk</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['safety_risk_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Severity</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['severity_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Urgency</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['urgency_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Location Criticality</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['location_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Impact on Operations</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['operations_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Report Frequency</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['frequency_score']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Issue Category</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['category_score']; ?></td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold">Total Score</td>
                                        <td class="text-end fw-bold"><?php echo $priorityScore['total_score']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>Assessed by: <?php echo htmlspecialchars($priorityScore['assessed_by'] ? '' : 'System'); ?>
                            </small>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calculator fa-2x text-muted mb-3"></i>
                                <p class="text-muted">No priority assessment yet.</p>
                                <a href="/admin/priority" class="btn btn-outline-gold btn-sm">
                                    <i class="fas fa-play me-1"></i>Assess Priority
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-history me-2"></i>Status History
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="timeline">
                            <?php
                                $history = $func->getDb()->fetchAll(
                                "SELECT h.*, u.full_name, u2.role_id, r.name as role_name
                                 FROM report_history h
                                 LEFT JOIN users u ON h.changed_by = u.id
                                 LEFT JOIN roles r ON u.role_id = r.id
                                 WHERE h.report_id = :report_id
                                 ORDER BY h.created_at ASC",
                                ['report_id' => $reportId]
                            );

                            if (!empty($history)):
                                foreach ($history as $h): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo htmlspecialchars($h['full_name'] ?? 'System'); ?></strong>
                                                <?php echo getReportStatusBadge($h['status_code']); ?>
                                            </div>
                                            <p class="text-muted small mb-0"><?php echo timeAgo($h['created_at']); ?></p>
                                            <?php if ($h['notes']): ?>
                                                <p class="text-muted small mt-1"><?php echo htmlspecialchars($h['notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div class="p-3 text-center text-muted small">No status changes recorded.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo renderFooter(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    if (form) {
        const photoInput = document.getElementById('photo');
        const previewContainer = document.getElementById('photoPreviewContainer');

        photoInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = '<img src="' + e.target.result + '" class="photo-preview" alt="Preview">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
