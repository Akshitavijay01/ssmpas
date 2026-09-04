<?php
$page_title = "Departments";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? ''); $code = trim($_POST['code'] ?? '');
        if (empty($name)||empty($code)) { set_flash('error','Name and code required.'); }
        else {
            try { $conn->prepare("INSERT INTO departments (name,code) VALUES (?,?)")->execute([$name,$code]); log_activity('dept_add',"Added dept: $name"); set_flash('success','Department added!'); header("Location: departments.php"); exit; }
            catch(PDOException $ex){ set_flash('error','Failed: '.$ex->getMessage()); }
        }
    } elseif ($action === 'edit') {
        $id=$_POST['id']; $name=trim($_POST['name']??''); $code=trim($_POST['code']??'');
        try { $conn->prepare("UPDATE departments SET name=?,code=? WHERE id=?")->execute([$name,$code,$id]); set_flash('success','Updated!'); header("Location: departments.php"); exit; }
        catch(PDOException $ex){ set_flash('error','Failed'); }
    } elseif ($action === 'delete') {
        try { $conn->prepare("DELETE FROM departments WHERE id=?")->execute([$_POST['id']]); set_flash('success','Deleted.'); header("Location: departments.php"); exit; }
        catch(PDOException $ex){ set_flash('error','Failed'); }
    }
}
$departments = $conn->query("SELECT d.*, (SELECT COUNT(*) FROM teachers WHERE department_id=d.id) AS t_count, (SELECT COUNT(*) FROM students WHERE department_id=d.id) AS s_count FROM departments d ORDER BY d.name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Departments</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Department</button>
</div>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>ID</th><th>Name</th><th>Code</th><th>Teachers</th><th>Students</th><th>Actions</th></tr></thead><tbody>
<?php foreach($departments as $d): ?>
<tr><td><?= $d['id'] ?></td><td><strong><?= e($d['name']) ?></strong></td><td><?= e($d['code']) ?></td><td><?= $d['t_count'] ?></td><td><?= $d['s_count'] ?></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $d['id'] ?>" data-name="<?= e($d['name']) ?>" data-code="<?= e($d['code']) ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $d['id'] ?>" data-name="<?= e($d['name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Code *</label><input type="text" name="code" class="form-control" required placeholder="e.g. CS"></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Code *</label><input type="text" name="code" id="edit_code" class="form-control" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>? This may affect linked records.</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('edit_id').value=b.dataset.id;document.getElementById('edit_name').value=b.dataset.name;document.getElementById('edit_code').value=b.dataset.code;new bootstrap.Modal(document.getElementById('editModal')).show();});});
document.querySelectorAll('.deleteBtn').forEach(function(b){b.addEventListener('click',function(){document.getElementById('delete_id').value=b.dataset.id;document.getElementById('delete_name').textContent=b.dataset.name;new bootstrap.Modal(document.getElementById('deleteModal')).show();});});
</script>
