<?php
/**
 * User Profile Page
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$departments = $func->getDepartments();

if ($_POST && CSRF::validate()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'department_id' => $_POST['department_id'] ?? null,
            'phone' => trim($_POST['phone'] ?? ''),
            'is_active' => 1,
        ];

        $func->updateUser($currentUser['id'], $data);
        $_SESSION['success_message'] = 'Profile updated successfully.';
        redirect('/profile');
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $currentUser['password'])) {
            $_SESSION['error_message'] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $_SESSION['error_message'] = 'New password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['error_message'] = 'Passwords do not match.';
        } else {
            $func->updateUser($currentUser['id'], ['password' => $newPassword], true);
            $_SESSION['success_message'] = 'Password changed successfully.';
        }
        redirect('/profile');
    }
}

$currentUser = $func->getUserById($currentUser['id']);

$pageTitle = 'Profile';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <h1 class="page-title">User Profile</h1>
        <p class="page-subtitle text-muted">Manage your account settings and preferences.</p>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 text-center mb-4">
                <div class="card-body py-4">
                    <div class="bg-gold text-bulsumaroon d-flex align-items-center justify-content-center mx-auto mb-3"
                         style="width: 100px; height: 100px; border-radius: 50%; font-size: 32px; font-weight: 700;">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                    <h3 class="fw-bold text-bulsumaroon"><?php echo htmlspecialchars($currentUser['full_name']); ?></h3>
                    <p class="text-muted"><?php echo $currentUser['role_display']; ?></p>
                    <p class="text-muted small">
                        <i class="fas fa-building me-1"></i>
                        <?php echo htmlspecialchars($currentUser['department_name'] ?? 'Not specified'); ?>
                    </p>
                </div>
                <div class="card-footer bg-light border-0">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="text-bulsumaroon"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-bulsumaroon"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form method="POST" action="/profile">
                <?php echo CSRF::tokenField(); ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-user-edit me-2"></i>Personal Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Full Name</label>
                                <input type="text" class="form-control" name="full_name"
                                       value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Username</label>
                                <input type="text" class="form-control" name="username"
                                       value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" name="phone"
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                        </div>
                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Department</label>
                            <select class="form-select" name="department_id">
                                <option value="">Select department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"
                                        <?php echo ($currentUser['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($currentUser['student_id']): ?>
                            <div class="mt-3">
                                <label class="form-label text-bulsumaroon fw-semibold">Student ID</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($currentUser['student_id']); ?>" readonly>
                            </div>
                        <?php endif; ?>
                        <?php if ($currentUser['employee_id']): ?>
                            <div class="mt-3">
                                <label class="form-label text-bulsumaroon fw-semibold">Employee ID</label>
                                <input type="text" class="form-control"
                                       value="<?php echo htmlspecialchars($currentUser['employee_id']); ?>" readonly>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light border-0 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>

            <form method="POST" action="/profile">
                <?php echo CSRF::tokenField(); ?>
                <input type="hidden" name="action" value="change_password">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h3 class="h5 mb-0 text-bulsumaroon fw-bold">
                            <i class="fas fa-key me-2"></i>Change Password
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">New Password</label>
                            <input type="password" class="form-control" name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="8">
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 text-end">
                        <button type="submit" class="btn btn-outline-gold">
                            <i class="fas fa-key me-2"></i>Update Password
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
