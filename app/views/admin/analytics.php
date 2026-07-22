<?php
$pageTitle = 'School Analytics';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$term = getCurrentTerm();
$totalStudents = countRecords('students');
$totalTeachers = countRecords('teachers');

// ============================================================
// OVERALL ATTENDANCE RATE
// ============================================================
$attStats = $db->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status IN ('Present','Late') THEN 1 ELSE 0 END) as present 
    FROM attendance" . ($term ? " WHERE term_id = {$term['id']}" : ""))->fetch();
$overallRate = $attStats['total'] > 0 
    ? round(($attStats['present'] / $attStats['total']) * 100, 1) 
    : 0;

// ============================================================
// CLASS COMPARISON (FIXED QUERY)
// ============================================================
// Compute the rate directly in SELECT so we can ORDER BY it safely
$classStats = $db->query("SELECT 
        c.class_name, 
        COUNT(a.id) as total,
        SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) as present,
        ROUND(
            (SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 
            1
        ) as rate
    FROM classes c 
    LEFT JOIN attendance a ON c.id = a.class_id " . ($term ? "AND a.term_id = {$term['id']}" : "") . "
    GROUP BY c.id, c.class_name 
    HAVING total > 0 
    ORDER BY rate DESC")->fetchAll();

// ============================================================
// ATTENDANCE DISTRIBUTION (Present/Late/Absent counts)
// ============================================================
$lateCount = $db->query("SELECT COUNT(*) FROM attendance WHERE status='Late'" . ($term ? " AND term_id={$term['id']}" : ""))->fetchColumn();
$absentCount = $db->query("SELECT COUNT(*) FROM attendance WHERE status='Absent'" . ($term ? " AND term_id={$term['id']}" : ""))->fetchColumn();
$presentCount = $attStats['present'] ?? 0;

// ============================================================
// ATTENDANCE CATEGORIES (Excellent/Good/Fair/At-Risk)
// ============================================================
$categoryStats = $db->query("SELECT 
        SUM(CASE WHEN pct >= 95 THEN 1 ELSE 0 END) as excellent,
        SUM(CASE WHEN pct >= 85 AND pct < 95 THEN 1 ELSE 0 END) as good,
        SUM(CASE WHEN pct >= 75 AND pct < 85 THEN 1 ELSE 0 END) as fair,
        SUM(CASE WHEN pct < 75 THEN 1 ELSE 0 END) as at_risk
    FROM (
        SELECT 
            s.id,
            ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) as pct
        FROM students s
        JOIN attendance a ON s.id = a.student_id " . ($term ? "WHERE a.term_id = {$term['id']}" : "") . "
        GROUP BY s.id
        HAVING COUNT(a.id) > 0
    ) as student_percentages")->fetch();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-chart-pie"></i> School Analytics Dashboard</h2>
                    <p>Intelligent insights across the entire school</p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-info">
                        <h4>Total Students</h4>
                        <div class="value"><?= number_format($totalStudents) ?></div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <h4>Total Teachers</h4>
                        <div class="value"><?= number_format($totalTeachers) ?></div>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-info">
                        <h4>Overall Attendance</h4>
                        <div class="value"><?= $overallRate ?>%</div>
                        <div class="change"><?= getAttendanceCategory($overallRate)['label'] ?></div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h4>Total Records</h4>
                        <div class="value"><?= number_format($attStats['total']) ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Category Breakdown Cards -->
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <h4>Excellent (95-100%)</h4>
                        <div class="value"><?= $categoryStats['excellent'] ?? 0 ?></div>
                        <div class="change">students</div>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-thumbs-up"></i></div>
                    <div class="stat-info">
                        <h4>Good (85-94%)</h4>
                        <div class="value"><?= $categoryStats['good'] ?? 0 ?></div>
                        <div class="change">students</div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-meh"></i></div>
                    <div class="stat-info">
                        <h4>Fair (75-84%)</h4>
                        <div class="value"><?= $categoryStats['fair'] ?? 0 ?></div>
                        <div class="change">students</div>
                    </div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-info">
                        <h4>At Risk (&lt;75%)</h4>
                        <div class="value"><?= $categoryStats['at_risk'] ?? 0 ?></div>
                        <div class="change negative">needs attention</div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid-2 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar text-primary"></i> Class Attendance Comparison</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="classChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie text-primary"></i> Attendance Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="distChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Category Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-layer-group text-primary"></i> Student Attendance Categories</h3>
                </div>
                <div class="card-body">
                    <div class="grid-2">
                        <div class="chart-container" style="height:300px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                        <div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-star text-success"></i> Excellent (95-100%)</span>
                                    <strong><?= $categoryStats['excellent'] ?? 0 ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= getPercentage($categoryStats['excellent'] ?? 0, $totalStudents) ?>%; background: #10b981;"></div>
                                </div>
                            </div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-thumbs-up text-primary"></i> Good (85-94%)</span>
                                    <strong><?= $categoryStats['good'] ?? 0 ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= getPercentage($categoryStats['good'] ?? 0, $totalStudents) ?>%; background: #3b82f6;"></div>
                                </div>
                            </div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-meh text-warning"></i> Fair (75-84%)</span>
                                    <strong><?= $categoryStats['fair'] ?? 0 ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= getPercentage($categoryStats['fair'] ?? 0, $totalStudents) ?>%; background: #f59e0b;"></div>
                                </div>
                            </div>
                            <div class="progress-bar-wrapper">
                                <div class="progress-label">
                                    <span><i class="fas fa-exclamation-triangle text-danger"></i> At Risk (&lt;75%)</span>
                                    <strong><?= $categoryStats['at_risk'] ?? 0 ?> students</strong>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= getPercentage($categoryStats['at_risk'] ?? 0, $totalStudents) ?>%; background: #ef4444;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Class Ranking Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy text-warning"></i> Class Ranking by Attendance</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Class</th>
                                    <th>Total Records</th>
                                    <th>Present/Late</th>
                                    <th>Attendance Rate</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($classStats)): ?>
                                    <tr><td colspan="6" class="text-center text-muted p-3">No attendance data available</td></tr>
                                <?php else: ?>
                                    <?php foreach ($classStats as $idx => $c): 
                                        $cat = getAttendanceCategory($c['rate']);
                                    ?>
                                        <tr>
                                            <td><strong>#<?= $idx + 1 ?></strong></td>
                                            <td><strong><?= e($c['class_name']) ?></strong></td>
                                            <td><?= number_format($c['total']) ?></td>
                                            <td class="text-success"><?= number_format($c['present']) ?></td>
                                            <td><strong><?= $c['rate'] ?>%</strong></td>
                                            <td>
                                                <span class="badge" style="background:<?= $cat['color'] ?>20; color:<?= $cat['color'] ?>">
                                                    <?= $cat['label'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
// ============================================================
// CLASS COMPARISON BAR CHART
// ============================================================
new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($classStats, 'class_name')) ?>,
        datasets: [{
            label: 'Attendance Rate (%)',
            data: <?= json_encode(array_column($classStats, 'rate')) ?>,
            backgroundColor: <?= json_encode(array_map(function($c) {
                if ($c['rate'] >= 95) return '#10b981';
                if ($c['rate'] >= 85) return '#3b82f6';
                if ($c['rate'] >= 75) return '#f59e0b';
                return '#ef4444';
            }, $classStats)) ?>,
            borderRadius: 6
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
// ATTENDANCE DISTRIBUTION PIE CHART
// ============================================================
new Chart(document.getElementById('distChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Late', 'Absent'],
        datasets: [{
            data: [<?= (int)$presentCount ?>, <?= (int)$lateCount ?>, <?= (int)$absentCount ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
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

// ============================================================
// CATEGORY PIE CHART
// ============================================================
new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: ['Excellent (95-100%)', 'Good (85-94%)', 'Fair (75-84%)', 'At Risk (<75%)'],
        datasets: [{
            data: [
                <?= (int)($categoryStats['excellent'] ?? 0) ?>, 
                <?= (int)($categoryStats['good'] ?? 0) ?>, 
                <?= (int)($categoryStats['fair'] ?? 0) ?>, 
                <?= (int)($categoryStats['at_risk'] ?? 0) ?>
            ],
            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
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