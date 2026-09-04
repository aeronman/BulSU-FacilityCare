<?php
/**
 * API - Report endpoint
 * GET: /api/report/{id} - return report JSON
 */
$auth = new Auth();
$func = new Functions();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$reportId = $params[0] ?? null;
if (!$reportId) {
    http_response_code(400);
    echo json_encode(['error' => 'Report ID required']);
    exit;
}

$report = $func->getReportById($reportId);
if (!$report) {
    http_response_code(404);
    echo json_encode(['error' => 'Report not found']);
    exit;
}

$score = $func->getPriorityScore($reportId);
$comments = $func->getReportComments($reportId, $auth->canUpdateReport());
$history = $func->getDb()->fetchAll(
    "SELECT h.*, u.full_name
     FROM report_history h
     LEFT JOIN users u ON h.changed_by = u.id
     WHERE h.report_id = :report_id
     ORDER BY h.created_at DESC",
    ['report_id' => $reportId]
);

header('Content-Type: application/json');
echo json_encode([
    'report' => $report,
    'priority' => $score,
    'comments' => $comments,
    'history' => $history,
]);
