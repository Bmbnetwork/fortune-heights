<?php
$pageTitle = 'Attendance Analytics';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();

// ============================================================
// VALIDATION: Check if teacher has a class assigned
// ============================================================
$classId = $_GET['class_id'] ?? $teacher['class_id'] ?? null;

if (!$classId) {
    // No class assigned - show helpful message
    ?>
    <div class="dashboard-wrapper">
        <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
        <div class="main-content">
            <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2><i class="fas fa-chart-bar"></i> Attendance Analytics</h2>
                        <p>Smart insights for student attendance</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state" style="padding:60px 20px;">
                            <i class="fas fa-school" style="font-size:60px;color:var(--warning);"></i>
                            <h3 style="margin-top:20px;color:var(--gray-800);">No Class Assigned</h3>
                            <p style="color:var(--gray-600);max-width:500px;margin:15px auto;">
                                You need to be assigned to a class to view attendance analytics. 
                                Please contact the school administrator to assign you to a class.
                            </p>
                            <a href="?page=dashboard" class="btn btn-primary" style="margin-top:20px;">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php include APP_PATH . '/views/layouts/footer.php'; ?>
        </div>
    </div>
    <?php
    return;
}

// ============================================================
// Get current term (with fallback)
// ============================================================
$term = getCurrentTerm();
$termId = $term['id'] ?? null;

// ============================================================
// Get class info
// ============================================================
$classStmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$class = $classStmt->fetch();

if (!$class) {
    setFlash('danger', 'Class not found');
    redirect('?page=dashboard');
}

// ============================================================
// Get all classes for selector (teacher's accessible classes)
// ============================================================
$accessibleClasses = $db->prepare("SELECT DISTINCT c.id, c.class_name 
    FROM classes c 
    LEFT JOIN subjects s ON c.id = s.class_id 
    WHERE c.id = ? OR s.teacher_id = ?
    ORDER BY c.class_name");
$accessibleClasses->execute([$classId, Auth::id()]);
$accessibleClasses = $accessibleClasses->fetchAll();

// ============================================================
// ATTENDANCE STATISTICS
// ============================================================
$where = "a.class_id = ?";
$params = [$classId];

if ($termId) {
    $where .= " AND a.term_id = ?";
    $params[] = $termId;
}

$statsStmt = $db->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance a 
    WHERE {$where}");
$statsStmt->execute($params);
$stats = $statsStmt->fetch();

$totalRecords = $stats['total'] ?? 0;
$presentCount = $stats['present'] ?? 0;
$absentCount = $stats['absent'] ?? 0;
$lateCount = $stats['late'] ?? 0;
$attendanceRate = $totalRecords > 0 
    ? round(($presentCount + $lateCount) / $totalRecords * 100, 2) 
    : 0;

// ============================================================
// DAILY TREND (Last 30 days)
// ============================================================
$trendStmt = $db->prepare("SELECT 
    DATE(date) as day,
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) as present
    FROM attendance 
    WHERE class_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(date) 
    ORDER BY day ASC");
$trendStmt->execute([$classId]);
$trend = $trendStmt->fetchAll();

// ============================================================
// AT-RISK STUDENTS (Below 75% attendance)
// ============================================================
$atRiskWhere = "s.class_id = ? AND s.is_active = 1";
$atRiskParams = [$classId];

if ($termId) {
    $atRiskWhere .= " AND a.term_id = ?";
    $atRiskParams[] = $termId;
}

$atRiskStmt = $db->prepare("SELECT 
    s.id, s.full_name, s.admission_no,
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present_days,
    ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as percentage
    FROM students s
    JOIN attendance a ON s.id = a.student_id
    WHERE {$atRiskWhere}
    GROUP BY s.id
    HAVING percentage < 75
    ORDER BY percentage ASC
    LIMIT 10");
$atRiskStmt->execute($atRiskParams);
$atRisk = $atRiskStmt->fetchAll();

// ============================================================
// CLASS COMPARISON (All classes for context)
// ============================================================
$classCompWhere = "";
$classCompParams = [];
if ($termId) {
    $classCompWhere = "AND a.term_id = ?";
    $classCompParams[] = $termId;
}

$classComparison = $db->prepare("SELECT 
    c.class_name,
    COUNT(a.id) as total,
    SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present,
    ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as rate
    FROM classes c
    LEFT JOIN attendance a ON c.id = a.class_id {$classCompWhere}
    GROUP BY c.id, c.class_name
    HAVING total > 0
    ORDER BY rate DESC");
$classComparison->execute($classCompParams);
$classComparison = $classComparison->fetchAll();

// ============================================================
// ATTENDANCE CATEGORIES (Excellent/Good/Fair/At-Risk)
// ============================================================
$excellent = $good = $fair = $risk = 0;

$catWhere = "s.class_id = ? AND s.is_active = 1";
$catParams = [$classId];
if ($termId) {
    $catWhere .= " AND a.term_id = ?";
    $catParams[] = $termId;
}

$studentStats = $db->prepare("SELECT 
    s.id,
    COUNT(a.id) as total,
    SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id
    WHERE {$catWhere}
    GROUP BY s.id");
$studentStats->execute($catParams);

foreach ($studentStats->fetchAll() as $s) {
    if ($s['total'] == 0) continue;
    $pct = ($s['present'] / $s['total']) * 100;
    if ($pct >= 95) $excellent++;
    elseif ($pct >= 85) $good++;
    elseif ($pct >= 75) $fair++;
    else $risk++;
}

$totalStudents = $excellent + $good + $fair + $risk;
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-chart-bar"></i> Attendance Analytics</h2>
                    <p>
                        <?= e($class['class_name']) ?> • 
                        <?php if ($term): ?>
                            <?= e($term['term_name']) ?> (<?= e($term['session_name'] ?? '') ?>)
                        <?php else: ?>
                            <span class="text-warning">No active term</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <select class="form-control" style="width:auto;" onchange="location.href='?page=attendance-analytics&class_id='+this.value">
                        <?php foreach ($accessibleClasses as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $classId ? 'selected' : '' ?>>
                                <?= e($c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="?page=attendance-history&class_id=<?= $classId ?>" class="btn btn-secondary">
                        <i class="fas fa-list"></i> View Details
                    </a>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if ($totalRecords === 0): ?>
                <!-- No attendance data yet -->
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state" style="padding:60px 20px;">
                            <i class="fas fa-clipboard-list" style="font-size:60px;color:var(--gray-400);"></i>
                            <h3 style="margin-top:20px;color:var(--gray-800);">No Attendance Data Yet</h3>
                            <p style="color:var(--gray-600);max-width:500px;margin:15px auto;">
                                You haven't marked any attendance for this class yet. 
                                Start marking daily attendance to see analytics and insights here.
                            </p>
                            <a href="?page=attendance&class_id=<?= $classId ?>" class="btn btn-primary" style="margin-top:20px;">
                                <i class="fas fa-clipboard-check"></i> Mark Attendance Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Summary Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                        <div class="stat-info">
                            <h4>Attendance Rate</h4>
                            <div class="value"><?= $attendanceRate ?>%</div>
                            <div class="change"><?= getAttendanceCategory($attendanceRate)['label'] ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h4>Present</h4>
                            <div class="value"><?= number_format($presentCount) ?></div>
                            <div class="change">records</div>
                        </div>
                    </div>
                    
                    <div class="stat-card danger">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-info">
                            <h4>Absent</h4>
                            <div class="value"><?= number_format($absentCount) ?></div>
                            <div class="change negative">needs attention</div>
                        </div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-info">
                            <h4>At Risk</h4>
                            <div class="value"><?= count($atRisk) ?></div>
                            <div class="change negative">students below 75%</div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 1 -->
                <div class="grid-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-layer-group text-primary"></i> Attendance Categories</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($totalStudents > 0): ?>
                                <div class="chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-pie"></i>
                                    <p>No student data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line text-primary"></i> 30-Day Trend</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($trend)): ?>
                                <div class="chart-container">
                                    <canvas id="trendChart"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-line"></i>
                                    <p>No trend data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row 2 -->
                <div class="grid-2 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-trophy text-primary"></i> Class Comparison</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($classComparison)): ?>
                                <div class="chart-container">
                                    <canvas id="comparisonChart"></canvas>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>No comparison data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-circle text-danger"></i> At-Risk Students</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($atRisk)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <p class="text-success">All students have good attendance!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($atRisk, 0, 8) as $student): 
                                    $riskLevel = $student['percentage'] < 50 ? 'high' : ($student['percentage'] < 65 ? 'medium' : 'low');
                                ?>
                                    <div class="risk-card <?= $riskLevel ?>">
                                        <div class="d-flex justify-between align-center">
                                            <div>
                                                <strong><?= e($student['full_name']) ?></strong>
                                                <small class="text-muted d-block"><?= e($student['admission_no']) ?></small>
                                            </div>
                                            <div class="text-right">
                                                <strong class="text-danger"><?= $student['percentage'] ?>%</strong>
                                                <small class="text-muted d-block">
                                                    <?= $student['present_days'] ?>/<?= $student['total_days'] ?> days
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bars -->
                <?php if ($totalStudents > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-tasks text-primary"></i> Attendance Distribution</h3>
                        </div>
                        <div class="card-body">
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-star text-success"></i> Excellent (95-100%)</span>
                                    <strong><?= $excellent ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($excellent / $totalStudents) * 100 ?>%; background: #10b981;"></div>
                                </div>
                            </div>
                            
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-thumbs-up text-primary"></i> Good (85-94%)</span>
                                    <strong><?= $good ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($good / $totalStudents) * 100 ?>%; background: #3b82f6;"></div>
                                </div>
                            </div>
                            
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-meh text-warning"></i> Fair (75-84%)</span>
                                    <strong><?= $fair ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($fair / $totalStudents) * 100 ?>%; background: #f59e0b;"></div>
                                </div>
                            </div>
                            
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-exclamation-triangle text-danger"></i> At Risk (&lt;75%)</span>
                                    <strong><?= $risk ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($risk / $totalStudents) * 100 ?>%; background: #ef4444;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<?php if ($totalRecords > 0): ?>
<script>
// ============================================================
// CHART.JS INITIALIZATION (with safe data handling)
// ============================================================

// Category Chart (Pie)
<?php if ($totalStudents > 0): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: ['Excellent (95-100%)', 'Good (85-94%)', 'Fair (75-84%)', 'At Risk (<75%)'],
        datasets: [{
            data: [<?= $excellent ?>, <?= $good ?>, <?= $fair ?>, <?= $risk ?>],
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return label + ': ' + value + ' students (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Trend Chart (Line)
<?php if (!empty($trend)): ?>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('M d', strtotime($d['day'])), $trend)) ?>,
        datasets: [{
            label: 'Attendance Rate',
            data: <?= json_encode(array_map(fn($d) => $d['total'] > 0 ? round(($d['present'] / $d['total']) * 100, 1) : 0, $trend)) ?>,
            borderColor: '#1e40af',
            backgroundColor: 'rgba(30, 64, 175, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                beginAtZero: true, 
                max: 100, 
                ticks: { callback: v => v + '%' } 
            } 
        }
    }
});
<?php endif; ?>

// Comparison Chart (Bar)
<?php if (!empty($classComparison)): ?>
new Chart(document.getElementById('comparisonChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($classComparison, 'class_name')) ?>,
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode(array_column($classComparison, 'rate')) ?>,
            backgroundColor: <?= json_encode(array_map(function($c) {
                if ($c['rate'] >= 95) return '#10b981';
                if ($c['rate'] >= 85) return '#3b82f6';
                if ($c['rate'] >= 75) return '#f59e0b';
                return '#ef4444';
            }, $classComparison)) ?>,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                beginAtZero: true, 
                max: 100, 
                ticks: { callback: v => v + '%' } 
            } 
        }
    }
});
<?php endif; ?>
</script>
<?php endif; ?>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>