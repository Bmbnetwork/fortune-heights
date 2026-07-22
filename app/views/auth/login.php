<?php
$pageTitle = 'Login';
$isLoginPage = true;
require_once APP_PATH . '/views/layouts/header.php';

$role = $_POST['role'] ?? ($_GET['role'] ?? 'parent');
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Fortune Heights</h1>
            <p>Montessori School, Ijokodo, Ibadan</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- ROLE TABS -->
        <div class="login-tabs" id="loginTabs">
            <button type="button" class="login-tab <?= $role === 'admin' ? 'active' : '' ?>" 
                    data-role="admin" onclick="switchRole(this, 'admin')">
                <i class="fas fa-user-shield"></i> Admin
            </button>
            <button type="button" class="login-tab <?= $role === 'teacher' ? 'active' : '' ?>" 
                    data-role="teacher" onclick="switchRole(this, 'teacher')">
                <i class="fas fa-chalkboard-teacher"></i> Teacher
            </button>
            <button type="button" class="login-tab <?= $role === 'parent' ? 'active' : '' ?>" 
                    data-role="parent" onclick="switchRole(this, 'parent')">
                <i class="fas fa-users"></i> Parent
            </button>
        </div>
        
        <form method="POST" action="index.php?page=login" id="loginForm">
            <?= Security::csrfField() ?>
            
            <!-- Hidden role field - updated by JavaScript -->
            <input type="hidden" name="role" id="roleInput" value="<?= e($role) ?>">
            
            <!-- Visual role indicator -->
            <div class="text-center mb-3">
                <small class="text-muted">Logging in as: </small>
                <strong id="roleDisplay" style="color: var(--primary); text-transform: capitalize;">
                    <?= e($role) ?>
                </strong>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" required 
                           value="<?= e($_POST['email'] ?? '') ?>"
                           autocomplete="email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required
                           autocomplete="current-password">
                </div>
            </div>
            
            <div class="form-group d-flex justify-between align-center">
                <label class="d-flex align-center gap-2" style="margin:0;font-size:13px;">
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Sign In as <span id="btnRole"><?= e(ucfirst($role)) ?></span>
            </button>
        </form>
        
        <div class="text-center mt-3">
            <p class="text-muted" style="font-size:12px;">
                <i class="fas fa-shield-alt"></i> Secure login • Select your role above before signing in
            </p>
        </div>
    </div>
</div>

<script>
/**
 * FIXED switchRole function
 * - No longer relies on global `event` object
 * - Uses the clicked button element directly
 * - Updates all visual indicators
 */
function switchRole(button, role) {
    // 1. Update hidden input
    document.getElementById('roleInput').value = role;
    
    // 2. Update active tab styling
    document.querySelectorAll('.login-tab').forEach(function(tab) {
        tab.classList.remove('active');
    });
    button.classList.add('active');
    
    // 3. Update visual indicators
    document.getElementById('roleDisplay').textContent = role;
    document.getElementById('btnRole').textContent = role.charAt(0).toUpperCase() + role.slice(1);
    
    // 4. Clear any existing error messages
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        alert.remove();
    });
}

// Verify role is set before form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    var role = document.getElementById('roleInput').value;
    if (!role || !['admin', 'teacher', 'parent'].includes(role)) {
        e.preventDefault();
        alert('Please select a login role (Admin, Teacher, or Parent)');
        return false;
    }
    
    // Show loading state
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
});
</script>

<?php require_once APP_PATH . '/views/layouts/footer.php'; ?>