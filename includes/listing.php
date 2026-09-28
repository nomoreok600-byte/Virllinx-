<?php
// Generic video listing renderer.
// Expects: $listing_title, $order_by (safe SQL), optional $listing_base_url
require_once __DIR__ . '/db.php';

$per_page = 24;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// ---------- Multi-category selection (?cats=1,2,3) ----------
// Backward compatible: legacy ?cat=5 still works
$cat_param = trim($_GET['cats'] ?? '');
if ($cat_param === '' && !empty($_GET['cat'])) $cat_param = trim($_GET['cat']);
$cat_ids = array_filter(array_map('intval', explode(',', $cat_param)));
$cat_ids = array_values(array_unique($cat_ids));

$q = trim($_GET['q'] ?? '');

$where = "WHERE v.status = 'published'";
$params = [];
if ($cat_ids) {
    $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
    $where .= " AND (EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id IN ($placeholders)) OR v.category_id IN ($placeholders))";
    $params = array_merge($params, $cat_ids, $cat_ids);
}
if ($q !== '') {
    $where .= " AND (v.title LIKE ? OR v.description LIKE ? OR v.tags LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM videos v $where");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id $where ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$videos = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$page_title = $listing_title . ' — ' . setting('site_name', 'ViralLinx');
$page_description = $listing_title . ' videos on ' . setting('site_name', 'ViralLinx') . '. Watch trending AI movies, viral videos and stories in high quality.';
require __DIR__ . '/header.php';

$base = $listing_base_url ?? strtok($_SERVER['REQUEST_URI'], '?');

// Build a URL preserving current multi-category selection (+ optional q)
function listing_url($base, array $cat_ids, $q, $page = null) {
    $qs = [];
    if ($cat_ids) $qs[] = 'cats=' . implode(',', $cat_ids);
    if ($q !== '') $qs[] = 'q=' . urlencode($q);
    if ($page) $qs[] = 'page=' . $page;
    return $base . ($qs ? ('?' . implode('&', $qs)) : '');
}
$paginate_url = listing_url($base, $cat_ids, $q);
?>

<div class="section-header">
    <h2 class="section-title"><?php echo e($listing_title); ?><?php if ($q !== '') echo ' for "' . e($q) . '"'; ?></h2>
    <span style="color:var(--yt-text-2);font-size:14px;"><?php echo number_format($total); ?> videos</span>
</div>

<?php if (!empty($categories)): ?>
<div class="cat-pills" id="catPills">
    <a href="<?php echo e(listing_url($base, [], $q)); ?>" class="cat-pill <?php echo !$cat_ids ? 'active' : ''; ?>">All</a>
    <?php foreach ($categories as $cat): $is_on = in_array((int)$cat['id'], $cat_ids, true); ?>
        <a href="<?php echo e(listing_url($base, $is_on ? array_values(array_diff($cat_ids, [(int)$cat['id']])) : array_merge($cat_ids, [(int)$cat['id']]), $q)); ?>"
           class="cat-pill <?php echo $is_on ? 'active' : ''; ?>"
           title="Click to <?php echo $is_on ? 'remove from' : 'add to'; ?> selection (multi-select)">
            <?php echo $is_on ? '<span class="chip-check">✓</span> ' : ''; ?><?php echo e($cat['name']); ?>
        </a>
    <?php endforeach; ?>
</div>
<?php if ($cat_ids): ?>
<div style="font-size:13px;color:var(--yt-text-2);margin:-6px 0 14px;">
    Filtering by <?php echo count($cat_ids); ?> categor<?php echo count($cat_ids) === 1 ? 'y' : 'ies'; ?> — click chips again to deselect.
</div>
<?php endif; ?>
<?php endif; ?>

<?php echo render_ads('between_grid'); ?>

<?php if (!empty($videos)): ?>
    <div class="video-grid">
        <?php foreach ($videos as $v) include __DIR__ . '/video_card.php'; ?>
    </div>
    <?php echo paginate($total, $per_page, $page, $paginate_url); ?>
<?php else: ?>
    <div class="empty-state">
        <h3>No videos found</h3>
        <p>Try a different keyword, tag, or category combination.</p>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
