<?php
require_once __DIR__ . '/includes/db.php';

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$page_title = $page['title'] . ' — ' . setting('site_name', 'ViralLinx');
$page_description = mb_substr(strip_tags($page['content']), 0, 160);
require __DIR__ . '/includes/header.php';
?>

<div class="static-page">
    <h1><?php echo e($page['title']); ?></h1>
    <div class="page-content"><?php echo $page['content']; ?></div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
