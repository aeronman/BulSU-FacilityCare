<?php
/**
 * Submit Report POST Handler
 */
if (!CSRF::validate()) {
    redirect('/submit-report');
}

$func = new Functions();

$required = ['title', 'category_id', 'facility_id', 'description', 'urgency', 'safety_risk', 'severity'];
$errors = [];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    }
}

if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_NO_FILE) {
    // Photo is optional but recommended
} elseif ($_FILES['photo']['error'] !== UPLOAD_OK) {
    $errors[] = 'Photo upload failed. Please try again.';
}

$affectedUsers = $_POST['affected_users'] ?? [];
$affectedUsersStr = is_array($affectedUsers) ? implode(', ', array_filter($affectedUsers)) : $affectedUsers;

$submitData = [
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'category_id' => (int)($_POST['category_id'] ?? 0),
    'facility_id' => (int)($_POST['facility_id'] ?? 0),
    'urgency' => $_POST['urgency'] ?? 'medium',
    'safety_risk' => $_POST['safety_risk'] ?? 'no',
    'severity' => $_POST['severity'] ?? 'moderate',
    'affected_users' => $affectedUsersStr,
    'additional_info' => trim($_POST['additional_info'] ?? ''),
];

if (empty($errors)) {
    $reportId = $func->createReport($submitData);

    $priority = new Priority();
    $priorityResult = $priority->calculatePriority($reportId);

    $report = $func->getReportById($reportId);

    $func->updateReportStatus($reportId, STATUS_UNDER_REVIEW, $_SESSION['user_id'], 'Report submitted. Awaiting validation.');

    $adminUsers = $func->getAdminUsers();
    foreach ($adminUsers as $admin) {
        $func->addNotification(
            $admin['id'],
            'New Report Submitted',
            'A new facility report has been submitted: "' . $report['title'] . '" (' . $report['report_number'] . '). Please review and validate.',
            NOTIF_REPORT_SUBMITTED,
            $reportId,
            '/admin/validate'
        );
    }

    $func->addNotification(
        $report['reporter_id'],
        'Report Submitted',
        'Your report #' . $report['report_number'] . ' has been submitted successfully. It will be reviewed by the maintenance team.',
        NOTIF_REPORT_SUBMITTED,
        $reportId,
        '/report/' . $reportId
    );

    $_SESSION['success_message'] = 'Report submitted successfully! Report #' . $report['report_number'] . ' has been created.';
    redirect('/report/' . $reportId);
} else {
    $_SESSION['submit_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    redirect('/submit-report');
}
