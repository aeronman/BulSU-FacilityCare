<?php
/**
 * Maintenance Update - POST handler
 * Adds a maintenance update note to a report
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/report/' . ($params[0] ?? ''));
}

$func = new Functions();
$db = Database::getInstance();

$reportId = (int)($params[0] ?? 0);
$workNotes = trim($_POST['work_notes'] ?? '');
$materialsUsed = trim($_POST['materials_used'] ?? '');
$timeSpent = trim($_POST['time_spent'] ?? '');
$newStatus = $_POST['status_code'] ?? '';
$photoPath = null;

if (!$reportId) {
    $_SESSION['error_message'] = 'Invalid report reference.';
    redirect('/dashboard');
}

$report = $func->getReportById($reportId);
if (!$report) {
    $_SESSION['error_message'] = 'Report not found.';
    redirect('/dashboard');
}

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_NO_FILE) {
    $photoPath = $func->uploadPhoto($_FILES['photo']);
}

$db->query(
    "INSERT INTO maintenance_updates (report_id, updated_by, status_code, work_notes, materials_used, time_spent, photo_path)
     VALUES (:report_id, :updated_by, :status_code, :work_notes, :materials_used, :time_spent, :photo_path)",
    [
        'report_id' => $reportId,
        'updated_by' => $_SESSION['user_id'],
        'status_code' => $newStatus ?: $report['status_code'],
        'work_notes' => $workNotes,
        'materials_used' => $materialsUsed ?: null,
        'time_spent' => $timeSpent ?: null,
        'photo_path' => $photoPath,
    ]
);

if ($newStatus && $newStatus !== $report['status_code']) {
    $func->updateReportStatus($reportId, $newStatus, $_SESSION['user_id'], $workNotes ?: 'Maintenance update: status changed to ' . $func->getStatusLabel($newStatus));

    $func->addNotification(
        $report['reporter_id'],
        'Report Status Updated',
        'Report #' . $report['report_number'] . ' status has been updated to: ' . $func->getStatusLabel($newStatus),
        NOTIF_REPORT_STATUS_CHANGE,
        $reportId,
        '/report/' . $reportId
    );
}

$func->addComment($reportId, $_SESSION['user_id'], $workNotes ?: 'Maintenance update added.', true);

$_SESSION['success_message'] = 'Maintenance update recorded successfully.';
redirect('/report/' . $reportId);
