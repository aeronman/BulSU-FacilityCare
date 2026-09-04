<?php
/**
 * Update Report Status - POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/report/' . ($params[0] ?? ''));
}

$func = new Functions();
$reportId = $params[0] ?? null;
$newStatus = $_POST['status_code'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!$reportId || empty($newStatus)) {
    $_SESSION['error_message'] = 'Invalid status update request.';
    redirect('/report/' . $reportId);
}

$report = $func->getReportById($reportId);
if (!$report) {
    $_SESSION['error_message'] = 'Report not found.';
    redirect('/dashboard');
}

$func->updateReportStatus($reportId, $newStatus, $_SESSION['user_id'], $notes);

$func->addNotification(
    $report['reporter_id'],
    'Report Status Updated',
    'Status of report #' . $report['report_number'] . ' has been updated to: ' . $func->getStatusLabel($newStatus),
    NOTIF_REPORT_STATUS_CHANGE,
    $reportId,
    '/report/' . $reportId
);

$_SESSION['success_message'] = 'Report status updated to: ' . $func->getStatusLabel($newStatus);
redirect('/report/' . $reportId);
