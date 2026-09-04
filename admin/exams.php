<?php
$page_title = "Exams";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name=trim($_POST['name']??''); $type=$_POST['type']??''; $class_id=$_POST['class_id']??null; $subject_id=$_POST['subject_id']??null; $total=(int)($_POST['total_marks']??100); $passing=(int)($_POST['passing_marks']??40); $date=$_POST['exam_date']??null; $stime=$_POST['start_time']??null; $etime=$_POST['end_time']??null;
        if (empty($name)) { set_flash('error','Exam name required.'); }
        elseif (empty($type)) { set_flash('error','Select exam type.'); }
        elseif ($total<=0) { set_flash('error','Total marks must be positive.'); }
        else { $conn->prepare("INSERT INTO exams (name,type,class_id,subject_id,total_marks,passing_marks,exam_date,start_time,end_time) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$name,$type,$class_id?:null,$subject_id?:null,$total,$passing,$date,$stime,$etime]); set_flash('success','Exam added!'); log_activity('exam_add',"Added exam: $name"); header("Location: exams.php"); exit; }
    } elseif ($action === 'edit') {
        $id=$_POST['id']; $name=trim($_POST['name']??''); $type=$_POST['type']??''; $class_id=$_POST['class_id']??null; $subject_id=$_POST['subject_id']??null; $total=(int)($_POST['total_marks']??100); $passing=(int)($_POST['passing_marks']??40); $date=$_POST['exam_date']??null; $stime=$_POST['start_time']??null; $etime=$_POST['end_time']??null;
        $conn->prepare("UPDATE exams SET name=?,type=?,class_id=?,subject_id=?,total_marks=?,passing_marks=?,exam_date=?,start_time=?,end_time=? WHERE id=?")->execute([$name,$type,$class_id?:null,$subject_id?:null,$total,$passing,$date,$stime,$etime,$id]);
        set_flash('success','Updated!'); log_activity('exam_edit',"Updated exam ID $id"); header("Location: exams.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM exams WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); log_activity('exam_delete',"Deleted exam ID ".$_POST['id']); header("Location: exams.php"); exit;
    }
}
$exams = $conn->query("SELECT e.*, c.name AS class_name, c.section, s.name AS subject_name FROM exams e LEFT JOIN classes c ON e.class_id=c.id LEFT JOIN subjects s ON e.subject_id=s.id ORDER BY e.created_at DESC")->fetchAll();
$classes = $conn->query("SELECT id,name,section FROM classes ORDER BY name")->fetchAll();
$subjects = $conn->query("SELECT id,name,code FROM subjects ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Exams</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Exam</button>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Class</th><th>Subject</th><th>Total</th><th>Passing</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php foreach($exams as $e): ?>
<tr><td><?= $e['id'] ?></td><td><strong><?= e($e['name']) ?></strong></td><td><?= e($e['type']) ?></td><td><?= e(($e['class_name']??'-').($e['section']?'-'.$e['section']:'')) ?></td><td><?= e($e['subject_name']??'-') ?></td><td><?= $e['total_marks'] ?></td><td><?= $e['passing_marks'] ?></td><td><?= e($e['exam_date']) ?><br><small><?= e($e['start_time']) ?> - <?= e($e['end_time']) ?></small></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $e['id'] ?>" data-name="<?= e($e['name']) ?>" data-type="<?= e($e['type']) ?>" data-class="<?= $e['class_id']??'' ?>" data-subject="<?= $e['subject_id']??'' ?>" data-total="<?= $e['total_marks'] ?>" data-passing="<?= $e['passing_marks'] ?>" data-date="<?= e($e['exam_date']) ?>" data-stime="<?= e($e['start_time']) ?>" data-etime="<?= e($e['end_time']) ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $e['id'] ?>" data-name="<?= e($e['name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Exam Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. Midterm Examination"></div>
<div class="mb-2"><label class="form-label">Type *</label><select name="type" class="form-select" required><option value="">-- Select --</option><option value="Quiz">Quiz</option><option value="Midterm">Midterm</option><option value="Final">Final</option><option value="Assignment">Assignment</option><option value="Practical">Practical</option><option value="Viva">Viva</option></select></div>
<div class="mb-2"><label class="form-label">Class</label><select name="class_id" class="form-select"><option value="">-- Select --</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject</label><select name="subject_id" class="form-select"><option value="">-- Select --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' - '.$s['code']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Total Marks</label><input type="number" name="total_marks" class="form-control" value="100" min="1"></div>
<div class="mb-2"><label class="form-label">Passing Marks</label><input type="number" name="passing_marks" class="form-control" value="40" min="0"></div>
<div class="mb-2"><label class="form-label">Exam Date (optional)</label><input type="date" name="exam_date" class="form-control"></div>
<div class="mb-2"><label class="form-label">Start Time (optional)</label><input type="time" name="start_time" class="form-control"></div>
<div class="mb-2"><label class="form-label">End Time (optional)</label><input type="time" name="end_time" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Exam Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Type *</label><select name="type" id="edit_type" class="form-select" required><option value="">-- Select --</option><option value="Quiz">Quiz</option><option value="Midterm">Midterm</option><option value="Final">Final</option><option value="Assignment">Assignment</option><option value="Practical">Practical</option><option value="Viva">Viva</option></select></div>
<div class="mb-2"><label class="form-label">Class</label><select name="class_id" id="edit_class" class="form-select"><option value="">-- Select --</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject</label><select name="subject_id" id="edit_subject" class="form-select"><option value="">-- Select --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' - '.$s['code']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Total Marks</label><input type="number" name="total_marks" id="edit_total" class="form-control" value="100" min="1"></div>
<div class="mb-2"><label class="form-label">Passing Marks</label><input type="number" name="passing_marks" id="edit_passing" class="form-control" value="40" min="0"></div>
<div class="mb-2"><label class="form-label">Exam Date</label><input type="date" name="exam_date" id="edit_date" class="form-control"></div>
<div class="mb-2"><label class="form-label">Start Time</label><input type="time" name="start_time" id="edit_stime" class="form-control"></div>
<div class="mb-2"><label class="form-label">End Time</label><input type="time" name="end_time" id="edit_etime" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>? This may affect related marks/results.</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_name').value=b.dataset.name;document.getElementById('edit_type').value=b.dataset.type;document.getElementById('edit_class').value=b.dataset.class;document.getElementById('edit_subject').value=b.dataset.subject;document.getElementById('edit_total').value=b.dataset.total;document.getElementById('edit_passing').value=b.dataset.passing;document.getElementById('edit_date').value=b.dataset.date;document.getElementById('edit_stime').value=b.dataset.stime;document.getElementById('edit_etime').value=b.dataset.etime;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>