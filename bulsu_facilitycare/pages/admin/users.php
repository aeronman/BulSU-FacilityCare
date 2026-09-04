<?php
/**
 * Admin - User Management
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser || !$auth->isAdmin()) {
    redirect('/admin/dashboard');
}

$users = $func->getAllUsers();
$roles = $func->getAllRoles();
$departments = $func->getDepartments();

$pageTitle = 'User Management';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container-fluid py-4">
    <div class="page-header">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard">Admin Dashboard</a></li>
                <li class="breadcrumb-item active">User Management</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle text-muted">
                    Manage system users. Total: <?php echo count($users); ?> users
                </p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="fas fa-plus me-2"></i>Add User
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-bulsu">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $user): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-gold text-bulsumaroon d-flex align-items-center justify-content-center me-2"
                                             style="width: 36px; height: 36px; border-radius: 50%; font-weight: 700; font-size: 12px;">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                            <?php
                                            $roleColors = ['student_staff' => 'info', 'maintenance' => 'warning', 'admin' => 'danger'];
                                            $color = $roleColors[$user['role_name']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $user['role_display']; ?></span>
                                        </td>
                                <td><?php echo htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                                <td>
                                            <?php echo $user['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'; ?>
                                        </td>
                                <td class="text-end">
                                            <a href="/admin/users"
                                               class="btn btn-sm btn-outline-gold"
                                               onclick="editUser(<?php echo $user['id']; ?>)"
                                               data-user='<?php echo json_encode($user); ?>'>
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/users/store" id="userForm">
                <div class="modal-content">
                    <div class="bulsu-modal-header">
                        <h5 class="modal-title" id="userModalTitle">
                            <i class="fas fa-user-plus me-2"></i>Add New User
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="id" id="user_id" value="">
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" id="user_full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Username *</label>
                            <input type="text" class="form-control" name="username" id="user_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Email *</label>
                            <input type="email" class="form-control" name="email" id="user_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Role *</label>
                            <select class="form-select" name="role_name" id="user_role_name" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['name']; ?>"><?php echo $role['display_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Department</label>
                            <select class="form-select" name="department_id" id="user_department_id">
                                <option value="">Select department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Employee ID</label>
                            <input type="text" class="form-control" name="employee_id" id="user_employee_id">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Password</label>
                            <input type="password" class="form-control" name="password" id="user_password">
                            <small class="text-muted">Leave blank to keep current password (min 8 chars for new password)</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="user_is_active" checked>
                                <label class="form-label form-check-label text-bulsumaroon fw-semibold" for="user_is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php echo renderFooter(); ?>

<script>
function editUser(user) {
    document.getElementById('user_id').value = user.id;
    document.getElementById('user_full_name').value = user.full_name;
    document.getElementById('user_username').value = user.username;
    document.getElementById('user_email').value = user.email;
    document.getElementById('user_role_name').value = user.role_name;
    document.getElementById('user_department_id').value = user.department_id || '';
    document.getElementById('user_employee_id').value = user.employee_id || '';
    document.getElementById('user_password').value = '';
    document.getElementById('user_is_active').checked = user.is_active == 1;
    document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-edit me-2"></i>Edit User';
    var modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);
