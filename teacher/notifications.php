<?php
$page_title = "Notifications";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');

$role = $_SESSION['role']; $uid = $_SESSION['user_id'];

if (isset($_GET['mark_read'])) {
    $id = $_GET['mark_read'];
    if ($id === 'all') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE is_read=0 AND (target_role='all' OR target_role=? OR target_user_id=?)");
        $stmt->execute([$role, $uid]);
        set_flash('success','All marked as read.');
    } else {
        $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND (target_role='all' OR target_role=? OR target_user_id=?)")->execute([$id,$role,$uid]);
        set_flash('success','Marked as read.');
    }
    header("Location: notifications.php"); exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $id = $_POST['id'] ?? $_GET['mark_read'] ?? '';
        if ($id === 'all') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE is_read=0 AND (target_role='all' OR target_role=? OR target_user_id=?)");
            $stmt->execute([$role, $uid]);
            set_flash('success','All marked as read.');
        }
        else {
            $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND (target_role='all' OR target_role=? OR target_user_id=?)")->execute([$id,$role,$uid]);
            set_flash('success','Marked as read.');
        }
        header("Location: notifications.php"); exit;
    }
}
$notifications = $conn->prepare("SELECT n.*, u.name AS creator_name FROM notifications n LEFT JOIN users u ON n.created_by=u.id WHERE n.target_role='all' OR n.target_role=? OR n.target_user_id=? ORDER BY n.created_at DESC LIMIT 30");
$notifications->execute([$role,$uid]); $notifications = $notifications->fetchAll();
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read=0 AND (target_role='all' OR target_role=? OR target_user_id=?)");
$unread_stmt->execute([$role,$uid]); $unread = (int)$unread_stmt->fetchColumn();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="mb-0">Notifications</h2><small class="text-muted"><?= $unread ?> unread</small></div>
    <div class="d-flex gap-2">
        <?php if($unread>0): ?><a href="notifications.php?mark_read=all" class="btn btn-success"><i class="bi bi-check-all me-1"></i>Mark All Read</a><?php endif; ?>
    </div>
</div>
<?php foreach($notifications as $n): ?>
<div class="border-bottom p-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <span class="badge bg-<?= $n['type']==='info'?'primary':($n['type']==='warning'?'warning':($n['type']==='success'?'success':'danger')) ?> me-2"><?= e(ucfirst($n['type'])) ?></span>
            <strong><?= e($n['title']) ?></strong>
            <?php if(!$n['is_read']): ?><span class="badge bg-danger ms-2">New</span><?php endif; ?>
            <p class="mb-0 mt-1" style="font-size:.84rem;color:#475569"><?= e($n['message']) ?></p>
            <small class="text-muted">By: <?= e($n['creator_name']??'System') ?> | <?= time_ago($n['created_at']) ?></small>
        </div>
        <?php if(!$n['is_read']): ?>
        <a href="notifications.php?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check"></i></a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; if(!$notifications): ?><div class="empty-state py-5"><i class="bi bi-bell-slash"></i><h3>No notifications</h3></div><?php endif; ?>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
