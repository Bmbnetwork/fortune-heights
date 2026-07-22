<?php
$pageTitle = 'Parent Dashboard';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$parent = Auth::user();
$term = getCurrentTerm();

// Get parent's children
$stmt = $db->prepare("SELECT s.*, c.class_name FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ? AND s.is_active = 1");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

$totalChildren = count($children);
$unreadMessages = countRecords('messages', 'receiver_id = ? AND receiver_type = ? AND is_read = 0', [Auth::id(), 'parent']);

// Get recent announcements
$announcements = $db->query("SELECT * FROM announcements WHERE status = 'published' AND target_audience IN ('all','parents') ORDER BY published_at DESC LIMIT 5")->fetchAll();

// Get recent messages from teachers
$recentMessages = $db->prepare("SELECT m.*, t.full_name as sender_name 
    FROM messages m JOIN teachers t ON m.sender_id = t.id 
    WHERE m.receiver_id = ? AND m.receiver_type = 'parent' 
    ORDER BY m.created_at DESC LIMIT 5");
$recentMessages->execute([Auth::id()]);
$recentMessages = $recentMessages->fetchAll();

// Child attendance summaries
$childStats = [];
foreach ($children as $child) {
    $percentage = calculateAttendancePercentage($child['id'], $term['id'] ?? null);
    $category = getAttendanceCategory($percentage);
    $childStats[] = [
        'child' => $child,
        'percentage' => $percentage,
        'category' => $category
    ];
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2>Welcome, <?= e($parent['full_name']) ?>! 👋</h2>
                    <p>Monitor your child's progress at <?= e(SCHOOL_NAME) ?></p>
                </div>
                <a href="?page=messages" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Message Teacher
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-child"></i></div>
                    <div class="stat-info">
                        <h4>My Children</h4>
                        <div class="value"><?= $totalChildren ?></div>
                        <div class="change">Registered</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-info">
                        <h4>Unread Messages</h4>
                        <div class="value"><?= $unreadMessages ?></div>
                        <div class="change">From teachers</div>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-bullhorn"></i></div>
                    <div class="stat-info">
                        <h4>Announcements</h4>
                        <div class="value"><?= count($announcements) ?></div>
                        <div class="change">Recent updates</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h4>Current Term</h4>
                        <div class="value" style="font-size:18px;"><?= e($term['term_name'] ?? 'N/A') ?></div>
                        <div class="change"><?= e($term['session_name'] ?? '') ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Children Overview -->
            <?php if (!empty($children)): ?>
                <div class="card mb-4">
                    <div class="card-header"><h3><i class="fas fa-child text-primary"></i> My Children</h3></div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;">
                            <?php foreach ($childStats as $stat): ?>
                                <div style="background:var(--gray-50);border-radius:var(--radius-lg);padding:20px;border:1px solid var(--gray-200);">
                                    <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
                                        <div class="user-avatar" style="width:55px;height:55px;font-size:20px;">
                                            <?= strtoupper(substr($stat['child']['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h4 style="margin:0;"><?= e($stat['child']['full_name']) ?></h4>
                                            <small class="text-muted"><?= e($stat['child']['class_name']) ?> • <?= e($stat['child']['admission_no']) ?></small>
                                        </div>
                                    </div>
                                    
                                    <div style="margin-bottom:15px;">
                                        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
                                            <small>Attendance</small>
                                            <strong style="color:<?= $stat['category']['color'] ?>"><?= $stat['percentage'] ?>%</strong>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width:<?= $stat['percentage'] ?>%;background:<?= $stat['category']['color'] ?>"></div>
                                        </div>
                                        <small style="color:<?= $stat['category']['color'] ?>"><?= $stat['category']['label'] ?></small>
                                    </div>
                                    
                                    <div style="display:flex;gap:8px;">
                                        <a href="?page=attendance" class="btn btn-sm btn-secondary" style="flex:1;text-align:center;">
                                            <i class="fas fa-clipboard-check"></i> Attendance
                                        </a>
                                        <a href="?page=results" class="btn btn-sm btn-primary" style="flex:1;text-align:center;">
                                            <i class="fas fa-graduation-cap"></i> Results
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-child"></i>
                            <p>No children registered yet. Please contact the school admin.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Messages & Announcements -->
            <div class="grid-2">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope text-primary"></i> Recent Messages</h3>
                        <a href="?page=messages" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentMessages)): ?>
                            <div class="empty-state"><i class="fas fa-inbox"></i><p>No messages from teachers</p></div>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $msg): ?>
                                <div style="padding:12px;border-bottom:1px solid var(--gray-100);display:flex;gap:12px;">
                                    <div class="user-avatar" style="width:40px;height:40px;flex-shrink:0;">
                                        <?= strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;justify-content:space-between;">
                                            <strong style="font-size:13px;"><?= e($msg['sender_name']) ?></strong>
                                            <small class="text-muted"><?= timeAgo($msg['created_at']) ?></small>
                                        </div>
                                        <p style="font-size:12px;color:var(--gray-600);margin:3px 0 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?= e($msg['message']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn text-primary"></i> Announcements</h3>
                        <a href="?page=announcements" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <div class="empty-state"><i class="fas fa-bullhorn"></i><p>No announcements</p></div>
                        <?php else: ?>
                            <?php foreach ($announcements as $ann): ?>
                                <div class="announcement-card <?= e($ann['priority']) ?>">
                                    <div class="announcement-title"><?= e($ann['title']) ?></div>
                                    <div class="announcement-content"><?= e(substr($ann['content'], 0, 100)) ?>...</div>
                                    <div class="announcement-meta">
                                        <span><i class="fas fa-clock"></i> <?= timeAgo($ann['published_at']) ?></span>
                                    </div>
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

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>