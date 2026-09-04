<?php
$page_title = "My Results";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";

$student_id = $conn->prepare("SELECT id FROM students WHERE user_id=?"); $student_id->execute([$_SESSION['user_id']]); $student_id=$student_id->fetch();
if (!$student_id) { echo '<div class="layout"><div class="main-content"><div class="content"><div class="empty-state"><i class="bi bi-person-x"></i><h3>Profile not linked</h3></div></div></div></div>'; exit; }
$sid=$student_id['id'];

$results = $conn->prepare("SELECT r.*, e.name AS exam_name, e.type AS exam_type FROM results r LEFT JOIN exams e ON r.exam_id=e.id WHERE r.student_id=? AND r.is_published=1 ORDER BY r.percentage DESC");
$results->execute([$sid]); $results=$results->fetchAll();
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Results</h2>
<?php if(!$results): ?>
<div class="empty-state py-5"><i class="bi bi-trophy"></i><h3>No results published yet</h3><p>Results will appear here once exams are evaluated.</p></div>
<?php endif; ?>
<div class="row g-3">
<?php foreach($results as $r): ?>
<div class="col-md-6 col-lg-4">
    <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center">
            <h3><?= e($r['exam_name']??'Result') ?></h3>
            <span class="badge bg-<?= $r['result_status']==='Pass'?'success':'danger' ?>"><?= e($r['result_status']) ?></span>
        </div>
        <div class="panel-body">
            <div class="d-flex gap-3 mb-3">
                <div class="text-center flex-fill">
                    <div style="font-size:1.8rem;font-weight:700;color:<?= $r['result_status']==='Pass'?'#10b981':'#ef4444' ?>"><?= $r['percentage'] ?>%</div>
                    <small class="text-muted">Overall Score</small>
                </div>
                <div class="text-center flex-fill">
                    <span class="badge bg-<?= grade_color($r['grade']) ?>" style="font-size:1rem;padding:6px 12px"><?= e($r['grade']) ?></span><br>
                    <small class="text-muted">Grade</small>
                </div>
            </div>
            <table class="data-table" style="font-size:.82rem"><tr><td>Marks Obtained</td><td><strong><?= $r['marks_obtained'] ?></strong></td></tr>
            <tr><td>Total Marks</td><td><strong><?= $r['total_marks'] ?></strong></td></tr>
            <tr><td>Type</td><td><?= e($r['exam_type']??'-') ?></td></tr>
            </table>
            <?php if($r['is_published']): ?><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Published <?= date('M d, Y', strtotime($r['published_at'])) ?></span><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
