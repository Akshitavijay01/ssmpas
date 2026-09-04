<?php
$page_title = "Attendance";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";

$search = trim($_GET['search'] ?? '');
$date_filter = $_GET['date'] ?? '';
$status_filter = $_GET['status'] ?? '';
$per_page = 15; $page = max(1,(int)($_GET['page'] ?? 1));

$where = []; $params = [];
if ($search !== '') { $where[] = "(s.name LIKE ? OR s.roll_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($date_filter) { $where[] = "a.date = ?"; $params[] = $date_filter; }
if ($status_filter) { $where[] = "a.status = ?"; $params[] = $status_filter; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM attendance a JOIN students s ON a.student_id=s.id $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pag = paginate($total, $per_page, $page);

$params2 = array_merge($params, [$pag['offset'], $pag['per_page']]);
$att_stmt = $conn->prepare("SELECT a.*, s.name AS student_name, s.roll_no FROM attendance a JOIN students s ON a.student_id=s.id $where_sql ORDER BY a.date DESC, a.id DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $att_stmt->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$att_stmt->execute();
$attendance = $att_stmt->fetchAll();

$today = date('Y-m-d');
$present_today = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='Present'");
$present_today->execute([$today]); $present_count = (int)$present_today->fetchColumn();
$absent_today = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date=? AND status='Absent'");
$absent_today->execute([$today]); $absent_count = (int)$absent_today->fetchColumn();
$total_today = $present_count + $absent_count;
$rate_today = $total_today > 0 ? round($present_count*100.0/$total_today,1) : '0.0';
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Attendance Records</h2>
    <div class="d-flex gap-2">
        <div class="stat-card" style="margin:0"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?= $present_count ?></h3><p>Present Today</p></div></div>
        <div class="stat-card" style="margin:0"><div class="stat-icon red"><i class="bi bi-x-circle"></i></div><div class="stat-info"><h3><?= $absent_count ?></h3><p>Absent Today</p></div></div>
        <div class="stat-card" style="margin:0"><div class="stat-icon blue"><i class="bi bi-percent"></i></div><div class="stat-info"><h3><?= $rate_today ?>%</h3><p>Today's Rate</p></div></div>
    </div>
</div>
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search student name or roll no" value="<?= e($search) ?>"></div>
    <div class="col-md-2"><input type="date" name="date" class="form-control" value="<?= e($date_filter) ?>"></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All Status</option><option value="Present" <?= $status_filter==='Present'?'selected':'' ?>>Present</option><option value="Absent" <?= $status_filter==='Absent'?'selected':'' ?>>Absent</option><option value="Late" <?= $status_filter==='Late'?'selected':'' ?>>Late</option></select></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="attendance.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>ID</th><th>Student</th><th>Roll No</th><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
<?php foreach($attendance as $a): ?>
<tr><td><?= $a['id'] ?></td><td><strong><?= e($a['student_name']) ?></strong></td><td><?= e($a['roll_no']) ?></td><td><?= e($a['date']) ?></td>
<td><span class="badge bg-<?= $a['status']==='Present'?'success':($a['status']==='Absent'?'danger':($a['status']==='Late'?'warning':'info')) ?>"><?= e($a['status']) ?></span></td>
<td><?= e($a['remarks']??'-') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search,'date'=>$date_filter,'status'=>$status_filter]); ?></div><?php endif; ?>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
