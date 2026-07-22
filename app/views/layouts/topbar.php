<?php
$notifications = [];
if (Auth::check()) {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM notifications 
        WHERE user_id = ? AND user_type = ? 
        ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([Auth::id(), Auth::role()]);
    $notifications = $stmt->fetchAll();
}
$unreadNotifCount = countRecords('notifications', 'user_id = ? AND user_type = ? AND is_read = 0', 
    [Auth::id(), Auth::role()]);
?>

<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    
    <div class="topbar-right">
        <!-- Notifications -->
        <div class="dropdown">
            <button class="topbar-icon" id="notifBtn">
                <i class="fas fa-bell"></i>
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge-dot"></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu notification-dropdown" id="notifDropdown">
                <div class="dropdown-header">
                    <strong>Notifications</strong>
                    <span class="badge badge-primary"><?= $unreadNotifCount ?> new</span>
                </div>
                <div class="dropdown-body">
                    <?php if (empty($notifications)): ?>
                        <p class="text-center text-muted p-3">No notifications</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                <div class="notif-icon">
                                    <i class="fas fa-<?= $notif['type'] === 'message' ? 'envelope' : ($notif['type'] === 'attendance' ? 'clipboard-check' : 'bell') ?>"></i>
                                </div>
                                <div class="notif-content">
                                    <strong><?= e($notif['title']) ?></strong>
                                    <p><?= e($notif['message']) ?></p>
                                    <small><?= timeAgo($notif['created_at']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="dropdown-footer">
                    <a href="?page=notifications">View All Notifications</a>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <a href="?page=messages" class="topbar-icon">
            <i class="fas fa-envelope"></i>
            <?php if (getUnreadMessagesCount() > 0): ?>
                <span class="badge-dot"></span>
            <?php endif; ?>
        </a>
        
        <!-- User Dropdown -->
        <div class="user-dropdown">
            <div class="user-avatar">
                <?= strtoupper(substr(Auth::name(), 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="name"><?= e(Auth::name()) ?></div>
                <div class="role"><?= e(ucfirst(Auth::role())) ?></div>
            </div>
        </div>
    </div>
</header>

<style>
.dropdown { position: relative; }
.dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    min-width: 350px;
    z-index: 1000;
    overflow: hidden;
}
.dropdown-menu.show { display: block; }
.dropdown-header {
    padding: 15px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.dropdown-body {
    max-height: 400px;
    overflow-y: auto;
}
.notif-item {
    padding: 12px 15px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    gap: 12px;
    cursor: pointer;
    transition: var(--transition);
}
.notif-item:hover { background: var(--gray-50); }
.notif-item.unread { background: #eff6ff; }
.notif-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
}
.notif-content strong { display: block; font-size: 13px; margin-bottom: 3px; }
.notif-content p { font-size: 12px; color: var(--gray-600); margin: 0; }
.notif-content small { font-size: 11px; color: var(--gray-400); }
.dropdown-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}
@media (max-width: 992px) {
    .sidebar.show + .sidebar-overlay { display: block; }
}
</style>