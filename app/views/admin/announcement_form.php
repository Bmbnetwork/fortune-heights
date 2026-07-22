<?php
Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $ann = null; $isEdit = isset($_GET['action']) && $_GET['action'] === 'edit';
if ($isEdit) { $ann = $db->prepare("SELECT * FROM announcements WHERE id=?"); $ann->execute([$_GET['id']]); $ann = $ann->fetch(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = Security::clean($_POST['title']); $content = Security::cleanHtml($_POST['content']);
    $audience = $_POST['target_audience']; $priority = $_POST['priority']; $status = $_POST['status'];
    $scheduled = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

    if ($isEdit) {
        $db->prepare("UPDATE announcements SET title=?, content=?, target_audience=?, priority=?, status=?, scheduled_at=?, published_at=? WHERE id=?")
           ->execute([$title, $content, $audience, $priority, $status, $scheduled, $publishedAt, $ann['id']]);
    } else {
        $db->prepare("INSERT INTO announcements (title, content, target_audience, priority, status, scheduled_at, published_at, created_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$title, $content, $audience, $priority, $status, $scheduled, $publishedAt, Auth::id()]);
    }
    setFlash('success', 'Announcement saved'); redirect('?page=announcements');
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><h2><?= $isEdit ? 'Edit' : 'Create' ?> Announcement</h2><a href="?page=announcements" class="btn btn-secondary">Back</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><form method="POST"><?= Security::csrfField() ?>
        <div class="form-group"><label>Title *</label><input type="text" name="title" class="form-control" value="<?= e($ann['title'] ?? '') ?>" required></div>
        <div class="form-group"><label>Content *</label><textarea name="content" class="form-control" rows="6" required><?= e($ann['content'] ?? '') ?></textarea></div>
        <div class="grid-3">
            <div class="form-group"><label>Audience</label><select name="target_audience" class="form-control">
                <option value="all" <?= ($ann['target_audience'] ?? 'all') == 'all' ? 'selected' : '' ?>>Everyone</option>
                <option value="parents" <?= ($ann['target_audience'] ?? '') == 'parents' ? 'selected' : '' ?>>Parents Only</option>
                <option value="teachers" <?= ($ann['target_audience'] ?? '') == 'teachers' ? 'selected' : '' ?>>Teachers Only</option>
            </select></div>
            <div class="form-group"><label>Priority</label><select name="priority" class="form-control">
                <option value="low" <?= ($ann['priority'] ?? 'medium') == 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= ($ann['priority'] ?? 'medium') == 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= ($ann['priority'] ?? '') == 'high' ? 'selected' : '' ?>>High</option>
                <option value="urgent" <?= ($ann['priority'] ?? '') == 'urgent' ? 'selected' : '' ?>>Urgent</option>
            </select></div>
            <div class="form-group"><label>Status</label><select name="status" class="form-control">
                <option value="draft" <?= ($ann['status'] ?? 'draft') == 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= ($ann['status'] ?? '') == 'published' ? 'selected' : '' ?>>Publish Now</option>
                <option value="scheduled" <?= ($ann['status'] ?? '') == 'scheduled' ? 'selected' : '' ?>>Schedule</option>
            </select></div>
        </div>
        <div class="form-group"><label>Scheduled Date/Time (if scheduling)</label><input type="datetime-local" name="scheduled_at" class="form-control" value="<?= e($ann['scheduled_at'] ?? '') ?>"></div>
        <button type="submit" class="btn btn-primary">Save Announcement</button>
    </form></div></div>
</div></div></div></div>