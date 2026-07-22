<?php
// Get action from global scope (set by router) or fall back to GET parameter
$action = $GLOBALS['form_action'] ?? ($_GET['action'] ?? 'add');
$id = $GLOBALS['form_id'] ?? ($_GET['id'] ?? null);

$pageTitle = $action === 'add' ? 'Add Administrator' : 'Edit Administrator';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$admin = null;

if ($action === 'edit') {
    if (!$id) {
        setFlash('danger', 'Invalid ID');
        redirect('?page=admins');
    }
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    if (!$admin) {
        setFlash('danger', 'Administrator not found');
        redirect('?page=admins');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=admins');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $email = Security::clean($_POST['email']);
    $phone = Security::clean($_POST['phone'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!Security::isValidEmail($email)) $errors[] = 'Invalid email format';
    if ($action === 'add' && empty($_POST['password'])) $errors[] = 'Password is required for new administrators';
    
    // Check email uniqueness
    $emailCheck = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $admin['id'] ?? 0]);
    if ($emailCheck->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setFlash('danger', $error);
        }
    } else {
        try {
            if ($action === 'add') {
                $password = $_POST['password'];
                if (!Security::isStrongPassword($password)) {
                    setFlash('danger', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
                    redirect("?page=admins&action=add");
                }
                
                $staff_id = generateId('ADM');
                $password_hash = Security::hashPassword($password);
                
                $stmt = $db->prepare("INSERT INTO admins (staff_id, full_name, email, phone, password, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$staff_id, $full_name, $email, $phone, $password_hash, $is_active]);
                
                Auth::logActivity(Auth::id(), 'admin', 'create_admin', "Created admin: {$full_name}");
                setFlash('success', 'Administrator added successfully');
            } else {
                $update_fields = "full_name = ?, email = ?, phone = ?, is_active = ?";
                $params = [$full_name, $email, $phone, $is_active];
                
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (!Security::isStrongPassword($password)) {
                        setFlash('danger', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
                        redirect("?page=admins&action=edit&id={$admin['id']}");
                    }
                    $update_fields .= ", password = ?";
                    $params[] = Security::hashPassword($password);
                }
                
                $params[] = $admin['id'];
                $stmt = $db->prepare("UPDATE admins SET {$update_fields} WHERE id = ?");
                $stmt->execute($params);
                
                Auth::logActivity(Auth::id(), 'admin', 'update_admin', "Updated admin: {$full_name}");
                setFlash('success', 'Administrator updated successfully');
            }
            
            redirect('?page=admins');
        } catch (Exception $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
        }
    }
}
?>

<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-user-shield"></i> <?= $action === 'add' ? 'Add' : 'Edit' ?> Administrator</h2>
                    <p><?= $action === 'add' ? 'Register a new administrator' : 'Update administrator details' ?></p>
                </div>
                <a href="?page=admins" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
            
            <?php displayFlash(); ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?= e($admin['full_name'] ?? $_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= e($admin['email'] ?? $_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?= e($admin['phone'] ?? $_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password"><?= $action === 'add' ? 'Password *' : 'New Password (optional)' ?></label>
                                <input type="password" id="password" name="password" class="form-control" 
                                       placeholder="<?= $action === 'add' ? 'Enter password' : 'Leave blank to keep current' ?>">
                                <?php if ($action === 'add'): ?>
                                    <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, and numbers</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="d-flex align-center gap-2">
                                <input type="checkbox" name="is_active" <?= ($admin['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Active Account
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Administrator' : 'Update Administrator' ?>
                            </button>
                            <a href="?page=admins" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>