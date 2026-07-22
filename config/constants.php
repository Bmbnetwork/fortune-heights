<?php
// ================================================================
// FORTUNE HEIGHTS MONTESSORI SCHOOL - SYSTEM CONSTANTS
// ================================================================

// School Information
define('SCHOOL_NAME', 'Fortune Heights Montessori School');
define('SCHOOL_ADDRESS', 'Ijokodo, Ibadan, Oyo State, Nigeria');
define('SCHOOL_EMAIL', 'info@fortuneheights.edu.ng');
define('SCHOOL_PHONE', '+234 801 234 5678');
define('SCHOOL_LOGO', '/assets/images/logo.png');

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('PUBLIC_PATH', BASE_PATH . '/public');

// URLs
define('BASE_URL', 'http://localhost/fortune-heights/public');
define('ASSETS_URL', BASE_URL . '/assets');

// Pagination
define('PER_PAGE', 20);

// Attendance Thresholds
define('ATTENDANCE_EXCELLENT', 95);
define('ATTENDANCE_GOOD', 85);
define('ATTENDANCE_FAIR', 75);
define('ATTENDANCE_AT_RISK', 75);

// Grading System
define('GRADING_SCALE', [
    ['min' => 80, 'max' => 100, 'grade' => 'A', 'remark' => 'Excellent'],
    ['min' => 70, 'max' => 79,  'grade' => 'B', 'remark' => 'Very Good'],
    ['min' => 60, 'max' => 69,  'grade' => 'C', 'remark' => 'Good'],
    ['min' => 50, 'max' => 59,  'grade' => 'D', 'remark' => 'Fair'],
    ['min' => 40, 'max' => 49,  'grade' => 'E', 'remark' => 'Pass'],
    ['min' => 0,  'max' => 39,  'grade' => 'F', 'remark' => 'Fail'],
]);

// Session name
define('SESSION_NAME', 'fhms_session');

// Timezone
date_default_timezone_set('Africa/Lagos');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);