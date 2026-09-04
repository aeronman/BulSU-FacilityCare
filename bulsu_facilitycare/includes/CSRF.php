<?php
/**
 * BulSU FacilityCare - CSRF Protection
 */

class CSRF {
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function tokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function validate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!self::validateToken($token)) {
                $_SESSION['error_message'] = 'Invalid or expired token. Please try again.';
                return false;
            }
        }
        return true;
    }

    public static function requireValid() {
        if (!self::validate()) {
            http_response_code(403);
            exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
        }
    }
}
