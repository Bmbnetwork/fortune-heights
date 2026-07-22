<?php
$pageTitle = 'Upload Results';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-graduation-cap"></i> Upload Results</h2>
                    <p>
                        <?php if (!$term): ?>
                            <span class="text-danger">⚠️ No active term — contact admin</span>
                        <?php else: ?>
                            <?= e($term['term_name'] ?? '') ?> • <?= e($term['session_name'] ?? '') ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (empty($subjects)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <h3>No Subjects Assigned</h3>
                            <p>You have not been assigned any subjects yet. Please contact the administrator.</p>
                        </div>
                    </div>
                </div>
            <?php elseif (!$term): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Active Term</h3>
                            <p>Please contact the administrator to set up the current academic term.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Subject/Class Selector -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                            <input type="hidden" name="page" value="results">
                            <div class="form-group mb-0" style="flex:1;min-width:250px;">
                                <label style="font-size:12px;">Subject</label>
                                <select name="subject_id" class="form-control" onchange="handleSubjectChange(this)">
                                    <?php foreach ($subjects as $sub): ?>
                                        <option value="<?= $sub['id'] ?>" 
                                                data-class-id="<?= $sub['class_id'] ?>"
                                                <?= $sub['id'] == $selectedSubject ? 'selected' : '' ?>>
                                            <?= e($sub['subject_name']) ?> - <?= e($sub['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" name="class_id" id="classIdInput" value="<?= $selectedClass ?>">
                            <div style="margin-top:20px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> Load Students
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Results Entry Form -->
                <?php if ($subjectInfo && !empty($students)): ?>
                    <form method="POST" id="resultsForm">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="subject_id" value="<?= $selectedSubject ?>">
                        <input type="hidden" name="class_id" value="<?= $selectedClass ?>">
                        
                        <div class="card">
                            <div class="card-header" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;">
                                <div>
                                    <h3 style="margin:0;color:white;">
                                        <i class="fas fa-book"></i> <?= e($subjectInfo['subject_name']) ?>
                                    </h3>
                                    <small style="opacity:0.9;">
                                        <?= e($subjectInfo['class_name']) ?> • 
                                        <?= count($students) ?> students • 
                                        CA (max 40) + Exam (max 60) = Total (100)
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light" onclick="saveDraft()">
                                        <i class="fas fa-save"></i> Save Draft
                                    </button>
                                    <button type="submit" class="btn btn-sm btn-success" onclick="publishResults()">
                                        <i class="fas fa-check"></i> Save & Publish
                                    </button>
                                </div>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead style="background:var(--gray-50);">
                                            <tr>
                                                <th style="width:40px;">#</th>
                                                <th>Student Name</th>
                                                <th>Adm No</th>
                                                <th style="width:100px;">CA (/40)</th>
                                                <th style="width:100px;">Exam (/60)</th>
                                                <th style="width:100px;">Total</th>
                                                <th style="width:80px;">Grade</th>
                                                <th style="width:120px;">Remark</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $idx => $student): ?>
                                                <tr>
                                                    <td><?= $idx + 1 ?></td>
                                                    <td><strong><?= e($student['full_name']) ?></strong></td>
                                                    <td><code><?= e($student['admission_no']) ?></code></td>
                                                    <td>
                                                        <input type="number" 
                                                               name="scores[<?= $student['id'] ?>][ca]" 
                                                               class="form-control ca-input"
                                                               min="0" max="40" step="0.5"
                                                               value="<?= e($student['ca_score'] ?? '') ?>"
                                                               data-student="<?= $student['id'] ?>"
                                                               style="text-align:center;"
                                                               oninput="calculateTotal(<?= $student['id'] ?>)">
                                                    </td>
                                                    <td>
                                                        <input type="number" 
                                                               name="scores[<?= $student['id'] ?>][exam]" 
                                                               class="form-control exam-input"
                                                               min="0" max="60" step="0.5"
                                                               value="<?= e($student['exam_score'] ?? '') ?>"
                                                               data-student="<?= $student['id'] ?>"
                                                               style="text-align:center;"
                                                               oninput="calculateTotal(<?= $student['id'] ?>)">
                                                    </td>
                                                    <td>
                                                        <strong id="total_<?= $student['id'] ?>" style="color:var(--primary);">
                                                            <?= $student['total_score'] ?? '-' ?>
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <span id="grade_<?= $student['id'] ?>" class="badge badge-primary">
                                                            <?= e($student['grade'] ?? '-') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small id="remark_<?= $student['id'] ?>">
                                                            <?= e($student['remark'] ?? '-') ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card-footer d-flex justify-between align-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Changes are not saved until you click Save Draft or Save & Publish
                                </small>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                                        <i class="fas fa-save"></i> Save Draft
                                    </button>
                                    <button type="submit" class="btn btn-success" onclick="publishResults()">
                                        <i class="fas fa-check"></i> Save & Publish
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="publish" id="publishInput" value="0">
                    </form>
                    
                    <!-- Class Summary -->
                    <?php
                    $classTotal = $classCount = 0;
                    $gradeDistribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0];
                    foreach ($students as $s) {
                        if (!empty($s['total_score'])) {
                            $classTotal += $s['total_score'];
                            $classCount++;
                            if (isset($gradeDistribution[$s['grade'] ?? ''])) {
                                $gradeDistribution[$s['grade']]++;
                            }
                        }
                    }
                    $classAverage = $classCount > 0 ? round($classTotal / $classCount, 1) : 0;
                    ?>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar text-primary"></i> Class Performance Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-grid">
                                <div class="stat-card info">
                                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                                    <div class="stat-info">
                                        <h4>Students</h4>
                                        <div class="value"><?= $classCount ?></div>
                                    </div>
                                </div>
                                <div class="stat-card success">
                                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                                    <div class="stat-info">
                                        <h4>Class Average</h4>
                                        <div class="value"><?= $classAverage ?>%</div>
                                    </div>
                                </div>
                                <div class="stat-card warning">
                                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                                    <div class="stat-info">
                                        <h4>Grade A</h4>
                                        <div class="value"><?= $gradeDistribution['A'] ?></div>
                                    </div>
                                </div>
                                <div class="stat-card danger">
                                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div class="stat-info">
                                        <h4>Grade F</h4>
                                        <div class="value"><?= $gradeDistribution['F'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($subjectInfo && empty($students)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <h3>No Students in this Class</h3>
                                <p>There are no active students in this class yet.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
// Grading scale (matches PHP GRADING_SCALE)
const gradingScale = [
    { min: 80, max: 100, grade: 'A', remark: 'Excellent' },
    { min: 70, max: 79,  grade: 'B', remark: 'Very Good' },
    { min: 60, max: 69,  grade: 'C', remark: 'Good' },
    { min: 50, max: 59,  grade: 'D', remark: 'Fair' },
    { min: 40, max: 49,  grade: 'E', remark: 'Pass' },
    { min: 0,  max: 39,  grade: 'F', remark: 'Fail' }
];

function calculateTotal(studentId) {
    const caInput = document.querySelector(`input[name="scores[${studentId}][ca]"]`);
    const examInput = document.querySelector(`input[name="scores[${studentId}][exam]"]`);
    
    let ca = parseFloat(caInput.value) || 0;
    let exam = parseFloat(examInput.value) || 0;
    
    // Enforce limits
    if (ca > 40) { ca = 40; caInput.value = 40; }
    if (ca < 0) { ca = 0; caInput.value = 0; }
    if (exam > 60) { exam = 60; examInput.value = 60; }
    if (exam < 0) { exam = 0; examInput.value = 0; }
    
    const total = ca + exam;
    
    // Find grade
    let grade = '-', remark = '-';
    for (const range of gradingScale) {
        if (total >= range.min && total <= range.max) {
            grade = range.grade;
            remark = range.remark;
            break;
        }
    }
    
    // Update UI
    document.getElementById(`total_${studentId}`).textContent = (ca > 0 || exam > 0) ? total.toFixed(1) : '-';
    document.getElementById(`grade_${studentId}`).textContent = grade;
    document.getElementById(`remark_${studentId}`).textContent = remark;
    
    // Color code grade
    const gradeEl = document.getElementById(`grade_${studentId}`);
    gradeEl.className = 'badge badge-' + (grade === 'A' ? 'success' : (grade === 'F' ? 'danger' : 'primary'));
}

function handleSubjectChange(select) {
    const selected = select.options[select.selectedIndex];
    const classId = selected.dataset.classId;
    document.getElementById('classIdInput').value = classId;
    select.form.submit();
}

function saveDraft() {
    document.getElementById('publishInput').value = '0';
    document.getElementById('resultsForm').submit();
}

function publishResults() {
    if (confirm('Are you sure you want to publish these results? Parents will be able to see them immediately.')) {
        document.getElementById('publishInput').value = '1';
        document.getElementById('resultsForm').submit();
    } else {
        event.preventDefault();
        return false;
    }
}

// Calculate all totals on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ca-input, .exam-input').forEach(input => {
        const studentId = input.dataset.student;
        if (studentId) calculateTotal(studentId);
    });
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>