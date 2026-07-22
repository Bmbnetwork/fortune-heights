<?php
Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $session = null; $isEdit = ($action ?? '') === 'edit_session';
if ($isEdit) { $session = $db->prepare("SELECT * FROM academic_sessions WHERE id=?"); $session->execute([$_GET['id']]); $session = $session->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = Security::clean($_POST['session_name']); $start = $_POST['start_date']; $end = $_POST['end_date']; $isCurrent = isset($_POST['is_current']) ? 1 : 0;
    if ($isCurrent) $db->query("UPDATE academic_sessions SET is_current = 0");
    if ($isEdit) $db->prepare("UPDATE academic_sessions SET session_name=?, start_date=?, end_date=?, is_current=? WHERE id=?")->execute([$name, $start, $end, $isCurrent, $session['id']]);
    else $db->prepare("INSERT INTO academic_sessions (session_name, start_date, end_date, is_current) VALUES (?,?,?,?)")->execute([$name, $start, $end, $isCurrent]);
    setFlash('success', 'Session saved'); redirect('?page=sessions');
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><h2><?= $isEdit ? 'Edit' : 'Add' ?> Session</h2><a href="?page=sessions" class="btn btn-secondary">Back</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><form method="POST"><?= Security::csrfField() ?>
        <div class="grid-2">
            <div class="form-group"><label>Session Name *</label><input type="text" name="session_name" class="form-control" value="<?= e($session['session_name'] ?? '') ?>" placeholder="e.g. 2025/2026" required></div>
            <div class="form-group"><label>&nbsp;</label><label class="d-flex align-center gap-2" style="margin-top:30px;"><input type="checkbox" name="is_current" <?= ($session['is_current'] ?? 0) ? 'checked' : '' ?>> Set as Current Session</label></div>
        </div>
        <div class="grid-2">
            <div class="form-group"><label>Start Date *</label><input type="date" name="start_date" class="form-control" value="<?= e($session['start_date'] ?? '') ?>" required></div>
            <div class="form-group"><label>End Date *</label><input type="date" name="end_date" class="form-control" value="<?= e($session['end_date'] ?? '') ?>" required></div>
        </div>
        <button type="submit" class="btn btn-primary">Save Session</button>
    </form></div></div>
</div></div></div></div>