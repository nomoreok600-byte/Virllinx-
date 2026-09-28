<?php
require_once __DIR__ . '/includes/db.php';
http_response_code(404);
$page_title = 'Page Not Found — ' . setting('site_name', 'ViralLinx');
require __DIR__ . '/includes/header.php';
?>

<div class="empty-state" style="margin:60px 0;">
    <h3 style="font-size:60px;margin-bottom:16px;">404</h3>
    <h3>Page not found</h3>
    <p>The page you're looking for doesn't exist or was removed.</p>
    <p style="margin-top:24px;"><a href="<?php echo url(''); ?>" class="btn-watch">Back to Home</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
