<?php
require_once __DIR__ . '/_admin.php';

$msg = '';

if (isset($_POST['ajax_action'])) {
    if (!csrf_check()) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid token']); exit; }
    header('Content-Type: application/json');
    $response = ['success' => false];
    if ($_POST['ajax_action'] === 'delete_category' && isset($_POST['id'])) {
        $cid = intval($_POST['id']);
        $pdo->prepare("UPDATE videos SET category_id = NULL WHERE category_id = ?")->execute([$cid]);
        try { $pdo->prepare("DELETE FROM video_categories WHERE category_id = ?")->execute([$cid]); } catch (Exception $e) {}
        $response['success'] = $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$cid]);
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, slugify($name)]);
        $msg = 'Category added!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_category'])) {
    $name = trim($_POST['name']);
    $cid = intval($_POST['cat_id']);
    if ($name !== '' && $cid) {
        $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?")->execute([$name, slugify($name), $cid]);
        $msg = 'Category renamed!';
    }
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM videos v WHERE v.category_id = c.id) AS video_count FROM categories c ORDER BY c.name ASC")->fetchAll();

admin_header('Categories', 'categories');
?>

<?php if ($msg): ?><div class="alert alert-info"><?php echo e($msg); ?></div><?php endif; ?>

<div class="layout-grid">
    <div class="card">
        <div class="card-header"><div class="card-title">All Categories</div></div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Name</th><th>Slug</th><th>Videos</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td>
                            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                                <input type="hidden" name="rename_category" value="1">
                                <input type="hidden" name="cat_id" value="<?php echo $c['id']; ?>">
                                <input type="text" name="name" value="<?php echo e($c['name']); ?>" style="max-width:200px;">
                                <button type="submit" class="btn btn-secondary btn-sm">Save</button>
                            </form>
                        </td>
                        <td style="color:var(--text-muted);"><?php echo e($c['slug']); ?></td>
                        <td><?php echo number_format($c['video_count']); ?></td>
                        <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow('delete_category', <?php echo $c['id']; ?>, 'category')">Delete</button></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" style="color:var(--text-muted);">No categories yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">➕ Add Category</div></div>
        <form method="POST">
            <input type="hidden" name="add_category" value="1">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" placeholder="e.g. AI Movies" required>
            </div>
            <div style="margin-top:16px;"><button type="submit" class="btn btn-primary">Add Category</button></div>
        </form>
    </div>
</div>

<?php admin_footer(); ?>
