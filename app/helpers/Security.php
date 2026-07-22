<?php
// ================================================================
// SECURITY HELPER - CSRF, XSS, INPUT VALIDATION
// ================================================================

class Security {
    
    // Generate CSRF Token
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF Token
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // CSRF Hidden Input
    public static function csrfField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generateCsrfToken() . '">';
    }
    
    // Sanitize Input (XSS Prevention)
    public static function clean($data) {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    // Sanitize without HTML encoding (for rich text)
    public static function cleanHtml($data) {
        $allowed = '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4>';
        return strip_tags(trim($data), $allowed);
    }
    
    // Validate Email
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate Phone (Nigerian format)
    public static function isValidPhone($phone) {
        return preg_match('/^(0[789][01]\d{8}|\+234[789][01]\d{8})$/', preg_replace('/\s+/', '', $phone));
    }
    
    // Password Strength Validation
    public static function isStrongPassword($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        return true;
    }
    
    // Hash Password
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    // Verify Password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate Random String
    public static function generateRandomString($length = 10) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $string;
    }
    
    // Rate Limiting (simple session-based)
    public static function rateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
        $now = time();
        if (!isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = ['count' => 0, 'start' => $now];
        }
        
        $data = &$_SESSION['rate_limit'][$key];
        if ($now - $data['start'] > $windowSeconds) {
            $data = ['count' => 0, 'start' => $now];
        }
        
        $data['count']++;
        return $data['count'] <= $maxAttempts;
    }
}