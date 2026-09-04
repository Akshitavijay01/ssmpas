<?php
$page_title = "Attendance";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');

$students = $conn->query("SELECT id,name,roll_no FROM students WHERE is_active=1 ORDER BY name")->fetchAll();
$classes = $conn->query("SELECT id,name,section FROM classes ORDER BY name")->fetchAll();
$subjects = $conn->query("SELECT id,name,code FROM subjects WHERE is_active=1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'mark_single') {
        $student_id = $_POST['student_id']; $status = $_POST['status']; $date = $_POST['date'] ?? date('Y-m-d'); $subject_id = $_POST['subject_id']??null;
        if (empty($student_id)||empty($status)) { set_flash('error','Student and status required.'); }
        else {
            try {
                $check = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND date=? AND (subject_id=? OR subject_id IS NULL)");
                $check->execute([$student_id,$date,$subject_id]);
                if ($check->fetch()) { set_flash('error','Attendance already marked for this student on this date.'); }
                else {
                    $conn->prepare("INSERT INTO attendance (student_id,status,date,subject_id,marked_by) VALUES (?,?,?,?,?)")->execute([$student_id,$status,$date,$subject_id?:null,$_SESSION['user_id']]);
                    set_flash('success','Attendance marked!'); log_activity('att_mark',"Marked attendance for student ID $student_id: $status on $date");
                }
            } catch(PDOException $ex){ set_flash('error','Failed: '.$ex->getMessage()); }
        }
        header("Location: attendance.php"); exit;
    } elseif ($action === 'mark_bulk') {
        $date = $_POST['date'] ?? date('Y-m-d'); $subject_id = $_POST['subject_id']??null;
        $marked = 0; $skipped = 0;
        foreach($_POST['attendance'] ?? [] as $stu_id => $status){
            $check = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND date=? AND (subject_id=? OR subject_id IS NULL)");
            $check->execute([$stu_id,$date,$subject_id]);
            if (!$check->fetch()) {
                $conn->prepare("INSERT INTO attendance (student_id,status,date,subject_id,marked_by) VALUES (?,?,?,?,?)")->execute([$stu_id,$status,$date,$subject_id?:null,$_SESSION['user_id']]);
                $marked++;
            } else { $skipped++; }
        }
        set_flash('success',"Marked: $marked students. Skipped (already marked): $skipped."); log_activity('att_bulk',"Bulk attendance marked: $marked entries on $date");
        header("Location: attendance.php"); exit;
    }
}

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";

$search = trim($_GET['search'] ?? ''); $date_filter = $_GET['date'] ?? ''; $status_filter = $_GET['status'] ?? '';
$where = []; $params = [];
if ($search !== '') { $where[] = "(s.name LIKE ? OR s.roll_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($date_filter) { $where[] = "a.date=?"; $params[] = $date_filter; }
if ($status_filter) { $where[] = "a.status=?"; $params[] = $status_filter; }
$where_sql = $where ? ' WHERE '.implode(' AND ',$where) : '';
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM attendance a JOIN students s ON a.student_id=s.id $where_sql"); $count_stmt->execute($params); $total=(int)$count_stmt->fetchColumn();
$per_page=15; $page=max(1,(int)($_GET['page']??1)); $pag=paginate($total,$per_page,$page);
$params2=array_merge($params, [$pag['offset'],$pag['per_page']]);
$att = $conn->prepare("SELECT a.*, s.name AS student_name, s.roll_no FROM attendance a JOIN students s ON a.student_id=s.id $where_sql ORDER BY a.date DESC LIMIT ?, ?");
foreach($params2 as $i=>$v){ $att->bindValue($i+1,$v,$i>=count($params)?PDO::PARAM_INT:PDO::PARAM_STR); }
$att->execute(); $attendance = $att->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">Attendance</h2>
<div class="d-flex gap-2 mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#singleModal"><i class="bi bi-person-check me-1"></i>Mark One Student</button>
    <button class="btn btn-primary-blue" data-bs-toggle="modal" data-bs-target="#bulkModal"><i class="bi bi-people me-1"></i>Mark All (Bulk)</button>
</div>

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search student" value="<?= e($search) ?>"></div>
    <div class="col-md-2"><input type="date" name="date" class="form-control" value="<?= e($date_filter) ?>"></div>
    <div class="col-md-2"><select name="status" class="form-select"><option value="">All Status</option><option value="Present" <?= $status_filter==='Present'?'selected':'' ?>>Present</option><option value="Absent" <?= $status_filter==='Absent'?'selected':'' ?>>Absent</option><option value="Late" <?= $status_filter==='Late'?'selected':'' ?>>Late</option></select></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary-blue">Search</button> <a href="attendance.php" class="btn btn-outline-secondary">Reset</a></div>
</form>

<div class="panel"><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>#</th><th>Student</th><th>Roll No</th><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
<?php foreach($attendance as $a): ?>
<tr><td><?= $a['id'] ?></td><td><strong><?= e($a['student_name']) ?></strong></td><td><?= e($a['roll_no']) ?></td><td><?= e($a['date']) ?></td><td><span class="badge bg-<?= $a['status']==='Present'?'success':($a['status']==='Absent'?'danger':'warning') ?>"><?= e($a['status']) ?></span></td><td><?= e($a['remarks']??'-') ?></td></tr>
<?php endforeach; if(!$attendance): ?><tr><td colspan="6" class="text-center text-muted py-4">No attendance records</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php if($pag['total_pages']>1): ?><div class="panel-footer"><?php render_pagination($pag['total_pages'],$pag['current_page'],['search'=>$search,'date'=>$date_filter,'status'=>$status_filter]); ?></div><?php endif; ?>
</div>
</div></div></div>

<!-- Single Modal -->
<div class="modal fade" id="singleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="mark_single"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Mark Attendance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<div class="mb-2"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="">-- Select --</option><?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name'].' ('.$s['roll_no'].')') ?></option><?php endforeach; ?></select></div>
<div class="mb-2"><label class="form-label">Status *</label><select name="status" class="form-select" required><option value="Present">Present</option><option value="Absent">Absent</option><option value="Late">Late</option></select></div>
<div class="mb-2"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
<div class="mb-2"><label class="form-label">Subject (optional)</label><select name="subject_id" class="form-select"><option value="">-- Select --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Mark</button></div>
</form></div></div></div>

<!-- Bulk Modal -->
<div class="modal fade" id="bulkModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
<form method="POST"><input type="hidden" name="action" value="mark_bulk"><?= csrf_field() ?>
<div class="modal-header"><h5 class="modal-title">Bulk Attendance - <?= date('M d, Y') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" style="max-height:300px;overflow-y:auto">
<div class="mb-2"><label class="form-label">Date *</label><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
<div class="mb-2"><label class="form-label">Subject (optional)</label><select name="subject_id" class="form-select"><option value="">-- Select --</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
<table class="data-table" style="font-size:.82rem">
<thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Late</th></tr></thead>
<tbody>
<?php foreach($students as $s): ?>
<tr><td><?= e($s['name'].' ('.$s['roll_no'].')') ?></td>
<td><input type="radio" name="attendance[<?= $s['id'] ?>]" value="Present" checked></td>
<td><input type="radio" name="attendance[<?= $s['id'] ?>]" value="Absent"></td>
<td><input type="radio" name="attendance[<?= $s['id'] ?>]" value="Late"></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-blue">Submit Bulk Attendance</button></div>
</form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
