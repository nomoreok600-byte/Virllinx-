<?php
require_once __DIR__ . '/_admin.php';

// ---------- AJAX ----------
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    $vid = intval($_POST['id'] ?? 0);
    if ($_POST['ajax_action'] === 'delete_video' && $vid) {
        $pdo->prepare("DELETE FROM comments WHERE video_id = ?")->execute([$vid]);
        $pdo->prepare("DELETE FROM video_likes WHERE video_id = ?")->execute([$vid]);
        try { $pdo->prepare("DELETE FROM video_categories WHERE video_id = ?")->execute([$vid]); } catch (Exception $e) {}
        $response['success'] = $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$vid]);
    }
    if ($_POST['ajax_action'] === 'toggle_status' && $vid) {
        $response['success'] = $pdo->prepare("UPDATE videos SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$vid]);
    }
    if ($_POST['ajax_action'] === 'toggle_featured' && $vid) {
        $response['success'] = $pdo->prepare("UPDATE videos SET is_featured = 1 - is_featured WHERE id = ?")->execute([$vid]);
    }
    echo json_encode($response);
    exit;
}

$msg = '';

// ---------- Save video ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_video'])) {
    $title = trim($_POST['title']);
    $embed_code = trim($_POST['embed_code']);
    $thumbnail_url = trim($_POST['thumbnail_url']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'published';
    $video_id = !empty($_POST['video_id']) ? intval($_POST['video_id']) : null;
    $slug = trim($_POST['slug']) !== '' ? slugify($_POST['slug']) : slugify($title);
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $tags = normalize_tags_input($_POST['tags'] ?? '');
    $selected_categories = array_map('intval', $_POST['categories'] ?? []);
    $category_id = $selected_categories[0] ?? null;

    if ($title !== '' && $embed_code !== '') {
        if ($video_id) {
            $stmt = $pdo->prepare("UPDATE videos SET title=?, slug=?, embed_code=?, video_url=?, thumbnail_url=?, description=?, status=?, category_id=?, meta_title=?, meta_description=?, is_featured=?, tags=? WHERE id=?");
            $stmt->execute([$title, $slug, $embed_code, $embed_code, $thumbnail_url, $description, $status, $category_id, $meta_title, $meta_description, $is_featured, $tags, $video_id]);
            $msg = 'Video updated!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO videos (title, slug, embed_code, video_url, thumbnail_url, description, status, category_id, meta_title, meta_description, is_featured, tags) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $slug, $embed_code, $embed_code, $thumbnail_url, $description, $status, $category_id, $meta_title, $meta_description, $is_featured, $tags]);
            $video_id = intval($pdo->lastInsertId());
            $msg = 'New video published!';
        }
        sync_video_categories($video_id, $selected_categories);
    } else {
        $msg = 'Title and Embed Code are required.';
    }
}

// ---------- Bulk actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (!empty($ids)) {
        $in = implode(',', $ids);
        switch ($_POST['bulk_action']) {
            case 'delete':
                $pdo->exec("DELETE FROM comments WHERE video_id IN ($in)");
                $pdo->exec("DELETE FROM video_likes WHERE video_id IN ($in)");
                try { $pdo->exec("DELETE FROM video_categories WHERE video_id IN ($in)"); } catch (Exception $e) {}
                $pdo->exec("DELETE FROM videos WHERE id IN ($in)");
                $msg = 'Selected videos deleted.';
                break;
            case 'publish':
                $pdo->exec("UPDATE videos SET status='published' WHERE id IN ($in)");
                $msg = 'Selected videos published.';
                break;
            case 'draft':
                $pdo->exec("UPDATE videos SET status='draft' WHERE id IN ($in)");
                $msg = 'Selected videos moved to draft.';
                break;
        }
    }
}

// ---------- Edit mode ----------
$edit_data = null;
$edit_cat_ids = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_data = $stmt->fetch();
    if ($edit_data) {
        $edit_cat_ids = array_map('intval', array_column(get_video_categories($edit_data['id']), 'id'));
        if (!$edit_cat_ids && !empty($edit_data['category_id'])) $edit_cat_ids = [(int)$edit_data['category_id']];
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$videos = $pdo->query("SELECT v.*, c.name AS category_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id ORDER BY v.id DESC")->fetchAll();

admin_header('Videos', 'videos');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?php echo $edit_data ? '✏️ Edit Video' : '➕ Add New Video'; ?></div>
        <?php if ($edit_data): ?><a href="/admin/videos" class="btn btn-secondary btn-sm">Cancel Editing</a><?php endif; ?>
    </div>
    <form method="POST" action="/admin/videos">
        <input type="hidden" name="save_video" value="1">
        <?php if ($edit_data): ?><input type="hidden" name="video_id" value="<?php echo $edit_data['id']; ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" value="<?php echo e($edit_data['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>URL Slug (auto-generated if empty)</label>
                <input type="text" name="slug" value="<?php echo e($edit_data['slug'] ?? ''); ?>" placeholder="my-video-title">
            </div>
            <div class="form-group">
                <label>Categories (hold Ctrl / Cmd to select multiple)</label>
                <select name="categories[]" multiple size="5" class="multi-select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo in_array((int)$cat['id'], $edit_cat_ids, true) ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">Selected videos appear in every chosen category. No categories yet? <a href="/admin/categories" style="color:#60a5fa;">Create some</a>.</div>
            </div>
            <div class="form-group">
                <label>Tags (comma separated, max 15)</label>
                <input type="text" name="tags" value="<?php echo e($edit_data['tags'] ?? ''); ?>" placeholder="ai movie, sci-fi, short film">
                <div class="hint">Tags appear on the video page and on <a href="/tags" target="_blank" style="color:#60a5fa;">/tags</a>. Viewers can browse each tag.</div>
            </div>
            <div class="form-group full">
                <label>Thumbnail URL</label>
                <input type="url" name="thumbnail_url" value="<?php echo e($edit_data['thumbnail_url'] ?? ''); ?>" placeholder="https://domain.com/image.jpg">
            </div>
            <div class="form-group full">
                <label>Embed Code / Player URL *</label>
                <textarea name="embed_code" rows="3" placeholder='Paste raw <iframe...> tag or direct embed link...' required><?php echo e($edit_data['embed_code'] ?? ''); ?></textarea>
            </div>
            <div class="form-group full">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo e($edit_data['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>SEO Meta Title (optional)</label>
                <input type="text" name="meta_title" value="<?php echo e($edit_data['meta_title'] ?? ''); ?>" maxlength="255">
            </div>
            <div class="form-group">
                <label>SEO Meta Description (optional)</label>
                <input type="text" name="meta_description" value="<?php echo e($edit_data['meta_description'] ?? ''); ?>" maxlength="500">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="published" <?php echo (($edit_data['status'] ?? '') === 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo (($edit_data['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>
            <div class="form-group">
                <label>Featured (shows in Hero banner)</label>
                <label class="checkbox-row">
                    <input type="checkbox" name="is_featured" <?php echo !empty($edit_data['is_featured']) ? 'checked' : ''; ?>> Mark as featured
                </label>
            </div>
        </div>
        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary"><?php echo $edit_data ? 'Update Video' : 'Publish Video'; ?></button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Videos (<?php echo count($videos); ?>)</div></div>
    <form method="POST" action="/admin/videos">
        <div class="table-toolbar">
            <input type="text" id="tableFilter" class="search-input" placeholder="Filter videos...">
            <div class="bulk-bar">
                <select name="bulk_action" required>
                    <option value="">Bulk action...</option>
                    <option value="publish">Publish</option>
                    <option value="draft">Move to Draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Apply bulk action to selected videos?')">Apply</button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="cb"></th>
                        <th>Thumb</th><th>Title</th><th>Categories</th><th>Tags</th><th>Views</th><th>Likes</th><th>Status</th><th>Featured</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($videos as $v): $v_cat_ids = array_map('intval', array_column(get_video_categories($v['id']), 'id')); ?>
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="<?php echo $v['id']; ?>" class="cb"></td>
                        <td><img class="thumb-img" src="<?php echo e($v['thumbnail_url']); ?>" alt="" loading="lazy"></td>
                        <td>
                            <a href="<?php echo video_url($v); ?>" target="_blank" style="color:#fff;text-decoration:none;font-weight:600;"><?php echo e(mb_substr($v['title'], 0, 45)); ?></a>
                            <div style="font-size:12px;color:var(--text-muted);">/watch/<?php echo $v['id']; ?>/<?php echo e($v['slug'] ?: slugify($v['title'])); ?></div>
                        </td>
                        <td style="color:var(--text-muted);font-size:12.5px;">
                            <?php
                            if ($v_cat_ids) {
                                $names = [];
                                foreach ($categories as $cat) if (in_array((int)$cat['id'], $v_cat_ids, true)) $names[] = $cat['name'];
                                echo e(implode(', ', $names));
                            } else echo '—';
                            ?>
                        </td>
                        <td style="color:var(--text-muted);font-size:12.5px;"><?php echo e($v['tags'] ?: '—'); ?></td>
                        <td><?php echo number_format($v['views']); ?></td>
                        <td><?php echo number_format($v['likes']); ?></td>
                        <td>
                            <button type="button" class="badge badge-<?php echo $v['status']; ?>" style="border:none;cursor:pointer;" onclick="toggleField('toggle_status', <?php echo $v['id']; ?>)" title="Click to toggle"><?php echo $v['status']; ?></button>
                        </td>
                        <td>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleField('toggle_featured', <?php echo $v['id']; ?>)"><?php echo $v['is_featured'] ? '⭐' : '☆'; ?></button>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="/admin/videos?edit=<?php echo $v['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteRow('delete_video', <?php echo $v['id']; ?>, 'video')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<?php admin_footer(); ?>
