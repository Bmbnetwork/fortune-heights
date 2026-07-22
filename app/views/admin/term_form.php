<?php
Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $term = null; $isEdit = ($action ?? '') === 'edit_term';
$sessions = $db->query("SELECT * FROM academic_sessions ORDER BY start_date DESC")->fetchAll();
if ($isEdit) { $term = $db->prepare("SELECT * FROM academic_terms WHERE id=?"); $term->execute([$_GET['id']]); $term = $term->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = (int)$_POST['session_id']; $name = Security::clean($_POST['term_name']); $start = $_POST['start_date']; $end = $_POST['end_date']; $isCurrent = isset($_POST['is_current']) ? 1 : 0;
    if ($isCurrent) $db->prepare("UPDATE academic_terms SET is_current = 0 WHERE session_id = ?")->execute([$sid]);
    if ($isEdit) $db->prepare("UPDATE academic_terms SET session_id=?, term_name=?, start_date=?, end_date=?, is_current=? WHERE id=?")->execute([$sid, $name, $start, $end, $isCurrent, $term['id']]);
    else $db->prepare("INSERT INTO academic_terms (session_id, term_name, start_date, end_date, is_current) VALUES (?,?,?,?,?)")->execute([$sid, $name, $start, $end, $isCurrent]);
    setFlash('success', 'Term saved'); redirect('?page=sessions');
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><h2><?= $isEdit ? 'Edit' : 'Add' ?> Term</h2><a href="?page=sessions" class="btn btn-secondary">Back</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><form method="POST"><?= Security::csrfField() ?>
        <div class="grid-2">
            <div class="form-group"><label>Session *</label><select name="session_id" class="form-control" required>
                <?php foreach($sessions as $s): ?><option value="<?= $s['id'] ?>" <?= ($term['session_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['session_name']) ?></option><?php endforeach; ?>
            </select></div>
            <div class="form-group"><label>Term Name *</label><input type="text" name="term_name" class="form-control" value="<?= e($term['term_name'] ?? '') ?>" placeholder="e.g. First Term" required></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Start Date *</label><input type="date" name="start_date" class="form-control" value="<?= e($term['start_date'] ?? '') ?>" required></div>
            <div class="form-group"><label>End Date *</label><input type="date" name="end_date" class="form-control" value="<?= e($term['end_date'] ?? '') ?>" required></div>
        </div>
        <div class="form-group"><label class="d-flex align-center gap-2"><input type="checkbox" name="is_current" <?= ($term['is_current'] ?? 0) ? 'checked' : '' ?>> Set as Current Term</label></div>
        <button type="submit" class="btn btn-primary">Save Term</button>
    </form></div></div>
</div></div></div></div>