<?php
// Get action from global scope (set by router) or fall back to GET parameter
$action = $GLOBALS['form_action'] ?? ($_GET['action'] ?? 'add');
$id = $GLOBALS['form_id'] ?? ($_GET['id'] ?? null);

$pageTitle = $action === 'add' ? 'Add Student' : 'Edit Student';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';

$db = db();
$student = null;
$classes = $db->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();
$parents = $db->query("SELECT * FROM parents WHERE is_active = 1 ORDER BY full_name")->fetchAll();

if ($action === 'edit') {
    if (!$id) {
        setFlash('danger', 'Invalid ID');
        redirect('?page=students');
    }
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    if (!$student) {
        setFlash('danger', 'Student not found');
        redirect('?page=students');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid request');
        redirect('?page=students');
    }
    
    $full_name = Security::clean($_POST['full_name']);
    $gender = $_POST['gender'] ?? 'Male';
    $date_of_birth = $_POST['date_of_birth'];
    $class_id = (int)$_POST['class_id'];
    $parent_id = (int)$_POST['parent_id'];
    $admission_date = $_POST['admission_date'] ?? date('Y-m-d');
    $address = Security::clean($_POST['address'] ?? '');
    $medical_info = Security::clean($_POST['medical_info'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($date_of_birth)) $errors[] = 'Date of birth is required';
    if (empty($class_id)) $errors[] = 'Class is required';
    if (empty($parent_id)) $errors[] = 'Parent is required';
    
    // Check admission number uniqueness (for new students)
    if ($action === 'add') {
        // Auto-generate admission number
        $admission_no = generateId('STD');
    } else {
        $admission_no = $student['admission_no'];
    }
    
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setFlash('danger', $error);
        }
    } else {
        try {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO students (admission_no, full_name, gender, date_of_birth, class_id, parent_id, admission_date, address, medical_info, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$admission_no, $full_name, $gender, $date_of_birth, $class_id, $parent_id, $admission_date, $address, $medical_info, $is_active]);
                
                Auth::logActivity(Auth::id(), 'admin', 'create_student', "Created student: {$full_name}");
                setFlash('success', 'Student added successfully with Admission No: ' . $admission_no);
            } else {
                $stmt = $db->prepare("UPDATE students SET full_name = ?, gender = ?, date_of_birth = ?, class_id = ?, parent_id = ?, admission_date = ?, address = ?, medical_info = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$full_name, $gender, $date_of_birth, $class_id, $parent_id, $admission_date, $address, $medical_info, $is_active, $student['id']]);
                
                Auth::logActivity(Auth::id(), 'admin', 'update_student', "Updated student: {$full_name}");
                setFlash('success', 'Student updated successfully');
            }
            
            redirect('?page=students');
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
                    <h2><i class="fas fa-user-graduate"></i> <?= $action === 'add' ? 'Add' : 'Edit' ?> Student</h2>
                    <p><?= $action === 'add' ? 'Enroll a new student' : 'Update student details' ?></p>
                </div>
                <a href="?page=students" class="btn btn-secondary">
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
                                       value="<?= e($student['full_name'] ?? $_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control">
                                    <option value="Male" <?= ($student['gender'] ?? 'Male') === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($student['gender'] ?? 'Male') === 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?= e($student['date_of_birth'] ?? $_POST['date_of_birth'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="admission_date">Admission Date</label>
                                <input type="date" id="admission_date" name="admission_date" class="form-control" 
                                       value="<?= e($student['admission_date'] ?? $_POST['admission_date'] ?? date('Y-m-d')) ?>">
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label for="class_id">Class *</label>
                                <select id="class_id" name="class_id" class="form-control" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class['id'] ?>" <?= ($student['class_id'] ?? '') == $class['id'] ? 'selected' : '' ?>>
                                            <?= e($class['class_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="parent_id">Parent/Guardian *</label>
                                <select id="parent_id" name="parent_id" class="form-control" required>
                                    <option value="">Select Parent</option>
                                    <?php foreach ($parents as $parent): ?>
                                        <option value="<?= $parent['id'] ?>" <?= ($student['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>>
                                            <?= e($parent['full_name']) ?> - <?= e($parent['phone']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" rows="2"><?= e($student['address'] ?? $_POST['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="medical_info">Medical Information</label>
                            <textarea id="medical_info" name="medical_info" class="form-control" rows="2" 
                                      placeholder="Allergies, conditions, medications, etc."><?= e($student['medical_info'] ?? $_POST['medical_info'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="d-flex align-center gap-2">
                                <input type="checkbox" name="is_active" <?= ($student['is_active'] ?? 1) ? 'checked' : '' ?>>
                                Active Enrollment
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Student' : 'Update Student' ?>
                            </button>
                            <a href="?page=students" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>