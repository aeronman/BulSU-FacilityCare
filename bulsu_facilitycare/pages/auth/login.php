<?php
$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('/dashboard');
}

$error = $_SESSION['error_message'] ?? '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);

ob_start();
?>

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center p-0">
    <div class="row g-0 min-vh-100">
        <div class="col-lg-6 col-xl-7 d-none d-lg-flex">
            <div class="h-100 w-100 d-flex flex-column justify-content-center align-items-center text-center"
                 style="background: linear-gradient(135deg, #8B0015 0%, #6B0011 100%);">
                <div class="bg-gold rounded-circle d-flex align-items-center justify-content-center mb-4"
                     style="width: 90px; height: 90px;">
                    <i class="fas fa-school fa-3x text-bulsumaroon"></i>
                </div>
                <h1 class="display-4 fw-bold text-white mb-3">BulSU FacilityCare</h1>
                <p class="lead text-white-50 mb-5 px-4" style="max-width: 550px;">
                    <?php echo APP_TAGLINE; ?>
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-5 text-white">
                    <div class="text-center">
                        <div class="display-5 fw-bold">8+</div>
                        <small class="text-white-50">Report Statuses</small>
                    </div>
                    <div class="text-center">
                        <div class="display-5 fw-bold">3</div>
                        <small class="text-white-50">User Roles</small>
                    </div>
                    <div class="text-center">
                        <div class="display-5 fw-bold">7</div>
                        <small class="text-white-50">Priority Factors</small>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <p class="text-white-50 mb-0">Bulacan State University</p>
                    <p class="text-white-50 mb-0">Established 1903</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xl-5 bg-light d-flex align-items-center">
            <div class="card shadow-lg border-0 m-4 w-100" style="max-width: 420px;">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-bulsumaroon mb-1">Welcome Back</h2>
                        <p class="text-muted">Sign in to access your dashboard</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-bulsu">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-bulsu">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/login">
                        <?php echo CSRF::tokenField(); ?>
                        <div class="mb-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Username or Email</label>
                            <input type="text" class="form-control" name="username"
                                   placeholder="Enter your username or email" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-bulsumaroon fw-semibold">Password</label>
                            <input type="password" class="form-control" name="password"
                                   placeholder="Enter your password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-2 fs-6">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="mb-2">
                            Don't have an account?
                            <a href="/register" class="text-bulsumaroon fw-bold">Register here</a>
                        </p>
                        <p class="mb-0">
                            <a href="#" class="text-muted small">Forgot your password?</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderPage('Login - Sign In', $content);
