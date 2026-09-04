<?php
$page_title = "Notifications";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'send') {
        $title = trim($_POST['title']??''); $message = trim($_POST['message']??''); $type = $_POST['type']??'info'; $target = $_POST['target_role']??'all';
        if (empty($title)||empty($message)) { set_flash('error','Title and message required.'); }
        else { $conn->prepare("INSERT INTO notifications (title,message,type,target_role,created_by) VALUES (?,?,?,?,?)")->execute([$title,$message,$type,$target,$_SESSION['user_id']]); set_flash('success','Notification sent!'); log_activity('notif_send',"Sent notification: $title"); header("Location: notifications.php"); exit; }
    } elseif ($action === 'mark_read') {
        $id = $_POST['id'] ?? $_GET['mark_read'] ?? '';
        if ($id === 'all') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE is_read=0");
            $stmt->execute();
            set_flash('success','All marked as read.');
        }
        else {
            $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
            set_flash('success','Marked as read.');
        }
        header("Location: notifications.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM notifications WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); header("Location: notifications.php"); exit;
    }
}
$notif_filter = $_GET['filter'] ?? '';
$where = []; $params = [];
if ($notif_filter === 'unread') { $where[] = "n.is_read=0"; }
elseif ($notif_filter === 'read') { $where[] = "n.is_read=1"; }
if ($where) { $params = []; $where_sql = ' WHERE '.implode(' AND ',$where); }
else { $where_sql = ''; $params = []; }
$notifications = $conn->prepare("SELECT n.*, u.name AS creator_name FROM notifications n LEFT JOIN users u ON n.created_by=u.id $where_sql ORDER BY n.created_at DESC LIMIT 50");
$notifications->execute($params); $notifications = $notifications->fetchAll();
$unread_count = (int)$conn->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Notifications</h2>
        <small class="text-muted"><?= $unread_count ?> unread</small>
    </div>
    <div class="d-flex gap-2">
        <a href="notifications.php?filter=<?= $notif_filter==='unread'?'all':'unread' ?>" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i><?= $notif_filter==='unread'?'All':'Unread Only' ?>
        </a>
        <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#sendModal"><i class="bi bi-send me-1"></i>Send Notification</button>
        <?php if($unread_count>0): ?>
        <a href="notifications.php?mark_read=all" class="btn btn-success"><i class="bi bi-check-all me-1"></i>Mark All Read</a>
        <?php endif; ?>
    </div>
</div>
<div class="panel"><div class="panel-body p-0">
<?php foreach($notifications as $n): ?>
<div class="border-bottom p-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <span class="badge bg-<?= $n['type']==='info'?'primary':($n['type']==='warning'?'warning':($n['type']==='success'?'success':'danger')) ?> me-2"><?= e(ucfirst($n['type'])) ?></span>
            <strong><?= e($n['title']) ?></strong>
            <?php if(!$n['is_read']): ?><span class="badge bg-danger ms-2">New</span><?php endif; ?>
            <p class="mb-0 mt-1" style="font-size:.84rem;color:#475569"><?= e($n['message']) ?></p>
            <small class="text-muted">Target: <?= e(ucfirst($n['target_role'])) ?> | By: <?= e($n['creator_name']??'System') ?> | <?= time_ago($n['created_at']) ?></small>
        </div>
        <div class="d-flex gap-1">
            <?php if(!$n['is_read']): ?>
            <a href="notifications.php?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-outline-success" title="Mark read"><i class="bi bi-check"></i></a>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-danger delNotif" data-id="<?= $n['id'] ?>" data-title="<?= e($n['title']) ?>" title="Delete"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</div>
<?php endforeach; if(!$notifications): ?>
<div class="empty-state py-5"><i class="bi bi-bell-slash"></i><h3>No notifications</h3><p>There are no notifications to show.</p></div>
<?php endif; ?>
</div></div>
</div></div></div>

<div class="modal fade" id="sendModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="send"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Send Notification</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
<div class="mb-2"><label class="form-label">Type</label><select name="type" class="form-select"><option value="info">Info</option><option value="warning">Warning</option><option value="success">Success</option><option value="danger">Danger</option></select></div>
<div class="mb-2"><label class="form-label">Target</label><select name="target_role" class="form-select"><option value="all">All</option><option value="admin">Admin</option><option value="teacher">Teacher</option><option value="student">Student</option></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Send</button></div>
</form></div></div></div>
<div class="modal fade" id="delModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="del_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="del_title"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.delNotif').forEach(function(b){b.addEventListener('click',function(){document.getElementById('del_id').value=b.dataset.id;document.getElementById('del_title').textContent=b.dataset.title;new bootstrap.Modal(document.getElementById('delModal')).show();});});
</script>
