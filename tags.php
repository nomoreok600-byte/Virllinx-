<?php
// All tags cloud — /tags
require_once __DIR__ . '/includes/db.php';

$all_tags = get_all_tags(200);

$page_title = 'All Tags — ' . setting('site_name', 'ViralLinx');
$page_description = 'Explore every topic and tag on ' . setting('site_name', 'ViralLinx') . '.';
$page_canonical = canonical_url('tags');
require __DIR__ . '/includes/header.php';
?>

<div class="section-header">
    <h2 class="section-title">All Tags</h2>
    <span style="color:var(--yt-text-2);font-size:14px;"><?php echo count($all_tags); ?> tags</span>
</div>

<?php if (!empty($all_tags)): ?>
<div class="tag-cloud">
    <?php foreach ($all_tags as $t): ?>
        <a class="tag-chip" href="<?php echo url('tag/' . urlencode($t['name'])); ?>">#<?php echo e($t['name']); ?> <span style="opacity:0.6;"><?php echo (int)$t['count']; ?></span></a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state">
    <h3>No tags yet</h3>
    <p>Add tags to videos in the admin panel to see them here.</p>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
