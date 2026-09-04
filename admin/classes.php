<?php
$page_title = "Classes";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name=trim($_POST['name']??''); $section=trim($_POST['section']??''); $dept=$_POST['department_id']??null; $year=trim($_POST['academic_year']??'');
        if (empty($name)) { set_flash('error','Class name required.'); }
        else { $conn->prepare("INSERT INTO classes (name,section,department_id,academic_year) VALUES (?,?,?,?)")->execute([$name,$section?:null,$dept?:null,$year]); set_flash('success','Class added!'); log_activity('class_add',"Added class: $name"); header("Location: classes.php"); exit; }
    } elseif ($action === 'edit') {
        $id=$_POST['id']; $name=trim($_POST['name']??''); $section=trim($_POST['section']??''); $dept=$_POST['department_id']??null; $year=trim($_POST['academic_year']??'');
        $conn->prepare("UPDATE classes SET name=?,section=?,department_id=?,academic_year=? WHERE id=?")->execute([$name,$section?:null,$dept?:null,$year,$id]);
        set_flash('success','Updated!'); log_activity('class_edit',"Updated class ID $id"); header("Location: classes.php"); exit;
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM classes WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); log_activity('class_delete',"Deleted class ID ".$_POST['id']); header("Location: classes.php"); exit;
    }
}
$classes = $conn->query("SELECT c.*, d.name AS dept_name, (SELECT COUNT(*) FROM students WHERE class_id=c.id) AS stu_count FROM classes c LEFT JOIN departments d ON c.department_id=d.id ORDER BY c.name")->fetchAll();
$departments = $conn->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Classes</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Class</button>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>ID</th><th>Name</th><th>Section</th><th>Department</th><th>Year</th><th>Students</th><th>Actions</th></tr></thead><tbody>
<?php foreach($classes as $c): ?>
<tr><td><?= $c['id'] ?></td><td><strong><?= e($c['name']) ?></strong></td><td><?= e($c['section']??'-') ?></td><td><?= e($c['dept_name']??'-') ?></td><td><?= e($c['academic_year']) ?></td><td><?= $c['stu_count'] ?></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>" data-section="<?= e($c['section']??'') ?>" data-dept="<?= $c['department_id']??'' ?>" data-year="<?= e($c['academic_year']) ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Class Name *</label><input type="text" name="name" class="form-control" required placeholder="e.g. First Year"></div>
<div class="mb-2"><label class="form-label">Section</label><input type="text" name="section" class="form-control" placeholder="e.g. A"></div>
<div class="mb-2"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Academic Year</label><input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025-2026" value="2025-2026"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Class Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Section</label><input type="text" name="section" id="edit_section" class="form-control"></div>
<div class="mb-2"><label class="form-label">Department</label><select name="department_id" id="edit_dept" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Academic Year</label><input type="text" name="academic_year" id="edit_year" class="form-control"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>? This may affect linked students.</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_name').value=b.dataset.name;document.getElementById('edit_section').value=b.dataset.section;document.getElementById('edit_dept').value=b.dataset.dept;document.getElementById('edit_year').value=b.dataset.year;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>
