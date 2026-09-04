<?php
/**
 * BulSU FacilityCare - Page Layout Helper
 * Each page sets variables then includes the layout
 */

function renderPage($title, $content, $pageCss = '', $pageJs = '') {
    global $auth, $currentUser;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="BulSU FacilityCare - Facility Maintenance Reporting & Risk Prioritization System">
        <title><?php echo $title . ' | ' . APP_NAME; ?></title>
        <link rel="icon" href="/assets/img/logo.png" type="image/png">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link href="/assets/css/style.css" rel="stylesheet">
        <?php echo $pageCss; ?>
    </head>
    <body class="bg-light">
        <?php echo $content; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js"></script>
        <script src="/assets/js/main.js"></script>
        <?php echo $pageJs; ?>
    </body>
    </html>
    <?php
}

function isActiveRoute($path) {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // For route groups, check if current starts with path or matches
    if ($current === $path) return 'active';
    return '';
}

function showAlert() {
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-bulsu"><i class="fas fa-exclamation-circle me-2"></i>' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-bulsu"><i class="fas fa-check-circle me-2"></i>' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day ago';
    return date('M j, Y', $time);
}

function getReportStatusBadge($code) {
    $labels = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'validated' => 'Validated',
        'assigned' => 'Assigned',
        'ongoing' => 'Ongoing',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'rejected' => 'Rejected',
    ];
    $classes = [
        'submitted' => 'status-submitted',
        'under_review' => 'status-under_review',
        'validated' => 'status-validated',
        'assigned' => 'status-assigned',
        'ongoing' => 'status-ongoing',
        'resolved' => 'status-resolved',
        'closed' => 'status-closed',
        'rejected' => 'status-rejected',
    ];
    $label = $labels[$code] ?? ucfirst($code);
    $class = $classes[$code] ?? 'secondary';
    return '<span class="status-badge ' . $class . '">' . $label . '</span>';
}

function getPriorityBadge($level, $score = null) {
    $labels = [
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];
    $classes = [
        'high' => 'priority-high',
        'medium' => 'priority-medium',
        'low' => 'priority-low',
    ];
    $label = $labels[$level] ?? ucfirst($level);
    $class = $classes[$level] ?? 'secondary';
    $output = '<span class="priority-badge ' . $class . '">' . $label;
    if ($score !== null) {
        $output .= ' <span class="priority-score">(' . $score . ')</span>';
    }
    $output .= '</span>';
    return $output;
}

function getSafetyRiskBadge($level) {
    $labels = ['no' => 'No Risk', 'minor' => 'Minor', 'moderate' => 'Moderate', 'severe' => 'Severe'];
    $classes = ['no' => 'bg-success', 'minor' => 'bg-info', 'moderate' => 'bg-warning', 'severe' => 'bg-danger'];
    return '<span class="badge ' . ($classes[$level] ?? 'bg-secondary') . '">' . ($labels[$level] ?? '') . '</span>';
}

function getSeverityBadge($level) {
    $labels = ['minor' => 'Minor', 'moderate' => 'Moderate', 'major' => 'Major', 'critical' => 'Critical'];
    $classes = ['minor' => 'bg-success', 'moderate' => 'bg-info', 'major' => 'bg-warning', 'critical' => 'bg-danger'];
    return '<span class="badge ' . ($classes[$level] ?? 'bg-secondary') . '">' . ($labels[$level] ?? '') . '</span>';
}

function getUrgencyBadge($level) {
    $labels = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
    $classes = ['low' => 'bg-success', 'medium' => 'bg-info', 'high' => 'bg-warning'];
    return '<span class="badge ' . ($classes[$level] ?? 'bg-secondary') . '">' . ($labels[$level] ?? '') . '</span>';
}

function renderNavbar($auth = null) {
    if (!$auth) $auth = new Auth();
    $currentUser = $auth->getCurrentUser();
    $func = new Functions();
    $unreadCount = $currentUser ? $func->getUnreadNotificationCount($currentUser['id']) : 0;
    $notifications = $currentUser ? $func->getNotifications($currentUser['id'], 6) : [];
    ob_start();
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-bulsumaroon sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="/dashboard">
                <span class="fs-4 text-gold me-1"><i class="fas fa-wrench"></i></span>
                FacilityCare
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($auth->isStudentStaff()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/dashboard'); ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/submit-report'); ?>" href="/submit-report">
                                <i class="fas fa-plus-circle me-1"></i> Submit Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/my-reports'); ?>" href="/my-reports">
                                <i class="fas fa-clipboard-list me-1"></i> My Reports
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($auth->isMaintenance()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/dashboard'); ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/assigned-reports'); ?>" href="/assigned-reports">
                                <i class="fas fa-user-helmet-safety me-1"></i> Assigned Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/all-reports'); ?>" href="/all-reports">
                                <i class="fas fa-clipboard-list me-1"></i> All Reports
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/dashboard'); ?>" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog me-1"></i> Admin Panel
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li><a class="dropdown-item" href="/admin/dashboard">Admin Dashboard</a></li>
                                <li><a class="dropdown-item" href="/admin/reports">All Reports</a></li>
                                <li><a class="dropdown-item" href="/admin/validate">Report Validation</a></li>
                                <li><a class="dropdown-item" href="/admin/duplicates">Duplicate Detection</a></li>
                                <li><a class="dropdown-item" href="/admin/priority">Priority Assessment</a></li>
                                <li><a class="dropdown-item" href="/admin/monitoring">Maintenance Monitoring</a></li>
                                <li><a class="dropdown-item" href="/admin/analytics">Reports & Analytics</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/admin/facilities">Facilities</a></li>
                                <li><a class="dropdown-item" href="/admin/users">User Management</a></li>
                                <li><a class="dropdown-item" href="/admin/categories">Categories</a></li>
                                <li><a class="dropdown-item" href="/admin/settings">Settings</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell fs-5"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                                      style="background: var(--bulsu-gold); color: var(--bulsu-maroon);">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end background-white">
                            <li class="dropdown-header fw-bold text-bulsumaroon">Notifications</li>
                            <?php if (empty($notifications)): ?>
                                <li><span class="dropdown-item text-muted small">No new notifications</span></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>"
                                           href="<?php echo htmlspecialchars($notif['url'] ?? '#'); ?>"
                                           data-notification-id="<?php echo $notif['id']; ?>">
                                            <div class="small"><?php echo htmlspecialchars($notif['title']); ?></div>
                                            <small class="text-muted d-block mt-1">
                                                <?php echo timeAgo($notif['created_at']); ?>
                                            </small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/notifications">View all notifications</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-gold text-bulsumaroon d-flex align-items-center justify-content-center me-2"
                                 style="width: 32px; height: 32px; border-radius: 50%; font-weight: 700; font-size: 12px;">
                                <?php echo strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span class="d-none d-lg-block"><?php echo $currentUser['full_name'] ?? ''; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end background-white">
                            <li><a class="dropdown-item" href="/profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php
    return ob_get_clean();
}

function renderFooter() {
    ob_start();
    ?>
    <footer class="app-footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <span class="fs-3 text-gold me-2"><i class="fas fa-wrench"></i></span>
                        <span class="fw-bold text-white fs-4">BulSU FacilityCare</span>
                    </div>
                    <p class="text-white-50 small mb-0">
                        <?php echo APP_TAGLINE; ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-white-50 small mb-1">
                        &copy; <?php echo date('Y'); ?> Bulacan State University
                    </p>
                    <p class="text-white-50 small mb-0">
                        <i class="fas fa-shield-alt me-1"></i>
                        University Physical Plant & Maintenance Office
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}
