<?php
/**
 * Validate Report - Admin/Maintenance POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/validate');
}

$func = new Functions();
$reportId = $params[0] ?? null;
$action = $_POST['action'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$assignTo = $_POST['assign_to'] ?? '';

if (!$reportId || empty($action)) {
    $_SESSION['error_message'] = 'Invalid validation request.';
    redirect('/admin/validate');
}

$report = $func->getReportById($reportId);
if (!$report) {
    $_SESSION['error_message'] = 'Report not found.';
    redirect('/admin/validate');
}

$priority = new Priority();
$priority->calculatePriority($reportId);
$priorityResult = $func->getPriorityScore($reportId);

if ($action === 'validate') {
    $func->updateReportStatus($reportId, STATUS_VALIDATED, $_SESSION['user_id'],
        $notes ?: ('Report validated. Priority: ' . getPriorityLabel($priorityResult['priority_level']) .
                   ' (Score: ' . $priorityResult['total_score'] . ')'));

    if ($assignTo) {
        $func->assignReport($reportId, (int)$assignTo, $_SESSION['user_id']);
        $assignedUser = $func->getUserById((int)$assignTo);
        $func->addNotification(
            $assignedUser['id'],
            'New Report Assigned',
            'Report #' . $report['report_number'] . ' has been assigned to you.',
            NOTIF_REPORT_ASSIGNED,
            $reportId,
            '/report/' . $reportId
        );
    } else {
        $setting = getSetting('maintenance_auto_assign', '1');
        if ($setting === '1') {
            $maintainers = $func->getUsersByRole(ROLE_MAINTENANCE);
            if (!empty($maintainers)) {
                $assignedTo = $maintainers[array_rand($maintainers)]['id'];
                $func->assignReport($reportId, $assignedTo, $_SESSION['user_id']);
            }
        }
    }

    $func->addNotification(
        $report['reporter_id'],
        'Report Validated',
        'Your report #' . $report['report_number'] . ' has been validated. Priority: ' . getPriorityLabel($priorityResult['priority_level']) . '.',
        NOTIF_REPORT_VALIDATED,
        $reportId,
        '/report/' . $reportId
    );

    $_SESSION['success_message'] = 'Report validated successfully.';
} elseif ($action === 'reject') {
    $func->updateReportStatus($reportId, STATUS_REJECTED, $_SESSION['user_id'], $notes ?: 'Report rejected.');

    $func->addNotification(
        $report['reporter_id'],
        'Report Rejected',
        'Your report #' . $report['report_number'] . ' has been rejected.' . ($notes ? ' Reason: ' . $notes : ''),
        NOTIF_REPORT_STATUS_CHANGE,
        $reportId,
        '/report/' . $reportId
    );

    $_SESSION['success_message'] = 'Report rejected.';
}

redirect('/admin/validate');
