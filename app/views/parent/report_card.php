<?php
$pageTitle = 'Report Card';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$terms = $db->query("SELECT t.*, s.session_name FROM academic_terms t 
    JOIN academic_sessions s ON t.session_id = s.id ORDER BY t.start_date DESC")->fetchAll();
$selectedTerm = $_GET['term_id'] ?? (getCurrentTerm()['id'] ?? null);
$selectedChild = $_GET['child_id'] ?? null;

$stmt = $db->prepare("SELECT s.*, c.class_name, t.full_name as class_teacher 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    LEFT JOIN teachers t ON c.id = t.class_id 
    WHERE s.parent_id = ? AND s.is_active = 1 ORDER BY s.full_name");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

if (!$selectedChild && !empty($children)) $selectedChild = $children[0]['id'];

$results = [];
$summary = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0];

if ($selectedChild && $selectedTerm) {
    $stmt = $db->prepare("SELECT r.*, sub.subject_name 
        FROM results r JOIN subjects sub ON r.subject_id = sub.id 
        WHERE r.student_id = ? AND r.term_id = ? AND r.is_published = 1
        ORDER BY sub.subject_name");
    $stmt->execute([$selectedChild, $selectedTerm]);
    $results = $stmt->fetchAll();
    
    // Attendance for term
    $attStmt = $db->prepare("SELECT status, COUNT(*) as count FROM attendance 
        WHERE student_id = ? AND term_id = ? GROUP BY status");
    $attStmt->execute([$selectedChild, $selectedTerm]);
    foreach ($attStmt->fetchAll() as $row) {
        $summary[strtolower($row['status'])] = $row['count'];
        $summary['total'] += $row['count'];
    }
}

$selectedChildData = null;
foreach ($children as $c) {
    if ($c['id'] == $selectedChild) { $selectedChildData = $c; break; }
}

$averageScore = !empty($results) ? round(array_sum(array_column($results, 'total_score')) / count($results), 1) : 0;
$attendancePct = $summary['total'] > 0 ? round((($summary['present'] + $summary['late']) / $summary['total']) * 100, 1) : 0;
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header no-print">
                <div>
                    <h2><i class="fas fa-file-alt"></i> Report Card</h2>
                    <p>Official term report for your child</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!empty($results)): ?>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Print Report Card
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($children)): ?>
                <div class="card"><div class="card-body">
                    <div class="empty-state"><i class="fas fa-child"></i><p>No children registered</p></div>
                </div></div>
            <?php else: ?>
                <!-- Filters (hidden on print) -->
                <div class="card mb-3 no-print">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                            <input type="hidden" name="page" value="report-card">
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
                    <!-- Report Card -->
                    <div class="card report-card">
                        <div class="card-body">
                            <!-- Header -->
                            <div style="text-align:center;border-bottom:3px double var(--primary);padding-bottom:20px;margin-bottom:20px;">
                                <h1 style="color:var(--primary);margin:0;font-size:28px;"><?= e(SCHOOL_NAME) ?></h1>
                                <p style="margin:5px 0;color:var(--gray-600);"><?= e(SCHOOL_ADDRESS) ?></p>
                                <p style="margin:5px 0;color:var(--gray-600);">📞 <?= e(SCHOOL_PHONE) ?> | ✉️ <?= e(SCHOOL_EMAIL) ?></p>
                                <h2 style="margin-top:15px;color:var(--gray-800);font-size:20px;">STUDENT REPORT CARD</h2>
                            </div>
                            
                            <!-- Student Info -->
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:25px;padding:15px;background:var(--gray-50);border-radius:var(--radius);">
                                <div><strong>Student Name:</strong> <?= e($selectedChildData['full_name']) ?></div>
                                <div><strong>Admission No:</strong> <?= e($selectedChildData['admission_no']) ?></div>
                                <div><strong>Class:</strong> <?= e($selectedChildData['class_name']) ?></div>
                                <div><strong>Class Teacher:</strong> <?= e($selectedChildData['class_teacher'] ?? 'N/A') ?></div>
                                <div><strong>Term:</strong> <?= e($terms[array_search($selectedTerm, array_column($terms, 'id'))]['term_name'] ?? '') ?></div>
                                <div><strong>Session:</strong> <?= e($terms[array_search($selectedTerm, array_column($terms, 'id'))]['session_name'] ?? '') ?></div>
                                <div><strong>Gender:</strong> <?= e($selectedChildData['gender']) ?></div>
                                <div><strong>Age:</strong> <?= floor((time() - strtotime($selectedChildData['date_of_birth'])) / (365.25 * 24 * 3600)) ?> years</div>
                            </div>
                            
                            <?php if (empty($results)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-graduation-cap"></i>
                                    <p>Results not yet published for this term</p>
                                </div>
                            <?php else: ?>
                                <!-- Academic Performance -->
                                <h3 style="color:var(--primary);border-bottom:2px solid var(--primary);padding-bottom:8px;">📚 ACADEMIC PERFORMANCE</h3>
                                <table class="table" style="margin-top:10px;">
                                    <thead style="background:var(--primary);color:white;">
                                        <tr>
                                            <th>S/N</th>
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
                                                <td><?= e($r['subject_name']) ?></td>
                                                <td><?= $r['ca_score'] ?></td>
                                                <td><?= $r['exam_score'] ?></td>
                                                <td><strong><?= $r['total_score'] ?></strong></td>
                                                <td><strong><?= e($r['grade']) ?></strong></td>
                                                <td><?= e($r['remark']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot style="background:var(--gray-100);font-weight:700;">
                                        <tr>
                                            <td colspan="4" style="text-align:right;">TOTAL / AVERAGE:</td>
                                            <td><?= array_sum(array_column($results, 'total_score')) ?> / <?= $averageScore ?>%</td>
                                            <td colspan="2">
                                                <?= $averageScore >= 80 ? 'Excellent' : ($averageScore >= 70 ? 'Very Good' : ($averageScore >= 60 ? 'Good' : ($averageScore >= 50 ? 'Fair' : 'Needs Improvement'))) ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                
                                <!-- Grading Scale -->
                                <div style="margin-top:20px;padding:15px;background:var(--gray-50);border-radius:var(--radius);">
                                    <h4 style="margin:0 0 10px;">📊 Grading Scale</h4>
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;font-size:13px;">
                                        <div><strong>A (80-100):</strong> Excellent</div>
                                        <div><strong>B (70-79):</strong> Very Good</div>
                                        <div><strong>C (60-69):</strong> Good</div>
                                        <div><strong>D (50-59):</strong> Fair</div>
                                        <div><strong>E (40-49):</strong> Pass</div>
                                        <div><strong>F (0-39):</strong> Fail</div>
                                    </div>
                                </div>
                                
                                <!-- Attendance -->
                                <h3 style="color:var(--primary);border-bottom:2px solid var(--primary);padding-bottom:8px;margin-top:25px;">📅 ATTENDANCE RECORD</h3>
                                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-top:15px;">
                                    <div style="padding:15px;background:#d1fae5;border-radius:var(--radius);text-align:center;">
                                        <div style="font-size:12px;color:#065f46;">Days Present</div>
                                        <div style="font-size:28px;font-weight:700;color:#065f46;"><?= $summary['present'] ?></div>
                                    </div>
                                    <div style="padding:15px;background:#fef3c7;border-radius:var(--radius);text-align:center;">
                                        <div style="font-size:12px;color:#92400e;">Days Late</div>
                                        <div style="font-size:28px;font-weight:700;color:#92400e;"><?= $summary['late'] ?></div>
                                    </div>
                                    <div style="padding:15px;background:#fee2e2;border-radius:var(--radius);text-align:center;">
                                        <div style="font-size:12px;color:#991b1b;">Days Absent</div>
                                        <div style="font-size:28px;font-weight:700;color:#991b1b;"><?= $summary['absent'] ?></div>
                                    </div>
                                    <div style="padding:15px;background:#dbeafe;border-radius:var(--radius);text-align:center;">
                                        <div style="font-size:12px;color:#1e40af;">Attendance %</div>
                                        <div style="font-size:28px;font-weight:700;color:#1e40af;"><?= $attendancePct ?>%</div>
                                    </div>
                                </div>
                                
                                <!-- Teacher's Comment -->
                                <div style="margin-top:30px;padding:20px;border:2px dashed var(--gray-300);border-radius:var(--radius);">
                                    <h4 style="margin:0 0 10px;color:var(--primary);">📝 Class Teacher's Comment</h4>
                                    <p style="min-height:60px;margin:0;color:var(--gray-700);">
                                        <?= $averageScore >= 80 ? 'An outstanding performance! Keep up the excellent work.' : 
                                           ($averageScore >= 70 ? 'Very good performance. Continue to strive for excellence.' : 
                                           ($averageScore >= 60 ? 'Good performance with room for improvement.' : 
                                           ($averageScore >= 50 ? 'Fair performance. More effort is needed.' : 
                                           'Needs significant improvement. Please see the class teacher.'))) ?>
                                    </p>
                                </div>
                                
                                <!-- Signatures -->
                                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:40px;">
                                    <div style="text-align:center;">
                                        <div style="border-top:1px solid var(--gray-600);padding-top:8px;margin-top:50px;">
                                            <strong>Class Teacher</strong>
                                        </div>
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="border-top:1px solid var(--gray-600);padding-top:8px;margin-top:50px;">
                                            <strong>Principal</strong>
                                        </div>
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="border-top:1px solid var(--gray-600);padding-top:8px;margin-top:50px;">
                                            <strong>Parent/Guardian</strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="text-align:center;margin-top:30px;padding-top:20px;border-top:1px solid var(--gray-200);">
                                    <small class="text-muted">
                                        Generated on <?= date('F d, Y h:i A') ?> | <?= e(SCHOOL_NAME) ?>
                                    </small>
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

<style>
@media print {
    .no-print, .sidebar, .topbar, .footer, .menu-toggle { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .content-area { padding: 0 !important; }
    .report-card { box-shadow: none !important; border: none !important; }
    body { background: white !important; }
    @page { margin: 1cm; }
}
</style>