<?php
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: ' . url(''));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!csrf_check()) {
        $error = 'Session expired. Please try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $u = $stmt->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            if ($u['status'] === 'banned') {
                $error = 'Your account has been suspended.';
            } else {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['user_name'] = $u['username'];
                $_SESSION['user_role'] = $u['role'];
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$u['id']]);
                $redirect = ($_GET['redirect'] ?? '') === 'admin' && $u['role'] === 'admin' ? 'admin' : '';
                header('Location: ' . url($redirect));
                exit;
            }
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$page_title = 'Sign In — ' . setting('site_name', 'ViralLinx');
require __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h1>Welcome back 👋</h1>
        <p class="sub">Sign in to like videos, comment and track your history.</p>
        <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="login" value="<?php echo e($_POST['login'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-full">Sign In</button>
        </form>
        <div class="auth-switch">New here? <a href="<?php echo url('register'); ?>">Create an account</a></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
