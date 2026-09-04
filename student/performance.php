<?php
$page_title = "My Performance";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?"); $student_id->execute([$_SESSION['user_id']]); $student_id=$student_id->fetch();
if (!$student_id) { echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Profile not linked</h3></div></div></div></div>'; exit; }
$sid=$student_id['id'];

$subject_data = $conn->prepare("SELECT subject, ROUND(AVG(marks_obtained*100.0/NULLIF(total_marks,0)),1) AS avg_pct FROM marks WHERE student_id=? GROUP BY subject ORDER BY avg_pct DESC");
$subject_data->execute([$sid]); $subject_data=$subject_data->fetchAll();
$s_labels=array_column($subject_data,'subject'); $s_data=array_column($subject_data,'avg_pct');

$grade_data = $conn->prepare("SELECT grade, COUNT(*) AS cnt FROM marks WHERE student_id=? GROUP BY grade");
$grade_data->execute([$sid]); $grade_data=$grade_data->fetchAll();
$g_labels=array_column($grade_data,'grade'); $g_data=array_column($grade_data,'cnt');

$att_trend = $conn->prepare("SELECT DATE_FORMAT(date,'%b') AS label, ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct FROM attendance WHERE student_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY label ORDER BY MIN(date)");
$att_trend->execute([$sid]); $att_trend=$att_trend->fetchAll();
$t_labels=array_column($att_trend,'label'); $t_data=array_column($att_trend,'pct');

$total_avg = $conn->prepare("SELECT ROUND(AVG(marks_obtained*100.0/NULLIF(total_marks,0)),1) AS overall FROM marks WHERE student_id=?"); $total_avg->execute([$sid]); $overall = $total_avg->fetchColumn();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Performance <?php if($overall): ?><span class="badge bg-<?= grade_color(calculate_grade($overall)) ?>" style="font-size:.82rem;margin-left:8px"><?= calculate_grade($overall) ?> - <?= $overall ?>%</span><?php endif; ?></h2>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Subject Performance</h3></div><div class="panel-body"><div class="chart-container"><canvas id="subjectChart"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Grade Distribution</h3></div><div class="panel-body"><div class="chart-container"><canvas id="gradeChart"></canvas></div></div></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-12"><div class="panel"><div class="panel-header"><h3>Attendance Trend</h3></div><div class="panel-body"><div class="chart-container"><canvas id="attChart"></canvas></div></div></div></div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
new Chart(document.getElementById('subjectChart'),{type:'bar',data:{labels:<?= json_encode($s_labels) ?>,datasets:[{label:'Avg %',data:<?= json_encode($s_data) ?>,backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'],borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
new Chart(document.getElementById('gradeChart'),{type:'doughnut',data:{labels:<?= json_encode($g_labels) ?>,datasets:[{data:<?= json_encode($g_data) ?>,backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('attChart'),{type:'line',data:{labels:<?= json_encode($t_labels) ?>,datasets:[{label:'Attendance %',data:<?= json_encode($t_data) ?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.1)',fill:true,tension:.35,pointRadius:4,pointBackgroundColor:'#10b981'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
</script>
