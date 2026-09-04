<?php
$page_title = "Marks Management";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $student_id = $_POST['student_id']; $exam_id = $_POST['exam_id']??null; $subject_id = $_POST['subject_id']??null; $subject = trim($_POST['subject']??''); $marks = (int)($_POST['marks']??0); $total = (int)($_POST['total_marks']??100);
        if (empty($student_id)) { set_flash('error','Student required.'); }
        elseif ($marks < 0) { set_flash('error','Marks cannot be negative.'); }
        elseif ($total <= 0) { set_flash('error','Total marks must be positive.'); }
        else {
            $grade = calculate_grade(round($marks*100.0/$total,1));
            $conn->prepare("INSERT INTO marks (student_id,exam_id,subject_id,subject,marks_obtained,total_marks,grade,entered_by) VALUES (?,?,?,?,?,?,?,?)")
                 ->execute([$student_id,$exam_id?:null,$subject_id?:null,$subject,$marks,$total,$grade,$_SESSION['user_id']]);
            set_flash('success','Marks added!'); log_activity('mark_add',"Added marks for student ID $student_id: $subject = $marks/$total");
            header("Location: marks.php"); exit;
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id']; $student_id = $_POST['student_id']; $exam_id = $_POST['exam_id']??null; $subject_id = $_POST['subject_id']??null; $subject = trim($_POST['subject']??''); $marks = (int)($_POST['marks']??0); $total = (int)($_POST['total_marks']??100);
        $grade = calculate_grade(round($marks*100.0/$total,1));
        $conn->prepare("UPDATE marks SET student_id=?,exam_id=?,subject_id=?,subject=?,marks_obtained=?,total_marks=?,grade=?,entered_by=? WHERE id=?")
             ->execute([$student_id,$exam_id?:null,$subject_id?:null,$subject,$marks,$total,$grade,$_SESSION['user_id'],$id]);
        set_flash('success','Updated!'); log_activity('mark_edit',"Updated marks ID $id"); header("Location: marks.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM marks WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); log_activity('mark_delete',"Deleted marks ID ".$_POST['id']); header("Location: marks.php"); exit;
    }
}
$marks = $conn->query("SELECT m.*, s.name AS student_name, s.roll_no, e.name AS exam_name, sub.name AS subject_name, u.name AS entered_by_name FROM marks m JOIN students s ON m.student_id=s.id LEFT JOIN exams e ON m.exam_id=e.id LEFT JOIN subjects sub ON m.subject_id=sub.id LEFT JOIN users u ON m.entered_by=u.id ORDER BY m.created_at DESC")->fetchAll();
$students = $conn->query("SELECT id,name,roll_no FROM students WHERE is_active=1 ORDER BY name")->fetchAll();
$exams = $conn->query("SELECT id,name,type FROM exams WHERE is_active=1 ORDER BY name")->fetchAll();
$subjects = $conn->query("SELECT id,name,code FROM subjects WHERE is_active=1 ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Marks Management</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Marks</button>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>ID</th><th>Student</th><th>Exam</th><th>Subject</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th><th>Entered</th><th>Actions</th></tr></thead><tbody>
<?php foreach($marks as $m): ?>
<tr><td><?= $m['id'] ?></td><td><strong><?= e($m['student_name']) ?></strong><small class="text-muted d-block">(<?= e($m['roll_no']) ?>)</small></td><td><?= e($m['exam_name']??'-') ?></td><td><?= e(($m['subject_name']??$m['subject']??'-').($m['subject_name']?'':'('.e($m['subject']).')')) ?></td><td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td><td><?= $m['total_marks']>0 ? round($m['marks_obtained']*100.0/$m['total_marks'],1) : '0.0' ?>%</td><td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td><td><?= e($m['entered_by_name']??'System') ?><br><small><?= time_ago($m['created_at']) ?></small></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $m['id'] ?>" data-student="<?= $m['student_id'] ?>" data-exam="<?= $m['exam_id']??'' ?>" data-subject="<?= $m['subject_id']??'' ?>" data-subjecttxt="<?= e($m['subject']??'') ?>" data-marks="<?= $m['marks_obtained'] ?>" data-total="<?= $m['total_marks'] ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $m['id'] ?>" data-name="<?= e($m['student_name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Marks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="">-- Select Student --</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].')') ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Exam (optional)</label><select name="exam_id" class="form-select"><option value="">-- Select Exam --</option><?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>"><?= e($e['name'].' - '.$e['type']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject (optional)</label><select name="subject_id" class="form-select"><option value="">-- Select Subject --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' - '.$s['code']) ?></option><?php endforeach; ?></select></div>
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
<div class="mb-2"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="">-- Select Student --</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].')') ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Exam (optional)</label><select name="exam_id" class="form-select"><option value="">-- Select Exam --</option><?php foreach($exams as $e): ?><option value="<?= $e['id'] ?>"><?= e($e['name'].' - '.$e['type']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject (optional)</label><select name="subject_id" class="form-select"><option value="">-- Select Subject --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' - '.$s['code']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Subject Name *</label><input type="text" name="subject" id="edit_subject" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Marks Obtained *</label><input type="number" name="marks" id="edit_marks" class="form-control" min="0" required></div>
<div class="mb-2"><label class="form-label">Total Marks *</label><input type="number" name="total_marks" id="edit_total" class="form-control" value="100" min="1" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete Marks</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete marks for <strong id="delete_name"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_student').value=b.dataset.student;document.getElementById('edit_exam').value=b.dataset.exam;document.getElementById('edit_subject').value=b.dataset.subject;document.getElementById('edit_subjecttxt').value=b.dataset.subjecttxt;document.getElementById('edit_marks').value=b.dataset.marks;document.getElementById('edit_total').value=b.dataset.total;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>