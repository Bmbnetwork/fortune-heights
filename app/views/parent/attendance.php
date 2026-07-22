<?php
$pageTitle = 'Attendance Records';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$term = getCurrentTerm();
$selectedChild = $_GET['child_id'] ?? null;

// Get all children
$stmt = $db->prepare("SELECT s.*, c.class_name FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ? AND s.is_active = 1 ORDER BY s.full_name");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

// Default to first child if none selected
if (!$selectedChild && !empty($children)) {
    $selectedChild = $children[0]['id'];
}

// Get attendance data for selected child
$attendanceRecords = [];
$summary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'percentage' => 0];

if ($selectedChild) {
    $stmt = $db->prepare("SELECT a.*, t.full_name as marked_by_name 
        FROM attendance a 
        LEFT JOIN teachers t ON a.marked_by = t.id 
        WHERE a.student_id = ? 
        ORDER BY a.date DESC LIMIT 100");
    $stmt->execute([$selectedChild]);
    $attendanceRecords = $stmt->fetchAll();
    
    // Calculate summary
    foreach ($attendanceRecords as $rec) {
        $summary['total']++;
        if ($rec['status'] === 'Present') $summary['present']++;
        elseif ($rec['status'] === 'Absent') $summary['absent']++;
        elseif ($rec['status'] === 'Late') $summary['late']++;
    }
    $summary['percentage'] = $summary['total'] > 0 
        ? round((($summary['present'] + $summary['late']) / $summary['total']) * 100, 1) 
        : 0;
}

$category = getAttendanceCategory($summary['percentage']);
$selectedChildData = null;
foreach ($children as $c) {
    if ($c['id'] == $selectedChild) { $selectedChildData = $c; break; }
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-clipboard-check"></i> Attendance Records</h2>
                    <p>Track your child's daily attendance</p>
                </div>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (empty($children)): ?>
                <div class="card"><div class="card-body">
                    <div class="empty-state">
                        <i class="fas fa-child"></i>
                        <p>No children registered. Contact admin.</p>
                    </div>
                </div></div>
            <?php else: ?>
                <!-- Child Selector -->
                <?php if (count($children) > 1): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" class="d-flex gap-2 align-center">
                                <input type="hidden" name="page" value="attendance">
                                <label style="margin:0;font-weight:600;">Select Child:</label>
                                <select name="child_id" class="form-control" style="max-width:300px;" onchange="this.form.submit()">
                                    <?php foreach ($children as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedChild ? 'selected' : '' ?>>
                                            <?= e($c['full_name']) ?> - <?= e($c['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($selectedChildData): ?>
                    <!-- Summary Cards -->
                    <div class="stats-grid">
                        <div class="stat-card" style="border-left-color:<?= $category['color'] ?>;">
                            <div class="stat-icon" style="background:<?= $category['color'] ?>;"><i class="fas fa-percentage"></i></div>
                            <div class="stat-info">
                                <h4>Attendance Rate</h4>
                                <div class="value"><?= $summary['percentage'] ?>%</div>
                                <div class="change"><?= $category['label'] ?></div>
                            </div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-info">
                                <h4>Present</h4>
                                <div class="value"><?= $summary['present'] ?></div>
                                <div class="change">days</div>
                            </div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-info">
                                <h4>Late</h4>
                                <div class="value"><?= $summary['late'] ?></div>
                                <div class="change">days</div>
                            </div>
                        </div>
                        <div class="stat-card danger">
                            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                            <div class="stat-info">
                                <h4>Absent</h4>
                                <div class="value"><?= $summary['absent'] ?></div>
                                <div class="change">days</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Attendance History - <?= e($selectedChildData['full_name']) ?></h3>
                            <span class="badge badge-info">Last 100 records</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($attendanceRecords)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-clipboard"></i>
                                    <p>No attendance records yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Day</th>
                                                <th>Status</th>
                                                <th>Remark</th>
                                                <th>Marked By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendanceRecords as $rec): ?>
                                                <tr>
                                                    <td><strong><?= formatDate($rec['date']) ?></strong></td>
                                                    <td><?= date('l', strtotime($rec['date'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= strtolower($rec['status']) ?>">
                                                            <i class="fas fa-<?= $rec['status'] === 'Present' ? 'check' : ($rec['status'] === 'Absent' ? 'times' : 'clock') ?>"></i>
                                                            <?= e($rec['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= e($rec['remark'] ?? '-') ?></td>
                                                    <td><?= e($rec['marked_by_name'] ?? 'System') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>