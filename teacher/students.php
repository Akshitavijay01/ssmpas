<?php
$page_title = "Students";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";

$search = trim($_GET['search'] ?? '');
$per_page = 10; $page = max(1,(int)($_GET['page'] ?? 1));
$where = []; $params = [];
if ($search !== '') { $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.roll_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM students s $where_sql"); $count_stmt->execute($params); $total = (int)$count_stmt->fetchColumn();
$pag = paginate($total,$per_page,$page);
$params2 = array_merge($params, [$pag['offset'],$pag['per_page']]);
$stu = $conn->prepare("SELECT s.*, c.name AS class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id=c.id $where_sql ORDER BY s.name LIMIT ?, ?");
foreach($params2 as $i=>$v){ $stu->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$stu->execute(); $students = $stu->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">Students (<?= $total ?>)</h2>
<form method="GET" class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search name, email, or roll no" value="<?= e($search) ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="students.php" class="btn btn-outline-secondary">Reset</a></div></form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Name</th><th>Roll No</th><th>Email</th><th>Class</th><th>Actions</th></tr></thead><tbody>
<?php foreach($students as $s): ?>
<tr><td><?= $s['id'] ?></td><td><strong><?= e($s['name']) ?></strong></td><td><?= e($s['roll_no']??'-') ?></td><td><?= e($s['email']??'-') ?></td><td><?= e(($s['class_name']??'-').($s['section']?' - '.$s['section']:'')) ?></td>
<td><a href="marks.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">Marks</a> <a href="attendance.php?student_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-success">Attendance</a></td>
</tr>
<?php endforeach; if(!$students): ?><tr><td colspan="6" class="text-center text-muted py-4">No students found</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search]); ?></div><?php endif; ?>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
