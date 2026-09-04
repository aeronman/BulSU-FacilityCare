<?php
/**
 * Admin - Settings
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$settings = $func->getDb()->fetchAll("SELECT * FROM settings ORDER BY setting_key");

$pageTitle = 'Settings';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">Settings</li>
            </ol>
        </nav>
        <h1 class="page-title">System Settings</h1>
        <p class="page-subtitle text-muted">Configure application preferences and system parameters.</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-bulsu">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/settings/update">
        <?php echo CSRF::tokenField(); ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                    <i class="fas fa-cog me-2"></i>General Settings
                </h3>
            </div>
            <div class="card-body">
                <?php foreach ($settings as $setting): ?>
                    <?php if (strpos($setting['setting_key'], 'working_hours') === 0 || strpos($setting['setting_key'], 'allow_registration') === 0 || strpos($setting['setting_key'], 'maintenance_auto_assign') === 0): ?>
                        <div class="mb-3">
                            <div class="row g-0 align-items-center">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-bulsumaroon">
                                        <?php echo formatSettingLabel($setting['setting_key']); ?>
                                    </label>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                                </div>
                                <div class="col-md-8">
                                    <?php echo renderSettingInput($setting); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                    <i class="fas fa-globe-americas me-2"></i>Priority Assessment Thresholds
                </h3>
            </div>
            <div class="card-body">
                <?php
                $thresholdSettings = array_filter($settings, function($s) {
                    return strpos($s['setting_key'], 'priority') === 0 ||
                           strpos($s['setting_key'], 'duplicate') === 0 ||
                           strpos($s['setting_key'], 'report_reminder') === 0;
                });
                ?>
                <?php foreach ($thresholdSettings as $setting): ?>
                    <div class="mb-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-bulsumaroon">
                                    <?php echo formatSettingLabel($setting['setting_key']); ?>
                                </label>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                            <div class="col-md-8">
                                <?php echo renderSettingInput($setting); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                    <i class="fas fa-clock me-2"></i>Working Hours
                </h3>
            </div>
            <div class="card-body">
                <?php
                $workingSettings = array_filter($settings, function($s) {
                    return strpos($s['setting_key'], 'working_hours') === 0;
                });
                ?>
                <?php foreach ($workingSettings as $setting): ?>
                    <div class="mb-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-bulsumaroon">
                                    <?php echo formatSettingLabel($setting['setting_key']); ?>
                                </label>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                            <div class="col-md-8">
                                <?php echo renderSettingInput($setting); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                    <i class="fas fa-info-circle me-2"></i>Application Information
                </h3>
            </div>
            <div class="card-body">
                <?php
                $appSettings = array_filter($settings, function($s) {
                    return strpos($s['setting_key'], 'app_') === 0 || $s['setting_key'] === 'admin_email';
                });
                ?>
                <?php foreach ($appSettings as $setting): ?>
                    <div class="mb-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold text-bulsumaroon">
                                    <?php echo formatSettingLabel($setting['setting_key']); ?>
                                </label>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($setting['description']); ?></small>
                            </div>
                            <div class="col-md-8">
                                <?php echo renderSettingInput($setting); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-footer bg-white text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </div>
    </form>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);

function formatSettingLabel($key) {
    $labels = [
        'app_name' => 'Application Name',
        'app_tagline' => 'Application Tagline',
        'admin_email' => 'Admin Email',
        'items_per_page' => 'Items per Page',
        'duplicate_detection_threshold' => 'Duplicate Detection Threshold',
        'priority_high_threshold' => 'High Priority Threshold',
        'priority_medium_threshold' => 'Medium Priority Threshold',
        'maintenance_auto_assign' => 'Auto-Assign Maintenance',
        'allow_registration' => 'Allow User Registration',
        'report_reminder_days' => 'Reminder Days',
        'working_hours_start' => 'Working Hours Start',
        'working_hours_end' => 'Working Hours End',
    ];
    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

function renderSettingInput($setting) {
    $key = $setting['setting_key'];
    $value = $setting['setting_value'];
    $inputType = isset($setting['setting_type']) && $setting['setting_type'] === 'boolean' ? 'checkbox' : 'text';

    if ($inputType === 'checkbox') {
        $checked = $value == '1';
        return '<input type="checkbox" class="form-check-input" name="' . $key . '" value="1" ' . ($checked ? 'checked' : '') . '>';
    }

    $type = (in_array($key, ['admin_email']) || in_array($key, ['app_name', 'app_tagline'])) ? 'text' : 'number';
    $extra = $type === 'number' ? 'step="0.01" min="0"' : '';

    return '<input type="' . $type . '" class="form-control form-control-sm" name="' . $key . '" value="' . htmlspecialchars($value) . '" ' . $extra . '>';
}
