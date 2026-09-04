<?php
/**
 * BulSU FacilityCare - Front Controller / Router
 */

require_once __DIR__ . '/../config/config.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$routes = [
    'GET' => [
        '/login' => 'login',
        '/register' => 'register',
        '/logout' => 'logout',
        '/dashboard' => 'dashboard',
        '/submit-report' => 'submit_report',
        '/my-reports' => 'my_reports',
        '/report/{id}' => 'report_details',
        '/notifications' => 'notifications',
        '/profile' => 'profile',
        '/assigned-reports' => 'assigned_reports',
        '/all-reports' => 'all_reports',
        '/admin/dashboard' => 'admin_dashboard',
        '/admin/reports' => 'admin_reports',
        '/admin/validate' => 'admin_validate',
        '/admin/duplicates' => 'admin_duplicates',
        '/admin/priority' => 'admin_priority',
        '/admin/monitoring' => 'admin_monitoring',
        '/admin/analytics' => 'admin_analytics',
        '/admin/facilities' => 'admin_facilities',
        '/admin/users' => 'admin_users',
        '/admin/categories' => 'admin_categories',
        '/admin/settings' => 'admin_settings',
        '/admin/categories/store' => 'category_store',
        '/api/facility' => 'api_facility',
        '/api/facility/{id}' => 'api_facility',
        '/api/report/{id}' => 'api_report',
    ],
    'POST' => [
        '/login' => 'login_post',
        '/register' => 'register_post',
        '/submit-report' => 'submit_report_post',
        '/report/{id}/comment' => 'add_comment',
        '/report/{id}/status' => 'update_status',
        '/admin/validate/{id}' => 'admin_validate_report',
        '/admin/duplicates/check' => 'admin_check_duplicates',
        '/admin/duplicates/merge' => 'admin_merge_duplicates',
        '/admin/duplicates/not-duplicate' => 'admin_not_duplicate',
        '/admin/facilities/store' => 'facility_store',
        '/admin/categories/store' => 'category_store',
        '/admin/users/store' => 'user_store',
        '/admin/settings/update' => 'settings_update',
        '/maintenance/report/{id}/update' => 'maintenance_update',
    ],
];

function matchRoute($routes, $method, $uri) {
    foreach ($routes[$method] ?? [] as $pattern => $handler) {
        $patternRegex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $patternRegex = '#^' . $patternRegex . '$#';

        if (preg_match($patternRegex, $uri, $matches)) {
            return [
                'handler' => $handler,
                'params' => array_slice($matches, 1),
            ];
        }
    }
    return null;
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

$match = matchRoute($routes, $requestMethod, $requestUri);

if (!$match) {
    http_response_code(404);
    echo '404 Not Found - Page does not exist.';
    exit;
}

$handler = $match['handler'];
$params = $match['params'];

$auth = new Auth();

function requireLogin() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login');
    }
}

function requireRole($roles) {
    global $auth;
    requireLogin();
    if (!$auth->hasRole($roles)) {
        $_SESSION['error_message'] = 'Access denied. Insufficient permissions.';
        redirect('/dashboard');
    }
}

function loadPage($handler, $params = []) {
    global $auth;
    $baseDir = dirname(__DIR__) . '/pages';

    switch ($handler) {
        case 'login':
            if ($auth->isLoggedIn()) redirect('/dashboard');
            require_once $baseDir . '/auth/login.php';
            break;

        case 'login_post':
            require_once $baseDir . '/auth/login_post.php';
            break;

        case 'register':
            if ($auth->isLoggedIn()) redirect('/dashboard');
            require_once $baseDir . '/auth/register.php';
            break;

        case 'register_post':
            require_once $baseDir . '/auth/register_post.php';
            break;

        case 'logout':
            require_once $baseDir . '/auth/logout.php';
            break;

        case 'dashboard':
            requireLogin();
            if ($auth->isAdmin()) {
                require_once $baseDir . '/admin/dashboard.php';
            } elseif ($auth->isMaintenance()) {
                require_once $baseDir . '/maintenance/dashboard.php';
            } else {
                require_once $baseDir . '/student/dashboard.php';
            }
            break;

        case 'submit_report':
            requireLogin();
            require_once $baseDir . '/student/submit_report.php';
            break;

        case 'submit_report_post':
            requireLogin();
            require_once $baseDir . '/student/submit_report_post.php';
            break;

        case 'my_reports':
            requireLogin();
            require_once $baseDir . '/student/my_reports.php';
            break;

        case 'report_details':
            requireLogin();
            require_once $baseDir . '/report_details.php';
            break;

        case 'notifications':
            requireLogin();
            require_once $baseDir . '/notifications.php';
            break;

        case 'profile':
            requireLogin();
            require_once $baseDir . '/profile.php';
            break;

        case 'add_comment':
            requireLogin();
            require_once $baseDir . '/actions/add_comment.php';
            break;

        case 'update_status':
            requireLogin();
            require_once $baseDir . '/actions/update_status.php';
            break;

        case 'assigned_reports':
            requireLogin();
            require_once $baseDir . '/maintenance/assigned_reports.php';
            break;

        case 'all_reports':
            requireLogin();
            require_once $baseDir . '/all_reports.php';
            break;

        case 'maintenance_update':
            requireRole([ROLE_MAINTENANCE, ROLE_ADMIN]);
            require_once $baseDir . '/actions/maintenance_update.php';
            break;

        case 'admin_dashboard':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/dashboard.php';
            break;

        case 'admin_reports':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/reports.php';
            break;

        case 'admin_validate':
            requireRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
            require_once $baseDir . '/admin/validate_reports.php';
            break;

        case 'admin_validate_report':
            requireRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
            require_once $baseDir . '/actions/validate_report.php';
            break;

        case 'admin_duplicates':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/duplicate_reports.php';
            break;

        case 'admin_merge_duplicates':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/merge_duplicates.php';
            break;

        case 'admin_priority':
            requireRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
            require_once $baseDir . '/admin/priority_assessment.php';
            break;

        case 'admin_monitoring':
            requireRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
            require_once $baseDir . '/admin/maintenance_monitoring.php';
            break;

        case 'admin_analytics':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/analytics.php';
            break;

        case 'admin_facilities':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/facilities.php';
            break;

        case 'facility_store':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/facility_store.php';
            break;

        case 'facility_update':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/facility_store.php';
            break;

        case 'category_store':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/category_store.php';
            break;

        case 'api_facility':
            requireLogin();
            require_once $baseDir . '/api/facility.php';
            break;

        case 'api_report':
            requireLogin();
            require_once $baseDir . '/api/report.php';
            break;

        case 'maintenance_update':
            requireRole([ROLE_MAINTENANCE, ROLE_ADMIN]);
            require_once $baseDir . '/actions/maintenance_update.php';
            break;

        case 'admin_users':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/users.php';
            break;

        case 'user_store':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/user_store.php';
            break;

        case 'admin_categories':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/categories.php';
            break;

        case 'admin_settings':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/admin/settings.php';
            break;

        case 'settings_update':
            requireRole(ROLE_ADMIN);
            require_once $baseDir . '/actions/settings_update.php';
            break;

        default:
            http_response_code(404);
            require_once $baseDir . '/errors/404.php';
            exit;
    }
}

loadPage($handler, $params);
