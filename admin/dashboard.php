<?php
$page_title = "Dashboard";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";

// --- Stats ---
$total_students   = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_teachers   = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$total_classes    = $conn->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$total_subjects   = $conn->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$total_exams      = $conn->query("SELECT COUNT(*) FROM exams")->fetchColumn();

$attendance_rate = $conn->query("
    SELECT ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1)
    FROM attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn() ?: '0.0';

$avg_performance = $conn->query("SELECT ROUND(AVG(marks_obtained*100.0/total_marks),1) FROM marks WHERE total_marks > 0")->fetchColumn() ?: '0.0';

$recent_students = $conn->query("SELECT name, created_at FROM students ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_teachers = $conn->query("SELECT name, created_at FROM teachers ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_activities = $conn->query("SELECT al.*, u.name AS user_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();

// Subject-wise marks (Chart)
$subject_chart = $conn->query("
    SELECT subject, ROUND(AVG(marks_obtained*100.0/NULLIF(total_marks,0)),1) AS avg_pct
    FROM marks WHERE total_marks > 0 GROUP BY subject ORDER BY avg_pct DESC LIMIT 6
")->fetchAll();
$s_labels = array_column($subject_chart, 'subject');
$s_data   = array_column($subject_chart, 'avg_pct');

// Monthly attendance trend (last 6 months)
$attendance_trend = $conn->query("
    SELECT DATE_FORMAT(date,'%b %Y') AS month_label,
           ROUND(COUNT(CASE WHEN status='Present' THEN 1 END)*100.0/NULLIF(COUNT(*),0),1) AS pct
    FROM attendance WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month_label ORDER BY MIN(date)
")->fetchAll();
$t_labels = array_column($attendance_trend, 'month_label');
$t_data   = array_column($attendance_trend, 'pct');
?>
<div class="layout">
<div class="main-content">
<div class="content">
<h2 class="mb-4">Admin Dashboard</h2>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-people-fill"></i></div><div class="stat-info"><h3><?= $total_students ?></h3><p>Students</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-person-workspace"></i></div><div class="stat-info"><h3><?= $total_teachers ?></h3><p>Teachers</p></div></div>
    <div class="stat-card"><div class="stat-icon cyan"><i class="bi bi-door-open"></i></div><div class="stat-info"><h3><?= $total_classes ?></h3><p>Classes</p></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="bi bi-book"></i></div><div class="stat-info"><h3><?= $total_subjects ?></h3><p>Subjects</p></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="bi bi-journal-text"></i></div><div class="stat-info"><h3><?= $total_exams ?></h3><p>Exams</p></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="bi bi-calendar-check"></i></div><div class="stat-info"><h3><?= $attendance_rate ?>%</h3><p>Attendance (30d)</p></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="bi bi-graph-up-arrow"></i></div><div class="stat-info"><h3><?= $avg_performance ?>%</h3><p>Avg Performance</p></div></div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h3><i class="bi bi-bar-chart me-2"></i>Subject Performance</h3></div>
            <div class="panel-body"><div class="chart-container"><canvas id="subjectChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h3><i class="bi bi-calendar3 me-2"></i>Attendance Trend (6 Months)</h3></div>
            <div class="panel-body"><div class="chart-container"><canvas id="attendanceChart"></canvas></div></div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h3><i class="bi bi-people me-2"></i>Recent Students</h3><a href="students.php" class="btn btn-sm btn-primary-blue">View All</a></div>
            <div class="panel-body p-0">
                <table class="data-table"><thead><tr><th>Name</th><th>Joined</th></tr></thead><tbody>
                <?php foreach($recent_students as $r): ?>
                <tr><td><?= e($r['name']) ?></td><td><?= time_ago($r['created_at']) ?></td></tr>
                <?php endforeach; if(!$recent_students): ?>
                <tr><td colspan="2" class="text-center text-muted py-3">No students yet</td></tr>
                <?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel">
            <div class="panel-header"><h3><i class="bi bi-clock-history me-2"></i>Recent Activity</h3><a href="activity_logs.php" class="btn btn-sm btn-primary-blue">View All</a></div>
            <div class="panel-body p-0">
                <table class="data-table"><thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead><tbody>
                <?php foreach($recent_activities as $a): ?>
                <tr>
                    <td><?= e($a['user_name'] ?? 'System') ?></td>
                    <td><?= e($a['action']) ?></td>
                    <td><?= time_ago($a['created_at']) ?></td>
                </tr>
                <?php endforeach; if(!$recent_activities): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">No recent activity</td></tr>
                <?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>
</div>

</div><!-- /content -->
</div><!-- /main-content -->
</div><!-- /layout -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('subjectChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($s_labels) ?>, datasets: [{ label:'Avg %', data: <?= json_encode($s_data) ?>, backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4'], borderRadius:8 }] },
    options: { responsive:true, maintainAspectRatio:false, scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}, plugins:{legend:{display:false}} }
});
new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: { labels: <?= json_encode($t_labels) ?>, datasets: [{ label:'Attendance %', data: <?= json_encode($t_data) ?>, borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.1)', fill:true, tension:.35, pointRadius:5, pointBackgroundColor:'#10b981' }] },
    options: { responsive:true, maintainAspectRatio:false, scales:{y:{beginAtZero:true,max:100,grid:{color:'#f1f5f9'}},x:{grid:{display:false}}}, plugins:{legend:{display:false}} }
});
</script>
<script src="../assets/js/sidebar.js"></script>
