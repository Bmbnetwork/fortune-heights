<?php
$pageTitle = 'Manage Teachers';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teachers = $db->query("SELECT t.*, c.class_name FROM teachers t 
                       LEFT JOIN classes c ON t.class_id = c.id 
                       ORDER BY t.created_at DESC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-chalkboard-teacher"></i> Teachers</h2>
                    <p>Manage teaching staff and class assignments</p>
                </div>
                <a href="?page=teachers&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Teacher
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($teachers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <p>No teachers found</p>
                            <a href="?page=teachers&action=add" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Teacher
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
                                        <th>Class</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($teacher['full_name']) ?></strong>
                                                <br><small class="text-muted"><?= e($teacher['qualification'] ?? '') ?></small>
                                            </td>
                                            <td><?= e($teacher['email']) ?></td>
                                            <td><?= e($teacher['staff_id']) ?></td>
                                            <td><?= e($teacher['class_name'] ?? 'Not Assigned') ?></td>
                                            <td>
                                                <?php if ($teacher['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-btns">
                                                <a href="?page=teachers&action=edit&id=<?= $teacher['id'] ?>" class="action-btn view" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" 
                                                        onclick="confirmDelete(<?= $teacher['id'] ?>, 'teacher')" 
                                                        title="Delete">
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