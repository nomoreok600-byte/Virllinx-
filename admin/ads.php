<?php
require_once __DIR__ . '/_admin.php';

$msg = '';

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    $aid = intval($_POST['id'] ?? 0);
    if ($_POST['ajax_action'] === 'delete_ad' && $aid) {
        $response['success'] = $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$aid]);
    }
    if ($_POST['ajax_action'] === 'toggle_ad' && $aid) {
        $response['success'] = $pdo->prepare("UPDATE ads SET is_active = 1 - is_active WHERE id = ?")->execute([$aid]);
    }
    echo json_encode($response);
    exit;
}

// Save ad unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ad'])) {
    $name = trim($_POST['name']);
    $placement = $_POST['placement'];
    $ad_code = $_POST['ad_code'];
    $ad_id = intval($_POST['ad_id'] ?? 0);
    $valid = ['header','footer','below_player','between_grid','sidebar','popup','popunder','custom'];
    if ($name !== '' && in_array($placement, $valid)) {
        if ($ad_id) {
            $pdo->prepare("UPDATE ads SET name=?, placement=?, ad_code=? WHERE id=?")->execute([$name, $placement, $ad_code, $ad_id]);
            $msg = 'Ad unit updated!';
        } else {
            $pdo->prepare("INSERT INTO ads (name, placement, ad_code) VALUES (?,?,?)")->execute([$name, $placement, $ad_code]);
            $msg = 'Ad unit created!';
        }
    }
}

// Save global ad codes (header/footer/analytics/popunder)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ad_settings'])) {
    save_setting('header_code', $_POST['header_code'] ?? '');
    save_setting('footer_code', $_POST['footer_code'] ?? '');
    save_setting('google_analytics_code', $_POST['google_analytics_code'] ?? '');
    save_setting('popunder_url', trim($_POST['popunder_url'] ?? ''));
    save_setting('popunder_frequency_hours', intval($_POST['popunder_frequency_hours'] ?? 12));
    save_setting('popup_frequency_hours', intval($_POST['popup_frequency_hours'] ?? 6));
    save_setting('popup_delay_seconds', intval($_POST['popup_delay_seconds'] ?? 5));
    $msg = 'Global ad codes saved!';
}

$edit_ad = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_ad = $stmt->fetch();
}

$ads = $pdo->query("SELECT * FROM ads ORDER BY placement, id DESC")->fetchAll();
$placement_labels = [
    'header' => 'Header (inside <head>)',
    'footer' => 'Footer (bottom of page)',
    'below_player' => 'Below Video Player',
    'between_grid' => 'Above Video Grid (home/listing)',
    'sidebar' => 'Watch Page Sidebar',
    'popup' => 'Popup (modal overlay)',
    'popunder' => 'Popunder (raw script)',
    'custom' => 'Custom (manual placement)',
];

admin_header('Ads Manager', 'ads');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">🌍 Global Codes (Google AdSense, Analytics, Header/Footer scripts, Popunder)</div></div>
    <form method="POST" action="/admin/ads">
        <input type="hidden" name="save_ad_settings" value="1">
        <div class="form-grid">
            <div class="form-group full">
                <label>Header Code — pasted into &lt;head&gt; on every page (AdSense auto ads, verification tags, custom scripts)</label>
                <textarea name="header_code" rows="4" placeholder='<script async src="https://pagead2.googlesyndication.com/..."></script>'><?php echo e(setting('header_code')); ?></textarea>
            </div>
            <div class="form-group full">
                <label>Footer Code — pasted before &lt;/body&gt; on every page</label>
                <textarea name="footer_code" rows="4"><?php echo e(setting('footer_code')); ?></textarea>
            </div>
            <div class="form-group full">
                <label>Google Analytics / Tag Manager Code</label>
                <textarea name="google_analytics_code" rows="4" placeholder='<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXX"></script>...'><?php echo e(setting('google_analytics_code')); ?></textarea>
            </div>
            <div class="form-group">
                <label>Popunder URL (opens in new tab on first click)</label>
                <input type="url" name="popunder_url" value="<?php echo e(setting('popunder_url')); ?>" placeholder="https://advertiser-link.com/offer">
            </div>
            <div class="form-group">
                <label>Popunder frequency (hours between shows per visitor)</label>
                <input type="number" name="popunder_frequency_hours" value="<?php echo e(setting('popunder_frequency_hours', 12)); ?>" min="0">
            </div>
            <div class="form-group">
                <label>Popup frequency (hours between shows per visitor)</label>
                <input type="number" name="popup_frequency_hours" value="<?php echo e(setting('popup_frequency_hours', 6)); ?>" min="0">
            </div>
            <div class="form-group">
                <label>Popup delay (seconds after page load)</label>
                <input type="number" name="popup_delay_seconds" value="<?php echo e(setting('popup_delay_seconds', 5)); ?>" min="0">
            </div>
        </div>
        <div style="margin-top:20px;"><button type="submit" class="btn btn-primary">Save Global Codes</button></div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?php echo $edit_ad ? '✏️ Edit Ad Unit' : '➕ Add Banner / Ad Unit'; ?></div>
        <?php if ($edit_ad): ?><a href="/admin/ads" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="POST" action="/admin/ads">
        <input type="hidden" name="save_ad" value="1">
        <?php if ($edit_ad): ?><input type="hidden" name="ad_id" value="<?php echo $edit_ad['id']; ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Ad Name (internal)</label>
                <input type="text" name="name" value="<?php echo e($edit_ad['name'] ?? ''); ?>" placeholder="e.g. Adsterra 728x90 banner" required>
            </div>
            <div class="form-group">
                <label>Placement</label>
                <select name="placement">
                    <?php foreach ($placement_labels as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (($edit_ad['placement'] ?? '') === $key) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Ad Code (HTML / JS — banner image tag, AdSense unit, Adsterra script, etc.)</label>
                <textarea name="ad_code" rows="5"><?php echo e($edit_ad['ad_code'] ?? ''); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;"><button type="submit" class="btn btn-primary"><?php echo $edit_ad ? 'Update Ad' : 'Create Ad'; ?></button></div>
    </form>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Ad Units (<?php echo count($ads); ?>)</div></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Name</th><th>Placement</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($ads as $ad): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo e($ad['name']); ?></td>
                    <td style="color:var(--text-muted);"><?php echo e($placement_labels[$ad['placement']] ?? $ad['placement']); ?></td>
                    <td><span class="badge badge-<?php echo $ad['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $ad['is_active'] ? 'active' : 'paused'; ?></span></td>
                    <td style="color:var(--text-muted);"><?php echo time_ago($ad['created_at']); ?></td>
                    <td style="white-space:nowrap;">
                        <button class="btn btn-secondary btn-sm" onclick="toggleField('toggle_ad', <?php echo $ad['id']; ?>)"><?php echo $ad['is_active'] ? 'Pause' : 'Activate'; ?></button>
                        <a href="/admin/ads?edit=<?php echo $ad['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm" onclick="deleteRow('delete_ad', <?php echo $ad['id']; ?>, 'ad unit')">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($ads)): ?>
                <tr><td colspan="5" style="color:var(--text-muted);">No ad units yet. Add banners above — they render automatically in the chosen placement.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
