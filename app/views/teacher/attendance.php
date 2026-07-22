<?php
$pageTitle = 'Mark Attendance';
require_once APP_PATH . '/views/layouts/header.php';
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2>Mark Attendance</h2>
                    <p><?= e($class['class_name']) ?> - <?= formatDate($date, 'l, F d, Y') ?></p>
                </div>
                <a href="?page=attendance-history&class_id=<?= $classId ?>" class="btn btn-secondary">
                    <i class="fas fa-history"></i> View History
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap:wrap;">
                        <input type="hidden" name="page" value="attendance">
                        <div class="form-group mb-0" style="flex:1;min-width:200px;">
                            <label>Class</label>
                            <select name="class_id" class="form-control" onchange="this.form.submit()">
                                <?php
                                $classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
                                foreach ($classes as $c):
                                ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $classId ? 'selected' : '' ?>>
                                        <?= e($c['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0" style="flex:1;min-width:200px;">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control" 
                                   value="<?= e($date) ?>" max="<?= date('Y-m-d') ?>"
                                   onchange="this.form.submit()">
                        </div>
                        <div style="margin-top:24px;">
                            <button type="button" class="btn btn-success btn-sm" onclick="markAll('Present')">
                                <i class="fas fa-check"></i> All Present
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="markAll('Absent')">
                                <i class="fas fa-times"></i> All Absent
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <form method="POST" action="?page=attendance-save" id="attendanceForm">
                <?= Security::csrfField() ?>
                <input type="hidden" name="class_id" value="<?= $classId ?>">
                <input type="hidden" name="date" value="<?= e($date) ?>">
                
                <div class="card">
                    <div class="card-header">
                        <h3>Students (<?= count($students) ?>)</h3>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <p>No students in this class</p>
                            </div>
                        <?php else: ?>
                            <div class="attendance-grid">
                                <?php foreach ($students as $student): 
                                    $currentStatus = $existing[$student['id']] ?? 'Present';
                                ?>
                                    <div class="attendance-card">
                                        <div class="student-avatar">
                                            <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                                        </div>
                                        <div class="student-info">
                                            <div class="student-name"><?= e($student['full_name']) ?></div>
                                            <div class="student-id"><?= e($student['admission_no']) ?></div>
                                        </div>
                                        <div class="attendance-options">
                                            <button type="button" class="attendance-option present <?= $currentStatus === 'Present' ? 'active' : '' ?>"
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'Present')">P</button>
                                            <button type="button" class="attendance-option absent <?= $currentStatus === 'Absent' ? 'active' : '' ?>"
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'Absent')">A</button>
                                            <button type="button" class="attendance-option late <?= $currentStatus === 'Late' ? 'active' : '' ?>"
                                                    onclick="setStatus(this, <?= $student['id'] ?>, 'Late')">L</button>
                                            <input type="hidden" name="attendance[<?= $student['id'] ?>]" 
                                                   value="<?= e($currentStatus) ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
function setStatus(btn, studentId, status) {
    const card = btn.closest('.attendance-card');
    card.querySelectorAll('.attendance-option').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    card.querySelector('input[type=hidden]').value = status;
}

function markAll(status) {
    document.querySelectorAll('.attendance-card').forEach(card => {
        card.querySelectorAll('.attendance-option').forEach(b => b.classList.remove('active'));
        card.querySelector('.attendance-option.' + status.toLowerCase()).classList.add('active');
        card.querySelector('input[type=hidden]').value = status;
    });
}

// Confirm before submit
document.getElementById('attendanceForm').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to save attendance?')) {
        e.preventDefault();
    }
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>