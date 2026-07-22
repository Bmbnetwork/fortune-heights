<?php
$pageTitle = ($action ?? 'add') === 'add' ? 'Add Class' : 'Edit Class';
Auth::requireRole('admin');
require_once APP_PATH . '/views/layouts/header.php';
$db = db(); $class = null;

if (($action ?? '') === 'edit') {
    $stmt = $db->prepare("SELECT * FROM classes WHERE id = ?"); $stmt->execute([$_GET['id']]); $class = $stmt->fetch();
    if (!$class) { setFlash('danger', 'Class not found'); redirect('?page=classes'); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) { setFlash('danger', 'Invalid request'); redirect('?page=classes'); }
    $name = Security::clean($_POST['class_name']); $code = Security::clean($_POST['class_code']);
    $level = $_POST['level']; $capacity = (int)$_POST['capacity'];
    
    try {
        if (($action ?? '') === 'add') {
            $db->prepare("INSERT INTO classes (class_name, class_code, level, capacity) VALUES (?, ?, ?, ?)")->execute([$name, $code, $level, $capacity]);
            setFlash('success', 'Class added successfully');
        } else {
            $db->prepare("UPDATE classes SET class_name=?, class_code=?, level=?, capacity=? WHERE id=?")->execute([$name, $code, $level, $capacity, $class['id']]);
            setFlash('success', 'Class updated successfully');
        }
        redirect('?page=classes');
    } catch (Exception $e) { setFlash('danger', 'Error: ' . $e->getMessage()); }
}
?>
<div class="dashboard-wrapper">
    <?php include APP_PATH . '/views/layouts/sidebar.php'; ?>
    <div class="main-content">
        <?php include APP_PATH . '/views/layouts/topbar.php'; ?>
        <div class="content-area">
            <div class="page-header"><div><h2><?= $pageTitle ?></h2></div><a href="?page=classes" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a></div>
            <?php displayFlash(); ?>
            <div class="card"><div class="card-body">
                <form method="POST"><?= Security::csrfField() ?>
                    <div class="grid-2">
                        <div class="form-group"><label>Class Name *</label><input type="text" name="class_name" class="form-control" value="<?= e($class['class_name'] ?? '') ?>" required></div>
                        <div class="form-group"><label>Class Code *</label><input type="text" name="class_code" class="form-control" value="<?= e($class['class_code'] ?? '') ?>" required></div>
                    </div>
                    <div class="grid-2">
                        <div class="form-group"><label>Level *</label>
                            <select name="level" class="form-control" required>
                                <option value="Creche" <?= ($class['level'] ?? '') == 'Creche' ? 'selected' : '' ?>>Creche</option>
                                <option value="Nursery" <?= ($class['level'] ?? '') == 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                                <option value="Primary" <?= ($class['level'] ?? '') == 'Primary' ? 'selected' : '' ?>>Primary</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Capacity *</label><input type="number" name="capacity" class="form-control" value="<?= e($class['capacity'] ?? 30) ?>" required></div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Class</button>
                </form>
            </div></div>
        </div>
        <?php include APP_PATH . '/views/layouts/footer.php'; ?>
    </div>
</div>