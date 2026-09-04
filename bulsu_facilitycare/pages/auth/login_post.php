<?php
/**
 * Login POST handler
 */
if (!CSRF::validate()) {
    redirect('/login');
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error_message'] = 'Please enter both username and password.';
    redirect('/login');
}

$auth = new Auth();

if ($auth->login($username, $password)) {
    $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
    unset($_SESSION['redirect_after_login']);
    redirect($redirect);
} else {
    $_SESSION['error_message'] = 'Invalid username or password. Please try again.';
    redirect('/login');
}
