<?php
require_once __DIR__ . '/includes/db.php';

$id = intval($_GET['id'] ?? 0);
$logged_in = is_logged_in();
$user = current_user();

// ---------- AJAX actions ----------
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!$logged_in) {
        echo json_encode(['success' => false, 'message' => 'Please sign in first.']);
        exit;
    }
    if ($_POST['action'] === 'like') {
        $vid = intval($_POST['id'] ?? $id);
        $check = $pdo->prepare("SELECT id FROM video_likes WHERE video_id = ? AND user_id = ?");
        $check->execute([$vid, $user['id']]);
        if ($check->fetch()) {
            // Unlike
            $pdo->prepare("DELETE FROM video_likes WHERE video_id = ? AND user_id = ?")->execute([$vid, $user['id']]);
            $pdo->prepare("UPDATE videos SET likes = GREATEST(likes - 1, 0) WHERE id = ?")->execute([$vid]);
            $liked = false;
        } else {
            $pdo->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)")->execute([$vid, $user['id']]);
            $pdo->prepare("UPDATE videos SET likes = likes + 1 WHERE id = ?")->execute([$vid]);
            $liked = true;
        }
        $stmt = $pdo->prepare("SELECT likes FROM videos WHERE id = ?");
        $stmt->execute([$vid]);
        echo json_encode(['success' => true, 'likes' => format_count($stmt->fetchColumn()), 'liked' => $liked]);
        exit;
    }
    if ($_POST['action'] === 'like_comment') {
        $comment_id = intval($_POST['comment_id']);
        $pdo->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?")->execute([$comment_id]);
        $stmt = $pdo->prepare("SELECT likes FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        echo json_encode(['success' => true, 'likes' => $stmt->fetchColumn()]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// ---------- Comment submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && $logged_in) {
    $text = trim($_POST['comment_text'] ?? '');
    if ($text !== '' && csrf_check()) {
        $status = setting('comment_moderation', '0') === '1' ? 'pending' : 'approved';
        $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, user_name, comment_text, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $user['id'], $user['username'], $text, $status]);
    }
    header('Location: ' . url('watch/' . $id . '/' . ($_GET['slug'] ?? '')));
    exit;
}

// ---------- Fetch video ----------
$stmt = $pdo->prepare("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id WHERE v.id = ? AND v.status = 'published'");
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// Redirect to canonical slug URL (SEO)
$slug = !empty($video['slug']) ? $video['slug'] : slugify($video['title']);
if (($_GET['slug'] ?? '') !== $slug && strpos($_SERVER['REQUEST_URI'], '.php') === false) {
    header('Location: ' . url('watch/' . $id . '/' . $slug), true, 301);
    exit;
}

// Increment views (once per session per video)
if (empty($_SESSION['viewed_' . $id])) {
    $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$id]);
    $_SESSION['viewed_' . $id] = true;
    $video['views']++;
}

// Watch history
if ($logged_in) {
    $pdo->prepare("INSERT INTO watch_history (video_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE watched_at = CURRENT_TIMESTAMP")->execute([$id, $user['id']]);
}

// User already liked?
$user_liked = false;
if ($logged_in) {
    $lk = $pdo->prepare("SELECT id FROM video_likes WHERE video_id = ? AND user_id = ?");
    $lk->execute([$id, $user['id']]);
    $user_liked = (bool)$lk->fetch();
}

// Video tags + all categories (for display)
$video_tags = get_video_tags($video);
$video_cats = get_video_categories($video['id']);
if (!$video_cats && $video['category_id']) {
    $video_cats = [['id' => $video['category_id'], 'name' => $video['category_name'], 'slug' => null]];
}

// Recommended: shared categories/tags first, then latest
$rec_stmt = $pdo->prepare("SELECT * FROM videos WHERE id != ? AND status = 'published' ORDER BY (category_id = ?) DESC, id DESC LIMIT 8");
$rec_stmt->execute([$id, $video['category_id']]);
$recommended = $rec_stmt->fetchAll();

$comm_stmt = $pdo->prepare("SELECT * FROM comments WHERE video_id = ? AND status = 'approved' ORDER BY id DESC");
$comm_stmt->execute([$id]);
$comments = $comm_stmt->fetchAll();

// ---------- SEO ----------
$current_url = canonical_url('watch/' . $id . '/' . $slug);
$page_title = ($video['meta_title'] ?: $video['title']) . ' - ' . setting('site_name', 'ViralLinx');
$page_description = $video['meta_description'] ?: mb_substr(strip_tags($video['description'] ?: $video['title']), 0, 160);
$page_canonical = $current_url;
$og_image = $video['thumbnail_url'];
$og_type = 'video.other';

// VideoObject structured data (Google rich results)
$json_ld = [
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
    'name' => $video['title'],
    'description' => $page_description,
    'thumbnailUrl' => $video['thumbnail_url'],
    'uploadDate' => date('c', strtotime($video['created_at'])),
    'url' => $current_url,
    'keywords' => implode(', ', array_merge(array_column($video_cats, 'name'), $video_tags)),
    'interactionStatistic' => [
        '@type' => 'InteractionCounter',
        'interactionType' => ['@type' => 'WatchAction'],
        'userInteractionCount' => (int)$video['views'],
    ],
];
$extra_head = '<script type="application/ld+json">' . json_encode($json_ld, JSON_UNESCAPED_SLASHES) . '</script>';

require __DIR__ . '/includes/header.php';
?>

<style>
/* 1. Base Reel Aspect Ratio */
.player-frame-container {
    position: relative;
    width: 100%;
    max-width: 450px;
    margin: 0 auto;
    aspect-ratio: 9 / 16;
    background-color: #000;
    overflow: hidden;
    border-radius: 12px;
}

.player-frame-container iframe,
.player-frame-container video,
.player-frame-container embed {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    border: none;
}

/* 2. Custom Reel Fullscreen Mode */
.player-frame-container.fixed-reel-mode {
    position: fixed !important;
    top: 0; 
    left: 0;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    aspect-ratio: auto !important;
    z-index: 999999;
    border-radius: 0;
    background: #000;
}

/* Close button hidden by default, shown in Reel Mode */
.close-reel-btn {
    display: none;
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 9999999;
    background: rgba(0, 0, 0, 0.6);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: bold;
    backdrop-filter: blur(5px);
    cursor: pointer;
}

.player-frame-container.fixed-reel-mode .close-reel-btn {
    display: block;
}
</style>

<div class="watch-container watch-grid">
    <div class="main-column">
        
        <!-- Player Container with Reel Mode -->
        <div class="player-frame-container" id="videoContainer">
            <button class="close-reel-btn" onclick="toggleReelMode()">✕ Close</button>
            <?php echo render_embed_player($video); ?>
        </div>

        <?php echo render_ads('below_player'); ?>

        <div class="video-details">
            <h1 class="video-title"><?php echo e($video['title']); ?></h1>
            <div class="video-meta">
                <span><?php echo number_format($video['views']); ?> views</span>
                <span>&bull;</span>
                <span><?php echo time_ago($video['created_at']); ?></span>
                <?php if (!empty($video_cats)): ?>
                    <span>&bull;</span>
                    <span>
                        <?php foreach ($video_cats as $i => $cat): ?>
                            <a href="<?php echo url('browse?cat=' . $cat['id']); ?>"><?php echo e($cat['name']); ?></a><?php echo $i < count($video_cats) - 1 ? ', ' : ''; ?>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="action-container">
                <button class="pill-btn <?php echo $user_liked ? 'liked' : ''; ?>" id="likeBtn" onclick="likeVideo(<?php echo $id; ?>)">
                    <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <span id="likeCount"><?php echo format_count($video['likes'] ?? 0); ?></span>
                </button>

                <button class="pill-btn" onclick="toggleReelMode()">
                    <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                    Reel Mode
                </button>

                <a class="pill-btn" href="https://api.whatsapp.com/send?text=<?php echo urlencode($video['title'] . ' ' . $current_url); ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </a>

                <button class="pill-btn" onclick="copyLink('<?php echo e($current_url); ?>')">
                    <svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                    Copy Link
                </button>
            </div>

            <?php if (!empty($video_tags) || !empty($video_cats)): ?>
            <div class="video-tags">
                <?php foreach ($video_cats as $cat): ?>
                    <a class="tag-chip" href="<?php echo url('browse?cat=' . $cat['id']); ?>"><?php echo e($cat['name']); ?></a>
                <?php endforeach; ?>
                <?php foreach ($video_tags as $tg): ?>
                    <a class="tag-chip" href="<?php echo url('tag/' . urlencode($tg)); ?>">#<?php echo e($tg); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="description-panel">
                <div class="desc-meta"><?php echo number_format($video['views']); ?> views &bull; <?php echo time_ago($video['created_at']); ?></div>
                <?php echo nl2br(e($video['description'] ?: 'No description provided.')); ?>
            </div>

            <div class="comments-section">
                <h3 class="section-header-sm"><?php echo count($comments); ?> Comments</h3>

                <div class="comment-input-box">
                    <?php if ($logged_in): ?>
                        <form method="POST">
                            <?php echo csrf_field(); ?>
                            <textarea name="comment_text" id="commentText" class="c-textarea" placeholder="Add a comment as <?php echo e($user['username']); ?>..." required></textarea>
                            <div class="c-form-footer">
                                <button type="submit" name="submit_comment" id="submitBtn" class="btn-submit" disabled>Post</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="font-size:14px; color:var(--yt-text-2); text-align:center; padding:10px;">
                            <a href="<?php echo url('login'); ?>" style="color:var(--yt-blue); text-decoration:none; font-weight:500;">Sign in</a> or
                            <a href="<?php echo url('register'); ?>" style="color:var(--yt-blue); text-decoration:none; font-weight:500;">register</a> to join the conversation.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="comment-list">
                    <?php foreach ($comments as $c): ?>
                        <div class="comment-card">
                            <div class="user-avatar"><?php echo e(strtoupper(substr($c['user_name'], 0, 1))); ?></div>
                            <div class="comment-content">
                                <div class="comment-user-row">
                                    <span class="comment-username"><?php echo e($c['user_name']); ?></span>
                                    <span class="comment-time"><?php echo time_ago($c['created_at']); ?></span>
                                </div>
                                <div class="comment-text"><?php echo nl2br(e($c['comment_text'])); ?></div>
                                <button class="comment-like-btn" onclick="likeComment(<?php echo $c['id']; ?>)">
                                    <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    <span id="commentLikes-<?php echo $c['id']; ?>"><?php echo (int)$c['likes']; ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <aside class="sidebar-recommendations">
        <?php echo render_ads('sidebar'); ?>
        <h3 class="section-header-sm" style="font-size:18px;font-weight:700;margin-bottom:16px;">Up Next</h3>
        <div class="rec-grid">
            <?php foreach ($recommended as $r): ?>
                <a href="<?php echo video_url($r); ?>" class="rec-card">
                    <div class="rec-thumb"><img src="<?php echo e($r['thumbnail_url']); ?>" alt="<?php echo e($r['title']); ?>" loading="lazy"></div>
                    <div class="rec-info">
                        <div class="rec-title"><?php echo e($r['title']); ?></div>
                        <div class="rec-meta"><?php echo format_count($r['views']); ?> views &bull; <?php echo time_ago($r['created_at']); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<!-- Reel Mode Toggle Logic -->
<script>
function toggleReelMode() {
    const container = document.getElementById('videoContainer');
    container.classList.toggle('fixed-reel-mode');
    
    try {
        if (container.classList.contains('fixed-reel-mode')) {
            screen.orientation.lock('portrait').catch(function() {});
        } else {
            screen.orientation.unlock();
        }
    } catch(e) {}
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
