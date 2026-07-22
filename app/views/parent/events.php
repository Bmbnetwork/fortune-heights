<?php
$pageTitle = 'Events & Schedule';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$term = getCurrentTerm();

// Get upcoming events (announcements with event-like keywords or future scheduled)
$upcomingEvents = $db->query("SELECT * FROM announcements 
    WHERE status = 'published' 
    AND (target_audience IN ('all','parents'))
    AND (scheduled_at IS NOT NULL OR published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))
    ORDER BY COALESCE(scheduled_at, published_at) DESC LIMIT 20")->fetchAll();

// Get exam schedules (look for announcements with exam keywords)
$examSchedules = $db->query("SELECT * FROM announcements 
    WHERE status = 'published' 
    AND target_audience IN ('all','parents')
    AND (LOWER(title) LIKE '%exam%' OR LOWER(title) LIKE '%test%' OR LOWER(title) LIKE '%assessment%' OR LOWER(content) LIKE '%exam%')
    ORDER BY published_at DESC LIMIT 10")->fetchAll();

// Get current term info
$session = getCurrentSession();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-calendar-alt"></i> Events & Schedule</h2>
                    <p>School calendar, events, and examination schedules</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Term Info Card -->
            <div class="card mb-4" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;">
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;">
                        <div>
                            <small style="opacity:0.8;">Current Session</small>
                            <h3 style="margin:5px 0;"><?= e($session['session_name'] ?? 'N/A') ?></h3>
                        </div>
                        <div>
                            <small style="opacity:0.8;">Current Term</small>
                            <h3 style="margin:5px 0;"><?= e($term['term_name'] ?? 'N/A') ?></h3>
                        </div>
                        <div>
                            <small style="opacity:0.8;">Term Period</small>
                            <h3 style="margin:5px 0;font-size:16px;">
                                <?= $term ? formatDate($term['start_date'], 'M d') . ' - ' . formatDate($term['end_date'], 'M d, Y') : 'N/A' ?>
                            </h3>
                        </div>
                        <div>
                            <small style="opacity:0.8;">Days Remaining</small>
                            <h3 style="margin:5px 0;">
                                <?php 
                                if ($term) {
                                    $end = strtotime($term['end_date']);
                                    $now = time();
                                    $days = max(0, ceil(($end - $now) / 86400));
                                    echo $days . ' days';
                                } else echo 'N/A';
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid-2">
                <!-- Upcoming Events -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check text-primary"></i> Recent & Upcoming Events</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingEvents)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar"></i>
                                <p>No events scheduled</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingEvents as $event): ?>
                                <div style="padding:15px;border-left:4px solid var(--primary);background:var(--gray-50);border-radius:0 var(--radius) var(--radius) 0;margin-bottom:12px;">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                        <div style="flex:1;">
                                            <h4 style="margin:0 0 5px;color:var(--gray-800);"><?= e($event['title']) ?></h4>
                                            <p style="margin:0;font-size:13px;color:var(--gray-600);">
                                                <?= e(substr($event['content'], 0, 120)) ?><?= strlen($event['content']) > 120 ? '...' : '' ?>
                                            </p>
                                        </div>
                                        <span class="badge badge-<?= $event['priority'] === 'urgent' ? 'danger' : 'info' ?>" style="margin-left:10px;">
                                            <?= e(ucfirst($event['priority'])) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted" style="display:block;margin-top:8px;">
                                        <i class="fas fa-clock"></i> <?= formatDateTime($event['published_at']) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Exam Schedule -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt text-warning"></i> Examination Schedule</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($examSchedules)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-alt"></i>
                                <p>No examination schedules published yet</p>
                                <small class="text-muted">Check back later or contact the school</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($examSchedules as $exam): ?>
                                <div style="padding:15px;border-left:4px solid var(--warning);background:#fffbeb;border-radius:0 var(--radius) var(--radius) 0;margin-bottom:12px;">
                                    <h4 style="margin:0 0 8px;color:#92400e;">
                                        <i class="fas fa-graduation-cap"></i> <?= e($exam['title']) ?>
                                    </h4>
                                    <p style="margin:0;font-size:13px;color:#78350f;">
                                        <?= nl2br(e(substr($exam['content'], 0, 200))) ?><?= strlen($exam['content']) > 200 ? '...' : '' ?>
                                    </p>
                                    <small style="color:#92400e;display:block;margin-top:8px;">
                                        <i class="fas fa-clock"></i> Published: <?= timeAgo($exam['published_at']) ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- School Hours -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-clock text-info"></i> School Hours & Important Information</h3>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
                        <div>
                            <h4 style="color:var(--primary);"><i class="fas fa-sun"></i> School Days</h4>
                            <p style="margin:5px 0;">Monday - Friday</p>
                            <p style="margin:5px 0;"><strong>Resumption:</strong> 7:30 AM</p>
                            <p style="margin:5px 0;"><strong>Dismissal:</strong> 2:00 PM</p>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);"><i class="fas fa-info-circle"></i> Important Notes</h4>
                            <ul style="margin:5px 0;padding-left:20px;font-size:13px;">
                                <li>Late arrival after 8:00 AM marked as "Late"</li>
                                <li>Absence requires parent notification</li>
                                <li>Uniform must be worn daily</li>
                                <li>Lunch break: 11:30 AM - 12:15 PM</li>
                            </ul>
                        </div>
                        <div>
                            <h4 style="color:var(--primary);"><i class="fas fa-phone"></i> Contact School</h4>
                            <p style="margin:5px 0;"><strong>Phone:</strong> <?= e(SCHOOL_PHONE) ?></p>
                            <p style="margin:5px 0;"><strong>Email:</strong> <?= e(SCHOOL_EMAIL) ?></p>
                            <p style="margin:5px 0;"><strong>Address:</strong> <?= e(SCHOOL_ADDRESS) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>