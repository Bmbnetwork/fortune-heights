<?php
$pageTitle = 'Manage Parents';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$parents = $db->query("SELECT p.*, COUNT(s.id) as children_count 
                      FROM parents p 
                      LEFT JOIN students s ON p.id = s.parent_id 
                      GROUP BY p.id 
                      ORDER BY p.created_at DESC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-users"></i> Parents</h2>
                    <p>Manage parent accounts and family information</p>
                </div>
                <a href="?page=parents&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Parent
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($parents)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No parents found</p>
                            <a href="?page=parents&action=add" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Parent
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Children</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parents as $parent): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($parent['full_name']) ?></strong>
                                                <br><small class="text-muted"><?= e($parent['occupation'] ?? '') ?></small>
                                            </td>
                                            <td><?= e($parent['email']) ?></td>
                                            <td><?= e($parent['phone']) ?></td>
                                            <td><?= number_format($parent['children_count']) ?> child<?= $parent['children_count'] != 1 ? 'ren' : '' ?></td>
                                            <td>
                                                <?php if ($parent['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-btns">
                                                <a href="?page=parents&action=edit&id=<?= $parent['id'] ?>" class="action-btn view" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" 
                                                        onclick="confirmDelete(<?= $parent['id'] ?>, 'parent')" 
                                                        title="Delete" <?= $parent['children_count'] > 0 ? 'disabled title="Cannot delete parent with children"' : '' ?>>
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