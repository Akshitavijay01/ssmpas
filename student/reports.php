<?php
$page_title = "My Reports";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?"); $student_id->execute([$_SESSION['user_id']]); $student_id=$student_id->fetch();
if (!$student_id) { echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Profile not linked</h3></div></div></div></div>'; exit; }
$sid=$student_id['id'];

$student = $conn->prepare("SELECT s.*, c.name AS class_name, c.section, d.name AS dept_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$student->execute([$sid]); $student=$student->fetch();

$marks = $conn->prepare("SELECT m.*, e.name AS exam_name FROM marks m LEFT JOIN exams e ON m.exam_id=e.id WHERE m.student_id=? ORDER BY m.created_at DESC"); $marks->execute([$sid]); $marks=$marks->fetchAll();
$total_obtained = array_sum(array_column($marks,'marks_obtained'));
$total_possible = array_sum(array_column($marks,'total_marks'));
$avg = $total_possible>0?round($total_obtained*100.0/$total_possible,1):'0.0';

$att = $conn->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_cnt, SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_cnt FROM attendance WHERE student_id=?"); $att->execute([$sid]); $att=$att->fetch();
$att_pct = $att['total']>0?round($att['present_cnt']*100.0/$att['total'],1):'0.0';

$results = $conn->prepare("SELECT r.*, e.name AS exam_name FROM results r LEFT JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? ORDER BY r.created_at DESC"); $results->execute([$sid]); $results=$results->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>My Academic Report</h2>
    <button class="btn btn-primary-blue" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print Report</button>
</div>

<div class="panel mb-3">
    <div class="panel-header"><h3>Student Information</h3></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6"><strong>Name:</strong> <?= e($student['name']) ?><br><strong>Email:</strong> <?= e($student['email']??'-') ?><br><strong>Roll No:</strong> <?= e($student['roll_no']??'-') ?></div>
            <div class="col-md-6"><strong>Class:</strong> <?= e(($student['class_name']??'-').($student['section']?' - '.$student['section']:'')) ?><br><strong>Department:</strong> <?= e($student['dept_name']??'-') ?><br><strong>Report Date:</strong> <?= date('M d, Y') ?></div>
        </div>
    </div>
</div>

<div class="stats-row mb-3">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-pencil-square"></i></div><div class="stat-info"><h3><?= count($marks) ?></h3><p>Exam Entries</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-graph-up"></i></div><div class="stat-info"><h3><?= $avg ?>%</h3><p>Overall Average</p></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-award"></i></div><div class="stat-info"><h3><?= calculate_grade($avg) ?></h3><p>Overall Grade</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-calendar-check"></i></div><div class="stat-info"><h3><?= $att_pct ?>%</h3><p>Attendance Rate</p></div></div>
</div>

<div class="panel mb-3"><div class="panel-header"><h3>Marks Summary</h3></div><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Total</th><th>%</th><th>Grade</th></tr></thead><tbody>
<?php foreach($marks as $m): ?>
<tr><td><?= e($m['exam_name']??'-') ?></td><td><?= e($m['subject']??'-') ?></td><td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td><td><?= $m['total_marks']>0?round($m['marks_obtained']*100.0/$m['total_marks'],1):0 ?>%</td><td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>

<div class="panel mb-3"><div class="panel-header"><h3>Attendance Summary</h3></div><div class="panel-body">
<table class="data-table" style="max-width:300px">
<tr><td>Total Working Days</td><td><strong><?= $att['total']??0 ?></strong></td></tr>
<tr><td>Days Present</td><td><strong><?= $att['present_cnt']??0 ?></strong></td></tr>
<tr><td>Days Absent</td><td><strong><?= $att['absent_cnt']??0 ?></strong></td></tr>
<tr><td>Attendance Rate</td><td><strong><?= $att_pct ?>%</strong></td></tr>
</table>
<div class="progress mt-2" style="height:16px;border-radius:8px"><div class="progress-bar bg-<?= $att_pct>=75?'success':'danger' ?>" style="width:<?= $att_pct ?>%"><?= $att_pct ?>%</div></div>
<?php if($att_pct<75): ?><p class="text-danger mt-2"><i class="bi bi-exclamation-triangle me-1"></i>Warning: Attendance below 75% may affect eligibility.</p><?php endif; ?>
</div></div>

<?php if($results): ?>
<div class="panel"><div class="panel-header"><h3>Results</h3></div><div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Exam</th><th>Obtained</th><th>Total</th><th>Percentage</th><th>Grade</th><th>Status</th></tr></thead><tbody>
<?php foreach($results as $r): ?>
<tr><td><?= e($r['exam_name']??'-') ?></td><td><?= $r['marks_obtained'] ?></td><td><?= $r['total_marks'] ?></td><td><?= $r['percentage'] ?>%</td><td><span class="badge bg-<?= grade_color($r['grade']) ?>"><?= e($r['grade']) ?></span></td><td><span class="badge bg-<?= $r['result_status']==='Pass'?'success':'danger' ?>"><?= e($r['result_status']) ?></span></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>
<?php endif; ?>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
