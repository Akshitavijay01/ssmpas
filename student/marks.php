<?php
$page_title = "My Marks";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?"); $student_id->execute([$_SESSION['user_id']]); $student_id=$student_id->fetch();
if (!$student_id) { echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Profile not linked</h3></div></div></div></div>'; include "../includes/footer.php"; exit; }
$sid = $student_id['id'];

$search = trim($_GET['search'] ?? ''); $per_page = 15; $page = max(1,(int)($_GET['page'] ?? 1));
$where = ["m.student_id=?"]; $params = [$sid];
if ($search !== '') { $where[] = "(m.subject LIKE ? OR m.grade LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where_sql = ' WHERE '.implode(' AND ',$where);
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM marks m $where_sql"); $count_stmt->execute($params); $total=(int)$count_stmt->fetchColumn();
$pag = paginate($total,$per_page,$page);
$params2 = array_merge($params, [$pag['offset'],$pag['per_page']]);
$marks = $conn->prepare("SELECT m.*, e.name AS exam_name FROM marks m LEFT JOIN exams e ON m.exam_id=e.id $where_sql ORDER BY m.created_at DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $marks->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$marks->execute(); $marks = $marks->fetchAll();

$total_obtained = array_sum(array_column($marks,'marks_obtained'));
$total_possible = array_sum(array_column($marks,'total_marks'));
$avg_pct = $total_possible > 0 ? round($total_obtained*100.0/$total_possible,1) : '0.0';
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Marks (<?= $total ?>)</h2>
<div class="stats-row mb-3">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-pencil-square"></i></div><div class="stat-info"><h3><?= $total ?></h3><p>Total Entries</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-trophy"></i></div><div class="stat-info"><h3><?= $total_obtained ?></h3><p>Total Obtained</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-graph-up"></i></div><div class="stat-info"><h3><?= $avg_pct ?>%</h3><p>Overall Average</p></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-award"></i></div><div class="stat-info"><h3><?= calculate_grade($avg_pct) ?></h3><p>Overall Grade</h3></div></div>
</div>
<form method="GET" class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search subject or grade" value="<?= e($search) ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="marks.php" class="btn btn-outline-secondary">Reset</a></div></form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Exam</th><th>Subject</th><th>Marks Obtained</th><th>Total</th><th>%</th><th>Grade</th><th>Remarks</th><th>Date</th></tr></thead><tbody>
<?php foreach($marks as $m): ?>
<tr><td><?= $m['id'] ?></td><td><?= e($m['exam_name']??'-') ?></td><td><strong><?= e($m['subject']??'-') ?></strong></td>
<td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td>
<td><?= $m['total_marks']>0?round($m['marks_obtained']*100.0/$m['total_marks'],1):0 ?>%</td>
<td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td>
<td><?= e($m['remarks']??'-') ?></td><td><?= date('M d, Y', strtotime($m['created_at'])) ?></td></tr>
<?php endforeach; if(!$marks): ?><tr><td colspan="9" class="text-center text-muted py-4">No marks found</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search]); ?></div><?php endif; ?>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
