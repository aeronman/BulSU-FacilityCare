<?php
/**
 * Register POST handler
 */
if (!CSRF::validate()) {
    redirect('/register');
}

$registerData = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'password' => $_POST['password'] ?? '',
    'confirm_password' => $_POST['confirm_password'] ?? '',
    'department_id' => $_POST['department_id'] ?? null,
    'student_id' => trim($_POST['student_id'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
];

$errors = [];

if (empty($registerData['full_name'])) {
    $errors[] = 'Full name is required.';
}
if (empty($registerData['username'])) {
    $errors[] = 'Username is required.';
} elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $registerData['username'])) {
    $errors[] = 'Username must be 3-20 characters (letters, numbers, underscore).';
}
if (empty($registerData['email'])) {
    $errors[] = 'Email is required.';
} elseif (!filter_var($registerData['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if (strlen($registerData['password']) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}
if ($registerData['password'] !== $registerData['confirm_password']) {
    $errors[] = 'Passwords do not match.';
}

if (getSetting('allow_registration', '1') !== '1') {
    $errors[] = 'Registration is currently closed.';
}

if (!empty($errors) || getSetting('allow_registration', '1') !== '1') {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['old_input'] = $registerData;
    redirect('/register');
}

$auth = new Auth();
$result = $auth->register($registerData);

if ($result['success']) {
    $_SESSION['success_message'] = 'Account registered successfully! Please log in.';
    redirect('/login');
} else {
    $_SESSION['register_errors'] = $result['errors'];
    redirect('/register');
}
