<?php
$pageTitle = 'My Profile';
Auth::requireRole('teacher');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = Auth::user();

// Get class info
$className = 'Not Assigned';
if ($teacher['class_id']) {
    $classStmt = $db->prepare("SELECT class_name FROM classes WHERE id = ?");
    $classStmt->execute([$teacher['class_id']]);
    $className = $classStmt->fetchColumn() ?: 'Not Assigned';
}

// Stats
$studentCount = $teacher['class_id'] 
    ? countRecords('students', 'class_id = ? AND is_active = 1', [$teacher['class_id']]) 
    : 0;
$subjectCount = countRecords('subjects', 'teacher_id = ?', [Auth::id()]);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=profile');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $email = Security::clean($_POST['email']);
    $phone = Security::clean($_POST['phone'] ?? '');
    $qualification = Security::clean($_POST['qualification'] ?? '');
    
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email) || !Security::isValidEmail($email)) $errors[] = 'Valid email is required';
    
    // Check email uniqueness
    $emailCheck = $db->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, Auth::id()]);
    if ($emailCheck->fetch()) $errors[] = 'Email already in use';
    
    if (!empty($errors)) {
        foreach ($errors as $err) setFlash('danger', $err);
    } else {
        $updateFields = "full_name = ?, email = ?, phone = ?, qualification = ?";
        $params = [$full_name, $email, $phone, $qualification];
        
        // Password change
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                setFlash('danger', 'Current password required');
                redirect('?page=profile');
            }
            if (!Security::verifyPassword($_POST['current_password'], $teacher['password'])) {
                setFlash('danger', 'Current password is incorrect');
                redirect('?page=profile');
            }
            if (!Security::isStrongPassword($_POST['new_password'])) {
                setFlash('danger', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
                redirect('?page=profile');
            }
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                setFlash('danger', 'Passwords do not match');
                redirect('?page=profile');
            }
            $updateFields .= ", password = ?";
            $params[] = Security::hashPassword($_POST['new_password']);
        }
        
        $params[] = Auth::id();
        try {
            $db->prepare("UPDATE teachers SET {$updateFields} WHERE id = ?")->execute($params);
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            Auth::logActivity(Auth::id(), 'teacher', 'update_profile', 'Updated profile');
            setFlash('success', 'Profile updated successfully');
            redirect('?page=profile');
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Refresh data
$teacher = Auth::user();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                    <p>Manage your account and professional information</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Profile Header -->
            <div class="card mb-4" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;">
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                        <div class="user-avatar" style="width:80px;height:80px;font-size:32px;background:rgba(255,255,255,0.2);border:3px solid white;">
                            <?= strtoupper(substr($teacher['full_name'], 0, 1)) ?>
                        </div>
                        <div style="flex:1;min-width:200px;">
                            <h2 style="margin:0;"><?= e($teacher['full_name']) ?></h2>
                            <p style="margin:5px 0;opacity:0.9;"><i class="fas fa-envelope"></i> <?= e($teacher['email']) ?></p>
                            <p style="margin:5px 0;opacity:0.9;"><i class="fas fa-school"></i> Class Teacher: <?= e($className) ?></p>
                        </div>
                        <div style="display:flex;gap:20px;text-align:center;">
                            <div>
                                <div style="font-size:32px;font-weight:700;"><?= $studentCount ?></div>
                                <small style="opacity:0.9;">Students</small>
                            </div>
                            <div>
                                <div style="font-size:32px;font-weight:700;"><?= $subjectCount ?></div>
                                <small style="opacity:0.9;">Subjects</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid-2">
                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user text-primary"></i> Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            
                            <div class="form-group">
                                <label>Staff ID</label>
                                <input type="text" class="form-control" value="<?= e($teacher['staff_id']) ?>" readonly style="background:var(--gray-100);">
                            </div>
                            
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= e($teacher['full_name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" value="<?= e($teacher['email']) ?>" required>
                            </div>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Gender</label>
                                    <input type="text" class="form-control" value="<?= e($teacher['gender']) ?>" readonly style="background:var(--gray-100);">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification" class="form-control" 
                                       value="<?= e($teacher['qualification'] ?? '') ?>" 
                                       placeholder="e.g., B.Ed, NCE, M.Ed">
                            </div>
                            
                            <div class="form-group">
                                <label>Assigned Class</label>
                                <input type="text" class="form-control" value="<?= e($className) ?>" readonly style="background:var(--gray-100);">
                                <small class="text-muted">Contact admin to change class assignment</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Security & Info -->
                <div>
                    <!-- Change Password -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-lock text-warning"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= Security::csrfField() ?>
                                <input type="hidden" name="full_name" value="<?= e($teacher['full_name']) ?>">
                                <input type="hidden" name="email" value="<?= e($teacher['email']) ?>">
                                
                                <div class="form-group">
                                    <label>Current Password *</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password *</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                    <small class="text-muted">At least 8 characters with uppercase, lowercase, and numbers</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password *</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle text-info"></i> Account Information</h3>
                        </div>
                        <div class="card-body">
                            <table style="width:100%;font-size:13px;">
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Account Status</td>
                                    <td style="padding:8px 0;">
                                        <span class="badge badge-<?= $teacher['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $teacher['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Member Since</td>
                                    <td style="padding:8px 0;"><?= formatDate($teacher['created_at']) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Last Login</td>
                                    <td style="padding:8px 0;"><?= $teacher['last_login'] ? formatDateTime($teacher['last_login']) : 'Never' ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Students</td>
                                    <td style="padding:8px 0;"><?= $studentCount ?> in <?= e($className) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Subjects</td>
                                    <td style="padding:8px 0;"><?= $subjectCount ?> assigned</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>