<?php
require_once __DIR__ . '/_admin.php';

$msg = '';

if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    if ($_POST['ajax_action'] === 'delete_page' && isset($_POST['id'])) {
        $response['success'] = $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([intval($_POST['id'])]);
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']) !== '' ? slugify($_POST['slug']) : slugify($title);
    $content = $_POST['content'];
    $page_id = intval($_POST['page_id'] ?? 0);
    if ($title !== '') {
        if ($page_id) {
            $pdo->prepare("UPDATE pages SET title=?, slug=?, content=? WHERE id=?")->execute([$title, $slug, $content, $page_id]);
            $msg = 'Page updated!';
        } else {
            $pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (?,?,?)")->execute([$title, $slug, $content]);
            $msg = 'Page created!';
        }
    }
}

$edit_page = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_page = $stmt->fetch();
}

$pages = $pdo->query("SELECT * FROM pages ORDER BY id DESC")->fetchAll();

admin_header('Static Pages', 'pages');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title"><?php echo $edit_page ? '✏️ Edit Page' : '➕ Create Page (About, Contact, Privacy Policy, DMCA, Terms...)'; ?></div>
        <?php if ($edit_page): ?><a href="/admin/pages" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="POST" action="/admin/pages">
        <input type="hidden" name="save_page" value="1">
        <?php if ($edit_page): ?><input type="hidden" name="page_id" value="<?php echo $edit_page['id']; ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Page Title</label>
                <input type="text" name="title" value="<?php echo e($edit_page['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>URL Slug (auto if empty) — page shows at /page/your-slug</label>
                <input type="text" name="slug" value="<?php echo e($edit_page['slug'] ?? ''); ?>">
            </div>
            <div class="form-group full">
                <label>Content (HTML allowed)</label>
                <textarea name="content" rows="10"><?php echo e($edit_page['content'] ?? ''); ?></textarea>
            </div>
        </div>
        <div style="margin-top:20px;"><button type="submit" class="btn btn-primary"><?php echo $edit_page ? 'Update Page' : 'Create Page'; ?></button></div>
    </form>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Pages</div></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Title</th><th>URL</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo e($p['title']); ?></td>
                    <td><a href="/page/<?php echo e($p['slug']); ?>" target="_blank" style="color:#60a5fa;text-decoration:none;">/page/<?php echo e($p['slug']); ?></a></td>
                    <td style="color:var(--text-muted);"><?php echo time_ago($p['created_at']); ?></td>
                    <td style="white-space:nowrap;">
                        <a href="/admin/pages?edit=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm" onclick="deleteRow('delete_page', <?php echo $p['id']; ?>, 'page')">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($pages)): ?>
                <tr><td colspan="4" style="color:var(--text-muted);">No pages yet. Create About, Privacy Policy, DMCA, Contact pages here — they'll appear in the footer automatically.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
