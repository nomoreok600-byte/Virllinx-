<?php
require_once __DIR__ . '/_admin.php';

$msg = '';
$me = current_user();

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    $uid = intval($_POST['id'] ?? 0);
    if ($uid && $uid === (int)$me['id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot modify your own account here.']);
        exit;
    }
    if ($_POST['ajax_action'] === 'toggle_ban' && $uid) {
        $response['success'] = $pdo->prepare("UPDATE users SET status = IF(status='active','banned','active') WHERE id = ?")->execute([$uid]);
    }
    if ($_POST['ajax_action'] === 'toggle_role' && $uid) {
        $response['success'] = $pdo->prepare("UPDATE users SET role = IF(role='user','admin','user') WHERE id = ?")->execute([$uid]);
    }
    if ($_POST['ajax_action'] === 'delete_user' && $uid) {
        $pdo->prepare("DELETE FROM video_likes WHERE user_id = ?")->execute([$uid]);
        $pdo->prepare("DELETE FROM watch_history WHERE user_id = ?")->execute([$uid]);
        $response['success'] = $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
    }
    echo json_encode($response);
    exit;
}

// Create user manually
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    if (preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username) && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $msg = 'Username or email already exists.';
        } else {
            $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?,?,?,?)")
                ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $msg = 'User created!';
        }
    } else {
        $msg = 'Invalid input: username 3-30 chars, valid email, password 6+ chars.';
    }
}

// Reset a user's password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $uid = intval($_POST['user_id']);
    $newpass = $_POST['new_password'];
    if ($uid && strlen($newpass) >= 6) {
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([password_hash($newpass, PASSWORD_DEFAULT), $uid]);
        $msg = 'Password reset for user #' . $uid;
    } else {
        $msg = 'Password must be at least 6 characters.';
    }
}

$users = $pdo->query("SELECT u.*,
    (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.id) AS comment_count,
    (SELECT COUNT(*) FROM video_likes l WHERE l.user_id = u.id) AS like_count
    FROM users u ORDER BY u.id DESC")->fetchAll();

admin_header('User Management', 'users');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="layout-grid">
    <div class="card">
        <div class="card-header"><div class="card-title">➕ Create User</div></div>
        <form method="POST">
            <input type="hidden" name="create_user" value="1">
            <div class="form-grid">
                <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Password</label><input type="text" name="password" minlength="6" required></div>
                <div class="form-group"><label>Role</label>
                    <select name="role"><option value="user">User</option><option value="admin">Admin</option></select>
                </div>
            </div>
            <div style="margin-top:16px;"><button type="submit" class="btn btn-primary">Create User</button></div>
        </form>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">🔑 Reset User Password</div></div>
        <form method="POST">
            <input type="hidden" name="reset_password" value="1">
            <div class="form-group"><label>User</label>
                <select name="user_id" required>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo e($u['username'] . ' (' . $u['email'] . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-top:12px;"><label>New Password</label><input type="text" name="new_password" minlength="6" required></div>
            <div style="margin-top:16px;"><button type="submit" class="btn btn-secondary">Reset Password</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Users (<?php echo count($users); ?>)</div></div>
    <div class="table-toolbar">
        <input type="text" id="tableFilter" class="search-input" placeholder="Filter users...">
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Likes</th><th>Comments</th><th>Last Login</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td style="font-weight:600;"><?php echo e($u['username']); ?><?php echo $u['id'] == $me['id'] ? ' <span style="color:var(--text-muted);font-size:12px;">(you)</span>' : ''; ?></td>
                    <td style="color:var(--text-muted);"><?php echo e($u['email']); ?></td>
                    <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                    <td><span class="badge badge-<?php echo $u['status'] === 'active' ? 'active' : 'banned'; ?>"><?php echo $u['status']; ?></span></td>
                    <td><?php echo number_format($u['like_count']); ?></td>
                    <td><?php echo number_format($u['comment_count']); ?></td>
                    <td style="color:var(--text-muted);"><?php echo $u['last_login'] ? time_ago($u['last_login']) : 'Never'; ?></td>
                    <td style="color:var(--text-muted);"><?php echo time_ago($u['created_at']); ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($u['id'] != $me['id']): ?>
                            <button class="btn btn-secondary btn-sm" onclick="toggleField('toggle_role', <?php echo $u['id']; ?>)"><?php echo $u['role'] === 'admin' ? 'Demote' : 'Make Admin'; ?></button>
                            <button class="btn <?php echo $u['status'] === 'active' ? 'btn-secondary' : 'btn-success'; ?> btn-sm" onclick="toggleField('toggle_ban', <?php echo $u['id']; ?>)"><?php echo $u['status'] === 'active' ? 'Ban' : 'Unban'; ?></button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRow('delete_user', <?php echo $u['id']; ?>, 'user')">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
