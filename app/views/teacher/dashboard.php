<?php
$pageTitle = 'Teacher Dashboard';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();
$term = getCurrentTerm();

// Teacher's assigned class
$classId = $teacher['class_id'] ?? null;
$className = 'Not Assigned';
$studentCount = 0;

if ($classId) {
    $classData = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
    $classData->execute([$classId]);
    $className = $classData->fetchColumn() ?: 'Unknown';
    $studentCount = countRecords('students', 'class_id = ? AND is_active = 1', [$classId]);
}

// Today's attendance stats
$today = date('Y-m-d');
$todayMarked = 0;
$todayPresent = 0;
if ($classId) {
    $todayMarked = countRecords('attendance', 'class_id = ? AND date = ?', [$classId, $today]);
    $todayPresent = countRecords('attendance', "class_id = ? AND date = ? AND status IN ('Present','Late')", [$classId, $today]);
}

// My subjects count
$mySubjects = countRecords('subjects', 'teacher_id = ?', [Auth::id()]);

// Unread messages
$unreadMessages = countRecords('messages', 'receiver_id = ? AND receiver_type = ? AND is_read = 0', [Auth::id(), 'teacher']);

// Pending feedback
$pendingFeedback = countRecords('feedback', 'teacher_id = ? AND status = ?', [Auth::id(), 'pending']);

// Recent messages
$recentMessages = $db->prepare("SELECT m.*, p.full_name as sender_name 
    FROM messages m JOIN parents p ON m.sender_id = p.id 
    WHERE m.receiver_id = ? AND m.receiver_type = 'teacher' 
    ORDER BY m.created_at DESC LIMIT 5");
$recentMessages->execute([Auth::id()]);
$recentMessages = $recentMessages->fetchAll();

// Recent announcements
$recentAnnouncements = $db->query("SELECT * FROM announcements WHERE status = 'published' AND target_audience IN ('all','teachers') ORDER BY published_at DESC LIMIT 5")->fetchAll();

// Attendance trend (last 7 days)
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $total = countRecords('attendance', 'class_id = ? AND date = ?', [$classId, $date]);
    $present = countRecords('attendance', "class_id = ? AND date = ? AND status IN ('Present','Late')", [$classId, $date]);
    $trendData[] = [
        'day' => date('D', strtotime($date)),
        'rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0
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
                    <h2>Welcome, <?= e($teacher['full_name']) ?>! 👋</h2>
                    <p>
                        <?php if ($classId): ?>
                            Class Teacher: <strong><?= e($className) ?></strong> • <?= $studentCount ?> students
                        <?php else: ?>
                            <span class="text-warning">No class assigned. Contact admin.</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($classId): ?>
                        <a href="?page=attendance&class_id=<?= $classId ?>" class="btn btn-primary">
                            <i class="fas fa-clipboard-check"></i> Mark Attendance
                        </a>
                    <?php endif; ?>
                    <a href="?page=results" class="btn btn-secondary">
                        <i class="fas fa-graduation-cap"></i> Upload Results
                    </a>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h4>My Students</h4>
                        <div class="value"><?= $studentCount ?></div>
                        <div class="change"><?= e($className) ?></div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-info">
                        <h4>My Subjects</h4>
                        <div class="value"><?= $mySubjects ?></div>
                        <div class="change">Assigned to you</div>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-info">
                        <h4>Today's Attendance</h4>
                        <div class="value"><?= $todayPresent ?>/<?= $todayMarked ?: $studentCount ?></div>
                        <div class="change"><?= $todayMarked > 0 ? 'Marked' : 'Not marked yet' ?></div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-info">
                        <h4>Unread Messages</h4>
                        <div class="value"><?= $unreadMessages ?></div>
                        <div class="change"><?= $pendingFeedback ?> pending feedback</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header"><h3><i class="fas fa-bolt text-warning"></i> Quick Actions</h3></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
                        <?php if ($classId): ?>
                            <a href="?page=attendance&class_id=<?= $classId ?>" class="quick-action">
                                <i class="fas fa-clipboard-check"></i><span>Mark Attendance</span>
                            </a>
                        <?php endif; ?>
                        <a href="?page=results" class="quick-action">
                            <i class="fas fa-graduation-cap"></i><span>Upload Results</span>
                        </a>
                        <a href="?page=messages" class="quick-action">
                            <i class="fas fa-envelope"></i><span>Messages</span>
                        </a>
                        <a href="?page=attendance-analytics" class="quick-action">
                            <i class="fas fa-chart-bar"></i><span>Analytics</span>
                        </a>
                        <a href="?page=my-students" class="quick-action">
                            <i class="fas fa-users"></i><span>My Students</span>
                        </a>
                        <a href="?page=feedback" class="quick-action">
                            <i class="fas fa-comment-dots"></i><span>Feedback</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <style>
            .quick-action {
                display: flex; flex-direction: column; align-items: center; gap: 8px;
                padding: 18px 12px; background: var(--gray-50); border-radius: var(--radius);
                color: var(--gray-700); text-decoration: none; transition: var(--transition);
                border: 1px solid var(--gray-100); text-align: center;
            }
            .quick-action:hover {
                background: var(--primary); color: white; transform: translateY(-3px); box-shadow: var(--shadow);
            }
            .quick-action i { font-size: 24px; }
            .quick-action span { font-size: 13px; font-weight: 500; }
            </style>
            
            <!-- Charts & Recent Data -->
            <div class="grid-2 mb-4">
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-chart-line text-primary"></i> Attendance Trend (7 Days)</h3></div>
                    <div class="card-body">
                        <div class="chart-container"><canvas id="trendChart"></canvas></div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope text-primary"></i> Recent Messages</h3>
                        <a href="?page=messages" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentMessages)): ?>
                            <div class="empty-state"><i class="fas fa-inbox"></i><p>No messages yet</p></div>
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
            </div>
            
            <!-- Announcements -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bullhorn text-primary"></i> School Announcements</h3>
                    <a href="?page=announcements" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAnnouncements)): ?>
                        <div class="empty-state"><i class="fas fa-bullhorn"></i><p>No announcements</p></div>
                    <?php else: ?>
                        <?php foreach ($recentAnnouncements as $ann): ?>
                            <div class="announcement-card <?= e($ann['priority']) ?>">
                                <div class="announcement-title"><?= e($ann['title']) ?></div>
                                <div class="announcement-content"><?= e(substr($ann['content'], 0, 150)) ?>...</div>
                                <div class="announcement-meta">
                                    <span><i class="fas fa-clock"></i> <?= timeAgo($ann['published_at']) ?></span>
                                    <span class="badge badge-<?= $ann['priority'] === 'urgent' ? 'danger' : 'info' ?>"><?= e(ucfirst($ann['priority'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($trendData, 'day')) ?>,
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode(array_column($trendData, 'rate')) ?>,
            borderColor: '#1e40af',
            backgroundColor: 'rgba(30, 64, 175, 0.1)',
            tension: 0.4, fill: true, pointRadius: 5
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
    }
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>