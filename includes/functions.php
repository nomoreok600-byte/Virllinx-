<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Fallback when the mbstring extension is not installed
if (!function_exists('mb_substr')) {
    function mb_substr($str, $start, $length = null) {
        return $length === null ? substr($str, $start) : substr($str, $start, $length);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($str) { return strtolower((string)$str); }
}

// ---------- Auth helpers ----------
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
function current_user() {
    global $pdo;
    if (!is_logged_in()) return null;
    static $user = false;
    if ($user === false) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}
function require_admin() {
    if (!is_admin()) {
        header('Location: ' . url('login') . '?redirect=admin');
        exit;
    }
}

// ---------- CSRF ----------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check() {
    return isset($_POST['csrf']) && hash_equals(csrf_token(), $_POST['csrf']);
}

// ---------- Settings ----------
function get_settings() {
    global $pdo;
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {}
    }
    return $settings;
}
function setting($key, $default = '') {
    $s = get_settings();
    return isset($s[$key]) && $s[$key] !== '' ? $s[$key] : $default;
}
function save_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// ---------- Ads ----------
function get_ads($placement) {
    global $pdo;
    static $ads = null;
    if ($ads === null) {
        $ads = [];
        try {
            foreach ($pdo->query("SELECT * FROM ads WHERE is_active = 1") as $ad) {
                $ads[$ad['placement']][] = $ad;
            }
        } catch (Exception $e) {}
    }
    return $ads[$placement] ?? [];
}
function render_ads($placement, $wrap = true) {
    $out = '';
    foreach (get_ads($placement) as $ad) {
        $out .= $wrap ? '<div class="ad-slot ad-' . $placement . '">' . $ad['ad_code'] . '</div>' : $ad['ad_code'];
    }
    return $out;
}

// ---------- URLs & SEO ----------
function url($path = '') {
    return '/' . ltrim($path, '/');
}
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    if (function_exists('iconv')) {
        $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        if ($converted !== false) $text = $converted;
    }
    $text = strtolower($text);
    $text = preg_replace('~[^-a-z0-9]+~', '', $text);
    return $text !== '' ? $text : 'video';
}
function video_url($video) {
    $slug = !empty($video['slug']) ? $video['slug'] : slugify($video['title']);
    return url('watch/' . $video['id'] . '/' . $slug);
}
function canonical_url($path) {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

// ---------- Formatting ----------
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}
function format_count($n) {
    $n = (int)$n;
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000) return round($n / 1000, 1) . 'K';
    return number_format($n);
}

// ---------- Tags & Multi-Categories ----------
function get_video_tags($video) {
    if (empty($video['tags'])) return [];
    $tags = array_filter(array_map('trim', explode(',', $video['tags'])));
    // Case-insensitive dedupe, keeping the first-seen casing
    $seen = [];
    $out = [];
    foreach ($tags as $t) {
        $key = mb_strtolower($t);
        if ($t !== '' && !isset($seen[$key])) {
            $seen[$key] = true;
            $out[] = $t;
        }
    }
    return array_values(array_slice($out, 0, 15));
}
function normalize_tags_input($raw) {
    $raw = strip_tags((string)$raw);
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    $parts = array_map(function($t) { return mb_substr(preg_replace('/[<>"\';]/', '', $t), 0, 50); }, $parts);
    $parts = array_filter($parts);
    $parts = array_values(array_unique($parts));
    return implode(', ', array_slice($parts, 0, 15));
}
function get_video_categories($video_id) {
    global $pdo;
    static $cache = [];
    if (isset($cache[$video_id])) return $cache[$video_id];
    try {
        $stmt = $pdo->prepare("SELECT c.id, c.name, c.slug FROM video_categories vc JOIN categories c ON c.id = vc.category_id WHERE vc.video_id = ? ORDER BY c.name ASC");
        $stmt->execute([$video_id]);
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }
    $cache[$video_id] = $rows;
    return $rows;
}
function sync_video_categories($video_id, array $category_ids) {
    global $pdo;
    $video_id = intval($video_id);
    $category_ids = array_values(array_unique(array_filter(array_map('intval', $category_ids))));
    try {
        $pdo->prepare("DELETE FROM video_categories WHERE video_id = ?")->execute([$video_id]);
        if ($category_ids) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO video_categories (video_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cid) $stmt->execute([$video_id, $cid]);
        }
        // Keep legacy column in sync (first chosen category) so old queries keep working
        $primary = $category_ids[0] ?? null;
        $pdo->prepare("UPDATE videos SET category_id = ? WHERE id = ?")->execute([$primary, $video_id]);
    } catch (Exception $e) {}
}
function get_all_tags($limit = 30) {
    global $pdo;
    static $tags = null;
    if ($tags !== null) return array_slice($tags, 0, $limit);
    $tags = [];
    try {
        $rows = $pdo->query("SELECT tags FROM videos WHERE status = 'published' AND tags IS NOT NULL AND tags != ''")->fetchAll();
        foreach ($rows as $row) {
            foreach (get_video_tags($row) as $t) {
                $key = mb_strtolower($t);
                if (!isset($tags[$key])) $tags[$key] = ['name' => $t, 'count' => 0];
                $tags[$key]['count']++;
            }
        }
        usort($tags, function($a, $b) { return $b['count'] - $a['count']; });
        $tags = array_values($tags);
    } catch (Exception $e) {}
    return array_slice($tags, 0, $limit);
}

// ---------- Player ----------
function render_embed_player($video_data) {
    $embed = $video_data['embed_code'] ?: ($video_data['video_url'] ?? '');
    if (stripos($embed, '<iframe') !== false) return $embed;
    if (!empty($embed)) return '<iframe src="' . e($embed) . '" frameborder="0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" loading="lazy"></iframe>';
    return '<div style="color:#71717a; display:flex; align-items:center; justify-content:center; height:100%; font-size:14px;">Stream unavailable</div>';
}

// ---------- Pagination ----------
function paginate($total, $per_page, $current_page, $base_url) {
    $pages = max(1, ceil($total / $per_page));
    $current_page = max(1, min($current_page, $pages));
    if ($pages <= 1) return '';
    $sep = strpos($base_url, '?') !== false ? '&' : '?';
    $html = '<nav class="pagination">';
    if ($current_page > 1) $html .= '<a href="' . e($base_url . $sep . 'page=' . ($current_page - 1)) . '">&larr; Prev</a>';
    $start = max(1, $current_page - 2);
    $end = min($pages, $current_page + 2);
    for ($i = $start; $i <= $end; $i++) {
        $cls = $i === $current_page ? ' class="active"' : '';
        $html .= '<a' . $cls . ' href="' . e($base_url . $sep . 'page=' . $i) . '">' . $i . '</a>';
    }
    if ($current_page < $pages) $html .= '<a href="' . e($base_url . $sep . 'page=' . ($current_page + 1)) . '">Next &rarr;</a>';
    $html .= '</nav>';
    return $html;
}
