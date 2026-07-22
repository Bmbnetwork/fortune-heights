<?php
$pageTitle = 'Manage Administrators';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$admins = $db->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-shield"></i> Administrators</h2>
                    <p>Manage school administrative staff</p>
                </div>
                <a href="?page=admins&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Administrator
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($admins)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-shield"></i>
                            <p>No administrators found</p>
                            <a href="?page=admins&action=add" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Administrator
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Staff ID</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admins as $admin): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($admin['full_name']) ?></strong>
                                            </td>
                                            <td><?= e($admin['email']) ?></td>
                                            <td><?= e($admin['staff_id']) ?></td>
                                            <td>
                                                <?php if ($admin['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $admin['last_login'] ? timeAgo($admin['last_login']) : '-' ?>
                                            </td>
                                            <td class="action-btns">
                                                <a href="?page=admins&action=edit&id=<?= $admin['id'] ?>" class="action-btn view" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" 
                                                        onclick="confirmDelete(<?= $admin['id'] ?>, 'admin')" 
                                                        title="Delete" <?= $admin['id'] == Auth::id() ? 'disabled' : '' ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>

<script>
function confirmDelete(id, type) {
    if (confirm('Are you sure you want to delete this ' + type + '? This action cannot be undone.')) {
        window.location.href = '?page=' + type + 's&action=delete&id=' + id;
    }
}
</script>