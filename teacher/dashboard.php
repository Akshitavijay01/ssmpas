<?php
$page_title = "Teacher Dashboard";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";

$total_students = (int)$conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$teacher_id = $_SESSION['user_id'];
$count_marks = $conn->prepare("SELECT COUNT(*) FROM marks WHERE entered_by=?"); $count_marks->execute([$teacher_id]); $total_marks = (int)$count_marks->fetchColumn();
$recent_marks = $conn->prepare("SELECT m.*, s.name AS student_name, s.roll_no FROM marks m JOIN students s ON m.student_id=s.id WHERE m.entered_by=? ORDER BY m.created_at DESC LIMIT 5");
$recent_marks->execute([$teacher_id]); $recent_marks = $recent_marks->fetchAll();
$present_today = $conn->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Present'")->fetchColumn();
$absent_today = $conn->query("SELECT COUNT(*) FROM attendance WHERE date=CURDATE() AND status='Absent'")->fetchColumn();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-4">Teacher Dashboard</h2>
<div class="stats-row mb-4">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people"></i></div><div class="stat-info"><h3><?= $total_students ?></h3><p>Total Students</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-pencil-square"></i></div><div class="stat-info"><h3><?= $total_marks ?></h3><p>Marks Entered</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?= $present_today ?></h3><p>Present Today</p></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle"></i></div><div class="stat-info"><h3><?= $absent_today ?></h3><p>Absent Today</p></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="panel"><div class="panel-header d-flex justify-content-between align-items-center"><h3>Recent Marks Entered</h3><a href="marks.php" class="btn btn-sm btn-primary-blue">View All</a></div><div class="panel-body p-0">
        <table class="data-table"><thead><tr><th>Student</th><th>Subject</th><th>Marks</th><th>Total</th><th>Grade</th></tr></thead><tbody>
        <?php foreach($recent_marks as $m): ?>
        <tr><td><?= e($m['student_name']) ?></td><td><?= e($m['subject']??'-') ?></td><td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td><td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td></tr>
        <?php endforeach; if(!$recent_marks): ?><tr><td colspan="5" class="text-center text-muted py-3">No marks entered yet</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
    <div class="col-lg-6">
        <div class="panel"><div class="panel-header"><h3>Quick Actions</h3></div>
        <div class="panel-body d-grid gap-2">
            <a href="marks.php" class="btn btn-primary-blue text-start"><i class="bi bi-pencil-square me-2"></i>Enter Marks</a>
            <a href="attendance.php" class="btn btn-success text-start"><i class="bi bi-calendar-check me-2"></i>Mark Attendance</a>
            <a href="students.php" class="btn btn-info text-start"><i class="bi bi-people me-2"></i>View Students</a>
            <a href="performance.php" class="btn btn-warning text-start"><i class="bi bi-bar-chart me-2"></i>Performance Analytics</a>
            <a href="reports.php" class="btn btn-secondary text-start"><i class="bi bi-file-earmark me-2"></i>Generate Reports</a>
        </div></div>
    </div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
