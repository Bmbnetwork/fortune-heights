<?php
$pageTitle = 'Parent Feedback';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();

// Handle response submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond'])) {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=feedback');
    }
    
    $feedbackId = (int)$_POST['feedback_id'];
    $response = Security::cleanHtml($_POST['response']);
    
    if (empty($response)) {
        setFlash('danger', 'Response cannot be empty');
    } else {
        try {
            $stmt = $db->prepare("UPDATE feedback SET response = ?, responded_at = NOW(), status = 'responded' 
                WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$response, $feedbackId, Auth::id()]);
            
            // Notify parent
            $fbData = $db->prepare("SELECT parent_id FROM feedback WHERE id = ?");
            $fbData->execute([$feedbackId]);
            $parentId = $fbData->fetchColumn();
            
            if ($parentId) {
                $db->prepare("INSERT INTO notifications (user_id, user_type, title, message, type, reference_id) 
                    VALUES (?, 'parent', ?, ?, 'feedback', ?)")
                   ->execute([$parentId, 'Teacher Responded to Your Feedback', 
                              substr($response, 0, 100) . '...', $feedbackId]);
            }
            
            setFlash('success', 'Response sent successfully');
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
    redirect('?page=feedback');
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = "f.teacher_id = ?";
$params = [Auth::id()];
if ($filter === 'pending') { $where .= " AND f.status = 'pending'"; }
elseif ($filter === 'responded') { $where .= " AND f.status = 'responded'"; }

// Get feedback
$stmt = $db->prepare("SELECT f.*, s.full_name as student_name, s.class_id, 
    c.class_name, p.full_name as parent_name, p.email as parent_email, p.phone as parent_phone
    FROM feedback f 
    JOIN students s ON f.student_id = s.id 
    JOIN classes c ON s.class_id = c.id 
    JOIN parents p ON f.parent_id = p.id 
    WHERE {$where}
    ORDER BY f.created_at DESC");
$stmt->execute($params);
$feedbackList = $stmt->fetchAll();

// Stats
$pendingCount = countRecords('feedback', "teacher_id = ? AND status = 'pending'", [Auth::id()]);
$respondedCount = countRecords('feedback', "teacher_id = ? AND status = 'responded'", [Auth::id()]);
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-comment-dots"></i> Parent Feedback</h2>
                    <p>View and respond to feedback from parents</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h4>Pending</h4>
                        <div class="value"><?= $pendingCount ?></div>
                        <div class="change negative">Needs response</div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h4>Responded</h4>
                        <div class="value"><?= $respondedCount ?></div>
                        <div class="change">Completed</div>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-info">
                        <h4>Total</h4>
                        <div class="value"><?= count($feedbackList) ?></div>
                        <div class="change">All feedback</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex gap-2" style="flex-wrap:wrap;">
                        <a href="?page=feedback&filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">
                            All (<?= count($feedbackList) ?>)
                        </a>
                        <a href="?page=feedback&filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-warning' : 'btn-secondary' ?>">
                            Pending (<?= $pendingCount ?>)
                        </a>
                        <a href="?page=feedback&filter=responded" class="btn btn-sm <?= $filter === 'responded' ? 'btn-success' : 'btn-secondary' ?>">
                            Responded (<?= $respondedCount ?>)
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Feedback List -->
            <?php if (empty($feedbackList)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>No Feedback Yet</h3>
                            <p>Parent feedback will appear here</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($feedbackList as $fb): ?>
                    <div class="card mb-3" style="border-left:4px solid <?= $fb['status'] === 'pending' ? 'var(--warning)' : 'var(--success)' ?>;">
                        <div class="card-header" style="background:var(--gray-50);">
                            <div style="flex:1;">
                                <div class="d-flex align-center gap-2" style="flex-wrap:wrap;">
                                    <h3 style="margin:0;font-size:16px;"><?= e($fb['subject']) ?></h3>
                                    <span class="badge badge-<?= $fb['status'] === 'pending' ? 'warning' : 'success' ?>">
                                        <?= e(ucfirst($fb['status'])) ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    From: <strong><?= e($fb['parent_name']) ?></strong> 
                                    (<?= e($fb['parent_email']) ?> • <?= e($fb['parent_phone']) ?>)
                                    • Re: <strong><?= e($fb['student_name']) ?></strong> 
                                    (<?= e($fb['class_name']) ?>)
                                </small>
                            </div>
                            <small class="text-muted"><?= timeAgo($fb['created_at']) ?></small>
                        </div>
                        
                        <div class="card-body">
                            <!-- Parent's Message -->
                            <div style="padding:15px;background:var(--gray-50);border-radius:var(--radius);margin-bottom:15px;">
                                <small style="color:var(--gray-500);font-weight:600;">
                                    <i class="fas fa-user"></i> PARENT'S MESSAGE:
                                </small>
                                <p style="margin:8px 0 0;color:var(--gray-800);line-height:1.6;">
                                    <?= nl2br(e($fb['message'])) ?>
                                </p>
                            </div>
                            
                            <!-- Teacher's Response -->
                            <?php if ($fb['status'] === 'responded' && $fb['response']): ?>
                                <div style="padding:15px;background:#d1fae5;border-radius:var(--radius);border-left:3px solid var(--success);">
                                    <small style="color:#065f46;font-weight:600;">
                                        <i class="fas fa-reply"></i> YOUR RESPONSE (<?= formatDateTime($fb['responded_at']) ?>):
                                    </small>
                                    <p style="margin:8px 0 0;color:#065f46;line-height:1.6;">
                                        <?= nl2br(e($fb['response'])) ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Response Form -->
                                <form method="POST">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                    <div class="form-group">
                                        <label><i class="fas fa-reply"></i> Your Response</label>
                                        <textarea name="response" class="form-control" rows="4" required 
                                                  placeholder="Type your response to the parent..."></textarea>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="respond" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Response
                                        </button>
                                        <a href="?page=messages" class="btn btn-secondary">
                                            <i class="fas fa-envelope"></i> Send Direct Message Instead
                                        </a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>