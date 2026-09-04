<?php
$page_title = "Teachers";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $emp = trim($_POST['employee_id'] ?? ''); $dept = $_POST['department_id'] ?? null;
        if (empty($name) || empty($email)) { set_flash('error','Name and email required.'); }
        elseif (!validate_email($email)) { set_flash('error','Invalid email.'); }
        else {
            try {
                $conn->beginTransaction();

                // Check if user already exists with this email
                $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $user_check->execute([$email]);
                $existing_user = $user_check->fetch();

                if ($existing_user) {
                    set_flash('error', 'Email already exists. Please use a different email.');
                    $conn->rollBack();
                } else {
                    // Check employee ID uniqueness if provided
                    if (!empty($emp)) {
                        $emp_check = $conn->prepare("SELECT id FROM teachers WHERE employee_id = ?");
                        $emp_check->execute([$emp]);
                        if ($emp_check->fetch()) {
                            set_flash('error', 'Employee ID already exists.');
                            $conn->rollBack();
                            header("Location: teachers.php"); exit;
                        }
                    }

                    // Create new user account for teacher
                    $temp_password = bin2hex(random_bytes(16));
                    $hashed = password_hash($temp_password, PASSWORD_DEFAULT);
                    $stmt_user = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'teacher')");
                    $stmt_user->execute([$name, $email, $hashed]);
                    $user_id = (int)$conn->lastInsertId();

                    $conn->prepare("INSERT INTO teachers (name,email,employee_id,department_id,user_id) VALUES (?,?,?,?,?)")
                         ->execute([$name,$email,$emp ?: null,$dept ?: null,$user_id]);

                    log_activity('teacher_add',"Added teacher: $name ($email)");
                    set_flash('success','Teacher added successfully!');
                    $conn->commit();
                    header("Location: teachers.php"); exit;
                }
            } catch(PDOException $ex){
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                set_flash('error','Failed: '.$ex->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? ''; $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $emp = trim($_POST['employee_id'] ?? ''); $dept = $_POST['department_id'] ?? null;
        if (empty($name)||empty($email)) { set_flash('error','Name and email required.'); }
        else {
            try {
                // Update teacher record
                $conn->prepare("UPDATE teachers SET name=?,email=?,employee_id=?,department_id=? WHERE id=?")
                     ->execute([$name,$email,$emp ?: null,$dept ?: null,$id]);

                // Update associated user record if exists
                $teacher_stmt = $conn->prepare("SELECT user_id FROM teachers WHERE id=?");
                $teacher_stmt->execute([$id]);
                $teacher = $teacher_stmt->fetch();
                if ($teacher && $teacher['user_id']) {
                    $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?")->execute([$name, $email, $teacher['user_id']]);
                }

                log_activity('teacher_edit',"Updated teacher ID $id"); set_flash('success','Updated!');
                header("Location: teachers.php"); exit;
            } catch(PDOException $ex){ set_flash('error','Failed: '.$ex->getMessage()); }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $conn->prepare("DELETE FROM teachers WHERE id=?")->execute([$id]);
            log_activity('teacher_delete',"Deleted teacher ID $id"); set_flash('success','Deleted.');
            header("Location: teachers.php"); exit;
        } catch(PDOException $ex){ set_flash('error','Failed: '.$ex->getMessage()); }
    }
}

$search = trim($_GET['search'] ?? '');
$per_page = 10; $page = max(1,(int)($_GET['page'] ?? 1));
$where = ''; $params = [];
if($search !== ''){ $where = " WHERE t.name LIKE ? OR t.email LIKE ? OR t.employee_id LIKE ?"; $params = ["%$search%","%$search%","%$search%"]; }
$stmtC = $conn->prepare("SELECT COUNT(*) FROM teachers t $where"); $stmtC->execute($params); $total=(int)$stmtC->fetchColumn();
$pag = paginate($total,$per_page,$page);
$off = $pag['offset'];
$stmt = $conn->prepare("SELECT t.*, d.name AS dept_name FROM teachers t LEFT JOIN departments d ON t.department_id=d.id $where ORDER BY t.created_at DESC LIMIT $off,$per_page");
$stmt->execute($params); $teachers = $stmt->fetchAll();
$departments = $conn->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout">
<div class="main-content">
<div class="content">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Teachers (<?= $total ?>)</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Teacher</button>
</div>
<form method="GET" class="row g-2 mb-3"><div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search name, email, employee ID" value="<?= e($search) ?>"></div><div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="teachers.php" class="btn btn-outline-secondary">Reset</a></div></form>
<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Name</th><th>Email</th><th>Employee ID</th><th>Department</th><th>Actions</th></tr></thead><tbody>
<?php foreach($teachers as $t): ?>
<tr><td><?= $t['id'] ?></td><td><strong><?= e($t['name']) ?></strong></td><td><?= e($t['email'] ?? '-') ?></td><td><?= e($t['employee_id'] ?? '-') ?></td><td><?= e($t['dept_name'] ?? '-') ?></td>
<td class="d-flex gap-1">
<button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $t['id'] ?>" data-name="<?= e($t['name']) ?>" data-email="<?= e($t['email']) ?>" data-emp="<?= e($t['employee_id'] ?? '') ?>" data-dept="<?= $t['department_id'] ?? '' ?>"><i class="bi bi-pencil"></i></button>
<button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $t['id'] ?>" data-name="<?= e($t['name']) ?>"><i class="bi bi-trash"></i></button>
</td></tr>
<?php endforeach; if(!$teachers): ?><tr><td colspan="6" class="text-center text-muted py-4">No teachers found</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search]); ?></div><?php endif; ?>
</div>
</div><!-- /content -->
</div><!-- /main-content -->
</div><!-- /layout -->

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Employee ID</label><input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP001"></div>
    <div class="mb-2"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Employee ID</label><input type="text" name="employee_id" id="edit_emp" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Department</label><select name="department_id" id="edit_dept" class="form-select"><option value="">-- Select --</option><?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>?</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){ b.addEventListener('click',function(){ document.getElementById('edit_id').value=b.dataset.id; document.getElementById('edit_name').value=b.dataset.name; document.getElementById('edit_email').value=b.dataset.email; document.getElementById('edit_emp').value=b.dataset.emp; document.getElementById('edit_dept').value=b.dataset.dept; new bootstrap.Modal(document.getElementById('editModal')).show(); });});
document.querySelectorAll('.deleteBtn').forEach(function(b){ b.addEventListener('click',function(){ document.getElementById('delete_id').value=b.dataset.id; document.getElementById('delete_name').textContent=b.dataset.name; new bootstrap.Modal(document.getElementById('deleteModal')).show(); });});
</script>
