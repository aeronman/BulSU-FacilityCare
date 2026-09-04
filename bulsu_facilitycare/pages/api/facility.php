<?php
/**
 * API - Facility endpoint
 * GET: /api/facility/{id} - return facility JSON
 */
$auth = new Auth();
$func = new Functions();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$facilityId = $params[0] ?? null;
if (!$facilityId) {
    http_response_code(400);
    echo json_encode(['error' => 'Facility ID required']);
    exit;
}

$facility = $func->getFacilityById($facilityId);
if (!$facility) {
    http_response_code(404);
    echo json_encode(['error' => 'Facility not found']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($facility);
