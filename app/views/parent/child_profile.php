<?php
$pageTitle = 'My Children';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$stmt = $db->prepare("SELECT s.*, c.class_name, t.full_name as class_teacher 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    LEFT JOIN teachers t ON c.id = t.class_id 
    WHERE s.parent_id = ? 
    ORDER BY s.full_name");
$stmt->execute([Auth::id()]);
$children = $stmt->fetchAll();

$term = getCurrentTerm();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-child"></i> My Children</h2>
                    <p>View detailed profiles of your children</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <?php if (empty($children)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty-state">
                            <i class="fas fa-child"></i>
                            <h3>No Children Registered</h3>
                            <p>Please contact the school administration to register your children.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($children as $child): 
                    $percentage = calculateAttendancePercentage($child['id'], $term['id'] ?? null);
                    $category = getAttendanceCategory($percentage);
                    $age = $child['date_of_birth'] ? floor((time() - strtotime($child['date_of_birth'])) / (365.25 * 24 * 3600)) : 0;
                ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex align-center gap-2">
                                <div class="user-avatar" style="width:50px;height:50px;font-size:20px;">
                                    <?= strtoupper(substr($child['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h3 style="margin:0;"><?= e($child['full_name']) ?></h3>
                                    <small class="text-muted"><?= e($child['admission_no']) ?> • <?= e($child['class_name']) ?></small>
                                </div>
                            </div>
                            <span class="badge badge-<?= $child['is_active'] ? 'success' : 'danger' ?>">
                                <?= $child['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <div class="grid-3">
                                <!-- Personal Info -->
                                <div>
                                    <h4 style="color:var(--primary);margin-bottom:12px;"><i class="fas fa-user"></i> Personal Information</h4>
                                    <table style="width:100%;font-size:13px;">
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Full Name</td><td style="padding:6px 0;font-weight:600;"><?= e($child['full_name']) ?></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Admission No</td><td style="padding:6px 0;"><code><?= e($child['admission_no']) ?></code></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Gender</td><td style="padding:6px 0;"><?= e($child['gender']) ?></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Date of Birth</td><td style="padding:6px 0;"><?= formatDate($child['date_of_birth']) ?> (<?= $age ?> yrs)</td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Admission Date</td><td style="padding:6px 0;"><?= formatDate($child['admission_date']) ?></td></tr>
                                    </table>
                                </div>
                                
                                <!-- Academic Info -->
                                <div>
                                    <h4 style="color:var(--primary);margin-bottom:12px;"><i class="fas fa-school"></i> Academic Information</h4>
                                    <table style="width:100%;font-size:13px;">
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Class</td><td style="padding:6px 0;font-weight:600;"><?= e($child['class_name']) ?></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Class Teacher</td><td style="padding:6px 0;"><?= e($child['class_teacher'] ?? 'Not Assigned') ?></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Current Term</td><td style="padding:6px 0;"><?= e($term['term_name'] ?? 'N/A') ?></td></tr>
                                        <tr><td style="padding:6px 0;color:var(--gray-500);">Session</td><td style="padding:6px 0;"><?= e($term['session_name'] ?? 'N/A') ?></td></tr>
                                    </table>
                                </div>
                                
                                <!-- Attendance Summary -->
                                <div>
                                    <h4 style="color:var(--primary);margin-bottom:12px;"><i class="fas fa-clipboard-check"></i> Attendance Summary</h4>
                                    <div class="text-center" style="padding:20px 0;">
                                        <div style="font-size:48px;font-weight:700;color:<?= $category['color'] ?>;line-height:1;">
                                            <?= $percentage ?>%
                                        </div>
                                        <div style="margin-top:8px;">
                                            <span class="badge" style="background:<?= $category['color'] ?>20;color:<?= $category['color'] ?>;padding:6px 14px;">
                                                <?= $category['label'] ?>
                                            </span>
                                        </div>
                                        <p class="text-muted" style="margin-top:12px;font-size:12px;">
                                            Current Term Attendance
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($child['medical_info'])): ?>
                                <div style="margin-top:20px;padding:15px;background:#fef3c7;border-left:4px solid #f59e0b;border-radius:var(--radius);">
                                    <h5 style="margin:0 0 8px;color:#92400e;"><i class="fas fa-notes-medical"></i> Medical Information</h5>
                                    <p style="margin:0;color:#78350f;"><?= nl2br(e($child['medical_info'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($child['address'])): ?>
                                <div style="margin-top:15px;">
                                    <h5 style="color:var(--gray-600);margin-bottom:5px;"><i class="fas fa-map-marker-alt"></i> Address</h5>
                                    <p style="margin:0;color:var(--gray-700);"><?= nl2br(e($child['address'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer d-flex gap-2" style="flex-wrap:wrap;">
                            <a href="?page=attendance" class="btn btn-sm btn-primary">
                                <i class="fas fa-clipboard-check"></i> View Attendance
                            </a>
                            <a href="?page=results" class="btn btn-sm btn-success">
                                <i class="fas fa-graduation-cap"></i> View Results
                            </a>
                            <a href="?page=report-card" class="btn btn-sm btn-info">
                                <i class="fas fa-file-alt"></i> Report Card
                            </a>
                            <a href="?page=messages" class="btn btn-sm btn-secondary">
                                <i class="fas fa-envelope"></i> Message Teacher
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>