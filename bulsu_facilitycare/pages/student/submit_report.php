<?php
/**
 * Submit Facility Report - Student/Faculty/Staff
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$categories = $func->getAllCategories();
$facilities = $func->getAllFacilities();
$departments = $func->getDepartments();

$errors = $_SESSION['submit_errors'] ?? [];
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['submit_errors'], $_SESSION['old_input']);

$pageTitle = 'Submit Report';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Submit Report</li>
            </ol>
        </nav>
        <h1 class="page-title">Submit Facility Report</h1>
        <p class="page-subtitle text-muted">Report maintenance issues with detailed information for priority assessment.</p>
    </div>

    <form method="POST" action="/submit-report" enctype="multipart/form-data" id="reportForm">
        <?php echo CSRF::tokenField(); ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-bulsu">
                <i class="fas fa-exclamation-circle me-2"></i>
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-file-alt me-2"></i>Report Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Report Title *</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   placeholder="Brief summary of the issue"
                                   value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Issue Category *</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select issue category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo (($old['category_id'] ?? '') == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="facility_id" class="form-label">Location *</label>
                            <select class="form-select" id="facility_id" name="facility_id" required>
                                <option value="">Select location</option>
                                <?php
                                $groupedFacilities = [];
                                foreach ($facilities as $f) {
                                    $groupedFacilities[$f['building']][] = $f;
                                }
                                foreach ($groupedFacilities as $building => $items):
                                    ?>
                                    <optgroup label="<?php echo htmlspecialchars($building); ?>">
                                        <?php foreach ($items as $f): ?>
                                            <option value="<?php echo $f['id']; ?>"
                                                <?php echo (($old['facility_id'] ?? '') == $f['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($f['location_name'] . ' (' . $f['room_number'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Detailed Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="5"
                                      placeholder="Provide a detailed description of the issue, including when it started and what area is affected..."
                                      required><?php echo htmlspecialchars($old['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo Evidence</label>
                            <input class="form-control" type="file" id="photo" name="photo"
                                   accept="image/*">
                            <small class="text-muted">Supported formats: JPG, PNG, GIF (max 5MB)</small>
                            <div id="photoPreviewContainer" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="additional_info" class="form-label">Additional Information</label>
                            <textarea class="form-control" id="additional_info" name="additional_info" rows="3"
                                      placeholder="Any other relevant information..."><?php echo htmlspecialchars($old['additional_info'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-gauge-high me-2"></i>Risk Assessment
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">These factors determine the priority level.</p>

                        <div class="mb-3">
                            <label for="urgency" class="form-label">Urgency *</label>
                            <select class="form-select" id="urgency" name="urgency" required>
                                <option value="medium" <?php echo (($old['urgency'] ?? '') == 'medium') ? 'selected' : ''; ?>>Medium - Needed within a week</option>
                                <option value="low" <?php echo (($old['urgency'] ?? '') == 'low') ? 'selected' : ''; ?>>Low - Needed within a month</option>
                                <option value="high" <?php echo (($old['urgency'] ?? '') == 'high') ? 'selected' : ''; ?>>High - Needed immediately</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="safety_risk" class="form-label">Safety Risk *</label>
                            <select class="form-select" id="safety_risk" name="safety_risk" required>
                                <option value="no" <?php echo (($old['safety_risk'] ?? '') == 'no') ? 'selected' : ''; ?>>No Risk</option>
                                <option value="minor" <?php echo (($old['safety_risk'] ?? '') == 'minor') ? 'selected' : ''; ?>>Minor Risk</option>
                                <option value="moderate" <?php echo (($old['safety_risk'] ?? '') == 'moderate') ? 'selected' : ''; ?>>Moderate Risk</option>
                                <option value="severe" <?php echo (($old['safety_risk'] ?? '') == 'severe') ? 'selected' : ''; ?>>Severe Risk</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="severity" class="form-label">Severity of Damage *</label>
                            <select class="form-select" id="severity" name="severity" required>
                                <option value="moderate" <?php echo (($old['severity'] ?? '') == 'moderate') ? 'selected' : ''; ?>>Moderate - Partially functional</option>
                                <option value="minor" <?php echo (($old['severity'] ?? '') == 'minor') ? 'selected' : ''; ?>>Minor - Cosmetic</option>
                                <option value="major" <?php echo (($old['severity'] ?? '') == 'major') ? 'selected' : ''; ?>>Major - Significant damage</option>
                                <option value="critical" <?php echo (($old['severity'] ?? '') == 'critical') ? 'selected' : ''; ?>>Critical - Completely non-functional</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="affected_users" class="form-label">Affected Users</label>
                            <select class="form-select" id="affected_users" name="affected_users[]" multiple>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['name']; ?>"
                                        <?php
                                        $oldAff = $old['affected_users'] ?? [];
                                        if (in_array($dept['name'], $oldAff)) echo 'selected';
                                        ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Students" <?php echo (isset($old['affected_users']) && in_array('Students', $old['affected_users'])) ? 'selected' : ''; ?>>Students</option>
                                <option value="Faculty" <?php echo (isset($old['affected_users']) && in_array('Faculty', $old['affected_users'])) ? 'selected' : ''; ?>>Faculty</option>
                                <option value="Staff" <?php echo (isset($old['affected_users']) && in_array('Staff', $old['affected_users'])) ? 'selected' : ''; ?>>Staff</option>
                            </select>
                            <small class="text-muted">Select all that apply</small>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-0">
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2">
                                <i class="fas fa-paper-plane me-2"></i>Submit Report
                            </button>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">Priority will be calculated based on your inputs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
$pageCss = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">';
$pageJs = '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>';
renderPage($pageTitle, $content, '', $pageJs);
