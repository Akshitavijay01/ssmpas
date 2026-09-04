<?php
$page_title = "Dashboard";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?");
$student_id->execute([$_SESSION['user_id']]); $student_id = $student_id->fetch();
if (!$student_id) {
    echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Student profile not linked</h3><p>Contact administrator to link your account.</p></div></div></div></div>';
    include "../includes/footer.php"; exit;
}
$sid = $student_id['id'];

$student = $conn->prepare("SELECT s.*, c.name AS class_name, c.section, d.name AS dept_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN departments d ON s.department_id=d.id WHERE s.id=?");
$student->execute([$sid]); $student = $student->fetch();

$total_att = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=?"); $total_att->execute([$sid]); $total_att=(int)$total_att->fetchColumn();
$present_att = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE student_id=? AND status='Present'"); $present_att->execute([$sid]); $present_att=(int)$present_att->fetchColumn();
$att_rate = $total_att > 0 ? round($present_att*100.0/$total_att,1) : '0.0';

$marks_stats = $conn->prepare("SELECT ROUND(AVG(marks_obtained*100.0/NULLIF(total_marks,0)),1) AS avg_pct, COUNT(*) AS total_marks FROM marks WHERE student_id=?");
$marks_stats->execute([$sid]); $marks_stats = $marks_stats->fetch();

$latest_results = $conn->prepare("SELECT r.*, e.name AS exam_name FROM results r LEFT JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? ORDER BY r.created_at DESC LIMIT 3");
$latest_results->execute([$sid]); $latest_results = $latest_results->fetchAll();

$recent_marks = $conn->prepare("SELECT * FROM marks WHERE student_id=? ORDER BY created_at DESC LIMIT 5");
$recent_marks->execute([$sid]); $recent_marks = $recent_marks->fetchAll();

$noti = $conn->prepare("SELECT * FROM notifications WHERE (target_role='all' OR target_role='student' OR target_user_id=?) AND is_read=0 ORDER BY created_at DESC LIMIT 5");
$noti->execute([$_SESSION['user_id']]); $noti=$noti->fetchAll();

$att_trend = $conn->prepare("SELECT DATE_FORMAT(date,'%b') AS month_label, ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct FROM attendance WHERE student_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_label ORDER BY MIN(date)");
$att_trend->execute([$sid]); $att_trend=$att_trend->fetchAll();
$t_labels=array_column($att_trend,'month_label'); $t_data=array_column($att_trend,'pct');
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-2">Welcome, <?= e($_SESSION['name']) ?>!</h2>
<p class="text-muted mb-4"><?= e(($student['class_name']??'').($student['section']?' - '.$student['section']:'')) ?> | Roll No: <?= e($student['roll_no']??'-') ?> | Dept: <?= e($student['dept_name']??'-') ?></p>

<div class="stats-row mb-4">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div><div class="stat-info"><h3><?= $att_rate ?>%</h3><p>Attendance</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-graph-up"></i></div><div class="stat-info"><h3><?= $marks_stats['avg_pct']??'0.0' ?>%</h3><p>Average Marks</p></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-trophy"></i></div><div class="stat-info"><h3><?= e($latest_results[0]['grade']??'-') ?></h3><p>Latest Grade</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-star"></i></div><div class="stat-info"><h3><?= e($latest_results[0]['result_status']??'-') ?></h3><p>Latest Result</p></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>My Attendance Trend</h3></div><div class="panel-body"><div class="chart-container"><canvas id="attChart"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Recent Marks</h3></div><div class="panel-body p-0">
        <table class="data-table"><thead><tr><th>Subject</th><th>Marks</th><th>Total</th><th>Grade</th></tr></thead><tbody>
        <?php foreach($recent_marks as $m): ?>
        <tr><td><?= e($m['subject']??'-') ?></td><td><?= $m['marks_obtained'] ?></td><td><?= $m['total_marks'] ?></td><td><span class="badge bg-<?= grade_color($m['grade']) ?>"><?= e($m['grade']) ?></span></td></tr>
        <?php endforeach; if(!$recent_marks): ?><tr><td colspan="4" class="text-center text-muted py-3">No marks yet</td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Notifications</h3></div><div class="panel-body">
        <?php foreach($noti as $n): ?>
        <div class="border-bottom pb-2 mb-2"><span class="badge bg-<?= $n['type']==='info'?'primary':($n['type']==='warning'?'warning':($n['type']==='success'?'success':'danger')) ?> me-1"><?= e(ucfirst($n['type'])) ?></span><strong><?= e($n['title']) ?></strong><p class="mb-0" style="font-size:.82rem;color:#64748b"><?= e($n['message']) ?></p><small class="text-muted"><?= time_ago($n['created_at']) ?></small></div>
        <?php endforeach; if(!$noti): ?><p class="text-muted text-center py-3">No unread notifications</p><?php endif; ?>
        </div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Quick Actions</h3></div>
        <div class="panel-body d-grid gap-2">
            <a href="marks.php" class="btn btn-primary-blue text-start"><i class="bi bi-pencil-square me-2"></i>View My Marks</a>
            <a href="attendance.php" class="btn btn-success text-start"><i class="bi bi-calendar-check me-2"></i>View My Attendance</a>
            <a href="results.php" class="btn btn-info text-start"><i class="bi bi-trophy me-2"></i>View My Results</a>
            <a href="reports.php" class="btn btn-secondary text-start"><i class="bi bi-file-earmark me-2"></i>My Reports</a>
            <a href="profile.php" class="btn btn-outline-secondary text-start"><i class="bi bi-person-circle me-2"></i>My Profile</a>
        </div></div>
    </div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
new Chart(document.getElementById('attChart'),{type:'line',data:{labels:<?= json_encode($t_labels) ?>,datasets:[{label:'Attendance %',data:<?= json_encode($t_data) ?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.15)',fill:true,tension:.35,pointRadius:5,pointBackgroundColor:'#10b981'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
</script>
