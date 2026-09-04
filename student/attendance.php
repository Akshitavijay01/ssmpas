<?php
$page_title = "My Attendance";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?"); $student_id->execute([$_SESSION['user_id']]); $student_id=$student_id->fetch();
if (!$student_id) { echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Profile not linked</h3></div></div></div></div>'; exit; }
$sid=$student_id['id'];

$total = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=?"); $total->execute([$sid]); $total=(int)$total->fetchColumn();
$present = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='Present'"); $present->execute([$sid]); $present=(int)$present->fetchColumn();
$absent = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='Absent'"); $absent->execute([$sid]); $absent=(int)$absent->fetchColumn();
$late = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='Late'"); $late->execute([$sid]); $late=(int)$late->fetchColumn();
$rate = $total>0 ? round($present*100.0/$total,1) : '0.0';
$low_warnings = $rate < 75 && $total > 5;
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Attendance</h2>
<?php if($low_warnings): ?>
<div class="alert alert-danger d-flex align-items-center" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>Your attendance is below 75%! Please attend classes regularly.</div>
<?php endif; ?>
<div class="stats-row mb-3">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-calendar-week"></i></div><div class="stat-info"><h3><?= $total ?></h3><p>Total Days</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?= $present ?></h3><p>Present</p></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle"></i></div><div class="stat-info"><h3><?= $absent ?></h3><p>Absent</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-clock-history"></i></div><div class="stat-info"><h3><?= $late ?></h3><p>Late</p></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-percent"></i></div><div class="stat-info"><h3><?= $rate ?>%</h3><p>Attendance Rate</p></div></div>
</div>
<div class="panel"><div class="panel-body">
    <div class="progress" style="height:20px;border-radius:10px"><div class="progress-bar bg-success" style="width:<?= $rate ?>%"><?= $rate ?>%</div></div>
</div></div>
<div class="panel"><div class="panel-header"><h3>Records</h3></div><div class="panel-body p-0">
<table class="data-table"><thead><tr><th>#</th><th>Date</th><th>Status</th><th>Remarks</th></tr></thead><tbody>
<?php $att=$conn->prepare("SELECT * FROM attendance WHERE student_id=? ORDER BY date DESC LIMIT 50"); $att->execute([$sid]); foreach($att->fetchAll() as $a): ?>
<tr><td><?= $a['id'] ?></td><td><strong><?= e($a['date']) ?></strong></td><td><span class="badge bg-<?= $a['status']==='Present'?'success':($a['status']==='Absent'?'danger':'warning') ?>"><?= e($a['status']) ?></span></td><td><?= e($a['remarks']??'-') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
