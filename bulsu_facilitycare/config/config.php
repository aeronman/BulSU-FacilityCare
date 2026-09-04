<?php
/**
 * BulSU FacilityCare - Application Configuration
 */

define('APP_NAME', 'BulSU FacilityCare');
define('APP_TAGLINE', 'Facility Maintenance Reporting & Risk Prioritization System');
define('APP_BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . '/public/assets/img/uploads');
define('UPLOAD_URL', '/assets/img/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

define('ROLE_STUDENT_STAFF', 'student_staff');
define('ROLE_MAINTENANCE', 'maintenance');
define('ROLE_ADMIN', 'admin');

define('STATUS_SUBMITTED', 'submitted');
define('STATUS_UNDER_REVIEW', 'under_review');
define('STATUS_VALIDATED', 'validated');
define('STATUS_ASSIGNED', 'assigned');
define('STATUS_ONGOING', 'ongoing');
define('STATUS_RESOLVED', 'resolved');
define('STATUS_CLOSED', 'closed');
define('STATUS_REJECTED', 'rejected');

define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');

define('NOTIF_REPORT_SUBMITTED', 'report_submitted');
define('NOTIF_REPORT_VALIDATED', 'report_validated');
define('NOTIF_REPORT_ASSIGNED', 'report_assigned');
define('NOTIF_REPORT_STATUS_CHANGE', 'report_status_change');
define('NOTIF_DUPLICATE_FOUND', 'duplicate_found');
define('NOTIF_PRIORITY_ASSESSED', 'priority_assessed');
define('NOTIF_MAINTENANCE_UPDATE', 'maintenance_update');
define('NOTIF_DUPLICATE_MERGED', 'duplicate_merged');

session_start();

spl_autoload_register(function ($class) {
    $includePaths = [
        APP_ROOT . '/includes/' . $class . '.php',
        APP_ROOT . '/config/' . $class . '.php',
    ];
    foreach ($includePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$dotenvPath = APP_ROOT . '/.env';
if (file_exists($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

require_once __DIR__ . '/../includes/helpers.php';

function getSetting($key, $default = null) {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = Database::getInstance();
            $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE is_public = 1");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return isset($settings[$key]) ? $settings[$key] : $default;
}
