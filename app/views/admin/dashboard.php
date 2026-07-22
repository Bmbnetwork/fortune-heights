<?php
$pageTitle = 'Admin Dashboard';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$term = getCurrentTerm();
$session = getCurrentSession();

// ============================================================
// CORE STATISTICS
// ============================================================
$totalStudents   = countRecords('students', 'is_active = 1');
$totalTeachers   = countRecords('teachers', 'is_active = 1');
$totalParents    = countRecords('parents', 'is_active = 1');
$totalClasses    = countRecords('classes');
$totalSubjects   = countRecords('subjects');

// Messages & Announcements
$totalMessages      = countRecords('messages');
$unreadMessages     = countRecords('messages', 'receiver_id = ? AND receiver_type = ? AND is_read = 0', [Auth::id(), 'admin']);
$totalAnnouncements = countRecords('announcements', "status = 'published'");

// Today's attendance
$today = date('Y-m-d');
$todayAttendance = countRecords('attendance', 'date = ?', [$today]);
$presentToday = countRecords('attendance', "date = ? AND status IN ('Present','Late')", [$today]);
$absentToday = countRecords('attendance', "date = ? AND status = 'Absent'", [$today]);
$attendanceRate = $todayAttendance > 0 ? round(($presentToday / $todayAttendance) * 100, 1) : 0;

// Overall attendance rate (current term)
$overallAtt = $db->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) as present 
    FROM attendance" . ($term ? " WHERE term_id = {$term['id']}" : ""))->fetch();
$overallRate = $overallAtt['total'] > 0 ? round(($overallAtt['present'] / $overallAtt['total']) * 100, 1) : 0;

// At-risk students
$atRiskCount = $db->query("SELECT COUNT(DISTINCT s.id) FROM students s 
    JOIN attendance a ON s.id = a.student_id " . ($term ? "WHERE a.term_id = {$term['id']}" : "") . "
    GROUP BY s.id 
    HAVING (SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(*)) * 100 < 75")->rowCount();

// Recent data
$recentActivities = $db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();
$recentAnnouncements = $db->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY published_at DESC LIMIT 5")->fetchAll();
$recentMessages = $db->query("SELECT m.*, 
    CASE 
        WHEN m.sender_type = 'parent' THEN (SELECT full_name FROM parents WHERE id = m.sender_id)
        WHEN m.sender_type = 'teacher' THEN (SELECT full_name FROM teachers WHERE id = m.sender_id)
        ELSE (SELECT full_name FROM admins WHERE id = m.sender_id)
    END as sender_name
    FROM messages m ORDER BY m.created_at DESC LIMIT 5")->fetchAll();

// Class distribution
$classDistribution = $db->query("SELECT c.class_name, COUNT(s.id) as count 
    FROM classes c LEFT JOIN students s ON c.id = s.class_id AND s.is_active = 1
    GROUP BY c.id ORDER BY c.class_name")->fetchAll();

// Top performing classes (by attendance)
$topClasses = $db->query("SELECT c.class_name, 
    COUNT(a.id) as total,
    SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present,
    ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as rate
    FROM classes c JOIN attendance a ON c.id = a.class_id " . ($term ? "WHERE a.term_id = {$term['id']}" : "") . "
    GROUP BY c.id HAVING total > 0 ORDER BY rate DESC LIMIT 5")->fetchAll();

// Gender distribution
$genderStats = $db->query("SELECT gender, COUNT(*) as count FROM students WHERE is_active = 1 GROUP BY gender")->fetchAll();
$maleCount = 0; $femaleCount = 0;
foreach ($genderStats as $g) {
    if ($g['gender'] === 'Male') $maleCount = $g['count'];
    else $femaleCount = $g['count'];
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>Welcome back, <?= e(Auth::name()) ?>! 👋</h2>
                    <p>
                        <?= e(SCHOOL_NAME) ?> • 
                        <?php if ($term): ?>
                            <strong><?= e($term['term_name']) ?></strong> | <?= e($session['session_name'] ?? '') ?>
                        <?php else: ?>
                            <span class="text-warning">No active term set</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2" style="flex-wrap:wrap;">
                    <a href="?page=students&action=add" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Student
                    </a>
                    <a href="?page=announcements&action=create" class="btn btn-warning">
                        <i class="fas fa-bullhorn"></i> Announcement
                    </a>
                    <a href="?page=attendance-reports" class="btn btn-info">
                        <i class="fas fa-clipboard-check"></i> Attendance
                    </a>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Main Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h4>Total Students</h4>
                        <div class="value"><?= number_format($totalStudents) ?></div>
                        <div class="change"><i class="fas fa-users"></i> <?= $totalClasses ?> classes</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <h4>Total Teachers</h4>
                        <div class="value"><?= number_format($totalTeachers) ?></div>
                        <div class="change"><i class="fas fa-book"></i> <?= $totalSubjects ?> subjects</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h4>Total Parents</h4>
                        <div class="value"><?= number_format($totalParents) ?></div>
                        <div class="change"><i class="fas fa-child"></i> Registered families</div>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-info">
                        <h4>Today's Attendance</h4>
                        <div class="value"><?= $attendanceRate ?>%</div>
                        <div class="change"><?= $presentToday ?> present / <?= $absentToday ?> absent</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-info">
                        <h4>Messages</h4>
                        <div class="value"><?= number_format($totalMessages) ?></div>
                        <div class="change <?= $unreadMessages > 0 ? 'negative' : '' ?>">
                            <i class="fas fa-<?= $unreadMessages > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i>
                            <?= $unreadMessages ?> unread
                        </div>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h4>At-Risk Students</h4>
                        <div class="value"><?= $atRiskCount ?></div>
                        <div class="change negative"><i class="fas fa-bell"></i> Below 75% attendance</div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions Panel -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-bolt text-warning"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
                        <a href="?page=teachers&action=add" class="quick-action">
                            <i class="fas fa-user-plus"></i><span>Add Teacher</span>
                        </a>
                        <a href="?page=parents&action=add" class="quick-action">
                            <i class="fas fa-user-friends"></i><span>Add Parent</span>
                        </a>
                        <a href="?page=students&action=add" class="quick-action">
                            <i class="fas fa-user-graduate"></i><span>Add Student</span>
                        </a>
                        <a href="?page=classes&action=add" class="quick-action">
                            <i class="fas fa-school"></i><span>Add Class</span>
                        </a>
                        <a href="?page=subjects&action=add" class="quick-action">
                            <i class="fas fa-book"></i><span>Add Subject</span>
                        </a>
                        <a href="?page=sessions&action=add_term" class="quick-action">
                            <i class="fas fa-calendar-plus"></i><span>Add Term</span>
                        </a>
                        <a href="?page=announcements&action=create" class="quick-action">
                            <i class="fas fa-bullhorn"></i><span>Announce</span>
                        </a>
                        <a href="?page=analytics" class="quick-action">
                            <i class="fas fa-chart-pie"></i><span>Analytics</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <style>
            .quick-action {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                padding: 18px 12px;
                background: var(--gray-50);
                border-radius: var(--radius);
                color: var(--gray-700);
                text-decoration: none;
                transition: var(--transition);
                border: 1px solid var(--gray-100);
                text-align: center;
            }
            .quick-action:hover {
                background: var(--primary);
                color: white;
                transform: translateY(-3px);
                box-shadow: var(--shadow);
            }
            .quick-action i {
                font-size: 24px;
            }
            .quick-action span {
                font-size: 13px;
                font-weight: 500;
            }
            </style>
            
            <!-- Charts Row -->
            <div class="grid-2 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line text-primary"></i> Attendance Trend (Last 7 Days)</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie text-primary"></i> Student Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="studentDistChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second Charts Row -->
            <div class="grid-2 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-trophy text-warning"></i> Top Performing Classes</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topClasses)): ?>
                            <div class="empty-state"><i class="fas fa-chart-bar"></i><p>No attendance data yet</p></div>
                        <?php else: ?>
                            <?php foreach ($topClasses as $idx => $class): ?>
                                <div class="progress-bar-wrapper">
                                    <div class="progress-label">
                                        <span>
                                            <strong>#<?= $idx + 1 ?></strong> <?= e($class['class_name']) ?>
                                        </span>
                                        <strong style="color:<?= $class['rate'] >= 90 ? '#10b981' : ($class['rate'] >= 75 ? '#f59e0b' : '#ef4444') ?>">
                                            <?= $class['rate'] ?>%
                                        </strong>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width:<?= $class['rate'] ?>%; background:<?= $class['rate'] >= 90 ? '#10b981' : ($class['rate'] >= 75 ? '#f59e0b' : '#ef4444') ?>"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-venus-mars text-info"></i> Gender Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:250px;">
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Data -->
            <div class="grid-2 mb-4">
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history text-primary"></i> Recent Activities</h3>
                        <a href="?page=activity-logs" class="btn btn-sm btn-secondary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr><th>User</th><th>Action</th><th>Time</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentActivities)): ?>
                                        <tr><td colspan="3" class="text-center text-muted p-3">No activities yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentActivities as $log): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-<?= $log['user_type'] === 'admin' ? 'primary' : ($log['user_type'] === 'teacher' ? 'success' : 'warning') ?>">
                                                        <?= e(ucfirst($log['user_type'])) ?>
                                                    </span>
                                                    <small class="text-muted">#<?= $log['user_id'] ?></small>
                                                </td>
                                                <td><small><?= e($log['action']) ?></small></td>
                                                <td><small><?= timeAgo($log['created_at']) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Messages -->
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
                                <div style="padding:12px;border-bottom:1px solid var(--gray-100);display:flex;gap:12px;align-items:flex-start;">
                                    <div class="user-avatar" style="width:40px;height:40px;flex-shrink:0;">
                                        <?= strtoupper(substr($msg['sender_name'] ?? '?', 0, 1)) ?>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;">
                                            <strong style="font-size:13px;"><?= e($msg['sender_name'] ?? 'Unknown') ?></strong>
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
                    <h3><i class="fas fa-bullhorn text-primary"></i> Recent Announcements</h3>
                    <a href="?page=announcements" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAnnouncements)): ?>
                        <div class="empty-state"><i class="fas fa-bullhorn"></i><p>No announcements yet</p></div>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px;">
                            <?php foreach ($recentAnnouncements as $ann): ?>
                                <div class="announcement-card <?= e($ann['priority']) ?>">
                                    <div class="announcement-header">
                                        <div>
                                            <div class="announcement-title"><?= e($ann['title']) ?></div>
                                            <span class="badge badge-<?= $ann['priority'] === 'urgent' ? 'danger' : ($ann['priority'] === 'high' ? 'warning' : 'info') ?>">
                                                <?= e(ucfirst($ann['priority'])) ?>
                                            </span>
                                            <span class="badge badge-secondary"><?= e(ucfirst($ann['target_audience'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="announcement-content">
                                        <?= e(substr($ann['content'], 0, 120)) ?><?= strlen($ann['content']) > 120 ? '...' : '' ?>
                                    </div>
                                    <div class="announcement-meta">
                                        <span><i class="fas fa-clock"></i> <?= timeAgo($ann['published_at']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-heartbeat text-danger"></i> System Overview</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
                        <div>
                            <small class="text-muted">Current Session</small>
                            <h4><?= e($session['session_name'] ?? 'Not Set') ?></h4>
                        </div>
                        <div>
                            <small class="text-muted">Current Term</small>
                            <h4><?= e($term['term_name'] ?? 'Not Set') ?></h4>
                        </div>
                        <div>
                            <small class="text-muted">Term Period</small>
                            <h4><?= $term ? formatDate($term['start_date'], 'M d') . ' - ' . formatDate($term['end_date'], 'M d, Y') : 'N/A' ?></h4>
                        </div>
                        <div>
                            <small class="text-muted">Overall Attendance</small>
                            <h4 style="color:<?= $overallRate >= 85 ? '#10b981' : ($overallRate >= 75 ? '#f59e0b' : '#ef4444') ?>">
                                <?= $overallRate ?>%
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
// ============================================================
// ATTENDANCE TREND CHART
// ============================================================
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($i) => date('D', strtotime("-$i days")), range(6, 0))) ?>,
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode(array_map(function($i) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $total = countRecords('attendance', 'date = ?', [$date]);
                $present = countRecords('attendance', "date = ? AND status IN ('Present','Late')", [$date]);
                return $total > 0 ? round(($present / $total) * 100, 1) : 0;
            }, range(6, 0))) ?>,
            borderColor: '#1e40af',
            backgroundColor: 'rgba(30, 64, 175, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#1e40af',
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } }
        }
    }
});

// ============================================================
// STUDENT DISTRIBUTION CHART
// ============================================================
const distCtx = document.getElementById('studentDistChart').getContext('2d');
new Chart(distCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($classDistribution, 'class_name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($classDistribution, 'count')) ?>,
            backgroundColor: ['#1e40af','#3b82f6','#60a5fa','#93c5fd','#f59e0b','#fbbf24','#10b981','#34d399','#6ee7b7'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right' } }
    }
});

// ============================================================
// GENDER DISTRIBUTION CHART
// ============================================================
const genderCtx = document.getElementById('genderChart').getContext('2d');
new Chart(genderCtx, {
    type: 'pie',
    data: {
        labels: ['Male', 'Female'],
        datasets: [{
            data: [<?= $maleCount ?>, <?= $femaleCount ?>],
            backgroundColor: ['#3b82f6', '#ec4899'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>