<?php
$pageTitle = 'Announcements'; Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db();
$announcements = $db->query("SELECT a.*, ad.full_name as author FROM announcements a JOIN admins ad ON a.created_by = ad.id ORDER BY a.created_at DESC")->fetchAll();
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><div><h2><i class="fas fa-bullhorn"></i> Announcements</h2></div><a href="?page=announcements&action=create" class="btn btn-primary"><i class="fas fa-plus"></i> New Announcement</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table">
        <thead><tr><th>Title</th><th>Audience</th><th>Priority</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($announcements as $ann): ?>
                <tr>
                    <td><strong><?= e($ann['title']) ?></strong><br><small class="text-muted">By <?= e($ann['author']) ?></small></td>
                    <td><span class="badge badge-info"><?= e(ucfirst($ann['target_audience'])) ?></span></td>
                    <td><span class="badge badge-<?= $ann['priority'] == 'urgent' ? 'danger' : 'warning' ?>"><?= e(ucfirst($ann['priority'])) ?></span></td>
                    <td><span class="badge badge-<?= $ann['status'] == 'published' ? 'success' : 'secondary' ?>"><?= e(ucfirst($ann['status'])) ?></span></td>
                    <td><?= formatDate($ann['created_at']) ?></td>
                    <td class="action-btns">
                        <a href="?page=announcements&action=edit&id=<?= $ann['id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                        <button class="action-btn delete" onclick="if(confirm('Delete?'))location.href='?page=announcements&action=delete&id=<?= $ann['id']?>'"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div></div></div>
</div></div></div></div>