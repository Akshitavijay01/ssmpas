<?php
$page_title = "Marks Management";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $student_id=$_POST['student_id']; $subject_id=$_POST['subject_id']??null; $subject=trim($_POST['subject']??''); $marks=(int)($_POST['marks']??0); $total=(int)($_POST['total_marks']??100);
        if (empty($student_id)) { set_flash('error','Student required.'); }
        elseif ($marks<0) { set_flash('error','Marks cannot be negative.'); }
        else {
            $grade = calculate_grade(round($marks*100.0/$total,1));
            $conn->prepare("INSERT INTO marks (student_id,subject_id,subject,marks_obtained,total_marks,grade,entered_by) VALUES (?,?,?,?,?,?,?)")->execute([$student_id,$subject_id?:null,$subject,$marks,$total,$grade,$_SESSION['user_id']]);
            set_flash('success','Marks added!'); log_activity('mark_add',"Teacher added marks for student ID $student_id: $subject = $marks/$total");
            header("Location: marks.php"); exit;
        }
    } elseif ($action === 'edit') {
        $id=$_POST['id']; $student_id=$_POST['student_id']; $subject_id=$_POST['subject_id']??null; $subject=trim($_POST['subject']??''); $marks=(int)($_POST['marks']??0); $total=(int)($_POST['total_marks']??100);
        $grade = calculate_grade(round($marks*100.0/$total,1));
        $conn->prepare("UPDATE marks SET student_id=?,subject_id=?,subject=?,marks_obtained=?,total_marks=?,grade=? WHERE id=?")->execute([$student_id,$subject_id?:null,$subject,$marks,$total,$grade,$id]);
        set_flash('success','Updated!'); log_activity('mark_edit',"Updated marks ID $id"); header("Location: marks.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM marks WHERE id=? AND entered_by=?")->execute([$_POST['id'],$_SESSION['user_id']]);
        set_flash('success','Deleted.'); log_activity('mark_delete',"Deleted marks ID ".$_POST['id']); header("Location: marks.php"); exit;
    }
}
$search = trim($_GET['search'] ?? ''); $student_filter = $_GET['student_id'] ?? '';
$per_page = 15; $page = max(1,(int)($_GET['page'] ?? 1));
$where = ["m.entered_by=?"]; $params = [$_SESSION['user_id']];
if ($search !== '') { $where[] = "(s.name LIKE ? OR s.roll_no LIKE ? OR m.subject LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($student_filter) { $where[] = "m.student_id=?"; $params[] = $student_filter; }
$where_sql = ' WHERE '.implode(' AND ',$where);
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM marks m JOIN students s ON m.student_id=s.id $where_sql"); $count_stmt->execute($params); $total = (int)$count_stmt->fetchColumn();
$pag = paginate($total,$per_page,$page);
$params2 = array_merge($params, [$pag['offset'],$pag['per_page']]);
$marks = $conn->prepare("SELECT m.*, s.name AS student_name, s.roll_no FROM marks m JOIN students s ON m.student_id=s.id $where_sql ORDER BY m.created_at DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $marks->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$marks->execute(); $marks = $marks->fetchAll();
$students = $conn->query("SELECT id,name,roll_no FROM students WHERE is_active=1 ORDER BY name")->fetchAll();
$subjects = $conn->query("SELECT id,name,code FROM subjects WHERE is_active=1 ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">My Marks (<?= $total ?>)</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Marks</button>
</div>
<form method="GET" class="row g-2 mb-3 align-items-end">
    <input type="hidden" name="student_id" value="<?= e($student_filter) ?>">
    <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search student or subject" value="<?= e($search) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="marks.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Student</th><th>Subject</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th><th>Actions</th></tr></thead><tbody>
<?php foreach($marks as $m): ?>
<tr><td><?= $m['id'] ?></td><td><strong><?= e($m['student_name']) ?></strong><small class="text-muted d-block"><?= e($m['roll_no']) ?></small></td>
<td><?= e($m['subject']??'-') ?></td><td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td>
<td><?= $m['total_marks']>0?round($m['marks_obtained']*100.0/$m['total_marks'],1):0 ?>%</td>
<td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $m['id'] ?>" data-student="<?= $m['student_id'] ?>" data-subject="<?= e($m['subject']??'') ?>" data-marks="<?= $m['marks_obtained'] ?>" data-total="<?= $m['total_marks'] ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $m['id'] ?>" data-name="<?= e($m['student_name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; if(!$marks): ?><tr><td colspan="8" class="text-center text-muted py-4">No marks found</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search,'student_id'=>$student_filter]); ?></div><?php endif; ?>
</div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Marks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="">-- Select --</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>" <?= $student_filter==$s['id']?'selected':'' ?>><?= e($s['name'].' ('.$s['roll_no'].')') ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject (optional)</label><select name="subject_id" class="form-select"><option value="">-- Select --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject Name *</label><input type="text" name="subject" class="form-control" required placeholder="e.g. Mathematics"></div>
<div class="mb-2"><label class="form-label">Marks Obtained *</label><input type="number" name="marks" class="form-control" min="0" required></div>
<div class="mb-2"><label class="form-label">Total Marks *</label><input type="number" name="total_marks" class="form-control" value="100" min="1" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Marks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Student *</label><select name="student_id" id="edit_student" class="form-select" required><option value="">-- Select --</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].')') ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject Name *</label><input type="text" name="subject" id="edit_subject" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Marks *</label><input type="number" name="marks" id="edit_marks" class="form-control" min="0" required></div>
<div class="mb-2"><label class="form-label">Total Marks *</label><input type="number" name="total_marks" id="edit_total" class="form-control" value="100" min="1" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete marks for <strong id="delete_name"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_student').value=b.dataset.student;document.getElementById('edit_subject').value=b.dataset.subject;document.getElementById('edit_marks').value=b.dataset.marks;document.getElementById('edit_total').value=b.dataset.total;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>
