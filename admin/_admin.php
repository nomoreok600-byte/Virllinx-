<?php
// Shared admin bootstrap: auth check + layout helpers
require_once dirname(__DIR__) . '/includes/db.php';
require_admin();

function admin_header($title, $active) {
    $items = [
        'dashboard' => ['index', '📊 Dashboard'],
        'videos' => ['videos', '🎬 Videos'],
        'categories' => ['categories', '🗂 Categories'],
        'comments' => ['comments', '💬 Comments'],
        'users' => ['users', '👥 Users'],
        'ads' => ['ads', '💰 Ads Manager'],
        'pages' => ['pages', '📄 Pages'],
        'settings' => ['settings', '⚙️ Settings'],
    ];
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo e($title); ?> — Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/admin.css?v=2">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="/admin" class="admin-logo"><?php echo e(setting('site_name', 'ViralLinx')); ?> <span>Admin</span></a>
        <nav>
            <?php foreach ($items as $key => $item): ?>
                <a href="/admin<?php echo $item[0] === 'index' ? '' : '/' . $item[0]; ?>" class="<?php echo $key === $active ? 'active' : ''; ?>"><?php echo $item[1]; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="/" target="_blank">🌐 View Site</a>
            <a href="/logout" class="danger">Logout</a>
        </div>
    </aside>
    <main class="admin-main">
        <h1 class="page-heading"><?php echo e($title); ?></h1>
    <?php
}

function admin_footer() {
    ?>
    </main>
</div>
<script src="/assets/admin.js?v=2"></script>
</body>
</html>
    <?php
}
