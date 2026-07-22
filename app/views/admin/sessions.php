<?php
$pageTitle = 'Sessions & Terms';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';
$db = db();
$sessions = $db->query("SELECT * FROM academic_sessions ORDER BY start_date DESC")->fetchAll();
$terms = $db->query("SELECT t.*, s.session_name FROM academic_terms t JOIN academic_sessions s ON t.session_id = s.id ORDER BY s.start_date DESC, t.start_date DESC")->fetchAll();
?>
<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        <div class="content-area">
            <div class="page-header"><div><h2><i class="fas fa-calendar-alt"></i> Sessions & Terms</h2></div></div>
            <?php displayFlash(); ?>
            
            <div class="card mb-3">
                <div class="card-header d-flex justify-between"><h3>Academic Sessions</h3><a href="?page=sessions&action=add_session" class="btn btn-sm btn-primary">Add Session</a></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table">
                    <thead><tr><th>Session Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td><strong><?= e($s['session_name']) ?></strong></td>
                                <td><?= formatDate($s['start_date']) ?></td><td><?= formatDate($s['end_date']) ?></td>
                                <td><?= $s['is_current'] ? '<span class="badge badge-success">Current</span>' : '<span class="badge badge-secondary">Past</span>' ?></td>
                                <td class="action-btns">
                                    <a href="?page=sessions&action=edit_session&id=<?= $s['id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                                    <button class="action-btn delete" onclick="if(confirm('Delete?'))location.href='?page=sessions&action=delete_session&id=<?= $s['id']?>'"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div></div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-between"><h3>Academic Terms</h3><a href="?page=sessions&action=add_term" class="btn btn-sm btn-primary">Add Term</a></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table">
                    <thead><tr><th>Term Name</th><th>Session</th><th>Start Date</th><th>End Date</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($terms as $t): ?>
                            <tr>
                                <td><strong><?= e($t['term_name']) ?></strong></td>
                                <td><?= e($t['session_name']) ?></td>
                                <td><?= formatDate($t['start_date']) ?></td><td><?= formatDate($t['end_date']) ?></td>
                                <td><?= $t['is_current'] ? '<span class="badge badge-success">Current</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
                                <td class="action-btns">
                                    <a href="?page=sessions&action=edit_term&id=<?= $t['id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                                    <button class="action-btn delete" onclick="if(confirm('Delete?'))location.href='?page=sessions&action=delete_term&id=<?= $t['id']?>'"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table></div></div>
            </div>
        </div>
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>