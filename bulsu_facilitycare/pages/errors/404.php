<?php
/**
 * 404 Error Page
 */
ob_start();
?>

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="text-center">
        <div class="display-1 fw-bold text-bulsumaroon mb-3" style="font-size: 6rem;">404</div>
        <h1 class="h3 fw-bold text-bulsumaroon mb-3">Page Not Found</h1>
        <p class="text-muted mb-4">
            The page you are looking for doesn't exist or has been moved.
        </p>
        <a href="/dashboard" class="btn btn-primary me-2">
            <i class="fas fa-home me-2"></i>Go to Dashboard
        </a>
        <a href="/login" class="btn btn-outline-gold">
            <i class="fas fa-right-to-bracket me-2"></i>Login
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
renderPage('Page Not Found', $content);
