<?php
$pageTitle = 'Academic Reports'; Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db();
$classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
$terms = $db->query("SELECT t.*, s.session_name FROM academic_terms t JOIN academic_sessions s ON t.session_id = s.id ORDER BY t.start_date DESC")->fetchAll();
$selClass = $_GET['class_id'] ?? ''; $selTerm = $_GET['term_id'] ?? '';

$results = [];
if ($selClass && $selTerm) {
    $stmt = $db->prepare("SELECT s.full_name, s.admission_no, sub.subject_name, r.ca_score, r.exam_score, r.total_score, r.grade, r.remark
        FROM students s JOIN results r ON s.id = r.student_id JOIN subjects sub ON r.subject_id = sub.id
        WHERE s.class_id = ? AND r.term_id = ? AND r.is_published = 1 ORDER BY s.full_name, sub.subject_name");
    $stmt->execute([$selClass, $selTerm]);
    $results = $stmt->fetchAll();
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><div><h2><i class="fas fa-chart-line"></i> Academic Reports</h2></div><button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i> Print</button></div>
    <?php displayFlash(); ?>
    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
            <input type="hidden" name="page" value="academic-reports">
            <select name="class_id" class="form-control" style="width:200px;" required><option value="">Select Class</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selClass == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option><?php endforeach; ?></select>
            <select name="term_id" class="form-control" style="width:200px;" required><option value="">Select Term</option><?php foreach($terms as $t): ?><option value="<?= $t['id'] ?>" <?= $selTerm == $t['id'] ? 'selected' : '' ?>><?= e($t['term_name']) ?> (<?= e($t['session_name']) ?>)</option><?php endforeach; ?></select>
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>
    </div></div>

    <?php if (!empty($results)): ?>
        <div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table">
            <thead><tr><th>Student</th><th>Adm No</th><th>Subject</th><th>CA</th><th>Exam</th><th>Total</th><th>Grade</th><th>Remark</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                    <tr>
                        <td><strong><?= e($r['full_name']) ?></strong></td><td><?= e($r['admission_no']) ?></td><td><?= e($r['subject_name']) ?></td>
                        <td><?= $r['ca_score'] ?></td><td><?= $r['exam_score'] ?></td><td><strong><?= $r['total_score'] ?></strong></td>
                        <td><span class="badge badge-primary"><?= e($r['grade']) ?></span></td><td><?= e($r['remark']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div></div></div>
    <?php endif; ?>
</div></div></div></div>