<?php
$auth = new Auth();

if ($auth->isLoggedIn()) {
    redirect('/dashboard');
}

$departments = (new Functions())->getDepartments();
$error = '';
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

ob_start();
?>

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="logo-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="fas fa-user-plus fa-2x text-bulsumaroon"></i>
                        </div>
                        <h2 class="fw-bold text-bulsumaroon mb-1">Create Account</h2>
                        <p class="text-muted">Register as a Student, Faculty, or Staff member</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-bulsu">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['register_errors']) && !empty($_SESSION['register_errors'])): ?>
                        <div class="alert alert-danger alert-bulsu">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['register_errors'] as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['register_errors']); ?>
                    <?php endif; ?>

                    <form method="POST" action="/register">
                        <?php echo CSRF::tokenField(); ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Full Name</label>
                                <input type="text" class="form-control" name="full_name" placeholder="Juan Del Cruz" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Username</label>
                                <input type="text" class="form-control" name="username"
                                       placeholder="username" required pattern="[a-zA-Z0-9_]{3,20}">
                                <small class="text-muted">Letters, numbers, and underscore only (3-20 chars)</small>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email"
                                   placeholder="you@bulsu.edu.ph" required>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Password</label>
                                <input type="password" class="form-control" name="password"
                                       placeholder="Min 8 characters" required minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-bulsumaroon fw-semibold">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password"
                                       placeholder="Re-type password" required minlength="8">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Department</label>
                            <select class="form-select" name="department_id">
                                <option value="">Select a department (optional)</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Student ID (if applicable)</label>
                            <input type="text" class="form-control" name="student_id"
                                   placeholder="e.g., STU-2024-0001">
                        </div>

                        <div class="mt-3">
                            <label class="form-label text-bulsumaroon fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" name="phone"
                                   placeholder="09XXXXXXXXX">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fs-6">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="mb-0">
                            Already have an account?
                            <a href="/login" class="text-bulsumaroon fw-bold">Sign in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
renderPage('Register', $content);
