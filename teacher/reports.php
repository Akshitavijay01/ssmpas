<?php
$page_title = "Reports";
include "../config/db.php";
include "../includes/helpers.php";
require_role('teacher');
include "../includes/header.php";
include "../includes/sidebar_teacher.php";
include "../includes/topbar.php";

$overview_students = (int)$conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$overview_present = (int)$conn->query("SELECT COUNT(*) FROM attendance WHERE status='Present'")->fetchColumn();
$overview_absent = (int)$conn->query("SELECT COUNT(*) FROM attendance WHERE status='Absent'")->fetchColumn();
$overview_pass = (int)$conn->query("SELECT COUNT(*) FROM results WHERE result_status='Pass'")->fetchColumn();
$overview_fail = (int)$conn->query("SELECT COUNT(*) FROM results WHERE result_status='Fail'")->fetchColumn();
$avg_marks = round((float)($conn->query("SELECT AVG(marks_obtained*100.0/NULLIF(total_marks,0)) FROM marks")->fetchColumn() ?? 0),1);
$class_perf = $conn->query("SELECT c.name AS class_label, c.section, ROUND(AVG(m.marks_obtained*100.0/NULLIF(m.total_marks,0)),1) AS avg_pct FROM marks m LEFT JOIN students s ON m.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id GROUP BY c.id ORDER BY avg_pct DESC LIMIT 6")->fetchAll();
$att_by_class = $conn->query("SELECT c.name AS class_label, c.section, ROUND(COUNT(CASE WHEN a.status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct FROM attendance a LEFT JOIN students s ON a.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY c.id HAVING COUNT(*)>0 ORDER BY pct DESC LIMIT 6")->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">Reports</h2>
<div class="stats-row mb-4">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people-fill"></i></div><div class="stat-info"><h3><?= $overview_students ?></h3><p>Students</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div class="stat-info"><h3><?= $overview_present ?></h3><p>Present (total)</p></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-x-circle"></i></div><div class="stat-info"><h3><?= $overview_absent ?></h3><p>Absent (total)</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-trophy"></i></div><div class="stat-info"><h3><?= $avg_marks ?>%</h3><p>Avg Marks</p></div></div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Class Performance (avg %)</h3></div><div class="panel-body"><div class="chart-container"><canvas id="classPerfChart2"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel"><div class="panel-header"><h3>Attendance by Class (30d)</h3></div><div class="panel-body"><div class="chart-container"><canvas id="attClassChart2"></canvas></div></div></div></div>
</div>
<div class="panel mb-3"><div class="panel-header d-flex justify-content-between align-items-center"><h3><i class="bi bi-person-lines-fill me-2"></i>Student Performance</h3><button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
<div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Student</th><th>Roll No</th><th>Class</th><th>Avg %</th><th>Grade</th></tr></thead><tbody>
<?php $perf=$conn->query("SELECT s.name,s.roll_no,c.name AS class_name,c.section, ROUND(AVG(m.marks_obtained*100.0/NULLIF(m.total_marks,0)),1) AS avg_pct FROM marks m JOIN students s ON m.student_id=s.id LEFT JOIN classes c ON s.class_id=c.id GROUP BY s.id ORDER BY avg_pct DESC LIMIT 20")->fetchAll();
foreach($perf as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['roll_no']) ?></td><td><?= e(($p['class_name']??'-').($p['section']?'-'.$p['section']:'')) ?></td><td><strong><?= $p['avg_pct']??'-' ?>%</strong></td><td><span class="badge bg-<?= grade_color(calculate_grade($p['avg_pct']??0)) ?>"><?= calculate_grade($p['avg_pct']??0) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<div class="panel"><div class="panel-header d-flex justify-content-between align-items-center"><h3><i class="bi bi-calendar3 me-2"></i>Attendance Report (per student)</h3><button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button></div>
<div class="panel-body p-0"><div class="table-responsive">
<table class="data-table"><thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Total</th><th>Attendance %</th></tr></thead><tbody>
<?php $att=$conn->query("SELECT s.name, COUNT(CASE WHEN a.status='Present' THEN 1 END) AS present_cnt, COUNT(CASE WHEN a.status='Absent' THEN 1 END) AS absent_cnt, COUNT(*) AS total_cnt, ROUND(COUNT(CASE WHEN a.status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct FROM attendance a JOIN students s ON a.student_id=s.id GROUP BY s.id ORDER BY pct DESC LIMIT 20")->fetchAll();
foreach($att as $a): ?><tr><td><?= e($a['name']) ?></td><td><?= $a['present_cnt'] ?></td><td><?= $a['absent_cnt'] ?></td><td><?= $a['total_cnt'] ?></td><td><div class="d-flex align-items-center gap-2"><div class="progress" style="width:80px;height:8px"><div class="progress-bar bg-success" style="width:<?= $a['pct']??0 ?>%"></div></div><?= $a['pct']??0 ?>%</div></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
new Chart(document.getElementById('classPerfChart2'),{type:'bar',data:{labels:<?= json_encode(array_map(function($c){return ($c['class_label']??'No class').($c['section']?' - '.$c['section']:'');},$class_perf)) ?>,datasets:[{label:'Avg %',data:<?= json_encode(array_column($class_perf,'avg_pct')) ?>,backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'],borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
new Chart(document.getElementById('attClassChart2'),{type:'bar',data:{labels:<?= json_encode(array_map(function($c){return ($c['class_label']??'No class').($c['section']?'-'.$c['section']:'' );},$att_by_class)) ?>,datasets:[{label:'Attendance %',data:<?= json_encode(array_column($att_by_class,'pct')) ?>,backgroundColor:'#10b981',borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}},plugins:{legend:{display:false}}}});
</script>