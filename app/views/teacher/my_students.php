<?php
$pageTitle = 'My Students';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();
$term = getCurrentTerm();
$classId = $teacher['class_id'];

if (!$classId) {
    setFlash('warning', 'You are not assigned to any class. Contact admin.');
}

// Get class info
$class = null;
$students = [];
if ($classId) {
    $classStmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
    $classStmt->execute([$classId]);
    $class = $classStmt->fetch();
    
    // Get students with attendance and performance data
    $stmt = $db->prepare("SELECT 
        s.id, s.full_name, s.admission_no, s.gender, s.date_of_birth,
        s.address, s.medical_info, s.admission_date,
        p.full_name as parent_name, p.phone as parent_phone, p.email as parent_email,
        COUNT(a.id) as total_days,
        SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present_days
        FROM students s
        JOIN parents p ON s.parent_id = p.id
        LEFT JOIN attendance a ON s.id = a.student_id " . ($term ? "AND a.term_id = {$term['id']}" : "") . "
        WHERE s.class_id = ? AND s.is_active = 1
        GROUP BY s.id
        ORDER BY s.full_name");
    $stmt->execute([$classId]);
    $students = $stmt->fetchAll();
    
    // Calculate average performance
    foreach ($students as &$student) {
        $student['attendance_pct'] = $student['total_days'] > 0 
            ? round(($student['present_days'] / $student['total_days']) * 100, 1) 
            : 0;
        $student['category'] = getAttendanceCategory($student['attendance_pct']);
        
        // Get average score
        $avgStmt = $db->prepare("SELECT AVG(total_score) as avg_score FROM results r 
            JOIN subjects sub ON r.subject_id = sub.id 
            WHERE r.student_id = ? AND sub.teacher_id = ? " . ($term ? "AND r.term_id = {$term['id']}" : ""));
        $avgStmt->execute([$student['id'], Auth::id()]);
        $student['avg_score'] = round($avgStmt->fetchColumn() ?: 0, 1);
    }
}

// Search
$search = $_GET['search'] ?? '';
if ($search && !empty($students)) {
    $students = array_filter($students, function($s) use ($search) {
        return stripos($s['full_name'], $search) !== false || stripos($s['admission_no'], $search) !== false;
    });
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-graduate"></i> My Students</h2>
                    <p><?= $class ? e($class['class_name']) . ' • ' . count($students) . ' students' : 'No class assigned' ?></p>
                </div>
                <?php if ($class): ?>
                    <a href="?page=attendance&class_id=<?= $classId ?>" class="btn btn-primary">
                        <i class="fas fa-clipboard-check"></i> Mark Attendance
                    </a>
                <?php endif; ?>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (!$class): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-school"></i>
                            <h3>No Class Assigned</h3>
                            <p>Please contact the administrator to be assigned to a class.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Search -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <input type="hidden" name="page" value="my-students">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name or admission no..." 
                                   value="<?= e($search) ?>" style="max-width:400px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if ($search): ?>
                                <a href="?page=my-students" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <!-- Students Grid -->
                <?php if (empty($students)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <p>No students found</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
                        <?php foreach ($students as $student): 
                            $age = $student['date_of_birth'] 
                                ? floor((time() - strtotime($student['date_of_birth'])) / (365.25 * 24 * 3600)) 
                                : 0;
                        ?>
                            <div class="card" style="transition:var(--transition);">
                                <div class="card-body">
                                    <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
                                        <div class="user-avatar" style="width:55px;height:55px;font-size:20px;background:linear-gradient(135deg,<?= $student['gender'] === 'Male' ? '#3b82f6,#1e40af' : '#ec4899,#be185d' ?>);">
                                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <h4 style="margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($student['full_name']) ?></h4>
                                            <small class="text-muted">
                                                <code><?= e($student['admission_no']) ?></code> • 
                                                <?= e($student['gender']) ?> • 
                                                <?= $age ?> yrs
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Stats -->
                                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:15px;">
                                        <div style="padding:10px;background:var(--gray-50);border-radius:var(--radius);text-align:center;">
                                            <small class="text-muted d-block">Attendance</small>
                                            <strong style="color:<?= $student['category']['color'] ?>;font-size:18px;">
                                                <?= $student['attendance_pct'] ?>%
                                            </strong>
                                        </div>
                                        <div style="padding:10px;background:var(--gray-50);border-radius:var(--radius);text-align:center;">
                                            <small class="text-muted d-block">Avg Score</small>
                                            <strong style="color:var(--primary);font-size:18px;">
                                                <?= $student['avg_score'] ?>%
                                            </strong>
                                        </div>
                                    </div>
                                    
                                    <!-- Parent Info -->
                                    <div style="padding:10px;background:var(--gray-50);border-radius:var(--radius);font-size:12px;margin-bottom:12px;">
                                        <div style="margin-bottom:3px;">
                                            <i class="fas fa-user text-primary"></i> 
                                            <strong><?= e($student['parent_name']) ?></strong>
                                        </div>
                                        <div style="margin-bottom:3px;">
                                            <i class="fas fa-phone text-success"></i> <?= e($student['parent_phone']) ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-envelope text-info"></i> <?= e($student['parent_email']) ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($student['medical_info'])): ?>
                                        <div style="padding:8px;background:#fef3c7;border-radius:var(--radius);font-size:11px;color:#92400e;margin-bottom:10px;">
                                            <i class="fas fa-notes-medical"></i> <?= e(substr($student['medical_info'], 0, 60)) ?>...
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <div style="display:flex;gap:6px;">
                                        <a href="?page=messages&contact=<?= $student['parent_id'] ?? '' ?>&type=parent" 
                                           class="btn btn-sm btn-primary" style="flex:1;"
                                           onclick="event.stopPropagation();">
                                            <i class="fas fa-envelope"></i> Message
                                        </a>
                                        <a href="?page=attendance-history&class_id=<?= $classId ?>" 
                                           class="btn btn-sm btn-secondary" style="flex:1;">
                                            <i class="fas fa-clipboard-check"></i> Records
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>