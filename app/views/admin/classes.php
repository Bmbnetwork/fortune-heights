<?php
$pageTitle = 'Manage Classes';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$classes = $db->query("SELECT c.*, COUNT(s.id) as student_count 
                      FROM classes c LEFT JOIN students s ON c.id = s.class_id 
                      GROUP BY c.id ORDER BY c.level, c.class_name")->fetchAll();
?>
<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        <div class="content-area">
            <div class="page-header">
                <div><h2><i class="fas fa-school"></i> Classes</h2><p>Manage school classes and capacities</p></div>
                <a href="?page=classes&action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Class</a>
            </div>
            <?php displayFlash(); ?>
            <div class="card"><div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Class Name</th><th>Code</th><th>Level</th><th>Capacity</th><th>Students</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><strong><?= e($class['class_name']) ?></strong></td>
                                    <td><code><?= e($class['class_code']) ?></code></td>
                                    <td><span class="badge badge-info"><?= e($class['level']) ?></span></td>
                                    <td><?= $class['capacity'] ?></td>
                                    <td><?= $class['student_count'] ?> / <?= $class['capacity'] ?></td>
                                    <td class="action-btns">
                                        <a href="?page=classes&action=edit&id=<?= $class['id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                                        <button type="button" class="action-btn delete" onclick="confirmDelete(<?= $class['id'] ?>, 'classes')"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>
<script>function confirmDelete(id, page) { if(confirm('Delete this class?')) location.href='?page='+page+'&action=delete&id='+id; }</script>