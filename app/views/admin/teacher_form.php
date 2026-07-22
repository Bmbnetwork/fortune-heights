<?php
// Get action from global scope (set by router) or fall back to GET parameter
$action = $GLOBALS['form_action'] ?? ($_GET['action'] ?? 'add');
$id = $GLOBALS['form_id'] ?? ($_GET['id'] ?? null);

$pageTitle = $action === 'add' ? 'Add Teacher' : 'Edit Teacher';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$teacher = null;
$classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();

if ($action === 'edit') {
    if (!$id) {
        setFlash('danger', 'Invalid ID');
        redirect('?page=teachers');
    }
    $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    $teacher = $stmt->fetch();
    if (!$teacher) {
        setFlash('danger', 'Teacher not found');
        redirect('?page=teachers');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=teachers');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $email = Security::clean($_POST['email']);
    $phone = Security::clean($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $qualification = Security::clean($_POST['qualification'] ?? '');
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!Security::isValidEmail($email)) $errors[] = 'Invalid email format';
    if ($action === 'add' && empty($_POST['password'])) $errors[] = 'Password is required for new teachers';
    
    // Check email uniqueness
    $emailCheck = $db->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $teacher['id'] ?? 0]);
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
                    redirect("?page=teachers&action=add");
                }
                
                $staff_id = generateId('TCH');
                $password_hash = Security::hashPassword($password);
                
                $stmt = $db->prepare("INSERT INTO teachers (staff_id, full_name, email, phone, gender, qualification, password, class_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$staff_id, $full_name, $email, $phone, $gender, $qualification, $password_hash, $class_id, $is_active]);
                
                Auth::logActivity(Auth::id(), 'admin', 'create_teacher', "Created teacher: {$full_name}");
                setFlash('success', 'Teacher added successfully');
            } else {
                $update_fields = "full_name = ?, email = ?, phone = ?, gender = ?, qualification = ?, class_id = ?, is_active = ?";
                $params = [$full_name, $email, $phone, $gender, $qualification, $class_id, $is_active];
                
                if (!empty($_POST['password'])) {
                    $password = $_POST['password'];
                    if (!Security::isStrongPassword($password)) {
                        setFlash('danger', 'Password must be at least 8 characters with uppercase, lowercase, and numbers');
                        redirect("?page=teachers&action=edit&id={$teacher['id']}");
                    }
                    $update_fields .= ", password = ?";
                    $params[] = Security::hashPassword($password);
                }
                
                $params[] = $teacher['id'];
                $stmt = $db->prepare("UPDATE teachers SET {$update_fields} WHERE id = ?");
                $stmt->execute($params);
                
                Auth::logActivity(Auth::id(), 'admin', 'update_teacher', "Updated teacher: {$full_name}");
                setFlash('success', 'Teacher updated successfully');
            }
            
            redirect('?page=teachers');
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
                    <h2><i class="fas fa-chalkboard-teacher"></i> <?= $action === 'add' ? 'Add' : 'Edit' ?> Teacher</h2>
                    <p><?= $action === 'add' ? 'Register a new teacher' : 'Update teacher details' ?></p>
                </div>
                <a href="?page=teachers" class="btn btn-secondary">
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
                                       value="<?= e($teacher['full_name'] ?? $_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= e($teacher['email'] ?? $_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?= e($teacher['phone'] ?? $_POST['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="Male" <?= ($teacher['gender'] ?? 'Male') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($teacher['gender'] ?? 'Male') === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="qualification">Qualification</label>
                                <input type="text" id="qualification" name="qualification" class="form-control" 
                                       value="<?= e($teacher['qualification'] ?? $_POST['qualification'] ?? '') ?>" 
                                       placeholder="e.g., B.Ed, NCE">
                            </div>
                            
                            <div class="form-group">
                                <label for="class_id">Assigned Class</label>
                                <select id="class_id" name="class_id" class="form-control">
                                    <option value="">Not Assigned</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>" <?= ($teacher['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                            <?= e($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password"><?= $action === 'add' ? 'Password *' : 'New Password (optional)' ?></label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="<?= $action === 'add' ? 'Enter password' : 'Leave blank to keep current' ?>">
                            <?php if ($action === 'add'): ?>
                                <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, and numbers</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="d-flex align-center gap-2">
                                <input type="checkbox" name="is_active" <?= ($teacher['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Active Account
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Teacher' : 'Update Teacher' ?>
                            </button>
                            <a href="?page=teachers" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>