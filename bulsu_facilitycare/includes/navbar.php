    <nav class="navbar navbar-expand-lg navbar-dark bg-bulsumaroon sticky-top shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="/dashboard">
                <div class="logo-circle me-2 d-flex align-items-center justify-content-center">
                    <i class="fas fa-wrench fa-sm text-gold"></i>
                </div>
                <span class="fw-bold fs-5">BulSU FacilityCare</span>
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
                                <i class="fas fa-task-list me-1"></i> Assigned Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActiveRoute('/all-reports'); ?>" href="/all-reports">
                                <i class="fas fa-clipboard-list me-1"></i> All Reports
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($auth->isAdmin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog me-1"></i> Admin Panel
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark bg-bulsupmaroon-border">
                                <li><a class="dropdown-item" href="/admin/dashboard">Dashboard</a></li>
                                <li><a class="dropdown-item" href="/admin/reports">All Reports</a></li>
                                <li><a class="dropdown-item" href="/admin/validate">Report Validation</a></li>
                                <li><a class="dropdown-item" href="/admin/duplicates">Duplicate Detection</a></li>
                                <li><a class="dropdown-item" href="/admin/priority">Priority Assessment</a></li>
                                <li><a class="dropdown-item" href="/admin/monitoring">Maintenance Monitoring</a></li>
                                <li><a class="dropdown-item" href="/admin/analytics">Reports & Analytics</a></li>
                                <li><a class="dropdown-item" href="/admin/facilities">Facilities</a></li>
                                <li><a class="dropdown-item" href="/admin/users">User Management</a></li>
                                <li><a class="dropdown-item" href="/admin/categories">Categories</a></li>
                                <li><a class="dropdown-item" href="/admin/settings">Settings</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php
                            $func = new Functions();
                            $unreadCount = $func->getUnreadNotificationCount($currentUser['id']);
                            if ($unreadCount > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-gold text-dark">
                                    <?php echo $unreadCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end background-white">
                            <li class="dropdown-header fw-bold">Notifications</li>
                            <?php
                            $notifications = $func->getNotifications($currentUser['id'], 5);
                            if (empty($notifications)): ?>
                                <li><span class="dropdown-item text-muted">No new notifications</span></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li>
                                        <a class="dropdown-item <?php echo $notif['is_read'] ? '' : 'fw-bold'; ?>"
                                           href="<?php echo $notif['url'] ?? '#'; ?>">
                                            <small><?php echo htmlspecialchars($notif['title']); ?></small>
                                            <div class="text-muted small mt-1">
                                                <?php echo timeAgo($notif['created_at']); ?>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/notifications">View all</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-placeholder bg-gold text-bulsupmaroon d-flex align-items-center justify-content-center me-2">
                                <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                            </div>
                            <span class="d-none d-lg-block"><?php echo $currentUser['full_name']; ?></span>
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
