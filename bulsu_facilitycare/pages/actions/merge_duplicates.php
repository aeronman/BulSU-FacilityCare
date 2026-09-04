<?php
/**
 * Merge Duplicates - Admin POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/duplicates');
}

$func = new Functions();
$originalId = $_POST['original_id'] ?? null;
$duplicateId = $_POST['duplicate_id'] ?? null;

if (!$originalId || !$duplicateId) {
    $_SESSION['error_message'] = 'Invalid merge request.';
    redirect('/admin/duplicates');
}

$func->mergeReports((int)$originalId, (int)$duplicateId, $_SESSION['user_id']);

$originalReport = $func->getReportById($originalId);
$duplicateReport = $func->getReportById($duplicateId);

$func->addNotification(
    $duplicateReport['reporter_id'],
    'Report Merged',
    'Your report #' . $duplicateReport['report_number'] . ' was merged with ' . $originalReport['report_number'] . ' as a duplicate.',
    NOTIF_DUPLICATE_MERGED,
    $originalId,
    '/report/' . $originalId
);

$_SESSION['success_message'] = 'Reports merged successfully.';
redirect('/admin/duplicates');
