<?php
// Get action from global scope (set by router) or fall back to GET parameter
$action = $GLOBALS['form_action'] ?? ($_GET['action'] ?? 'add');
$id = $GLOBALS['form_id'] ?? ($_GET['id'] ?? null);

$pageTitle = $action === 'add' ? 'Add Parent' : 'Edit Parent';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$parent = null;

if ($action === 'edit') {
    if (!$id) {
        setFlash('danger', 'Invalid ID');
        redirect('?page=parents');
    }
    $stmt = $db->prepare("SELECT * FROM parents WHERE id = ?");
    $stmt->execute([$id]);
    $parent = $stmt->fetch();
    if (!$parent) {
        setFlash('danger', 'Parent not found');
        redirect('?page=parents');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=parents');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $email = Security::clean($_POST['email']);
    $phone = Security::clean($_POST['phone']);
    $gender = $_POST['gender'] ?? 'Male';
    $occupation = Security::clean($_POST['occupation'] ?? '');
    $address = Security::clean($_POST['address'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!Security::isValidEmail($email)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (!Security::isValidPhone($phone)) $errors[] = 'Invalid phone number format (use 08012345678 or +2348012345678)';
    if ($action === 'add' && empty($_POST['password'])) $errors[] = 'Password is required for new parents';
    
    // Check email uniqueness
    $emailCheck = $db->prepare("SELECT id FROM parents WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $parent['id'] ?? 0]);
    if ($emailCheck->fetch()) {
        $errors[] = 'Email already exists';
    }
    
    // Check phone uniqueness
    $phoneCheck = $db->prepare("SELECT id FROM parents WHERE phone = ? AND id != ?");
    $phoneCheck->execute([$phone, $parent['id'] ?? 0]);
    if ($phoneCheck->fetch()) {
        $errors[] = 'Phone number already exists';
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
                    redirect("?page=parents&action=add");
                }
                
                $parent_id = generateId('PAR');
                $password_hash = Security::hashPassword($password);
                
                $stmt = $db->prepare("INSERT INTO parents (parent_id, full_name, email, phone, gender, occupation, address, password, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$parent_id, $full_name, $email, $phone, $gender, $occupation, $address, $password_hash, $is_active]);
                
                Auth::logActivity(Auth::id(), 'admin', 'create_parent', "Created parent: {$full_name}");
                setFlash('success', 'Parent added successfully');
            } else {
                $update_fields = "full_name = ?, email = ?, phone = ?, gender = ?, occupation = ?, address = ?, is_active = ?";
                $params = [$full_name, $email, $phone, $gender, $occupation, $address, $is_active];
                
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (!Security::isStrongPassword($password)) {
                        setFlash('danger', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
                        redirect("?page=parents&action=edit&id={$parent['id']}");
                    }
                    $update_fields .= ", password = ?";
                    $params[] = Security::hashPassword($password);
                }
                
                $params[] = $parent['id'];
                $stmt = $db->prepare("UPDATE parents SET {$update_fields} WHERE id = ?");
                $stmt->execute($params);
                
                Auth::logActivity(Auth::id(), 'admin', 'update_parent', "Updated parent: {$full_name}");
                setFlash('success', 'Parent updated successfully');
            }
            
            redirect('?page=parents');
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
                    <h2><i class="fas fa-users"></i> <?= $action === 'add' ? 'Add' : 'Edit' ?> Parent</h2>
                    <p><?= $action === 'add' ? 'Register a new parent' : 'Update parent details' ?></p>
                </div>
                <a href="?page=parents" class="btn btn-secondary">
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
                                       value="<?= e($parent['full_name'] ?? $_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= e($parent['email'] ?? $_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?= e($parent['phone'] ?? $_POST['phone'] ?? '') ?>" 
                                       placeholder="e.g., 08012345678" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="Male" <?= ($parent['gender'] ?? 'Male') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($parent['gender'] ?? 'Male') === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" id="occupation" name="occupation" class="form-control" 
                                       value="<?= e($parent['occupation'] ?? $_POST['occupation'] ?? '') ?>">
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
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="3"><?= e($parent['address'] ?? $_POST['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="d-flex align-center gap-2">
                                <input type="checkbox" name="is_active" <?= ($parent['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Active Account
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Parent' : 'Update Parent' ?>
                            </button>
                            <a href="?page=parents" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>