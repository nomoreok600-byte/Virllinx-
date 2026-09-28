<?php
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?php echo canonical_url('/'); ?></loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc><?php echo canonical_url('browse'); ?></loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    <url><loc><?php echo canonical_url('popular'); ?></loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    <url><loc><?php echo canonical_url('new'); ?></loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    <url><loc><?php echo canonical_url('most-liked'); ?></loc><changefreq>daily</changefreq><priority>0.8</priority></url>
<?php
foreach ($pdo->query("SELECT id, title, slug, created_at FROM videos WHERE status = 'published' ORDER BY id DESC") as $v) {
    $slug = !empty($v['slug']) ? $v['slug'] : slugify($v['title']);
    echo '    <url><loc>' . e(canonical_url('watch/' . $v['id'] . '/' . $slug)) . '</loc><lastmod>' . date('Y-m-d', strtotime($v['created_at'])) . '</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
}
foreach ($pdo->query("SELECT slug FROM pages") as $p) {
    echo '    <url><loc>' . e(canonical_url('page/' . $p['slug'])) . '</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>' . "\n";
}
?>
</urlset>
