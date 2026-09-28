<?php
require_once __DIR__ . '/_admin.php';

if (isset($_POST['ajax_action'])) {
    if (!csrf_check()) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Invalid token']); exit; }
    header('Content-Type: application/json');
    $response = ['success' => false];
    $cid = intval($_POST['id'] ?? 0);
    if ($_POST['ajax_action'] === 'delete_comment' && $cid) {
        $response['success'] = $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$cid]);
    }
    if ($_POST['ajax_action'] === 'approve_comment' && $cid) {
        $response['success'] = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$cid]);
    }
    if ($_POST['ajax_action'] === 'spam_comment' && $cid) {
        $response['success'] = $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id = ?")->execute([$cid]);
    }
    echo json_encode($response);
    exit;
}

$filter = $_GET['status'] ?? 'all';
$where = in_array($filter, ['approved', 'pending', 'spam']) ? "WHERE c.status = " . $pdo->quote($filter) : '';
$comments = $pdo->query("SELECT c.*, v.title AS video_title FROM comments c JOIN videos v ON c.video_id = v.id $where ORDER BY c.id DESC LIMIT 200")->fetchAll();

admin_header('Comments', 'comments');
?>

<div class="tabs">
    <a href="/admin/comments" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
    <a href="/admin/comments?status=approved" class="<?php echo $filter === 'approved' ? 'active' : ''; ?>">Approved</a>
    <a href="/admin/comments?status=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
    <a href="/admin/comments?status=spam" class="<?php echo $filter === 'spam' ? 'active' : ''; ?>">Spam</a>
</div>

<div class="card">
    <div class="table-toolbar">
        <input type="text" id="tableFilter" class="search-input" placeholder="Filter comments...">
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>User</th><th>Comment</th><th>Video</th><th>Status</th><th>When</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($comments as $c): ?>
                <tr>
                    <td style="font-weight:600;"><?php echo e($c['user_name']); ?></td>
                    <td><?php echo e(mb_substr($c['comment_text'], 0, 90)); ?></td>
                    <td style="color:var(--text-muted);"><?php echo e(mb_substr($c['video_title'], 0, 35)); ?></td>
                    <td><span class="badge badge-<?php echo $c['status']; ?>"><?php echo $c['status']; ?></span></td>
                    <td style="color:var(--text-muted);"><?php echo time_ago($c['created_at']); ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($c['status'] !== 'approved'): ?>
                            <button class="btn btn-success btn-sm" onclick="toggleField('approve_comment', <?php echo $c['id']; ?>)">Approve</button>
                        <?php endif; ?>
                        <?php if ($c['status'] !== 'spam'): ?>
                            <button class="btn btn-secondary btn-sm" onclick="toggleField('spam_comment', <?php echo $c['id']; ?>)">Spam</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-sm" onclick="deleteRow('delete_comment', <?php echo $c['id']; ?>, 'comment')">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?>
                <tr><td colspan="6" style="color:var(--text-muted);">No comments.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
