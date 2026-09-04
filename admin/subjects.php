<?php
$page_title = "Subjects";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name=trim($_POST['name']??''); $code=trim($_POST['code']??''); $class_id=$_POST['class_id']??null; $teacher_id=$_POST['teacher_id']??null; $dept=$_POST['department_id']??null; $credits=(int)($_POST['credits']??3);
        if (empty($name)||empty($code)) { set_flash('error','Name and code required.'); }
        else { $conn->prepare("INSERT INTO subjects (name,code,class_id,teacher_id,department_id,credits) VALUES (?,?,?,?,?,?)")->execute([$name,$code,$class_id?:null,$teacher_id?:null,$dept?:null,$credits]); set_flash('success','Subject added!'); log_activity('subject_add',"Added subject: $name"); header("Location: subjects.php"); exit; }
    } elseif ($action === 'edit') {
        $id=$_POST['id']; $name=trim($_POST['name']??''); $code=trim($_POST['code']??''); $class_id=$_POST['class_id']??null; $teacher_id=$_POST['teacher_id']??null; $dept=$_POST['department_id']??null; $credits=(int)($_POST['credits']??3);
        $conn->prepare("UPDATE subjects SET name=?,code=?,class_id=?,teacher_id=?,department_id=?,credits=? WHERE id=?")->execute([$name,$code,$class_id?:null,$teacher_id?:null,$dept?:null,$credits,$id]);
        set_flash('success','Updated!'); log_activity('subject_edit',"Updated subject ID $id"); header("Location: subjects.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM subjects WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); header("Location: subjects.php"); exit;
    }
}
$subjects = $conn->query("SELECT s.*, c.name AS class_name, c.section, t.name AS teacher_name, d.name AS dept_name FROM subjects s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN teachers t ON s.teacher_id=t.id LEFT JOIN departments d ON s.department_id=d.id ORDER BY s.name")->fetchAll();
$classes = $conn->query("SELECT id,name,section FROM classes ORDER BY name")->fetchAll();
$teachers_list = $conn->query("SELECT id,name FROM teachers ORDER BY name")->fetchAll();
$departments = $conn->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Subjects</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Subject</button>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Code</th><th>Name</th><th>Class</th><th>Teacher</th><th>Dept</th><th>Credits</th><th>Actions</th></tr></thead><tbody>
<?php foreach($subjects as $s): ?>
<tr><td><strong><?= e($s['code']) ?></strong></td><td><?= e($s['name']) ?></td><td><?= e(($s['class_name']??'-').($s['section']?' - '.$s['section']:'')) ?></td><td><?= e($s['teacher_name']??'-') ?></td><td><?= e($s['dept_name']??'-') ?></td><td><?= $s['credits'] ?></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-code="<?= e($s['code']) ?>" data-class="<?= $s['class_id']??'' ?>" data-teacher="<?= $s['teacher_id']??'' ?>" data-dept="<?= $s['department_id']??'' ?>" data-credits="<?= $s['credits'] ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Code *</label><input type="text" name="code" class="form-control" required placeholder="e.g. CS101"></div>
<div class="mb-2"><label class="form-label">Class</label><select name="class_id" class="form-select"><option value="">-- Select --</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Teacher</label><select name="teacher_id" class="form-select"><option value="">-- Select --</option><?php foreach($teachers_list as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Credits</label><input type="number" name="credits" class="form-control" value="3" min="1"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Code *</label><input type="text" name="code" id="edit_code" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Class</label><select name="class_id" id="edit_class" class="form-select"><option value="">-- Select --</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Teacher</label><select name="teacher_id" id="edit_teacher" class="form-select"><option value="">-- Select --</option><?php foreach($teachers_list as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Department</label><select name="department_id" id="edit_dept" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Credits</label><input type="number" name="credits" id="edit_credits" class="form-control" min="1"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_name').value=b.dataset.name;document.getElementById('edit_code').value=b.dataset.code;document.getElementById('edit_class').value=b.dataset.class;document.getElementById('edit_teacher').value=b.dataset.teacher;document.getElementById('edit_dept').value=b.dataset.dept;document.getElementById('edit_credits').value=b.dataset.credits;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>
