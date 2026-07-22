<?php
$pageTitle = 'Academic Results';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$terms = $db->query("SELECT t.*, s.session_name FROM academic_terms t 
    JOIN academic_sessions s ON t.session_id = s.id ORDER BY t.start_date DESC")->fetchAll();
$selectedTerm = $_GET['term_id'] ?? (getCurrentTerm()['id'] ?? null);
$selectedChild = $_GET['child_id'] ?? null;

// Get children
$stmt = $db->prepare("SELECT s.*, c.class_name FROM students s 
    JOIN classes c ON s.class_id = c.id 
    WHERE s.parent_id = ? AND s.is_active = 1 ORDER BY s.full_name");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

if (!$selectedChild && !empty($children)) $selectedChild = $children[0]['id'];

// Get results
$results = [];
$subjectTotals = [];
$totalSubjects = 0;
$totalPoints = 0;

if ($selectedChild && $selectedTerm) {
    $stmt = $db->prepare("SELECT r.*, sub.subject_name 
        FROM results r 
        JOIN subjects sub ON r.subject_id = sub.id 
        WHERE r.student_id = ? AND r.term_id = ? AND r.is_published = 1
        ORDER BY sub.subject_name");
    $stmt->execute([$selectedChild, $selectedTerm]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $r) {
        $totalSubjects++;
        $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 0];
        $totalPoints += $gradePoints[$r['grade']] ?? 0;
    }
}

$averageScore = $totalSubjects > 0 ? round(array_sum(array_column($results, 'total_score')) / $totalSubjects, 1) : 0;
$gpa = $totalSubjects > 0 ? round($totalPoints / $totalSubjects, 2) : 0;

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
                    <h2><i class="fas fa-graduation-cap"></i> Academic Results</h2>
                    <p>View your child's academic performance</p>
                </div>
                <?php if (!empty($results)): ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Results
                    </button>
                <?php endif; ?>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (empty($children)): ?>
                <div class="card"><div class="card-body">
                    <div class="empty-state"><i class="fas fa-child"></i><p>No children registered</p></div>
                </div></div>
            <?php else: ?>
                <!-- Filters -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                            <input type="hidden" name="page" value="results">
                            <?php if (count($children) > 1): ?>
                                <select name="child_id" class="form-control" style="width:250px;" onchange="this.form.submit()">
                                    <?php foreach ($children as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedChild ? 'selected' : '' ?>>
                                            <?= e($c['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <select name="term_id" class="form-control" style="width:250px;" onchange="this.form.submit()">
                                <?php foreach ($terms as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $selectedTerm ? 'selected' : '' ?>>
                                        <?= e($t['term_name']) ?> - <?= e($t['session_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if ($selectedChildData && $selectedTerm): ?>
                    <!-- Summary -->
                    <div class="stats-grid">
                        <div class="stat-card info">
                            <div class="stat-icon"><i class="fas fa-book"></i></div>
                            <div class="stat-info">
                                <h4>Subjects</h4>
                                <div class="value"><?= $totalSubjects ?></div>
                            </div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-info">
                                <h4>Average Score</h4>
                                <div class="value"><?= $averageScore ?>%</div>
                            </div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon"><i class="fas fa-award"></i></div>
                            <div class="stat-info">
                                <h4>GPA</h4>
                                <div class="value"><?= $gpa ?></div>
                                <div class="change">out of 5.0</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                            <div class="stat-info">
                                <h4>Performance</h4>
                                <div class="value" style="font-size:20px;">
                                    <?= $averageScore >= 80 ? 'Excellent' : ($averageScore >= 70 ? 'Very Good' : ($averageScore >= 60 ? 'Good' : ($averageScore >= 50 ? 'Fair' : 'Needs Improvement'))) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results Table -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-list"></i> Subject Results</h3>
                            <span class="badge badge-info"><?= e($selectedChildData['full_name']) ?> • <?= e($selectedChildData['class_name']) ?></span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($results)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>No published results for this term yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Subject</th>
                                                <th>CA (30)</th>
                                                <th>Exam (70)</th>
                                                <th>Total (100)</th>
                                                <th>Grade</th>
                                                <th>Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $idx => $r): ?>
                                                <tr>
                                                    <td><?= $idx + 1 ?></td>
                                                    <td><strong><?= e($r['subject_name']) ?></strong></td>
                                                    <td><?= $r['ca_score'] ?></td>
                                                    <td><?= $r['exam_score'] ?></td>
                                                    <td><strong><?= $r['total_score'] ?></strong></td>
                                                    <td>
                                                        <span class="badge badge-<?= $r['grade'] === 'A' ? 'success' : ($r['grade'] === 'F' ? 'danger' : 'primary') ?>">
                                                            <?= e($r['grade']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= e($r['remark']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr style="background:var(--gray-50);font-weight:700;">
                                                <td colspan="4" class="text-right">TOTAL / AVERAGE:</td>
                                                <td><?= array_sum(array_column($results, 'total_score')) ?> / <?= $averageScore ?></td>
                                                <td colspan="2">GPA: <?= $gpa ?>/5.0</td>
                                            </tr>
                                        </tfoot>
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