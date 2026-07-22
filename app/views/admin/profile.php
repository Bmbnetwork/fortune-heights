<?php
$pageTitle = 'My Profile'; Auth::requireRole('admin'); require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $admin = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) { setFlash('danger', 'Invalid request'); redirect('?page=profile'); }
    
    $name = Security::clean($_POST['full_name']); $email = Security::clean($_POST['email']); $phone = Security::clean($_POST['phone']);
    $updateFields = "full_name = ?, email = ?, phone = ?"; $params = [$name, $email, $phone];
    
    if (!empty($_POST['password'])) {
        if (!Security::isStrongPassword($_POST['password'])) { setFlash('danger', 'Password too weak'); redirect('?page=profile'); }
        if (!Security::verifyPassword($_POST['current_password'], $admin['password'])) { setFlash('danger', 'Current password is incorrect'); redirect('?page=profile'); }
        $updateFields .= ", password = ?"; $params[] = Security::hashPassword($_POST['password']);
    }
    
    $params[] = Auth::id();
    $db->prepare("UPDATE admins SET {$updateFields} WHERE id = ?")->execute($params);
    
    $_SESSION['user_name'] = $name; $_SESSION['user_email'] = $email;
    Auth::logActivity(Auth::id(), 'admin', 'update_profile', 'Updated profile');
    setFlash('success', 'Profile updated successfully');
    redirect('?page=profile');
}
?>
<div class="dashboard-wrapper"><?php include APP_PATH . '/views/layouts/sidebar.php'; ?><div class="main-content"><?php include APP_PATH . '/views/layouts/topbar.php'; ?><div class="content-area">
    <div class="page-header"><h2><i class="fas fa-user-circle"></i> My Profile</h2></div>
    <?php displayFlash(); ?>
    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Account Information</h3></div>
            <div class="card-body text-center">
                <div class="user-avatar" style="width:100px;height:100px;font-size:40px;margin:0 auto 15px;"><?= strtoupper(substr($admin['full_name'], 0, 1)) ?></div>
                <h3><?= e($admin['full_name']) ?></h3>
                <p class="text-muted"><?= e($admin['email']) ?></p>
                <p>Staff ID: <code><?= e($admin['staff_id']) ?></code></p>
                <p>Last Login: <?= $admin['last_login'] ? formatDateTime($admin['last_login']) : 'Never' ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>Update Details</h3></div>
            <div class="card-body">
                <form method="POST"><?= Security::csrfField() ?>
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($admin['full_name']) ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($admin['email']) ?>" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?= e($admin['phone']) ?>"></div>
                    <hr>
                    <div class="form-group"><label>New Password (leave blank to keep current)</label><input type="password" name="password" class="form-control"></div>
                    <div class="form-group"><label>Current Password (required if changing password)</label><input type="password" name="current_password" class="form-control"></div>
                    <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
</div></div></div></div>