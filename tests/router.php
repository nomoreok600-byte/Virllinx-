<?php
// Local test router for php -S — routes clean URLs to the real site files.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_USER_NOTICE);
define('DB_HOST', 'localhost');
define('DB_NAME', 'vl_test');
define('DB_USER', 'vl_test');
define('DB_PASS', 'vl_test_pass');
define('SITE_URL', 'http://127.0.0.1:8099');
define('DEFAULT_ADMIN_EMAIL', 'a@b.c');
define('DEFAULT_ADMIN_PASS', 'x');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static assets served directly
if (preg_match('#^/assets/#', $uri)) return false;

// Clean URL routes (mirror .htaccess)
if (preg_match('#^/watch/(\d+)(?:/([a-z0-9-]*))?/?$#', $uri, $m)) { $_GET['id'] = $m[1]; $_GET['slug'] = $m[2] ?? ''; require __DIR__ . '/../watch.php'; exit; }
if (preg_match('#^/page/([a-z0-9-]+)/?$#', $uri, $m)) { $_GET['slug'] = $m[1]; require __DIR__ . '/../page.php'; exit; }
if (preg_match('#^/tag/([^/]+)/?$#', $uri, $m)) { $_GET['tag'] = urldecode($m[1]); require __DIR__ . '/../tag.php'; exit; }
if (preg_match('#^/tags/?$#', $uri)) { require __DIR__ . '/../tags.php'; exit; }
if (preg_match('#^/sitemap\.xml$#', $uri)) { require __DIR__ . '/../sitemap.php'; exit; }
if (preg_match('#^/admin/?$#', $uri)) { require __DIR__ . '/../admin/index.php'; exit; }
if (preg_match('#^/admin/([a-z-]+)/?$#', $uri, $m)) { require __DIR__ . '/../admin/' . $m[1] . '.php'; exit; }
if (preg_match('#^/([a-z0-9-]+)/?$#', $uri, $m)) {
    $file = __DIR__ . '/../' . $m[1] . '.php';
    if (file_exists($file)) { require $file; exit; }
}
if ($uri === '/' || $uri === '') { require __DIR__ . '/../index.php'; exit; }

http_response_code(404);
require __DIR__ . '/../404.php';
exit;
