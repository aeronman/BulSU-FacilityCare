<?php
/**
 * Logout handler
 */
$auth = new Auth();
$auth->logout();
$_SESSION['success_message'] = 'You have been logged out successfully.';
redirect('/login');
