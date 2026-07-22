<?php
$pageTitle = 'Manage Subjects'; Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db();
$subjects = $db->query("SELECT sub.*, c.class_name, t.full_name as teacher_name 
                       FROM subjects sub JOIN classes c ON sub.class_id = c.id 
                       LEFT JOIN teachers t ON sub.teacher_id = t.id ORDER BY c.class_name, sub.subject_name")->fetchAll();
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><div><h2><i class="fas fa-book"></i> Subjects</h2></div><a href="?page=subjects&action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Subject</a></div>
    <?php displayFlash(); ?>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table">
        <thead><tr><th>Subject Name</th><th>Code</th><th>Class</th><th>Teacher</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($subjects as $sub): ?>
                <tr>
                    <td><strong><?= e($sub['subject_name']) ?></strong></td><td><code><?= e($sub['subject_code']) ?></code></td>
                    <td><?= e($sub['class_name']) ?></td><td><?= e($sub['teacher_name'] ?? 'Unassigned') ?></td>
                    <td class="action-btns">
                        <a href="?page=subjects&action=edit&id=<?= $sub['id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                        <button class="action-btn delete" onclick="if(confirm('Delete?'))location.href='?page=subjects&action=delete&id=<?= $sub['id']?>'"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table></div></div></div>
</div></div></div></div>