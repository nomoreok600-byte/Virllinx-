<?php
require_once __DIR__ . '/_admin.php';

// Export CSV backup
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="videos_backup_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Title', 'Views', 'Likes', 'Status', 'Created At']);
    foreach ($pdo->query("SELECT id, title, views, likes, status, created_at FROM videos ORDER BY id DESC") as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$total_videos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn() ?: 0;
$published = $pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'published'")->fetchColumn() ?: 0;
$total_views = $pdo->query("SELECT COALESCE(SUM(views),0) FROM videos")->fetchColumn();
$total_likes = $pdo->query("SELECT COALESCE(SUM(likes),0) FROM videos")->fetchColumn();
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn() ?: 0;
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
$new_users_week = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn() ?: 0;
$active_ads = $pdo->query("SELECT COUNT(*) FROM ads WHERE is_active = 1")->fetchColumn() ?: 0;

$top_videos = $pdo->query("SELECT * FROM videos ORDER BY views DESC LIMIT 5")->fetchAll();
$recent_comments = $pdo->query("SELECT c.*, v.title AS video_title FROM comments c JOIN videos v ON c.video_id = v.id ORDER BY c.id DESC LIMIT 8")->fetchAll();
$recent_users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 8")->fetchAll();

admin_header('Dashboard', 'dashboard');
?>

<div class="metrics-grid">
    <div class="metric-card"><div class="metric-label">Total Videos</div><div class="metric-val"><?php echo number_format($total_videos); ?></div></div>
    <div class="metric-card"><div class="metric-label">Published</div><div class="metric-val green"><?php echo number_format($published); ?></div></div>
    <div class="metric-card"><div class="metric-label">Total Views</div><div class="metric-val"><?php echo number_format($total_views); ?></div></div>
    <div class="metric-card"><div class="metric-label">Total Likes</div><div class="metric-val purple"><?php echo number_format($total_likes); ?></div></div>
    <div class="metric-card"><div class="metric-label">Comments</div><div class="metric-val"><?php echo number_format($total_comments); ?></div></div>
    <div class="metric-card"><div class="metric-label">Users</div><div class="metric-val"><?php echo number_format($total_users); ?></div></div>
    <div class="metric-card"><div class="metric-label">New Users (7d)</div><div class="metric-val amber"><?php echo number_format($new_users_week); ?></div></div>
    <div class="metric-card"><div class="metric-label">Active Ads</div><div class="metric-val green"><?php echo number_format($active_ads); ?></div></div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Quick Actions</div>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="/admin/videos" class="btn btn-primary">+ Add Video</a>
        <a href="/admin/ads" class="btn btn-success">Manage Ads</a>
        <a href="/admin/users" class="btn btn-secondary">User Management</a>
        <a href="/admin/settings" class="btn btn-secondary">Site Settings</a>
        <a href="/admin?action=export_csv" class="btn btn-secondary">📥 Export CSV Backup</a>
        <a href="/sitemap.xml" target="_blank" class="btn btn-secondary">View Sitemap</a>
    </div>
</div>

<div class="layout-grid">
    <div class="card">
        <div class="card-header"><div class="card-title">🔥 Top Videos by Views</div></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Video</th><th>Views</th><th>Likes</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($top_videos as $v): ?>
                    <tr>
                        <td><a href="<?php echo video_url($v); ?>" target="_blank" style="color:#fff;text-decoration:none;"><?php echo e(mb_substr($v['title'], 0, 50)); ?></a></td>
                        <td><?php echo number_format($v['views']); ?></td>
                        <td><?php echo number_format($v['likes']); ?></td>
                        <td><span class="badge badge-<?php echo $v['status']; ?>"><?php echo $v['status']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">👥 New Users</div></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>User</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($recent_users as $u): ?>
                    <tr>
                        <td><?php echo e($u['username']); ?> <span class="badge badge-<?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                        <td style="color:var(--text-muted);"><?php echo time_ago($u['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">💬 Latest Comments</div><a href="/admin/comments" class="btn btn-secondary btn-sm">Manage all</a></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>User</th><th>Comment</th><th>Video</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($recent_comments as $c): ?>
                <tr>
                    <td><?php echo e($c['user_name']); ?></td>
                    <td><?php echo e(mb_substr($c['comment_text'], 0, 60)); ?></td>
                    <td style="color:var(--text-muted);"><?php echo e(mb_substr($c['video_title'], 0, 35)); ?></td>
                    <td style="color:var(--text-muted);"><?php echo time_ago($c['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
