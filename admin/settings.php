<?php
require_once __DIR__ . '/_admin.php';

$msg = '';
$err = '';

// Handle logo file upload (validated + hardened)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_logo'])) {
    if (!csrf_check()) {
        $err = 'Session expired. Please try again.';
    } elseif (empty($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
        $err = 'Please choose a logo file to upload.';
    } else {
        $f = $_FILES['logo_file'];
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'];
        $info = @getimagesize($f['tmp_name']);
        $mime = $info['mime'] ?? (function_exists('mime_content_type') ? mime_content_type($f['tmp_name']) : '');
        // SVG has no getimagesize dimensions; accept declared svg xml
        if ($mime === '' && strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) === 'svg') {
            $head = (string)file_get_contents($f['tmp_name'], false, null, 0, 512);
            if (stripos($head, '<svg') !== false) $mime = 'image/svg+xml';
        }
        if (!isset($allowed[$mime])) {
            $err = 'Invalid file type. Allowed: PNG, JPG, WEBP, SVG, ICO.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $err = 'Logo must be under 2 MB.';
        } else {
            $ext = $allowed[$mime];
            $dir = dirname(__DIR__) . '/uploads/branding';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $protect = $dir . '/.htaccess';
            if (!file_exists($protect)) {
                @file_put_contents($protect, "Require all granted\nOptions -Indexes\n<FilesMatch \"\\.(php|phtml|phar|php[0-9]|cgi|pl|py|sh)$\">\n    Require all denied\n</FilesMatch>\n<IfModule mod_headers.c>\n    Header set X-Content-Type-Options \"nosniff\"\n</IfModule>\n");
            }
            $name = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = $dir . '/' . $name;
            if (@move_uploaded_file($f['tmp_name'], $dest)) {
                @chmod($dest, 0644);
                $old = setting('site_logo_url', '');
                // Delete previous uploaded logo
                if (strpos($old, '/uploads/branding/') === 0) {
                    $oldpath = dirname(__DIR__) . $old;
                    if (is_file($oldpath)) @unlink($oldpath);
                }
                save_setting('site_logo_url', '/uploads/branding/' . $name);
                save_setting('site_logo_type', 'upload');
                $msg = 'Logo uploaded and applied!';
            } else {
                $err = 'Could not save the uploaded file (check folder permissions).';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!csrf_check()) {
        $err = 'Session expired. Please try again.';
    } else {
        $keys = ['site_name', 'site_tagline', 'seo_home_title', 'seo_home_description', 'seo_keywords', 'seo_default_image', 'google_site_verification', 'comment_moderation'];
        foreach ($keys as $key) {
            save_setting($key, trim($_POST[$key] ?? ''));
        }
        // Logo URL (only http(s))
        $logo_url = trim($_POST['site_logo_url'] ?? '');
        if ($logo_url !== '' && !preg_match('#^https?://#i', $logo_url) && strpos($logo_url, '/uploads/branding/') !== 0) {
            $err = 'Logo URL must start with https:// (or be an uploaded file).';
        } elseif ($logo_url !== '' && preg_match('#^https?://#i', $logo_url) && !filter_var($logo_url, FILTER_VALIDATE_URL)) {
            $err = 'Logo URL is not a valid URL.';
        } else {
            save_setting('site_logo_url', $logo_url);
            save_setting('site_logo_type', $logo_url !== '' ? 'url' : '');
        }
        if ($err === '') $msg = 'Settings saved!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_logo'])) {
    if (csrf_check()) {
        $old = setting('site_logo_url', '');
        if (strpos($old, '/uploads/branding/') === 0) {
            $oldpath = dirname(__DIR__) . $old;
            if (is_file($oldpath)) @unlink($oldpath);
        }
        save_setting('site_logo_url', '');
        save_setting('site_logo_type', '');
        $msg = 'Logo removed — using the default ViralLinx mark.';
    }
}

admin_header('Site & SEO Settings', 'settings');
$current_logo = setting('site_logo_url', '');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?php echo e($err); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">🖼 Logo / Branding</div></div>
    <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;margin-bottom:20px;">
        <div style="background:#121212;border:1px solid var(--card-border);border-radius:12px;padding:18px 26px;display:flex;align-items:center;justify-content:center;min-width:180px;min-height:70px;">
            <?php echo site_logo_html(40); ?>
        </div>
        <div style="font-size:13px;color:var(--text-muted);line-height:1.7;">
            <b style="color:#fff;">Current logo preview</b> (header size scaled up)<br>
            Shown in the site header, sidebar footer and admin sidebar.
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label>Upload logo file (PNG, JPG, WEBP, SVG, ICO — max 2 MB)</label>
            <form method="POST" action="/admin/settings" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="upload_logo" value="1">
                <input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,.svg,.ico,image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" required style="padding:8px;">
                <button type="submit" class="btn btn-primary">Upload & Apply</button>
            </form>
        </div>
        <div class="form-group">
            <form method="POST" action="/admin/settings">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="remove_logo" value="1">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-secondary" onclick="return confirm('Remove the custom logo and restore the default mark?')">Remove Custom Logo</button>
            </form>
        </div>
    </div>

    <form method="POST" action="/admin/settings">
        <input type="hidden" name="save_settings" value="1">
        <?php echo csrf_field(); ?>
        <div class="form-group" style="margin-top:8px;">
            <label>…or paste a logo image URL (https://…)</label>
            <input type="url" name="site_logo_url" value="<?php echo e($current_logo && strpos($current_logo, '/uploads/') !== 0 ? $current_logo : ''); ?>" placeholder="https://yourdomain.com/logo.png">
            <div class="hint">A uploaded file takes priority while it exists; pasting a URL here switches to that image. Empty = default ViralLinx mark.</div>
        </div>

        <div class="card-header" style="margin-top:20px;"><div class="card-title">🌐 General</div></div>
        <div class="form-grid">
            <div class="form-group">
                <label>Site Name (used as logo text next to the mark)</label>
                <input type="text" name="site_name" value="<?php echo e(setting('site_name', 'ViralLinx')); ?>">
            </div>
            <div class="form-group">
                <label>Tagline (shown in footer)</label>
                <input type="text" name="site_tagline" value="<?php echo e(setting('site_tagline')); ?>">
            </div>
            <div class="form-group">
                <label>Comment Moderation</label>
                <select name="comment_moderation">
                    <option value="0" <?php echo setting('comment_moderation', '0') === '0' ? 'selected' : ''; ?>>Auto-approve comments</option>
                    <option value="1" <?php echo setting('comment_moderation') === '1' ? 'selected' : ''; ?>>Hold for approval</option>
                </select>
            </div>
        </div>

        <div class="card-header" style="margin-top:28px;"><div class="card-title">🔎 SEO</div></div>
        <div class="form-grid">
            <div class="form-group full">
                <label>Homepage Meta Title</label>
                <input type="text" name="seo_home_title" value="<?php echo e(setting('seo_home_title')); ?>" placeholder="ViralLinx - Watch AI Movies & Stories" maxlength="70">
            </div>
            <div class="form-group full">
                <label>Homepage Meta Description (max ~160 chars)</label>
                <textarea name="seo_home_description" rows="2" maxlength="300"><?php echo e(setting('seo_home_description')); ?></textarea>
            </div>
            <div class="form-group full">
                <label>Meta Keywords (comma separated)</label>
                <input type="text" name="seo_keywords" value="<?php echo e(setting('seo_keywords')); ?>" placeholder="ai movies, viral videos, watch online">
            </div>
            <div class="form-group">
                <label>Default Social Share Image URL (og:image)</label>
                <input type="url" name="seo_default_image" value="<?php echo e(setting('seo_default_image')); ?>">
            </div>
            <div class="form-group">
                <label>Google Search Console Verification Code (content value only)</label>
                <input type="text" name="google_site_verification" value="<?php echo e(setting('google_site_verification')); ?>">
            </div>
        </div>
        <div class="hint" style="margin-top:12px;">Sitemap is auto-generated at <a href="/sitemap.xml" target="_blank" style="color:#60a5fa;">/sitemap.xml</a> — submit it in Google Search Console. Clean URLs are handled by .htaccess (no .php shown).</div>
        <div style="margin-top:20px;"><button type="submit" class="btn btn-primary">Save Settings</button></div>
    </form>
</div>

<?php admin_footer(); ?>
