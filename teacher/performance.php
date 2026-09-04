<?php
$page_title = "Performance Analytics";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";

$subject_chart = $conn->query("SELECT subject, ROUND(AVG(marks_obtained*100.0/NULLIF(total_marks,0)),1) AS avg_pct FROM marks WHERE total_marks>0 GROUP BY subject ORDER BY avg_pct DESC LIMIT 6")->fetchAll();
$s_labels = array_column($subject_chart,'subject');
$s_data = array_column($subject_chart,'avg_pct');
$grade_dist = $conn->query("SELECT grade, COUNT(*) AS cnt FROM marks GROUP BY grade ORDER BY cnt DESC")->fetchAll();
$g_labels = array_column($grade_dist,'grade');
$g_data = array_column($grade_dist,'cnt');
$att_trend = $conn->query("SELECT DATE_FORMAT(date,'%b %Y') AS month_label, ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct FROM attendance WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month_label ORDER BY MIN(date)")->fetchAll();
$t_labels = array_column($att_trend,'month_label');
$t_data = array_column($att_trend,'pct');
$student_perf = $conn->query("SELECT s.name,s.roll_no, ROUND(AVG(m.marks_obtained*100.0/NULLIF(m.total_marks,0)),1) AS avg_pct, COUNT(*) AS exams FROM marks m JOIN students s ON m.student_id=s.id GROUP BY s.id ORDER BY avg_pct DESC LIMIT 10")->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">Performance Analytics</h2>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Subject vs Average Performance</h3></div><div class="panel-body"><div class="chart-container"><canvas id="subjectChart"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Grade Distribution</h3></div><div class="panel-body"><div class="chart-container"><canvas id="gradeChart"></canvas></div></div></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Attendance Trend (6 Months)</h3></div><div class="panel-body"><div class="chart-container"><canvas id="attTrendChart"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Top Performing Students</h3></div><div class="panel-body p-0">
        <table class="data-table"><thead><tr><th>#</th><th>Student</th><th>Avg %</th><th>Exams</th></tr></thead><tbody>
        <?php foreach($student_perf as $i=>$p): ?>
        <tr><td><?= $i+1 ?></td><td><strong><?= e($p['name']) ?></strong> <small class="text-muted"><?= e($p['roll_no']) ?></small></td><td><?= $p['avg_pct'] ?>%</td><td><?= $p['exams'] ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div></div></div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
new Chart(document.getElementById('subjectChart'),{type:'bar',data:{labels:<?= json_encode($s_labels) ?>,datasets:[{label:'Avg %',data:<?= json_encode($s_data) ?>,backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'],borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
new Chart(document.getElementById('gradeChart'),{type:'doughnut',data:{labels:<?= json_encode($g_labels) ?>,datasets:[{data:<?= json_encode($g_data) ?>,backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899']}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
new Chart(document.getElementById('attTrendChart'),{type:'line',data:{labels:<?= json_encode($t_labels) ?>,datasets:[{label:'Attendance %',data:<?= json_encode($t_data) ?>,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.1)',fill:true,tension:.35,pointRadius:5,pointBackgroundColor:'#10b981'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
</script>