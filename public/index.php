<?php
// ================================================================
// FORTUNE HEIGHTS MONTESSORI SCHOOL
// Main Entry Point - Front Controller (LOOP-SAFE VERSION)
// ================================================================

require_once dirname(__DIR__) . '/config/constants.php';
require_once CONFIG_PATH . '/database.php';
require_once CONFIG_PATH . '/session.php';
require_once APP_PATH . '/helpers/Functions.php';
require_once APP_PATH . '/helpers/Security.php';
require_once APP_PATH . '/helpers/Auth.php';

foreach (glob(APP_PATH . '/models/*.php') as $model) require_once $model;
foreach (glob(APP_PATH . '/controllers/*.php') as $controller) require_once $controller;

$page   = $_GET['page']   ?? (Auth::check() ? 'dashboard' : 'login');
$action = $_GET['action'] ?? 'list';
$id     = $_GET['id']     ?? null;

$publicPages = ['login', 'logout'];

// 🔒 REDIRECT LOOP PREVENTION: Track redirect count
if (!isset($_SESSION['_redirect_count'])) {
    $_SESSION['_redirect_count'] = 0;
}
if (!isset($_SESSION['_redirect_page'])) {
    $_SESSION['_redirect_page'] = '';
}

// If same page redirects 3+ times, force logout to break loop
if ($_SESSION['_redirect_page'] === $page) {
    $_SESSION['_redirect_count']++;
    if ($_SESSION['_redirect_count'] >= 3) {
        $_SESSION['_redirect_count'] = 0;
        $_SESSION['_redirect_page'] = '';
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?page=login&loop=detected');
        exit;
    }
} else {
    $_SESSION['_redirect_count'] = 0;
    $_SESSION['_redirect_page'] = $page;
}

try {
    if (!in_array($page, $publicPages) && !Auth::check()) {
        redirect(BASE_URL . '/index.php?page=login');
    }

    switch ($page) {

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                handleLogin();
            } else {
                require APP_PATH . '/views/auth/login.php';
            }
            break;

        case 'logout':
            Auth::logout();
            redirect(BASE_URL . '/index.php?page=login');
            break;

        // ✅ FIXED: Dashboard with safe file checking
        case 'dashboard':
            Auth::requireAuth();
            $role = Auth::role();
            $dashboardFile = APP_PATH . "/views/{$role}/dashboard.php";
            
            if (file_exists($dashboardFile)) {
                require $dashboardFile;
            } else {
                // Don't redirect back to dashboard - show error instead
                echo "<div style='font-family:Arial;max-width:600px;margin:80px auto;padding:30px;background:#fee2e2;border-radius:12px;border-left:5px solid #ef4444;'>";
                echo "<h2 style='color:#991b1b;margin:0 0 15px;'>⚠️ Dashboard Not Ready</h2>";
                echo "<p style='color:#7f1d1d;'>The <strong>" . ucfirst($role) . " Dashboard</strong> is being set up.</p>";
                echo "<p style='color:#7f1d1d;'>Missing file: <code>views/{$role}/dashboard.php</code></p>";
                echo "<p><a href='?page=logout' style='background:#1e40af;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;margin-top:10px;'>Logout</a></p>";
                echo "</div>";
                exit;
            }
            break;

        case 'admins':
        case 'teachers':
        case 'parents':
        case 'students':
            Auth::requireRole('admin');
            handleUserManagement($page, $action, $id);
            break;

        case 'classes':
            Auth::requireRole('admin');
            if ($action === 'add' || $action === 'edit') {
                require APP_PATH . '/views/admin/class_form.php';
            } elseif ($action === 'delete') {
                handleSimpleDelete('classes', $id, 'Class', '?page=classes');
            } else {
                require APP_PATH . '/views/admin/classes.php';
            }
            break;

        case 'subjects':
            Auth::requireRole('admin');
            if ($action === 'add' || $action === 'edit') {
                require APP_PATH . '/views/admin/subject_form.php';
            } elseif ($action === 'delete') {
                handleSimpleDelete('subjects', $id, 'Subject', '?page=subjects');
            } else {
                require APP_PATH . '/views/admin/subjects.php';
            }
            break;

        case 'sessions':
            Auth::requireRole('admin');
            if ($action === 'add_session' || $action === 'edit_session') {
                require APP_PATH . '/views/admin/session_form.php';
            } elseif ($action === 'add_term' || $action === 'edit_term') {
                require APP_PATH . '/views/admin/term_form.php';
            } elseif ($action === 'delete_session') {
                handleSimpleDelete('academic_sessions', $id, 'Session', '?page=sessions');
            } elseif ($action === 'delete_term') {
                handleSimpleDelete('academic_terms', $id, 'Term', '?page=sessions');
            } else {
                require APP_PATH . '/views/admin/sessions.php';
            }
            break;

        case 'announcements':
            Auth::requireAuth();
            if (in_array($action, ['create', 'edit'])) {
                Auth::requireRole('admin');
                require APP_PATH . '/views/admin/announcement_form.php';
            } elseif ($action === 'delete') {
                Auth::requireRole('admin');
                handleSimpleDelete('announcements', $id, 'Announcement', '?page=announcements');
            } else {
                $role = Auth::role();
                if (file_exists(APP_PATH . "/views/{$role}/announcements.php")) {
                    require APP_PATH . "/views/{$role}/announcements.php";
                } else {
                    require APP_PATH . "/views/admin/announcements.php";
                }
            }
            break;

        case 'messages':
    Auth::requireAuth();
    (new MessageController())->index();
    break;

case 'message-send':
    Auth::requireAuth();
    (new MessageController())->send();
    break;

        case 'attendance':
            Auth::requireAuth();
            $controller = new AttendanceController();
            if (Auth::role() === 'parent') {
                $controller->parentView();
            } elseif (Auth::role() === 'teacher') {
                $controller->mark();
            } else {
                redirect('?page=attendance-reports');
            }
            break;

        case 'attendance-save':
            Auth::requireRole('teacher');
            (new AttendanceController())->save();
            break;

        case 'attendance-history':
            Auth::requireRole('teacher');
            (new AttendanceController())->history();
            break;

        case 'attendance-analytics':
            Auth::requireRole('teacher');
            require APP_PATH . '/views/teacher/analytics.php';
            break;

        case 'attendance-reports':
            Auth::requireRole('admin');
            require APP_PATH . '/views/admin/attendance-reports.php';
            break;

        case 'results':
    Auth::requireAuth();
    $controller = new ResultController();
    if (Auth::role() === 'teacher') {
        $controller->upload();
    } elseif (Auth::role() === 'parent') {
        $controller->parentView();
    } else {
        redirect('?page=academic-reports');
    }
    break;

        case 'academic-reports':
            Auth::requireRole('admin');
            require APP_PATH . '/views/admin/academic-reports.php';
            break;

        case 'analytics':
            Auth::requireRole('admin');
            require APP_PATH . '/views/admin/analytics.php';
            break;

        case 'my-students':
    Auth::requireRole('teacher');
    require APP_PATH . '/views/teacher/my_students.php';
    break;

        case 'feedback':
            Auth::requireAuth();
            $role = Auth::role();
            if (file_exists(APP_PATH . "/views/{$role}/feedback.php")) {
                require APP_PATH . "/views/{$role}/feedback.php";
            } else {
                require APP_PATH . '/views/parent/feedback.php';
            }
            break;

        case 'child-profile':
case 'report-card':
case 'events':
    Auth::requireRole('parent');
    
    // Map hyphenated URLs to underscored file names
    $fileMap = [
        'child-profile' => 'child_profile',
        'report-card'   => 'report_card',
        'events'        => 'events'
    ];
    
    $fileName = $fileMap[$page] ?? $page;
    $filePath = APP_PATH . "/views/parent/{$fileName}.php";
    
    if (file_exists($filePath)) {
        require $filePath;
    } else {
        setFlash('danger', "Page not found: {$fileName}.php");
        redirect('?page=dashboard');
    }
    break;

        case 'profile':
            Auth::requireAuth();
            $role = Auth::role();
            if (file_exists(APP_PATH . "/views/{$role}/profile.php")) {
                require APP_PATH . "/views/{$role}/profile.php";
            } else {
                require APP_PATH . '/views/admin/profile.php';
            }
            break;

        case 'notifications':
            Auth::requireAuth();
            if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode([
                    'unread_count' => getUnreadNotificationsCount(),
                    'unread_messages' => getUnreadMessagesCount()
                ]);
                exit;
            }
            if (file_exists(APP_PATH . '/views/layouts/notifications.php')) {
                require APP_PATH . '/views/layouts/notifications.php';
            } else {
                redirect('?page=dashboard');
            }
            break;

        case 'mark-notification-read':
            Auth::requireAuth();
            if ($id) {
                $db = db();
                $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND user_type = ?")
                   ->execute([$id, Auth::id(), Auth::role()]);
            }
            redirect($_SERVER['HTTP_REFERER'] ?? '?page=dashboard');
            break;

        case 'mark-all-notifications-read':
            Auth::requireAuth();
            $db = db();
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ?")
               ->execute([Auth::id(), Auth::role()]);
            redirect('?page=notifications');
            break;

        case 'activity-logs':
            Auth::requireRole('admin');
            if (file_exists(APP_PATH . '/views/admin/activity_logs.php')) {
                require APP_PATH . '/views/admin/activity_logs.php';
            } else {
                redirect('?page=dashboard');
            }
            break;

        case 'unauthorized':
            http_response_code(403);
            echo '<h1 style="text-align:center;margin-top:100px;color:#ef4444;">403 - Unauthorized Access</h1>';
            echo '<p style="text-align:center;"><a href="?page=dashboard">Back to Dashboard</a></p>';
            break;

        default:
            http_response_code(404);
            echo '<h1 style="text-align:center;margin-top:100px;color:#6b7280;">404 - Page Not Found</h1>';
            echo '<p style="text-align:center;"><a href="?page=dashboard">Back to Dashboard</a></p>';
            break;
    }

} catch (Exception $e) {
    error_log('Application Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    
    // 🔒 BREAK THE LOOP: Don't redirect to dashboard if we're already there
    if ($page === 'dashboard') {
        echo "<div style='font-family:Arial;max-width:600px;margin:80px auto;padding:30px;background:#fee2e2;border-radius:12px;'>";
        echo "<h2 style='color:#991b1b;'>⚠️ System Error</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><a href='?page=logout' style='background:#1e40af;color:white;padding:10px 20px;border-radius:6px;text-decoration:none;'>Logout & Retry</a></p>";
        echo "</div>";
        exit;
    }
    
    if (Auth::check()) {
        setFlash('danger', 'An error occurred: ' . $e->getMessage());
        redirect(BASE_URL . '/index.php?page=dashboard');
    } else {
        setFlash('danger', 'System error. Please try again.');
        redirect(BASE_URL . '/index.php?page=login');
    }
}

// ================================================================
// HELPER FUNCTIONS (same as before)
// ================================================================

function handleLogin() {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['login_error'] = 'Invalid request. Please try again.';
        redirect(BASE_URL . '/index.php?page=login');
    }

    if (!Security::rateLimit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 300)) {
        $_SESSION['login_error'] = 'Too many login attempts. Please try again in 5 minutes.';
        redirect(BASE_URL . '/index.php?page=login');
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? '');

    $validRoles = ['admin', 'teacher', 'parent'];
    if (!in_array($role, $validRoles)) {
        $_SESSION['login_error'] = 'Please select a valid role.';
        redirect(BASE_URL . '/index.php?page=login&role=parent');
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = 'Please enter a valid email address.';
        redirect(BASE_URL . '/index.php?page=login&role=' . $role);
    }

    if (empty($password)) {
        $_SESSION['login_error'] = 'Please enter your password.';
        redirect(BASE_URL . '/index.php?page=login&role=' . $role);
    }

    if (Auth::attempt($email, $password, $role)) {
        // Reset redirect counter on successful login
        $_SESSION['_redirect_count'] = 0;
        $_SESSION['_redirect_page'] = '';
        setFlash('success', 'Welcome back, ' . Auth::name() . '!');
        redirect(BASE_URL . '/index.php?page=dashboard');
    } else {
        $_SESSION['login_error'] = 'Invalid email or password. Make sure you selected the correct role tab.';
        redirect(BASE_URL . '/index.php?page=login&role=' . $role);
    }
}

function handleUserManagement($page, $action, $id) {
    $fileMap = ['admins' => 'admin', 'teachers' => 'teacher', 'parents' => 'parent', 'students' => 'student'];
    $singular = $fileMap[$page] ?? rtrim($page, 's');
    $formFile = APP_PATH . "/views/admin/{$singular}_form.php";
    $listFile = APP_PATH . "/views/admin/{$page}.php";
    
    if ($action === 'add' || $action === 'edit') {
        if ($action === 'edit' && !$id) { setFlash('danger', 'Invalid ID'); redirect("?page={$page}"); }
        if (!file_exists($formFile)) { setFlash('danger', "Form file not found"); redirect("?page={$page}"); }
        $GLOBALS['form_action'] = $action;
        $GLOBALS['form_id'] = $id;
        require $formFile;
    } elseif ($action === 'delete') {
        if (!$id) redirect("?page={$page}");
        $db = db();
        $tableMap = ['admins' => 'admins', 'teachers' => 'teachers', 'parents' => 'parents', 'students' => 'students'];
        $table = $tableMap[$page];
        if ($page === 'admins' && $id == Auth::id()) { setFlash('danger', 'Cannot delete yourself'); redirect("?page={$page}"); }
        if ($page === 'parents') {
            $childCount = $db->prepare("SELECT COUNT(*) FROM students WHERE parent_id = ?");
            $childCount->execute([$id]);
            if ($childCount->fetchColumn() > 0) { setFlash('danger', 'Cannot delete parent with children'); redirect("?page={$page}"); }
        }
        try {
            $db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
            setFlash('success', ucfirst($singular) . ' deleted');
        } catch (Exception $e) { setFlash('danger', 'Error: ' . $e->getMessage()); }
        redirect("?page={$page}");
    } else {
        require $listFile;
    }
}

function handleSimpleDelete($table, $id, $label, $redirect) {
    if (!$id) redirect($redirect);
    $db = db();
    try {
        $db->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
        setFlash('success', $label . ' deleted');
    } catch (Exception $e) { setFlash('danger', 'Cannot delete: ' . $e->getMessage()); }
    redirect($redirect);
}