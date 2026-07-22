<?php
// ================================================================
// SESSION CONFIGURATION
// ================================================================

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    // ini_set('session.cookie_secure', 1); // Enable in production with HTTPS
    
    session_name(SESSION_NAME);
    session_start();
}

// Regenerate session ID every 30 minutes to prevent fixation
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}