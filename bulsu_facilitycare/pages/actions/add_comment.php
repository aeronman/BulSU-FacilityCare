<?php
/**
 * Add Comment - POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/report/' . ($params[0] ?? ''));
}

$func = new Functions();
$reportId = $params[0] ?? null;
$message = trim($_POST['message'] ?? '');
$isInternal = isset($_POST['is_internal']) && $_POST['is_internal'];

if (!$reportId || empty($message)) {
    $_SESSION['error_message'] = 'Message is required.';
    redirect('/report/' . $reportId);
}

$func->addComment($reportId, $_SESSION['user_id'], $message, $isInternal);

$report = $func->getReportById($reportId);
$func->addNotification(
    $report['reporter_id'],
    'New Comment on Your Report',
    'A new comment was added to your report #' . $report['report_number'] . '.',
    NOTIF_MAINTENANCE_UPDATE,
    $reportId,
    '/report/' . $reportId
);

redirect('/report/' . $reportId);
