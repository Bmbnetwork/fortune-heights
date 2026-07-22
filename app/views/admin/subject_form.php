<?php
Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $subject = null; $isEdit = ($action ?? '') === 'edit';
$classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
$teachers = $db->query("SELECT * FROM teachers WHERE is_active = 1 ORDER BY full_name")->fetchAll();
if ($isEdit) { $subject = $db->prepare("SELECT * FROM subjects WHERE id=?"); $subject->execute([$_GET['id']]); $subject = $subject->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = Security::clean($_POST['subject_name']); $code = Security::clean($_POST['subject_code']);
    $cid = (int)$_POST['class_id']; $tid = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
    if ($isEdit) $db->prepare("UPDATE subjects SET subject_name=?, subject_code=?, class_id=?, teacher_id=? WHERE id=?")->execute([$name, $code, $cid, $tid, $subject['id']]);
    else $db->prepare("INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES (?,?,?,?)")->execute([$name, $code, $cid, $tid]);
    setFlash('success', 'Subject saved'); redirect('?page=subjects');
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><h2><?= $isEdit ? 'Edit' : 'Add' ?> Subject</h2><a href="?page=subjects" class="btn btn-secondary">Back</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><form method="POST"><?= Security::csrfField() ?>
        <div class="grid-2">
            <div class="form-group"><label>Subject Name *</label><input type="text" name="subject_name" class="form-control" value="<?= e($subject['subject_name'] ?? '') ?>" required></div>
            <div class="form-group"><label>Subject Code *</label><input type="text" name="subject_code" class="form-control" value="<?= e($subject['subject_code'] ?? '') ?>" required></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Class *</label><select name="class_id" class="form-control" required>
                <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= ($subject['class_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="form-group"><label>Assigned Teacher</label><select name="teacher_id" class="form-control">
                <option value="">-- Unassigned --</option>
                <?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= ($subject['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['full_name']) ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <button type="submit" class="btn btn-primary">Save Subject</button>
    </form></div></div>
</div></div></div></div>