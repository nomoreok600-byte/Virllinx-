<?php
/**
 * Render a single page via CLI with mocked superglobals.
 * Usage: php tests/render_page.php <route> [querystring]
 * Exit 0 = page rendered cleanly, 1 = error detected.
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Convert every warning/notice into a fatal so nothing slips through
set_error_handler(function ($sev, $msg, $file, $line) {
    throw new ErrorException($msg, 0, $sev, $file, $line);
});

// Test overrides consumed by includes/config.php (which uses getenv with cPanel defaults)
putenv('VL_DB_HOST=localhost');
putenv('VL_DB_NAME=vl_test');
putenv('VL_DB_USER=vl_test');
putenv('VL_DB_PASS=vl_test_pass');
putenv('VL_SITE_URL=http://127.0.0.1:8099');

$route = $argv[1] ?? '/';
parse_str($argv[2] ?? '', $_GET);
$_REQUEST = $_GET;
$_SERVER = array_merge($_SERVER, [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => $route . (isset($argv[2]) ? '?' . $argv[2] : ''),
    'HTTP_HOST' => '127.0.0.1:8099',
]);
// Start the session here so the app's own session_start() (in functions.php) is skipped
// and our simulated session values survive.
session_start();
$_SESSION = [];

$pdo = new PDO('mysql:host=localhost;dbname=vl_test;charset=utf8mb4', 'vl_test', 'vl_test_pass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Simulate an authenticated admin session when VL_TEST_ADMIN=1
if (getenv('VL_TEST_ADMIN') === '1') {
    $aid = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetchColumn();
    if ($aid) { $_SESSION['user_id'] = (int)$aid; $_SESSION['user_name'] = 'admin'; $_SESSION['user_role'] = 'admin'; }
}

$root = dirname(__DIR__);

// Mirror .htaccess clean-URL routing
if (preg_match('#^/watch/(\d+)(?:/([a-z0-9-]*))?$#', $route, $m)) { $_GET['id'] = $m[1]; $_GET['slug'] = $m[2] ?? ''; $file = "$root/watch.php"; }
elseif (preg_match('#^/page/([a-z0-9-]+)$#', $route, $m)) { $_GET['slug'] = $m[1]; $file = "$root/page.php"; }
elseif (preg_match('#^/tag/([^/]+)$#', $route, $m)) { $_GET['tag'] = urldecode($m[1]); $file = "$root/tag.php"; }
elseif ($route === '/tags') $file = "$root/tags.php";
elseif ($route === '/sitemap.xml') $file = "$root/sitemap.php";
elseif ($route === '/admin') $file = "$root/admin/index.php";
elseif (preg_match('#^/admin/([a-z-]+)$#', $route, $m)) $file = "$root/admin/{$m[1]}.php";
elseif (preg_match('#^/([a-z0-9-]+)$#', $route, $m) && file_exists("$root/{$m[1]}.php")) $file = "$root/{$m[1]}.php";
elseif ($route === '/') $file = "$root/index.php";
else { fwrite(STDERR, "no route match for $route\n"); exit(1); }

ob_start();
try {
    require $file;
    $html = ob_get_clean();
} catch (Throwable $e) {
    ob_end_clean();
    // A 301 Location header is the expected behavior for redirects, not an error
    $is_redirect = false;
    foreach (headers_list() as $h) if (stripos($h, 'Location:') === 0) $is_redirect = true;
    if ($is_redirect) { echo "REDIRECT-OK\n"; exit(0); }
    fwrite(STDERR, "ERROR rendering $route: " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine() . "\n");
    exit(1);
}

$len = strlen($html);
// Optionally dump the rendered HTML for content assertions
if (getenv('VL_TEST_DUMP')) file_put_contents(getenv('VL_TEST_DUMP'), $html);
echo "OK ($len bytes)\n";
exit(0);
