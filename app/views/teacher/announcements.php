<?php
$pageTitle = 'School Announcements';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$stmt = $db->query("SELECT * FROM announcements 
    WHERE status = 'published' AND target_audience IN ('all','teachers') 
    ORDER BY published_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-bullhorn"></i> School Announcements</h2>
                    <p>Stay updated with the latest school news</p>
                </div>
                <span class="badge badge-info"><?= count($announcements) ?> announcements</span>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (empty($announcements)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-bullhorn"></i>
                            <h3>No Announcements</h3>
                            <p>Check back later for school updates</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-card <?= e($ann['priority']) ?>">
                        <div class="announcement-header">
                            <div style="flex:1;">
                                <div class="announcement-title"><?= e($ann['title']) ?></div>
                                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                                    <span class="badge badge-<?= $ann['priority'] === 'urgent' ? 'danger' : ($ann['priority'] === 'high' ? 'warning' : 'info') ?>">
                                        <i class="fas fa-<?= $ann['priority'] === 'urgent' ? 'exclamation-circle' : 'info-circle' ?>"></i>
                                        <?= e(ucfirst($ann['priority'])) ?> Priority
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="announcement-content" style="margin-top:15px;">
                            <?= nl2br(e($ann['content'])) ?>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="fas fa-calendar"></i> Published: <?= formatDateTime($ann['published_at']) ?></span>
                            <span><i class="fas fa-clock"></i> <?= timeAgo($ann['published_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>