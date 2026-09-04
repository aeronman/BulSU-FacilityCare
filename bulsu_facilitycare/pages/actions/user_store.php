<?php
/**
 * Store/Update User - POST handler
 */
if (!CSRF::validate()) {
    $_SESSION['error_message'] = 'Invalid CSRF token.';
    redirect('/admin/users');
}

$func = new Functions();
$db = Database::getInstance();

$userId = $_POST['id'] ?? null;
$fullName = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$roleName = $_POST['role_name'] ?? 'student_staff';
$departmentId = $_POST['department_id'] ?: null;
$employeeId = trim($_POST['employee_id'] ?? '');
$password = $_POST['password'] ?? '';
$isActive = isset($_POST['is_active']) ? 1 : 0;

if (empty($fullName) || empty($username) || empty($email)) {
    $_SESSION['error_message'] = 'Full name, username, and email are required.';
    redirect('/admin/users');
}

$roleId = $db->fetch("SELECT id FROM roles WHERE name = :name", ['name' => $roleName]);
if (!$roleId) {
    $_SESSION['error_message'] = 'Invalid role selected.';
    redirect('/admin/users');
}

if ($userId) {
    $existing = $db->fetch("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id", [
        'username' => $username, 'email' => $email, 'id' => $userId
    ]);
    if ($existing) {
        $_SESSION['error_message'] = 'Username or email already exists for another user.';
        redirect('/admin/users');
    }

    $updates = "full_name = :full_name, username = :username, email = :email,
                role_id = :role_id, department_id = :department_id, employee_id = :emp_id,
                is_active = :is_active";

    $params = [
        'full_name' => $fullName, 'username' => $username, 'email' => $email,
        'role_id' => $roleId['id'], 'department_id' => $departmentId,
        'emp_id' => $employeeId ?: null, 'is_active' => $isActive, 'id' => $userId
    ];

    if (!empty($password)) {
        if (strlen($password) < 8) {
            $_SESSION['error_message'] = 'Password must be at least 8 characters.';
            redirect('/admin/users');
        }
        $updates .= ", password = :password";
        $params['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $db->query("UPDATE users SET $updates WHERE id = :id", $params);
    $_SESSION['success_message'] = 'User updated successfully.';
} else {
    $existing = $db->fetch("SELECT id FROM users WHERE username = :username OR email = :email", [
        'username' => $username, 'email' => $email
    ]);
    if ($existing) {
        $_SESSION['error_message'] = 'Username or email already exists.';
        redirect('/admin/users');
    }

    if (empty($password)) {
        $_SESSION['error_message'] = 'Password is required for new users.';
        redirect('/admin/users');
    }

    if (strlen($password) < 8) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters.';
        redirect('/admin/users');
    }

    $db->query(
        "INSERT INTO users (role_id, department_id, employee_id, username, email, password, full_name, is_active)
         VALUES (:role_id, :dept_id, :emp_id, :username, :email, :password, :full_name, :is_active)",
        [
            'role_id' => $roleId['id'], 'dept_id' => $departmentId, 'emp_id' => $employeeId ?: null,
            'username' => $username, 'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $fullName, 'is_active' => $isActive,
        ]
    );
    $_SESSION['success_message'] = 'User created successfully.';
}

redirect('/admin/users');
