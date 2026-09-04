<?php
$page_title = "Students";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

// --- Handle Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $roll_no = trim($_POST['roll_no'] ?? ''); $class_id = $_POST['class_id'] ?? '';
        $dept_id = $_POST['department_id'] ?? null;
        if (empty($name) || empty($email)) {
            set_flash('error', 'Name and email are required.');
        } elseif (!validate_email($email)) {
            set_flash('error', 'Invalid email address.');
        } else {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $existing_user = $stmt->fetch();

                if ($existing_user) {
                    set_flash('error', 'Email already exists in users. Please use a different email.');
                    $conn->rollBack();
                } else {
                    $temp_password = bin2hex(random_bytes(16));
                    $hashed = password_hash($temp_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                    $stmt->execute([$name, $email, $hashed]);
                    $user_id = (int)$conn->lastInsertId();

                    $stmt = $conn->prepare("INSERT INTO students (name,email,roll_no,class_id,department_id,user_id) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$name,$email,$roll_no ?: null, $class_id ?: null, $dept_id ?: null, $user_id]);

                    log_activity('student_add', "Added student: $name ($email)");
                    set_flash('success', 'Student added successfully!');
                    $conn->commit();
                    header("Location: students.php"); exit;
                }
            } catch(PDOException $ex){
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                set_flash('error', 'Failed to add student: ' . $ex->getMessage());
            }
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? ''; $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
        $roll_no = trim($_POST['roll_no'] ?? ''); $class_id = $_POST['class_id'] ?? ''; $dept_id = $_POST['department_id'] ?? null;
        if (empty($name) || empty($email)) { set_flash('error','Name and email are required.'); }
        elseif (!validate_email($email)) { set_flash('error','Invalid email.'); }
        else {
            try {
                $stmt = $conn->prepare("UPDATE students SET name=?,email=?,roll_no=?,class_id=?,department_id=? WHERE id=?");
                $stmt->execute([$name,$email,$roll_no ?: null,$class_id ?: null,$dept_id ?: null,$id]);

                // Update associated user record if exists
                $stu_stmt = $conn->prepare("SELECT user_id FROM students WHERE id=?");
                $stu_stmt->execute([$id]);
                $stu = $stu_stmt->fetch();
                if ($stu && $stu['user_id']) {
                    $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?")->execute([$email, $stu['user_id']]);
                }

                log_activity('student_edit',"Updated student ID $id: $name");
                set_flash('success','Student updated!'); header("Location: students.php"); exit;
            } catch(PDOException $ex){ set_flash('error','Update failed: '.$ex->getMessage()); }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        try {
            $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
            $stmt->execute([$id]);
            log_activity('student_delete',"Deleted student ID $id");
            set_flash('success','Student deleted.');
            header("Location: students.php"); exit;
        } catch(PDOException $ex){ set_flash('error','Delete failed: '.$ex->getMessage()); }
    }
}

// --- Fetch filters ---
$search = trim($_GET['search'] ?? '');
$class_filter = $_GET['class_id'] ?? '';
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

// Count total
$params = []; $where = [];
if ($search !== '') { $where[] = "(s.name LIKE ? OR s.email LIKE ? OR s.roll_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($class_filter !== '') { $where[] = "s.class_id = ?"; $params[] = $class_filter; }
$where_sql = $where ? (' WHERE '.implode(' AND ',$where)) : '';
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM students s" . $where_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pag = paginate($total, $per_page, $page);

// Fetch students
$params2 = $params;
$params2[] = $pag['offset']; $params2[] = $pag['per_page'];
$stu_stmt = $conn->prepare("
    SELECT s.*, c.name AS class_name, c.section, d.name AS dept_name
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN departments d ON s.department_id = d.id
    $where_sql
    ORDER BY s.created_at DESC
    LIMIT ?, ?
");
foreach($params2 as $i=>$v){ $stu_stmt->bindValue($i+1,$v,$i >= count($params) ? PDO::PARAM_INT : PDO::PARAM_STR); }
$stu_stmt->execute();
$students = $stu_stmt->fetchAll();

// For dropdowns
$classes = $conn->query("SELECT id,name,section FROM classes ORDER BY name")->fetchAll();
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
    <h2 class="mb-0">Students (<?= $total ?>)</h2>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i>Add Student</button>
</div>

<!-- Filters -->
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search name, email, roll no" value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
        <select name="class_id" class="form-select">
            <option value="">All Classes</option>
            <?php foreach($classes as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $class_filter == $c['id'] ? 'selected':'' ?>><?= e($c['name'].' - '.$c['section']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary-blue"><i class="bi bi-search me-1"></i>Search</button>
        <a href="students.php" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="panel">
    <div class="panel-body p-0">
        <div class="table-responsive">
        <table class="data-table"><thead><tr>
            <th>#</th><th>Name</th><th>Roll No</th><th>Email</th><th>Class</th><th>Dept</th><th>Actions</th>
        </tr></thead><tbody>
        <?php foreach($students as $s): ?>
        <tr>
            <td><?= $s['id'] ?></td>
            <td><strong><?= e($s['name']) ?></strong></td>
            <td><?= e($s['roll_no'] ?? '-') ?></td>
            <td><?= e($s['email'] ?? '-') ?></td>
            <td><?= e($s['class_name'] ?? '-') ?><?= $s['section'] ? ' - '.e($s['section']) : '' ?></td>
            <td><?= e($s['dept_name'] ?? '-') ?></td>
            <td class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary editBtn"
                    data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>" data-email="<?= e($s['email']) ?>"
                    data-roll="<?= e($s['roll_no'] ?? '') ?>" data-class="<?= $s['class_id'] ?? '' ?>" data-dept="<?= $s['department_id'] ?? '' ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger deleteBtn" data-id="<?= $s['id'] ?>" data-name="<?= e($s['name']) ?>">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$students): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No students found</td></tr>
        <?php endif; ?>
        </tbody></table>
        </div>
    </div>
    <?php if($pag['total_pages']>1): ?>
    <div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search,'class_id'=>$class_filter]); ?></div>
    <?php endif; ?>
</div>

</div><!-- /content -->
</div><!-- /main-content -->
</div><!-- /layout -->

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST">
<input type="hidden" name="action" value="add"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Roll Number</label><input type="text" name="roll_no" class="form-control" placeholder="e.g. CS2025001"></div>
    <div class="mb-2"><label class="form-label">Class</label>
        <select name="class_id" class="form-select"><option value="">-- Select --</option>
            <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Department</label>
        <select name="department_id" class="form-select"><option value="">-- Select --</option>
            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Save</button></div>
</form></div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST">
<input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <div class="mb-2"><label class="form-label">Full Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Roll Number</label><input type="text" name="roll_no" id="edit_roll" class="form-control"></div>
    <div class="mb-2"><label class="form-label">Class</label>
        <select name="class_id" id="edit_class" class="form-select"><option value="">-- Select --</option>
            <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name'].' - '.$c['section']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Department</label>
        <select name="department_id" id="edit_dept" class="form-select"><option value="">-- Select --</option>
            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Update</button></div>
</form></div></div></div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST">
<input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="delete_id"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Delete Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Delete <strong id="delete_name"></strong>? This will also remove related marks and attendance.</p></div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete</button></div>
</form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
document.querySelectorAll('.editBtn').forEach(function(b){
    b.addEventListener('click', function(){
        document.getElementById('edit_id').value=b.dataset.id;
        document.getElementById('edit_name').value=b.dataset.name;
        document.getElementById('edit_email').value=b.dataset.email;
        document.getElementById('edit_roll').value=b.dataset.roll;
        document.getElementById('edit_class').value=b.dataset.class;
        document.getElementById('edit_dept').value=b.dataset.dept;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
});
document.querySelectorAll('.deleteBtn').forEach(function(b){
    b.addEventListener('click', function(){
        document.getElementById('delete_id').value=b.dataset.id;
        document.getElementById('delete_name').textContent=b.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
</script>
