<?php
/**
 * Update Settings - POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/settings');
}

$db = Database::getInstance();
$updatedCount = 0;

$allowedKeys = [
    'app_name', 'app_tagline', 'admin_email', 'items_per_page',
    'duplicate_detection_threshold', 'priority_high_threshold', 'priority_medium_threshold',
    'maintenance_auto_assign', 'allow_registration', 'report_reminder_days',
    'working_hours_start', 'working_hours_end'
];

foreach ($allowedKeys as $key) {
    if (array_key_exists($key, $_POST)) {
        $value = $_POST[$key];
        if (in_array($key, ['maintenance_auto_assign', 'allow_registration'])) {
            $value = isset($value) ? '1' : '0';
        }
        $db->query(
            "UPDATE settings SET setting_value = :value WHERE setting_key = :key",
            ['value' => $value, 'key' => $key]
        );
        $updatedCount++;
    }
}

if ($updatedCount > 0) {
    $_SESSION['success_message'] = 'Settings updated successfully (' . $updatedCount . ' changed).';
} else {
    $_SESSION['success_message'] = 'No settings were changed.';
}

// Clear cached settings
if (isset($_SESSION['cached_settings'])) {
    unset($_SESSION['cached_settings']);
}

redirect('/admin/settings');
