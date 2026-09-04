<?php
$page_title = "Settings";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    foreach(['school_name','academic_year','attendance_warning_threshold','max_marks','passing_percentage'] as $key){
        $val = trim($_POST[$key] ?? '');
        if ($key === 'attendance_warning_threshold' || $key === 'max_marks' || $key === 'passing_percentage') {
            if (!empty($val) && (!is_numeric($val) || $val < 0)) { set_flash('error', 'Invalid value for '.ucfirst($key)); continue; }
        }
        if ($key === 'passing_percentage' && $val !== '' && ($val < 0 || $val > 100)) { set_flash('error','Passing percentage must be between 0 and 100.'); continue; }
        $stmt = $conn->prepare("INSERT INTO settings (setting_key,setting_value,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)");
        $stmt->execute([$key,$val,$_SESSION['user_id']]);
    }
    log_activity('settings_update','Updated system settings');
    set_flash('success','Settings saved successfully!');
    header("Location: settings.php"); exit;
}
$settings_rows = $conn->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach($settings_rows as $s){ $settings[$s['setting_key']] = $s['setting_value']; }

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">System Settings</h2>
<div class="panel"><div class="panel-body">
<form method="POST">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Institution Name</label>
    <input type="text" name="school_name" class="form-control" value="<?= e($settings['school_name']??'') ?>" required>
</div>
<div class="mb-3">
    <label class="form-label">Academic Year</label>
    <input type="text" name="academic_year" class="form-control" value="<?= e($settings['academic_year']??'') ?>" placeholder="e.g. 2025-2026">
</div>
<div class="mb-3">
    <label class="form-label">Attendance Warning Threshold (%)</label>
    <input type="number" name="attendance_warning_threshold" class="form-control" value="<?= e($settings['attendance_warning_threshold']??'75') ?>" min="0" max="100">
    <small class="text-muted">Students below this percentage will receive a warning</small>
</div>
<div class="mb-3">
    <label class="form-label">Default Maximum Marks per Exam</label>
    <input type="number" name="max_marks" class="form-control" value="<?= e($settings['max_marks']??'100') ?>" min="1">
</div>
<div class="mb-3">
    <label class="form-label">Passing Percentage (%)</label>
    <input type="number" name="passing_percentage" class="form-control" value="<?= e($settings['passing_percentage']??'40') ?>" min="0" max="100">
</div>
<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary-blue"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>
</form>
</div></div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
