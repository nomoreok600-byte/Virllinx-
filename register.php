<?php
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: ' . url(''));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!csrf_check()) {
        $error = 'Session expired. Please try again.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username must be 3-30 characters (letters, numbers, underscores).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already taken.';
        } else {
            $colors = ['#8b5cf6', '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444'];
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, avatar_color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $colors[array_rand($colors)]]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $username;
            $_SESSION['user_role'] = 'user';
            header('Location: ' . url(''));
            exit;
        }
    }
}

$page_title = 'Create Account — ' . setting('site_name', 'ViralLinx');
require __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Create your account ✨</h1>
        <p class="sub">Join to like videos, comment and get a personalized feed.</p>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?php echo e($_POST['username'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" minlength="6" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit" class="btn-full">Create Account</button>
        </form>
        <div class="auth-switch">Already have an account? <a href="<?php echo url('login'); ?>">Sign in</a></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
