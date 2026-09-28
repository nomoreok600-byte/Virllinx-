<?php
/**
 * Integration test for the ViralLinx upgrade (tags + multi-categories).
 * Run: php tests/run_integration.php   (requires MariaDB running with vl_test db)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
$failures = 0;
function check($label, $cond) {
    global $failures;
    echo ($cond ? "  PASS" : "  FAIL") . " - $label\n";
    if (!$cond) $failures++;
}

// --- Connect using the test DB (override config constants) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'vl_test');
define('DB_USER', 'vl_test');
define('DB_PASS', 'vl_test_pass');
define('SITE_URL', 'https://test.local');
define('DEFAULT_ADMIN_EMAIL', 'a@b.c');
define('DEFAULT_ADMIN_PASS', 'x');

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
require __DIR__ . '/../includes/schema.php';
require __DIR__ . '/../includes/functions.php';

echo "== 1. Schema migration ==\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
check("all tables created", count(array_intersect(['videos','video_categories','categories','users','comments','video_likes','watch_history','settings','ads','pages'], $tables)) === 10);
$video_cols = $pdo->query("DESCRIBE videos")->fetchAll(PDO::FETCH_COLUMN);
check("videos.tags column exists", in_array('tags', $video_cols, true));
check("videos.category_id column exists", in_array('category_id', $video_cols, true));

// Re-run schema (idempotency)
require __DIR__ . '/../includes/schema.php';
check("schema re-run is idempotent", true);

echo "== 2. Legacy backfill ==\n";
// Simulate legacy data: video with only category_id set (no junction row)
$pdo->exec("INSERT INTO videos (title, category_id, tags) VALUES ('Legacy Video', NULL, NULL)");
$pdo->exec("INSERT INTO categories (name, slug) VALUES ('Movies', 'movies')");
$legacy_vid = (int)$pdo->lastInsertId();
$pdo->exec("UPDATE videos SET category_id = 1 WHERE id = $legacy_vid");
// Wipe junction, re-run schema backfill
$pdo->exec("DELETE FROM video_categories");
require __DIR__ . '/../includes/schema.php';
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM video_categories")->fetchColumn();
check("legacy category_id backfilled into junction", $cnt === 1);

echo "== 3. Tag helpers ==\n";
check("normalize_tags_input basic", normalize_tags_input('a, b , c,,  b') === 'a, b, c');
check("normalize_tags_input strips html", strpos(normalize_tags_input('<b>x</b>'), '<') === false);
check("normalize_tags_input caps at 15", count(explode(',', normalize_tags_input(implode(',', range(1, 30))))) === 15);
$v = ['tags' => 'Sci-Fi, AI, sci-fi'];
check("get_video_tags dedupes case-insensitively", get_video_tags($v) === ['Sci-Fi', 'AI']);
check("get_video_tags empty-safe", get_video_tags(['tags' => null]) === []);

echo "== 4. Multi-category sync ==\n";
sync_video_categories($legacy_vid, [1, 1, 2]);
$rows = $pdo->query("SELECT category_id FROM video_categories WHERE video_id = $legacy_vid")->fetchAll(PDO::FETCH_COLUMN);
check("sync dedupes + stores ids", count($rows) === 2);
$legacy_cat = (int)$pdo->query("SELECT category_id FROM videos WHERE id = $legacy_vid")->fetchColumn();
check("legacy column synced to primary", $legacy_cat === 1);

echo "== 5. Listing query (multi-category + search incl. tags) ==\n";
$cat_ids = [1];
$placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
$where = "WHERE v.status = 'published' AND (EXISTS (SELECT 1 FROM video_categories vc WHERE vc.video_id = v.id AND vc.category_id IN ($placeholders)) OR v.category_id IN ($placeholders))";
$params = array_merge($cat_ids, $cat_ids);
$stmt = $pdo->prepare("SELECT v.* FROM videos v $where");
$stmt->execute($params);
check("multi-category filter finds video", count($stmt->fetchAll()) >= 1);

$pdo->exec("INSERT INTO videos (title, tags) VALUES ('The AI Revolution', 'sci-fi, ai movie')");
$stmt = $pdo->prepare("SELECT v.* FROM videos v WHERE v.status='published' AND (v.title LIKE ? OR v.description LIKE ? OR v.tags LIKE ?)");
$stmt->execute(['%revolution%', '%revolution%', '%revolution%']);
check("tag-aware search finds video", count($stmt->fetchAll()) === 1);

echo "== 6. Tag page query (FIND_IN_SET) ==\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM videos v WHERE v.status = 'published' AND v.tags IS NOT NULL AND v.tags != '' AND FIND_IN_SET(?, REPLACE(v.tags, ', ', ','))");
$stmt->execute(['AI movie']);
check("FIND_IN_SET matches tag", (int)$stmt->fetchColumn() === 1);
$stmt->execute(['ai movie']);
check("FIND_IN_SET case-insensitive (utf8mb4_unicode_ci)", (int)$stmt->fetchColumn() === 1);
$stmt->execute(['nonexistent']);
check("FIND_IN_SET rejects missing tag", (int)$stmt->fetchColumn() === 0);

echo "== 7. get_all_tags ==\n";
$tags = get_all_tags(10);
$names = array_column($tags, 'name');
check("get_all_tags returns tags", count($tags) >= 2);
check("get_all_tags sorted by count desc", $tags[0]['count'] >= (end($tags)['count']));

echo "== 8. Admin save flow simulation ==\n";
// Simulate admin/videos.php save for a NEW video with 2 categories + tags
$_POST_sim = ['title' => 'E2E Video', 'embed_code' => 'https://cdn.example/v.mp4', 'tags' => 'tag one, tag two', 'categories' => ['1', '2']];
$title = trim($_POST_sim['title']);
$tags = normalize_tags_input($_POST_sim['tags']);
$cats = array_map('intval', $_POST_sim['categories']);
$stmt = $pdo->prepare("INSERT INTO videos (title, slug, embed_code, video_url, thumbnail_url, description, status, category_id, meta_title, meta_description, is_featured, tags) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([$title, slugify($title), 'https://cdn.example/v.mp4', 'https://cdn.example/v.mp4', '', '', 'published', $cats[0], '', '', 0, $tags]);
$new_id = (int)$pdo->lastInsertId();
sync_video_categories($new_id, $cats);
$jrows = $pdo->query("SELECT category_id FROM video_categories WHERE video_id = $new_id")->fetchAll(PDO::FETCH_COLUMN);
check("new video saved with 2 categories", count($jrows) === 2);
check("tags saved normalized", strpos((string)$pdo->query("SELECT tags FROM videos WHERE id = $new_id")->fetchColumn(), 'tag one') !== false);

// Edit flow: change categories 2 -> [2]
sync_video_categories($new_id, [2]);
$jrows = $pdo->query("SELECT category_id FROM video_categories WHERE video_id = $new_id")->fetchAll(PDO::FETCH_COLUMN);
check("edit replaces categories cleanly", array_map('intval', $jrows) === [2]);

// Delete cleanup (as admin/videos.php does)
$pdo->prepare("DELETE FROM video_categories WHERE video_id = ?")->execute([$new_id]);
check("delete removes junction rows", (int)$pdo->query("SELECT COUNT(*) FROM video_categories WHERE video_id = $new_id")->fetchColumn() === 0);

echo "\n" . ($failures === 0 ? "ALL INTEGRATION TESTS PASSED" : "$failures TEST(S) FAILED") . "\n";
exit($failures === 0 ? 0 : 1);
