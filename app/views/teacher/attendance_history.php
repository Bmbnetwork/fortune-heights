<?php
$pageTitle = 'Attendance History';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();
$term = getCurrentTerm();

// Get teacher's class
$classId = $_GET['class_id'] ?? $teacher['class_id'];
$month = $_GET['month'] ?? date('Y-m');

if (!$classId) {
    setFlash('danger', 'No class assigned to you');
    redirect('?page=dashboard');
}

// Get class info
$classStmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
$classStmt->execute([$classId]);
$class = $classStmt->fetch();

// Get all classes teacher can access (own class + classes of subjects they teach)
$accessibleClasses = $db->prepare("SELECT DISTINCT c.id, c.class_name FROM classes c 
    LEFT JOIN subjects s ON c.id = s.class_id 
    WHERE c.id = ? OR s.teacher_id = ?
    ORDER BY c.class_name");
$accessibleClasses->execute([$classId, Auth::id()]);
$accessibleClasses = $accessibleClasses->fetchAll();

// Get attendance summary per student for the month
$stmt = $db->prepare("SELECT 
    s.id, s.full_name, s.admission_no,
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
    ROUND((SUM(CASE WHEN a.status IN ('Present','Late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as percentage
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id AND a.date LIKE ?
    WHERE s.class_id = ? AND s.is_active = 1
    GROUP BY s.id
    ORDER BY percentage DESC, s.full_name ASC");
$stmt->execute([$month . '%', $classId]);
$records = $stmt->fetchAll();

// Class-level stats
$classTotal = $classPresent = $classAbsent = $classLate = 0;
foreach ($records as $r) {
    $classTotal += $r['total_days'];
    $classPresent += $r['present'];
    $classAbsent += $r['absent'];
    $classLate += $r['late'];
}
$classRate = $classTotal > 0 ? round((($classPresent + $classLate) / $classTotal) * 100, 1) : 0;

// Daily breakdown for the month
$dailyBreakdown = $db->prepare("SELECT 
    DATE(date) as day,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
    SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late
    FROM attendance 
    WHERE class_id = ? AND date LIKE ?
    GROUP BY DATE(date)
    ORDER BY day ASC");
$dailyBreakdown->execute([$classId, $month . '%']);
$dailyBreakdown = $dailyBreakdown->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-history"></i> Attendance History</h2>
                    <p><?= e($class['class_name']) ?> • <?= date('F Y', strtotime($month . '-01')) ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=attendance&class_id=<?= $classId ?>" class="btn btn-primary">
                        <i class="fas fa-clipboard-check"></i> Mark Attendance
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                        <input type="hidden" name="page" value="attendance-history">
                        <select name="class_id" class="form-control" style="width:200px;" onchange="this.form.submit()">
                            <?php foreach ($accessibleClasses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $classId ? 'selected' : '' ?>>
                                    <?= e($c['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="month" name="month" class="form-control" style="width:200px;" 
                               value="<?= e($month) ?>" onchange="this.form.submit()">
                    </form>
                </div>
            </div>
            
            <!-- Class Stats -->
            <div class="stats-grid">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <div class="stat-info">
                        <h4>Class Rate</h4>
                        <div class="value"><?= $classRate ?>%</div>
                        <div class="change"><?= getAttendanceCategory($classRate)['label'] ?></div>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-check"></i></div>
                    <div class="stat-info">
                        <h4>Total Present</h4>
                        <div class="value"><?= $classPresent ?></div>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h4>Total Late</h4>
                        <div class="value"><?= $classLate ?></div>
                    </div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="fas fa-times"></i></div>
                    <div class="stat-info">
                        <h4>Total Absent</h4>
                        <div class="value"><?= $classAbsent ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Breakdown Chart -->
            <?php if (!empty($dailyBreakdown)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line text-primary"></i> Daily Trend</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container"><canvas id="dailyChart"></canvas></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Student Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Student Attendance Summary</h3>
                    <span class="badge badge-info"><?= count($records) ?> students</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($records)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <p>No attendance records for this month</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>Adm No</th>
                                        <th>Present</th>
                                        <th>Late</th>
                                        <th>Absent</th>
                                        <th>Total</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $idx => $r): 
                                        $cat = getAttendanceCategory($r['percentage'] ?? 0);
                                    ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><strong><?= e($r['full_name']) ?></strong></td>
                                            <td><code><?= e($r['admission_no']) ?></code></td>
                                            <td class="text-success fw-bold"><?= $r['present'] ?></td>
                                            <td class="text-warning fw-bold"><?= $r['late'] ?></td>
                                            <td class="text-danger fw-bold"><?= $r['absent'] ?></td>
                                            <td><?= $r['total_days'] ?></td>
                                            <td><strong><?= $r['percentage'] ?? 0 ?>%</strong></td>
                                            <td>
                                                <span class="badge" style="background:<?= $cat['color'] ?>20;color:<?= $cat['color'] ?>">
                                                    <?= $cat['label'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<?php if (!empty($dailyBreakdown)): ?>
<script>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('M d', strtotime($d['day'])), $dailyBreakdown)) ?>,
        datasets: [
            {
                label: 'Present',
                data: <?= json_encode(array_column($dailyBreakdown, 'present')) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                tension: 0.3, fill: true
            },
            {
                label: 'Absent',
                data: <?= json_encode(array_column($dailyBreakdown, 'absent')) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.1)',
                tension: 0.3, fill: true
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
<?php endif; ?>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>