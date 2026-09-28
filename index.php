<?php
require_once __DIR__ . '/includes/db.php';

// Featured / hero video
$hero_video = $pdo->query("SELECT * FROM videos WHERE status = 'published' ORDER BY is_featured DESC, id DESC LIMIT 1")->fetch();

$latest = $pdo->query("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.status = 'published' ORDER BY v.id DESC LIMIT 12")->fetchAll();
$popular = $pdo->query("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.status = 'published' ORDER BY v.views DESC LIMIT 8")->fetchAll();
$liked = $pdo->query("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.status = 'published' ORDER BY v.likes DESC LIMIT 8")->fetchAll();

$hero_tags = $hero_video ? get_video_tags($hero_video) : [];
$all_tags = get_all_tags(14);
$home_categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC LIMIT 12")->fetchAll();

$page_canonical = canonical_url('/');
$og_image = $hero_video['thumbnail_url'] ?? '';
require __DIR__ . '/includes/header.php';
?>

<?php if ($hero_video): ?>
<section class="hero-banner" style="background-image:url('<?php echo e($hero_video['thumbnail_url']); ?>')">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="badge">Featured Premiere</span>
        <h1 class="hero-title"><?php echo e($hero_video['title']); ?></h1>
        <p class="hero-desc"><?php echo e($hero_video['description'] ?: 'Immerse yourself in this full story, streaming now in high quality.'); ?></p>
        <?php if ($hero_tags): ?>
        <div class="video-tags" style="margin:0 0 20px;">
            <?php foreach (array_slice($hero_tags, 0, 5) as $tg): ?>
                <a class="tag-chip" href="<?php echo url('tag/' . urlencode($tg)); ?>">#<?php echo e($tg); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="<?php echo video_url($hero_video); ?>" class="btn-watch">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            Watch Now
        </a>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($home_categories)): ?>
<div class="cat-pills">
    <a href="<?php echo url('browse'); ?>" class="cat-pill active">All</a>
    <?php foreach ($home_categories as $cat): ?>
        <a href="<?php echo url('browse?cat=' . $cat['id']); ?>" class="cat-pill"><?php echo e($cat['name']); ?></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php echo render_ads('between_grid'); ?>

<section>
    <div class="section-header">
        <h2 class="section-title">Latest Uploads</h2>
        <a class="see-all" href="<?php echo url('new'); ?>">See all &rarr;</a>
    </div>
    <?php if (!empty($latest)): ?>
        <div class="video-grid">
            <?php foreach ($latest as $v) include __DIR__ . '/includes/video_card.php'; ?>
        </div>
    <?php else: ?>
        <div class="empty-state"><h3>No videos yet</h3><p>Check back soon for new content.</p></div>
    <?php endif; ?>
</section>

<?php if (!empty($popular)): ?>
<section>
    <div class="section-header">
        <h2 class="section-title">Popular Now</h2>
        <a class="see-all" href="<?php echo url('popular'); ?>">See all &rarr;</a>
    </div>
    <div class="video-grid">
        <?php foreach ($popular as $v) include __DIR__ . '/includes/video_card.php'; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($liked)): ?>
<section>
    <div class="section-header">
        <h2 class="section-title">Most Liked</h2>
        <a class="see-all" href="<?php echo url('most-liked'); ?>">See all &rarr;</a>
    </div>
    <div class="video-grid">
        <?php foreach ($liked as $v) include __DIR__ . '/includes/video_card.php'; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($all_tags)): ?>
<section>
    <div class="section-header">
        <h2 class="section-title">Explore by Tag</h2>
        <a class="see-all" href="<?php echo url('tags'); ?>">All tags &rarr;</a>
    </div>
    <div class="tag-cloud">
        <?php foreach ($all_tags as $t): ?>
            <a class="tag-chip" href="<?php echo url('tag/' . urlencode($t['name'])); ?>">#<?php echo e($t['name']); ?> <span style="opacity:0.6;"><?php echo (int)$t['count']; ?></span></a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
