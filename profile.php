<?php
require_once __DIR__ . '/includes/db.php';

if (!is_logged_in()) {
    header('Location: ' . url('login'));
    exit;
}
$user = current_user();
$msg = '';
$error = '';

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!csrf_check()) {
        $error = 'Session expired. Please try again.';
    } elseif (!password_verify($_POST['current_password'] ?? '', $user['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($_POST['new_password'] ?? '') < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user['id']]);
        $msg = 'Password updated successfully.';
    }
}

// Stats & history
$likes_count = $pdo->prepare("SELECT COUNT(*) FROM video_likes WHERE user_id = ?");
$likes_count->execute([$user['id']]);
$likes_count = $likes_count->fetchColumn();

$comments_count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$comments_count->execute([$user['id']]);
$comments_count = $comments_count->fetchColumn();

$history = $pdo->prepare("SELECT v.*, h.watched_at FROM watch_history h JOIN videos v ON v.id = h.video_id WHERE h.user_id = ? AND v.status='published' ORDER BY h.watched_at DESC LIMIT 12");
$history->execute([$user['id']]);
$history = $history->fetchAll();

$liked_videos = $pdo->prepare("SELECT v.* FROM video_likes l JOIN videos v ON v.id = l.video_id WHERE l.user_id = ? AND v.status='published' ORDER BY l.created_at DESC LIMIT 12");
$liked_videos->execute([$user['id']]);
$liked_videos = $liked_videos->fetchAll();

$page_title = 'My Profile — ' . setting('site_name', 'ViralLinx');
require __DIR__ . '/includes/header.php';
?>

<div style="max-width:900px;margin:40px auto;">
    <div class="auth-card" style="margin-bottom:32px;">
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px;">
            <span class="avatar" style="width:64px;height:64px;font-size:26px;background:<?php echo e($user['avatar_color']); ?>"><?php echo e(strtoupper(substr($user['username'], 0, 1))); ?></span>
            <div>
                <h1 style="font-size:24px;letter-spacing:-0.5px;"><?php echo e($user['username']); ?></h1>
                <p style="color:var(--text-secondary);font-size:14px;"><?php echo e($user['email']); ?> • Joined <?php echo time_ago($user['created_at']); ?></p>
            </div>
        </div>
        <div style="display:flex;gap:24px;font-size:14px;color:var(--text-secondary);">
            <span>❤ <b style="color:#fff;"><?php echo number_format($likes_count); ?></b> likes given</span>
            <span>💬 <b style="color:#fff;"><?php echo number_format($comments_count); ?></b> comments</span>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo e($msg); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?php echo e($error); ?></div><?php endif; ?>

    <div class="auth-card" style="margin-bottom:32px;">
        <h2 style="font-size:18px;margin-bottom:20px;">Change Password</h2>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="change_password" value="1">
            <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
            <div class="form-group"><label>New Password</label><input type="password" name="new_password" minlength="6" required></div>
            <button type="submit" class="btn-full" style="max-width:220px;">Update Password</button>
        </form>
    </div>

    <?php if (!empty($liked_videos)): ?>
    <div class="section-header"><h2 class="section-title">❤ Videos You Liked</h2></div>
    <div class="video-grid">
        <?php foreach ($liked_videos as $v) include __DIR__ . '/includes/video_card.php'; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($history)): ?>
    <div class="section-header"><h2 class="section-title">🕘 Watch History</h2></div>
    <div class="video-grid">
        <?php foreach ($history as $v) include __DIR__ . '/includes/video_card.php'; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
