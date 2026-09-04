<?php
/**
 * BulSU FacilityCare - Authentication & Authorization
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($username, $password) {
        $stmt = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.display_name as role_display,
                    d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE (u.username = :username OR u.email = :email) AND u.is_active = 1",
            [
                'username' => $username,
                'email' => $username,
            ]
        );

        if (!$stmt) {
            return false;
        }

        if (password_verify($password, $stmt['password'])) {
            $this->setSession($stmt);
            $this->updateLastLogin($stmt['id']);
            return true;
        }

        return false;
    }

    public function register($data) {
        $errors = [];

        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            [
                'username' => $data['username'],
                'email' => $data['email'],
            ]
        );

        if ($existing) {
            $errors[] = 'Username or email already exists.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $roleId = $this->db->fetch("SELECT id FROM roles WHERE name = 'student_staff'")['id'];

        $userId = $this->db->query(
            "INSERT INTO users (role_id, department_id, student_id, username, email, password, full_name, phone)
             VALUES (:role_id, :dept_id, :student_id, :username, :email, :password, :full_name, :phone)",
            [
                'role_id' => $roleId,
                'dept_id' => $data['department_id'] ?: null,
                'student_id' => $data['student_id'] ?: null,
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?: null,
            ]
        );

        return ['success' => true, 'user_id' => $userId, 'errors' => []];
    }

    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_name'] = $user['role_name'];
        $_SESSION['role_display'] = $user['role_display'];
        $_SESSION['department_name'] = $user['department_name'];
        $_SESSION['login_time'] = time();
        session_regenerate_id(true);
    }

    private function updateLastLogin($userId) {
        $this->db->query("UPDATE users SET updated_at = NOW() WHERE id = :id", ['id' => $userId]);
    }

    public function logout() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        return $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.display_name as role_display,
                    d.name as department_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE u.id = :id",
            ['id' => $_SESSION['user_id']]
        );
    }

    public function hasRole($role) {
        if (!$this->isLoggedIn()) return false;
        $roles = is_array($role) ? $role : [$role];
        return in_array($_SESSION['role_name'], $roles);
    }

    public function isAdmin() {
        return $this->hasRole(ROLE_ADMIN);
    }

    public function isMaintenance() {
        return $this->hasRole(ROLE_MAINTENANCE);
    }

    public function isStudentStaff() {
        return $this->hasRole(ROLE_STUDENT_STAFF);
    }

    public function canValidate() {
        return $this->hasRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
    }

    public function canAssign() {
        return $this->hasRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
    }

    public function canUpdateReport() {
        return $this->hasRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
    }

    public function canAssessPriority() {
        return $this->hasRole([ROLE_ADMIN, ROLE_MAINTENANCE]);
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    public function requireRole($role) {
        $this->requireLogin();
        if (!$this->hasRole($role)) {
            $_SESSION['error_message'] = 'Access denied. Insufficient permissions.';
            header('Location: /dashboard');
            exit;
        }
    }
}
