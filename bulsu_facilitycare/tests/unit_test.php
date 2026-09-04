<?php
/**
 * BulSU FacilityCare - Unit Test Script
 * ============================================================
 * Tests core classes and business logic without HTTP.
 * Run: php tests/unit_test.php
 *
 * Prerequisites:
 *   - Database setup (run setup_database.php)
 *   - MySQL running
 * ============================================================
 */

require_once __DIR__ . '/../config/config.php';

$pass = 0;
$fail = 0;
$total = 0;

function pass($desc) {
    global $pass, $total;
    $total++;
    $pass++;
    echo "  \033[32m✓ PASS\033[0m: $desc\n";
}

function fail($desc, $expected, $actual) {
    global $fail, $total;
    $total++;
    $fail++;
    echo "  \033[31m✗ FAIL\033[0m: $desc\n";
    echo "     Expected: $expected\n";
    echo "     Got: $actual\n";
}

function section($name) {
    echo "\n\033[33m=== $name ===\033[0m\n";
}

echo "============================================\n";
echo " BulSU FacilityCare - Unit Tests\n";
echo "============================================\n";

// ============================================================
// 1. Database Connection
// ============================================================
section("1. Database Connection");

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    pass("Database connection established");
} catch (Exception $e) {
    fail("Database connection", "success", "failed: " . $e->getMessage());
    exit(1);
}

// ============================================================
// 2. Auth Class Tests
// ============================================================
section("2. Auth Class Tests");

$auth = new Auth();

// Test login with valid credentials
$loginResult = $auth->login('admin', 'admin123');
if ($loginResult) {
    pass("Admin login with valid credentials");
} else {
    fail("Admin login", "true", "false");
}

// Test login with invalid credentials
$loginResult = $auth->login('admin', 'wrongpassword');
if (!$loginResult) {
    pass("Admin login with invalid credentials fails");
} else {
    fail("Admin login with wrong password", "false", "true");
}

// Test isLoggedIn
if ($auth->isLoggedIn()) {
    pass("isLoggedIn() returns true after login");
} else {
    fail("isLoggedIn()", "true", "false");
}

// Test isAdmin
if ($auth->isAdmin()) {
    pass("isAdmin() returns true for admin");
} else {
    fail("isAdmin()", "true", "false");
}

// Test hasRole
if ($auth->hasRole(ROLE_ADMIN)) {
    pass("hasRole(ROLE_ADMIN) returns true for admin");
} else {
    fail("hasRole(ROLE_ADMIN)", "true", "false");
}

// Test session data
if ($auth->getUserId() === 1) {
    pass("getUserId() returns 1 for admin (first user)");
} else {
    fail("getUserId()", "1", $auth->getUserId());
}

// Logout
$auth->logout();
if (!$auth->isLoggedIn()) {
    pass("Logout clears session");
} else {
    fail("Logout", "false (not logged in)", "true (still logged in)");
}

// ============================================================
// 3. Functions Class Tests
// ============================================================
section("3. Functions Class Tests");

$func = new Functions();

// Test createReport
$func->login('admin', 'admin123');

$reportId = $func->createReport([
    'title' => 'Unit Test Report',
    'description' => 'This is a test report created by the unit test script.',
    'category_id' => 1,
    'facility_id' => 1,
    'urgency' => 'high',
    'safety_risk' => 'moderate',
    'severity' => 'major',
    'affected_users' => '25',
    'created_by' => 1,
]);

if ($reportId) {
    pass("createReport() returns report ID: $reportId");
} else {
    fail("createReport()", "non-zero ID", $reportId);
}

// Test getReportById
$report = $func->getReportById($reportId);
if ($report && $report['title'] === 'Unit Test Report') {
    pass("getReportById() returns correct report");
} else {
    fail("getReportById()", "title = 'Unit Test Report'", $report['title'] ?? 'null');
}

// Test getReports
$reports = $func->getReports([], 10, 0);
if (count($reports) > 0) {
    pass("getReports() returns array of reports");
} else {
    fail("getReports()", "non-empty array", "empty array");
}

// Test getCategories
$categories = $func->getCategories();
if (count($categories) > 0) {
    pass("getCategories() returns categories");
} else {
    fail("getCategories()", "non-empty", "empty");
}

// Test getFacilities
$facilities = $func->getFacilities();
if (count($facilities) > 0) {
    pass("getFacilities() returns facilities");
} else {
    fail("getFacilities()", "non-empty", "empty");
}

// Test getReportStatus
$status = $func->getReportStatus($reportId);
if ($status && $status['status_code'] === STATUS_SUBMITTED) {
    pass("Report has initial status: " . $status['status_code']);
} else {
    fail("getReportStatus()", STATUS_SUBMITTED, $status['status_code'] ?? 'null');
}

// Test searchSimilarReports
$similar = $func->searchSimilarReports($reportId);
if (is_array($similar)) {
    pass("searchSimilarReports() returns array");
} else {
    fail("searchSimilarReports()", "array", gettype($similar));
}

// ============================================================
// 4. Priority Class Tests
// ============================================================
section("4. Priority Class Tests");

$priority = new Priority();

// Test calculatePriority
$score = $priority->calculatePriority($reportId);
if ($score) {
    pass("calculatePriority() returns result with total_score: " . $score['total_score']);
} else {
    fail("calculatePriority()", "result object", "null");
}

// Test criteria descriptions
$descriptions = $priority->getCriteriaDescriptions();
if (isset($descriptions['safety_risk']) && isset($descriptions['severity'])) {
    pass("getCriteriaDescriptions() returns expected criteria");
} else {
    fail("getCriteriaDescriptions()", "contains safety_risk and severity", "missing");
}

// Test calculatePriorityForSubmission
$submissionScore = $priority->calculatePriorityForSubmission([
    'safety_risk' => 'severe',
    'severity' => 'critical',
    'urgency' => 'high',
    'facility_id' => 1,
    'category_id' => 1,
    'affected_users' => 100,
]);

if ($submissionScore) {
    pass("calculatePriorityForSubmission() returns score: " . $submissionScore['total_score']);
} else {
    fail("calculatePriorityForSubmission()", "result object", "null");
}

// Verify High priority threshold
if ($submissionScore['priority_level'] === PRIORITY_HIGH) {
    pass("Severe/Critical/High issue scores as HIGH priority");
} else {
    fail("High priority threshold", PRIORITY_HIGH, $submissionScore['priority_level']);
}

// ============================================================
// 5. CSRF Class Tests
// ============================================================
section("5. CSRF Class Tests");

$token1 = CSRF::generateToken();
if (strlen($token1) >= 32) {
    pass("CSRF::generateToken() returns token (>= 32 chars)");
} else {
    fail("CSRF token length", ">= 32", strlen($token1));
}

$token2 = CSRF::generateToken();
if ($token1 !== $token2) {
    pass("CSRF tokens are unique");
} else {
    fail("Unique tokens", "different", "same");
}

if (CSRF::validateToken($token1)) {
    pass("CSRF::validateToken() accepts valid token");
} else {
    fail("CSRF validation", "true", "false");
}

if (!CSRF::validateToken('invalid-token')) {
    pass("CSRF::validateToken() rejects invalid token");
} else {
    fail("CSRF validation (invalid)", "false", "true");
}

// ============================================================
// 6. Settings Tests
// ============================================================
section("6. Settings Tests");

$setting = getSetting('app_name');
if ($setting) {
    pass("getSetting('app_name') returns: $setting");
} else {
    fail("getSetting('app_name')", "non-empty", "empty/null");
}

$threshold = getSetting('priority_high_threshold', 7.5);
if ($threshold === 7.5 || is_numeric($threshold)) {
    pass("getSetting('priority_high_threshold') returns numeric: $threshold");
} else {
    fail("getSetting('priority_threshold')", "numeric", "non-numeric");
}

// ============================================================
// 7. Helper Function Tests
// ============================================================
section("7. Helper Function Tests");

$badge = getReportStatusBadge(STATUS_SUBMITTED);
if (strpos($badge, 'badge') !== false) {
    pass("getReportStatusBadge() returns HTML badge");
} else {
    fail("getReportStatusBadge()", "contains 'badge'", $badge);
}

$prioBadge = getPriorityBadge(PRIORITY_HIGH);
if (strpos($prioBadge, 'badge') !== false) {
    pass("getPriorityBadge() returns HTML badge");
} else {
    fail("getPriorityBadge()", "contains 'badge'", $prioBadge);
}

// ============================================================
// SUMMARY
// ============================================================
section("Test Summary");
echo "\n  Total:  $total\n";
echo "  \033[32mPassed:  $pass\033[0m\n";
echo "  \033[31mFailed:  $fail\033[0m\n";
echo "\n";

if ($fail === 0) {
    echo "\033[32mAll tests passed!\033[0m\n";
    exit(0);
} else {
    echo "\033[31mSome tests failed. Review the output above.\033[0m\n";
    exit(1);
}
