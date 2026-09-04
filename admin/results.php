<?php
$page_title = "Results";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'generate') {
        $exam_id = $_POST['exam_id'] ?? null;
        if (!$exam_id) { set_flash('error','Please select an exam.'); header("Location: results.php"); exit; }
        $exam = $conn->prepare("SELECT * FROM exams WHERE id=?"); $exam->execute([$exam_id]); $exam = $exam->fetch();
        if (!$exam) { set_flash('error','Exam not found.'); header("Location: results.php"); exit; }
        // Calculate total marks per student for the selected exam
        $student_marks = $conn->prepare("SELECT student_id, SUM(marks_obtained) AS obtained, SUM(total_marks) AS total FROM marks WHERE exam_id=? GROUP BY student_id"); $student_marks->execute([$exam_id]);
        $existing = $conn->prepare("SELECT student_id FROM results WHERE exam_id=?"); $existing->execute([$exam_id]);
        $existing_ids = array_column($existing->fetchAll(),'student_id');
        $count = 0;
        while ($sm = $student_marks->fetch()) {
            $pct = $sm['total'] > 0 ? round($sm['obtained']*100.0/$sm['total'],2) : 0;
            $grade = calculate_grade($pct);
            $status = $pct >= ($exam['passing_marks']*100.0/$exam['total_marks']) ? 'Pass' : 'Fail';
            if (in_array($sm['student_id'],$existing_ids)) {
                $conn->prepare("UPDATE results SET total_marks=?,marks_obtained=?,percentage=?,grade=?,result_status=?,published_by=?,published_at=NOW(),is_published=1 WHERE student_id=? AND exam_id=?")
                     ->execute([$sm['total'],$sm['obtained'],$pct,$grade,$status,$_SESSION['user_id'],$sm['student_id'],$exam_id]);
            } else {
                $conn->prepare("INSERT INTO results (student_id,exam_id,total_marks,marks_obtained,percentage,grade,result_status,published_by,published_at,is_published) VALUES (?,?,?,?,?,?,?,?,NOW(),1)")
                     ->execute([$sm['student_id'],$exam_id,$sm['total'],$sm['obtained'],$pct,$grade,$status,$_SESSION['user_id']]);
            }
            $count++;
        }
        set_flash('success',"Results generated for $count students in exam: ".$exam['name']); log_activity('results_generate',"Generated results for exam ID $exam_id");
        header("Location: results.php?exam_id=$exam_id"); exit;
    } elseif ($action === 'publish') {
        $conn->prepare("UPDATE results SET is_published=1, published_at=NOW() WHERE exam_id=?")->execute([$_POST['exam_id']]);
        set_flash('success','Results published!'); log_activity('results_publish',"Published results for exam ID ".$_POST['exam_id']);
        header("Location: results.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM results WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); header("Location: results.php"); exit;
    }
}
$exam_filter = $_GET['exam_id'] ?? '';
$per_page = 15; $page = max(1,(int)($_GET['page'] ?? 1));
$where = []; $params = [];
if ($exam_filter) { $where[] = "r.exam_id = ?"; $params[] = $exam_filter; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM results r $where_sql"); $count_stmt->execute($params); $total = (int)$count_stmt->fetchColumn();
$pag = paginate($total,$per_page,$page);
$params2 = array_merge($params, [$pag['offset'], $pag['per_page']]);
$r_stmt = $conn->prepare("SELECT r.*, s.name AS student_name, s.roll_no, e.name AS exam_name, e.type AS exam_type FROM results r JOIN students s ON r.student_id=s.id LEFT JOIN exams e ON r.exam_id=e.id $where_sql ORDER BY r.percentage DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $r_stmt->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$r_stmt->execute(); $results = $r_stmt->fetchAll();
$exams = $conn->query("SELECT id,name,type FROM exams ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Results (<?= $total ?>)</h2>
    <div class="d-flex gap-2">
        <form method="POST" class="d-flex gap-2">
            <input type="hidden" name="action" value="generate"><?= csrf_field() ?>
            <select name="exam_id" class="form-select" style="width:auto" required>
                <option value="">Select Exam</option>
                <?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>" <?= $exam_filter==$e['id']?'selected':'' ?>><?= e($e['name'].' ('.$e['type'].')') ?></option><?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary-blue"><i class="bi bi-calculator me-1"></i>Generate</button>
        </form>
        <?php if($exam_filter): ?>
        <form method="POST"><input type="hidden" name="action" value="publish"><input type="hidden" name="exam_id" value="<?= e($exam_filter) ?>"><?= csrf_field() ?>
            <button type="submit" class="btn btn-success"><i class="bi bi-send me-1"></i>Publish</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Rank</th><th>Student</th><th>Exam</th><th>Obtained</th><th>Total</th><th>%</th><th>Grade</th><th>Status</th><th>Published</th></tr></thead><tbody>
<?php $rank=0; foreach($results as $r): $rank++; ?>
<tr><td><?= $r['id'] ?></td><td><?= $rank ?></td><td><strong><?= e($r['student_name']) ?></strong><small class="text-muted d-block"><?= e($r['roll_no']) ?></small></td><td><?= e($r['exam_name']??'-') ?> <span class="badge bg-secondary"><?= e($r['exam_type']??'') ?></span></td>
<td><?= $r['marks_obtained'] ?></td><td><?= $r['total_marks'] ?></td><td><?= $r['percentage'] ?>%</td><td><span class="badge bg-<?= grade_color($r['grade']) ?>"><?= e($r['grade']) ?></span></td>
<td><span class="badge bg-<?= $r['result_status']==='Pass'?'success':'danger' ?>"><?= e($r['result_status']) ?></span></td>
<td><?php if($r['is_published']): ?><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Published</span><?php else: ?><span class="badge bg-secondary">Draft</span><?php endif; ?></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $r['id'] ?>" data-name="<?= e($r['student_name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; if(!$results): ?><tr><td colspan="10" class="text-center text-muted py-4">No results found. Select an exam and click Generate to calculate results.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['exam_id'=>$exam_filter]); ?></div><?php endif; ?>
</div>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete Result</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete result for <strong id="delete_name"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>