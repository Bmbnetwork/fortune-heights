<?php
$pageTitle = 'Manage Students';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$students = $db->query("SELECT s.*, c.class_name, p.full_name as parent_name 
                       FROM students s 
                       JOIN classes c ON s.class_id = c.id 
                       JOIN parents p ON s.parent_id = p.id 
                       ORDER BY s.created_at DESC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-graduate"></i> Students</h2>
                    <p>Manage student enrollment and records</p>
                </div>
                <a href="?page=students&action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Student
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <p>No students found</p>
                            <a href="?page=students&action=add" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Student
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Admission No</th>
                                        <th>Class</th>
                                        <th>Parent</th>
                                        <th>Date of Birth</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e($student['full_name']) ?></strong>
                                            </td>
                                            <td><?= e($student['admission_no']) ?></td>
                                            <td><?= e($student['class_name']) ?></td>
                                            <td><?= e($student['parent_name']) ?></td>
                                            <td><?= formatDate($student['date_of_birth']) ?></td>
                                            <td>
                                                <?php if ($student['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-btns">
                                                <a href="?page=students&action=edit&id=<?= $student['id'] ?>" class="action-btn view" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="action-btn delete" 
                                                        onclick="confirmDelete(<?= $student['id'] ?>, 'student')" 
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