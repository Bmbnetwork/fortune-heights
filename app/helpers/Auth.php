<?php
// ================================================================
// AUTHENTICATION HELPER - FIXED VERSION
// ================================================================

require_once APP_PATH . '/helpers/Security.php';

class Auth {
    
    /**
     * Attempt login with improved error handling
     */
    public static function attempt($email, $password, $role) {
        $db = db();
        
        // Validate role
        $validRoles = ['admin', 'teacher', 'parent'];
        if (!in_array($role, $validRoles)) {
            error_log("Login attempt with invalid role: {$role}");
            return false;
        }
        
        $table = self::getTableByRole($role);
        
        try {
            // Query user by email
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // User not found
            if (!$user) {
                error_log("Login failed: No {$role} found with email {$email}");
                return false;
            }
            
            // Account inactive
            if (!isset($user['is_active']) || $user['is_active'] != 1) {
                error_log("Login failed: {$role} account {$email} is inactive");
                return false;
            }
            
            // Verify password
            if (!Security::verifyPassword($password, $user['password'])) {
                error_log("Login failed: Wrong password for {$role} {$email}");
                return false;
            }
            
            // === LOGIN SUCCESSFUL ===
            
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $role;
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? 'default.png';
            $_SESSION['login_time'] = time();
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            
            // Update last login
            $db->prepare("UPDATE {$table} SET last_login = NOW() WHERE id = ?")
               ->execute([$user['id']]);
            
            // Log activity
            self::logActivity($user['id'], $role, 'login', 'User logged in successfully');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Login error for {$role} {$email}: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user is logged in
    public static function check() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
    
    // Get current user ID
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current user role
    public static function role() {
        return $_SESSION['user_role'] ?? null;
    }
    
    // Get current user name
    public static function name() {
        return $_SESSION['user_name'] ?? 'Guest';
    }
    
    // Check specific role
    public static function isRole($role) {
        return self::check() && self::role() === $role;
    }
    
    // Require authentication
    public static function requireAuth() {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }
    }
    
    // Require specific role
    public static function requireRole($role) {
        self::requireAuth();
        if (!self::isRole($role)) {
            header('Location: ' . BASE_URL . '/index.php?page=unauthorized');
            exit;
        }
    }
    
    // Logout
    public static function logout() {
        if (self::check()) {
            self::logActivity(self::id(), self::role(), 'logout', 'User logged out');
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    // Get table by role
    private static function getTableByRole($role) {
        return match($role) {
            'admin'   => 'admins',
            'teacher' => 'teachers',
            'parent'  => 'parents',
            default   => throw new Exception("Invalid role: {$role}")
        };
    }
    
    // Log activity
    public static function logActivity($userId, $userType, $action, $description = '') {
        try {
            $db = db();
            $stmt = $db->prepare("INSERT INTO activity_logs 
                (user_id, user_type, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $userType,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
            ]);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }
    
    // Get current user full data
    public static function user() {
        if (!self::check()) return null;
        
        try {
            $db = db();
            $table = self::getTableByRole(self::role());
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
            $stmt->execute([self::id()]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }
}