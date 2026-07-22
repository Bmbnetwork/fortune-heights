<?php
// ================================================================
// GLOBAL HELPER FUNCTIONS
// ================================================================

// Escape output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

// Time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($datetime);
}

// Get grade from score
function getGrade($score) {
    foreach (GRADING_SCALE as $range) {
        if ($score >= $range['min'] && $score <= $range['max']) {
            return $range;
        }
    }
    return ['grade' => 'F', 'remark' => 'Fail'];
}

// Get attendance category
function getAttendanceCategory($percentage) {
    if ($percentage >= ATTENDANCE_EXCELLENT) return ['class' => 'excellent', 'label' => 'Excellent', 'color' => '#10b981'];
    if ($percentage >= ATTENDANCE_GOOD) return ['class' => 'good', 'label' => 'Good', 'color' => '#3b82f6'];
    if ($percentage >= ATTENDANCE_FAIR) return ['class' => 'fair', 'label' => 'Fair', 'color' => '#f59e0b'];
    return ['class' => 'at-risk', 'label' => 'At Risk', 'color' => '#ef4444'];
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Display flash
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']);
        $message = e($flash['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show'>
                <i class='fas fa-info-circle'></i> {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// Generate unique ID
function generateId($prefix, $length = 4) {
    $num = random_int(10 ** ($length - 1), 10 ** $length - 1);
    return $prefix . $num;
}

// Redirect
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Get current term
function getCurrentTerm() {
    $db = db();
    $stmt = $db->query("SELECT t.*, s.session_name 
        FROM academic_terms t 
        JOIN academic_sessions s ON t.session_id = s.id 
        WHERE t.is_current = 1 LIMIT 1");
    return $stmt->fetch();
}

// Get current session
function getCurrentSession() {
    $db = db();
    $stmt = $db->query("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
    return $stmt->fetch();
}

// Count records
function countRecords($table, $where = '1=1', $params = []) {
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    return $stmt->fetch()['total'] ?? 0;
}

// Pagination
function paginate($total, $perPage = PER_PAGE) {
    $pages = ceil($total / $perPage);
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $currentPage = min($currentPage, $pages ?: 1);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'pages' => $pages,
        'current' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset
    ];
}

// Render pagination links
function renderPagination($pagination, $baseUrl) {
    if ($pagination['pages'] <= 1) return;
    
    echo '<nav><ul class="pagination justify-content-center">';
    
    // Previous
    $prevDisabled = $pagination['current'] <= 1 ? 'disabled' : '';
    $prevPage = $pagination['current'] - 1;
    echo "<li class='page-item {$prevDisabled}'><a class='page-link' href='{$baseUrl}&page={$prevPage}'>Previous</a></li>";
    
    // Pages
    for ($i = 1; $i <= $pagination['pages']; $i++) {
        $active = $i === $pagination['current'] ? 'active' : '';
        echo "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}&page={$i}'>{$i}</a></li>";
    }
    
    // Next
    $nextDisabled = $pagination['current'] >= $pagination['pages'] ? 'disabled' : '';
    $nextPage = $pagination['current'] + 1;
    echo "<li class='page-item {$nextDisabled}'><a class='page-link' href='{$baseUrl}&page={$nextPage}'>Next</a></li>";
    
    echo '</ul></nav>';
}

// Get unread notifications count
function getUnreadNotificationsCount() {
    if (!Auth::check()) return 0;
    return countRecords('notifications', 'user_id = ? AND user_type = ? AND is_read = 0', 
        [Auth::id(), Auth::role()]);
}

// Get unread messages count
function getUnreadMessagesCount() {
    if (!Auth::check()) return 0;
    return countRecords('messages', 'receiver_id = ? AND receiver_type = ? AND is_read = 0', 
        [Auth::id(), Auth::role()]);
}

// Calculate attendance percentage
function calculateAttendancePercentage($studentId, $termId = null) {
    $db = db();
    
    if ($termId) {
        $stmt = $db->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
            FROM attendance WHERE student_id = ? AND term_id = ?");
        $stmt->execute([$studentId, $termId]);
    } else {
        $stmt = $db->prepare("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
            FROM attendance WHERE student_id = ?");
        $stmt->execute([$studentId]);
    }
    
    $data = $stmt->fetch();
    if (!$data || $data['total'] == 0) return 0;
    
    return round((($data['present'] + $data['late']) / $data['total']) * 100, 2);
}

// File upload handler
function handleFileUpload($file, $folder = 'avatars', $allowedTypes = ['jpg','jpeg','png','gif'], $maxSize = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = UPLOAD_PATH . '/' . $folder . '/' . $filename;
    
    if (!is_dir(UPLOAD_PATH . '/' . $folder)) {
        mkdir(UPLOAD_PATH . '/' . $folder, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $folder . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move file'];
}

// Calculate percentage safely
function getPercentage($part, $total) {
    if ($total <= 0) return 0;
    return round(($part / $total) * 100, 1);
}

// JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}