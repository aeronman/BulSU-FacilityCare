<?php
/**
 * Notifications Page
 */
$auth = new Auth();
$func = new Functions();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    redirect('/login');
}

$allNotifications = $func->getNotifications($currentUser['id'], 50);
$func->markAllNotificationsRead($currentUser['id']);

$pageTitle = 'Notifications';
ob_start();
?>

<?php echo renderNavbar($auth); ?>

<div class="container py-4">
    <div class="page-header">
        <h1 class="page-title text-bulsumaroon">Notifications</h1>
        <p class="page-subtitle text-muted">All your notifications and alerts</p>
    </div>

    <?php if (empty($allNotifications)): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h4 class="h5 text-bulsumaroon">No notifications</h4>
                <p class="text-muted">You have no notifications at this time.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($allNotifications as $notif): ?>
                        <div class="list-group-item border-0 py-3 notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="row g-0">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-gold text-bulsumaroon d-flex align-items-center justify-content-center"
                                                 style="width: 40px; height: 40px; border-radius: 10px;">
                                                <i class="fas fa-<?php echo getNotificationIcon($notif['type']); ?>"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h4 class="h6 mb-0 fw-semibold"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="badge bg-gold text-bulsumaroon">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <?php if ($notif['report_number']): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($notif['report_number']); ?>
                                                    </span>
                                                    <span class="text-muted small">
                                                        <?php echo htmlspecialchars($notif['report_title'] ?? ''); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted"><?php echo timeAgo($notif['created_at']); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-start justify-content-md-end">
                                    <?php if ($notif['url']): ?>
                                        <a href="<?php echo htmlspecialchars($notif['url']); ?>"
                                           class="btn btn-sm btn-outline-gold">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php echo renderFooter(); ?>

<?php
$content = ob_get_clean();
renderPage($pageTitle, $content);

function getNotificationIcon($type) {
    $icons = [
        NOTIF_REPORT_SUBMITTED => 'file-alt',
        NOTIF_REPORT_VALIDATED => 'check-circle',
        NOTIF_REPORT_ASSIGNED => 'user-check',
        NOTIF_REPORT_STATUS_CHANGE => 'exchange-alt',
        NOTIF_DUPLICATE_FOUND => 'copy',
        NOTIF_PRIORITY_ASSESSED => 'globe',
        NOTIF_MAINTENANCE_UPDATE => 'tools',
        NOTIF_DUPLICATE_MERGED => '-compress',
    ];
    return $icons[$type] ?? 'bell';
}
