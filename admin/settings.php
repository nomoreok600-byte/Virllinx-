<?php
require_once __DIR__ . '/_admin.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keys = ['site_name', 'site_tagline', 'seo_home_title', 'seo_home_description', 'seo_keywords', 'seo_default_image', 'google_site_verification', 'comment_moderation'];
    foreach ($keys as $key) {
        save_setting($key, trim($_POST[$key] ?? ''));
    }
    $msg = 'Settings saved!';
}

admin_header('Site & SEO Settings', 'settings');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card">
    <form method="POST" action="/admin/settings">
        <input type="hidden" name="save_settings" value="1">
        <div class="card-header"><div class="card-title">🌐 General</div></div>
        <div class="form-grid">
            <div class="form-group">
                <label>Site Name</label>
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
