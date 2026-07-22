<?php
$pageTitle = 'Send Feedback';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();

// Get children with their class teachers
$stmt = $db->prepare("SELECT s.id, s.full_name, s.class_id, c.class_name, t.id as teacher_id, t.full_name as teacher_name 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    LEFT JOIN teachers t ON c.id = t.class_id 
    WHERE s.parent_id = ? AND s.is_active = 1");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=feedback');
    }
    
    $childId = (int)$_POST['student_id'];
    $teacherId = (int)$_POST['teacher_id'];
    $subject = Security::clean($_POST['subject']);
    $message = Security::cleanHtml($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        setFlash('danger', 'Please fill in all required fields');
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO feedback (parent_id, teacher_id, student_id, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([Auth::id(), $teacherId, $childId, $subject, $message]);
            
            // Notify teacher
            $db->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, reference_id) VALUES (?, 'teacher', ?, ?, 'feedback', ?)")
               ->execute([$teacherId, 'New Feedback from Parent', substr($message, 0, 100), $db->lastInsertId()]);
            
            setFlash('success', 'Feedback sent successfully! The teacher will respond soon.');
            redirect('?page=feedback');
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Get feedback history
$stmt = $db->prepare("SELECT f.*, s.full_name as student_name, t.full_name as teacher_name, c.class_name 
    FROM feedback f 
    JOIN students s ON f.student_id = s.id 
    JOIN teachers t ON f.teacher_id = t.id 
    JOIN classes c ON s.class_id = c.id 
    WHERE f.parent_id = ? 
    ORDER BY f.created_at DESC LIMIT 20");
$stmt->execute([Auth::id()]);
$feedbackHistory = $stmt->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-comment-dots"></i> Send Feedback</h2>
                    <p>Share your thoughts with your child's teachers</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="grid-2">
                <!-- Feedback Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-paper-plane text-primary"></i> Send New Feedback</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($children)): ?>
                            <div class="empty-state">
                                <i class="fas fa-child"></i>
                                <p>No children registered. Contact admin.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <?= Security::csrfField() ?>
                                
                                <div class="form-group">
                                    <label>Select Child *</label>
                                    <select name="student_id" class="form-control" required id="childSelect">
                                        <option value="">Choose your child...</option>
                                        <?php foreach ($children as $c): ?>
                                            <option value="<?= $c['id'] ?>" data-teacher-id="<?= $c['teacher_id'] ?>" data-teacher-name="<?= e($c['teacher_name'] ?? '') ?>">
                                                <?= e($c['full_name']) ?> - <?= e($c['class_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Teacher *</label>
                                    <input type="text" id="teacherDisplay" class="form-control" readonly placeholder="Auto-selected based on child's class">
                                    <input type="hidden" name="teacher_id" id="teacherId" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Subject *</label>
                                    <select name="subject" class="form-control" required>
                                        <option value="">Select feedback type...</option>
                                        <option value="Academic Progress">Academic Progress</option>
                                        <option value="Behavior Concern">Behavior Concern</option>
                                        <option value="Homework Query">Homework Query</option>
                                        <option value="Attendance Issue">Attendance Issue</option>
                                        <option value="Appreciation">Appreciation / Thank You</option>
                                        <option value="Suggestion">Suggestion</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Your Feedback *</label>
                                    <textarea name="message" class="form-control" rows="6" required placeholder="Please share your feedback, concerns, or suggestions..."></textarea>
                                    <small class="text-muted">Be specific and constructive. The teacher will respond within 48 hours.</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-paper-plane"></i> Send Feedback
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Feedback History -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history text-primary"></i> Feedback History</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($feedbackHistory)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No feedback submitted yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($feedbackHistory as $fb): ?>
                                <div style="padding:15px;border:1px solid var(--gray-200);border-radius:var(--radius);margin-bottom:12px;">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                                        <div>
                                            <strong style="color:var(--primary);"><?= e($fb['subject']) ?></strong>
                                            <small class="text-muted d-block">
                                                To: <?= e($fb['teacher_name']) ?> | 
                                                Re: <?= e($fb['student_name']) ?> (<?= e($fb['class_name']) ?>)
                                            </small>
                                        </div>
                                        <span class="badge badge-<?= $fb['status'] === 'responded' ? 'success' : 'warning' ?>">
                                            <?= e(ucfirst($fb['status'])) ?>
                                        </span>
                                    </div>
                                    <p style="margin:8px 0;font-size:13px;color:var(--gray-700);">
                                        <?= e(substr($fb['message'], 0, 150)) ?><?= strlen($fb['message']) > 150 ? '...' : '' ?>
                                    </p>
                                    <small class="text-muted"><i class="fas fa-clock"></i> <?= timeAgo($fb['created_at']) ?></small>
                                    
                                    <?php if ($fb['status'] === 'responded' && $fb['response']): ?>
                                        <div style="margin-top:10px;padding:10px;background:#d1fae5;border-radius:var(--radius);border-left:3px solid var(--success);">
                                            <small style="color:#065f46;font-weight:600;"><i class="fas fa-reply"></i> Teacher's Response:</small>
                                            <p style="margin:5px 0 0;font-size:13px;color:#065f46;"><?= nl2br(e($fb['response'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
document.getElementById('childSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const teacherId = selected.dataset.teacherId;
    const teacherName = selected.dataset.teacherName;
    
    document.getElementById('teacherId').value = teacherId || '';
    document.getElementById('teacherDisplay').value = teacherName ? teacherName + ' (Class Teacher)' : 'No class teacher assigned';
});
</script>