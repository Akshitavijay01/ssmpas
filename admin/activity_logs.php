<?php
$page_title = "Activity Logs";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";

$search = trim($_GET['search'] ?? '');
$per_page = 15; $page = max(1,(int)($_GET['page'] ?? 1));
$where = []; $params = [];
if ($search !== '') { $where[] = "(a.action LIKE ? OR u.name LIKE ? OR a.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM activity_logs a LEFT JOIN users u ON a.user_id=u.id $where_sql");
$count_stmt->execute($params); $total = (int)$count_stmt->fetchColumn();
$pag = paginate($total,$per_page,$page);
$params2 = array_merge($params, [$pag['offset'],$pag['per_page']]);
$log_stmt = $conn->prepare("SELECT a.*, u.name AS user_name, u.role AS user_role FROM activity_logs a LEFT JOIN users u ON a.user_id=u.id $where_sql ORDER BY a.created_at DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $log_stmt->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$log_stmt->execute(); $logs = $log_stmt->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">Activity Logs (<?= $total ?>)</h2>
<form method="GET" class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search action, user, or description" value="<?= e($search) ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="activity_logs.php" class="btn btn-outline-secondary">Reset</a></div></form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Description</th><th>IP</th></tr></thead><tbody>
<?php foreach($logs as $l): ?>
<tr><td><small><?= e($l['created_at']) ?><br><span class="text-muted"><?= time_ago($l['created_at']) ?></span></small></td><td><?= e($l['user_name']??'System') ?></td><td><span class="badge bg-secondary"><?= e($l['user_role']??'-') ?></span></td><td><strong><?= e($l['action']) ?></strong></td><td><?= e($l['description']??'-') ?></td><td><small><?= e($l['ip_address']) ?></small></td></tr>
<?php endforeach; if(!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No activity logs found</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search]); ?></div><?php endif; ?>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
