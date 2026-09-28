<?php
// Tag landing page — /tag/<name> (case-insensitive match)
require_once __DIR__ . '/includes/db.php';

$tag = trim(urldecode($_GET['tag'] ?? ''));
if ($tag === '') { header('Location: ' . url('tags')); exit; }

$per_page = 24;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = "WHERE v.status = 'published' AND v.tags IS NOT NULL AND v.tags != '' AND FIND_IN_SET(?, REPLACE(v.tags, ', ', ','))";
$params = [$tag];

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM videos v $where");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id $where ORDER BY v.id DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$videos = $stmt->fetchAll();

$page_title = '#' . $tag . ' Videos — ' . setting('site_name', 'ViralLinx');
$page_description = 'Watch videos tagged ' . $tag . ' on ' . setting('site_name', 'ViralLinx') . '.';
$page_canonical = canonical_url('tag/' . urlencode($tag));
require __DIR__ . '/includes/header.php';
?>

<div class="section-header">
    <h2 class="section-title">#<?php echo e($tag); ?></h2>
    <span style="color:var(--yt-text-2);font-size:14px;"><?php echo number_format($total); ?> video<?php echo $total === 1 ? '' : 's'; ?></span>
</div>

<?php echo render_ads('between_grid'); ?>

<?php if (!empty($videos)): ?>
    <div class="video-grid">
        <?php foreach ($videos as $v) include __DIR__ . '/includes/video_card.php'; ?>
    </div>
    <?php echo paginate($total, $per_page, $page, url('tag/' . urlencode($tag))); ?>
<?php else: ?>
    <div class="empty-state">
        <h3>No videos with this tag</h3>
        <p><a href="<?php echo url('tags'); ?>" style="color:var(--yt-blue);text-decoration:none;">Browse all tags &rarr;</a></p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
