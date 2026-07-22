<?php
$pageTitle = 'Attendance Reports'; Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db();
$classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
$selectedClass = $_GET['class_id'] ?? ''; $term = getCurrentTerm();

$students = [];
if ($selectedClass && $term) {
    $stmt = $db->prepare("SELECT s.id, s.full_name, s.admission_no,
        COUNT(a.id) as total_days,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM students s LEFT JOIN attendance a ON s.id = a.student_id AND a.term_id = ?
        WHERE s.class_id = ? GROUP BY s.id ORDER BY s.full_name");
    $stmt->execute([$term['id'], $selectedClass]);
    $students = $stmt->fetchAll();
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><div><h2><i class="fas fa-clipboard-check"></i> Attendance Reports</h2></div><button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print Report</button></div>
    <?php displayFlash(); ?>
    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <input type="hidden" name="page" value="attendance-reports">
            <select name="class_id" class="form-control" style="width:200px;" required>
                <option value="">Select Class</option>
                <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selectedClass == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
    </div></div>

    <?php if (!empty($students)): ?>
        <div class="card"><div class="card-header"><h3>Report for <?= e($db->query("SELECT class_name FROM classes WHERE id=$selectedClass")->fetchColumn()) ?> (<?= e($term['term_name']) ?>)</h3></div>
        <div class="card-body p-0"><div class="table-responsive"><table class="table">
            <thead><tr><th>Student Name</th><th>Adm No</th><th>Present</th><th>Late</th><th>Absent</th><th>Total Days</th><th>Percentage</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($students as $s): 
                    $pct = $s['total_days'] > 0 ? round((($s['present'] + $s['late']) / $s['total_days']) * 100, 1) : 0;
                    $cat = getAttendanceCategory($pct);
                ?>
                    <tr>
                        <td><strong><?= e($s['full_name']) ?></strong></td><td><?= e($s['admission_no']) ?></td>
                        <td class="text-success fw-bold"><?= $s['present'] ?></td>
                        <td class="text-warning fw-bold"><?= $s['late'] ?></td>
                        <td class="text-danger fw-bold"><?= $s['absent'] ?></td>
                        <td><?= $s['total_days'] ?></td>
                        <td><strong><?= $pct ?>%</strong></td>
                        <td><span class="badge" style="background:<?= $cat['color'] ?>20; color:<?= $cat['color'] ?>"><?= $cat['label'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div></div></div>
    <?php endif; ?>
</div></div></div></div>