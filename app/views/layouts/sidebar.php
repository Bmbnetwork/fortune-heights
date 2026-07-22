<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$unreadMessages = getUnreadMessagesCount();
$unreadNotifications = getUnreadNotificationsCount();
$role = Auth::role();
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-circle">FH</div>
        <div class="logo-text">
            <h3>Fortune Heights</h3>
            <p>Montessori School</p>
        </div>
    </div>
    
    <nav class="sidebar-menu">
        <?php if ($role === 'admin'): ?>
            <div class="menu-category">Main</div>
            <a href="?page=dashboard" class="menu-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            
            <div class="menu-category">User Management</div>
            <a href="?page=admins" class="menu-item <?= in_array($currentPage, ['admins']) ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i> <span>Administrators</span>
            </a>
            <a href="?page=teachers" class="menu-item <?= in_array($currentPage, ['teachers']) ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i> <span>Teachers</span>
            </a>
            <a href="?page=parents" class="menu-item <?= in_array($currentPage, ['parents']) ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>Parents</span>
            </a>
            <a href="?page=students" class="menu-item <?= in_array($currentPage, ['students']) ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> <span>Students</span>
            </a>
            
            <div class="menu-category">Academic</div>
            <a href="?page=classes" class="menu-item <?= $currentPage === 'classes' ? 'active' : '' ?>">
                <i class="fas fa-school"></i> <span>Classes</span>
            </a>
            <a href="?page=subjects" class="menu-item <?= $currentPage === 'subjects' ? 'active' : '' ?>">
                <i class="fas fa-book"></i> <span>Subjects</span>
            </a>
            <a href="?page=sessions" class="menu-item <?= $currentPage === 'sessions' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> <span>Sessions & Terms</span>
            </a>
            
            <div class="menu-category">Communication</div>
            <a href="?page=announcements" class="menu-item <?= $currentPage === 'announcements' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a>
            <a href="?page=messages" class="menu-item <?= $currentPage === 'messages' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> <span>Messages</span>
                <?php if ($unreadMessages > 0): ?>
                    <span class="badge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>
            
            <div class="menu-category">Reports</div>
            <a href="?page=attendance-reports" class="menu-item <?= $currentPage === 'attendance-reports' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i> <span>Attendance</span>
            </a>
            <a href="?page=academic-reports" class="menu-item <?= $currentPage === 'academic-reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> <span>Academic</span>
            </a>
            <a href="?page=analytics" class="menu-item <?= $currentPage === 'analytics' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> <span>Analytics</span>
            </a>
            
        <?php elseif ($role === 'teacher'): ?>
            <div class="menu-category">Main</div>
            <a href="?page=dashboard" class="menu-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            
            <div class="menu-category">Academic</div>
            <a href="?page=attendance" class="menu-item <?= $currentPage === 'attendance' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i> <span>Attendance</span>
            </a>
            <a href="?page=results" class="menu-item <?= $currentPage === 'results' ? 'active' : '' ?>">
                <i class="fas fa-graduation-cap"></i> <span>Results</span>
            </a>
            <a href="?page=my-students" class="menu-item <?= $currentPage === 'my-students' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> <span>My Students</span>
            </a>
            
            <div class="menu-category">Communication</div>
            <a href="?page=messages" class="menu-item <?= $currentPage === 'messages' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> <span>Messages</span>
                <?php if ($unreadMessages > 0): ?>
                    <span class="badge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=feedback" class="menu-item <?= $currentPage === 'feedback' ? 'active' : '' ?>">
                <i class="fas fa-comment-dots"></i> <span>Parent Feedback</span>
            </a>
            <a href="?page=announcements" class="menu-item <?= $currentPage === 'announcements' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a>
            
            <div class="menu-category">Analytics</div>
            <a href="?page=attendance-analytics" class="menu-item <?= $currentPage === 'attendance-analytics' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> <span>Attendance Analytics</span>
            </a>
            
        <?php elseif ($role === 'parent'): ?>
            <div class="menu-category">Main</div>
            <a href="?page=dashboard" class="menu-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            
            <div class="menu-category">My Child</div>
            <a href="?page=child-profile" class="menu-item <?= $currentPage === 'child-profile' ? 'active' : '' ?>">
                <i class="fas fa-child"></i> <span>Child Profile</span>
            </a>
            <a href="?page=attendance" class="menu-item <?= $currentPage === 'attendance' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-check"></i> <span>Attendance</span>
            </a>
            <a href="?page=results" class="menu-item <?= $currentPage === 'results' ? 'active' : '' ?>">
                <i class="fas fa-graduation-cap"></i> <span>Results</span>
            </a>
            <a href="?page=report-card" class="menu-item <?= $currentPage === 'report-card' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> <span>Report Card</span>
            </a>
            
            <div class="menu-category">Communication</div>
            <a href="?page=messages" class="menu-item <?= $currentPage === 'messages' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> <span>Messages</span>
                <?php if ($unreadMessages > 0): ?>
                    <span class="badge"><?= $unreadMessages ?></span>
                <?php endif; ?>
            </a>
            <a href="?page=feedback" class="menu-item <?= $currentPage === 'feedback' ? 'active' : '' ?>">
                <i class="fas fa-comment-dots"></i> <span>Send Feedback</span>
            </a>
            
            <div class="menu-category">School Updates</div>
            <a href="?page=announcements" class="menu-item <?= $currentPage === 'announcements' ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> <span>Announcements</span>
            </a>
            <a href="?page=events" class="menu-item <?= $currentPage === 'events' ? 'active' : '' ?>">
                <i class="fas fa-calendar"></i> <span>Events & Schedule</span>
            </a>
        <?php endif; ?>
        
        <div class="menu-category">Account</div>
        <a href="?page=profile" class="menu-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i> <span>My Profile</span>
        </a>
        <a href="?page=logout" class="menu-item">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>