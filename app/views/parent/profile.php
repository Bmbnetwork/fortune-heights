<?php
$pageTitle = 'My Profile';
Auth::requireRole('parent');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$parent = Auth::user();

// Get children count
$childrenCount = countRecords('students', 'parent_id = ? AND is_active = 1', [Auth::id()]);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=profile');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $email = Security::clean($_POST['email']);
    $phone = Security::clean($_POST['phone']);
    $occupation = Security::clean($_POST['occupation'] ?? '');
    $address = Security::clean($_POST['address'] ?? '');
    
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email) || !Security::isValidEmail($email)) $errors[] = 'Valid email is required';
    if (empty($phone) || !Security::isValidPhone($phone)) $errors[] = 'Valid phone number is required';
    
    // Check email uniqueness
    $emailCheck = $db->prepare("SELECT id FROM parents WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, Auth::id()]);
    if ($emailCheck->fetch()) $errors[] = 'Email already in use';
    
    // Check phone uniqueness
    $phoneCheck = $db->prepare("SELECT id FROM parents WHERE phone = ? AND id != ?");
    $phoneCheck->execute([$phone, Auth::id()]);
    if ($phoneCheck->fetch()) $errors[] = 'Phone number already in use';
    
    if (!empty($errors)) {
        foreach ($errors as $err) setFlash('danger', $err);
    } else {
        $updateFields = "full_name = ?, email = ?, phone = ?, occupation = ?, address = ?";
        $params = [$full_name, $email, $phone, $occupation, $address];
        
        // Password change
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                setFlash('danger', 'Current password required to change password');
                redirect('?page=profile');
            }
            if (!Security::verifyPassword($_POST['current_password'], $parent['password'])) {
                setFlash('danger', 'Current password is incorrect');
                redirect('?page=profile');
            }
            if (!Security::isStrongPassword($_POST['new_password'])) {
                setFlash('danger', 'New password must be at least 8 characters with uppercase, lowercase, and numbers');
                redirect('?page=profile');
            }
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                setFlash('danger', 'New passwords do not match');
                redirect('?page=profile');
            }
            $updateFields .= ", password = ?";
            $params[] = Security::hashPassword($_POST['new_password']);
        }
        
        $params[] = Auth::id();
        try {
            $db->prepare("UPDATE parents SET {$updateFields} WHERE id = ?")->execute($params);
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            Auth::logActivity(Auth::id(), 'parent', 'update_profile', 'Updated profile');
            setFlash('success', 'Profile updated successfully');
            redirect('?page=profile');
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}

// Refresh parent data
$parent = Auth::user();
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                    <p>Manage your account information</p>
                </div>
            </div>
            
            <?php displayFlash(); ?>
            
            <!-- Profile Header Card -->
            <div class="card mb-4" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:white;">
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                        <div class="user-avatar" style="width:80px;height:80px;font-size:32px;background:rgba(255,255,255,0.2);border:3px solid white;">
                            <?= strtoupper(substr($parent['full_name'], 0, 1)) ?>
                        </div>
                        <div style="flex:1;min-width:200px;">
                            <h2 style="margin:0;"><?= e($parent['full_name']) ?></h2>
                            <p style="margin:5px 0;opacity:0.9;"><i class="fas fa-envelope"></i> <?= e($parent['email']) ?></p>
                            <p style="margin:5px 0;opacity:0.9;"><i class="fas fa-phone"></i> <?= e($parent['phone']) ?></p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:36px;font-weight:700;"><?= $childrenCount ?></div>
                            <small style="opacity:0.9;">Children Registered</small>
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
                                <label>Parent ID</label>
                                <input type="text" class="form-control" value="<?= e($parent['parent_id']) ?>" readonly style="background:var(--gray-100);">
                            </div>
                            
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" class="form-control" value="<?= e($parent['full_name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" value="<?= e($parent['email']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($parent['phone']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Gender</label>
                                <input type="text" class="form-control" value="<?= e($parent['gender']) ?>" readonly style="background:var(--gray-100);">
                            </div>
                            
                            <div class="form-group">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="<?= e($parent['occupation'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= e($parent['address'] ?? '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Security & Account Info -->
                <div>
                    <!-- Change Password -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3><i class="fas fa-lock text-warning"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= Security::csrfField() ?>
                                <!-- Hidden fields to prevent validation errors -->
                                <input type="hidden" name="full_name" value="<?= e($parent['full_name']) ?>">
                                <input type="hidden" name="email" value="<?= e($parent['email']) ?>">
                                <input type="hidden" name="phone" value="<?= e($parent['phone']) ?>">
                                
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
                                        <span class="badge badge-<?= $parent['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $parent['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Member Since</td>
                                    <td style="padding:8px 0;"><?= formatDate($parent['created_at']) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Last Login</td>
                                    <td style="padding:8px 0;"><?= $parent['last_login'] ? formatDateTime($parent['last_login']) : 'Never' ?></td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;color:var(--gray-500);">Children</td>
                                    <td style="padding:8px 0;"><?= $childrenCount ?> registered</td>
                                </tr>
                            </table>
                            
                            <div style="margin-top:15px;padding:12px;background:#dbeafe;border-radius:var(--radius);font-size:12px;color:#1e40af;">
                                <i class="fas fa-shield-alt"></i> 
                                <strong>Security Tip:</strong> Change your password regularly and never share it with anyone.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>