<?php
/**
 * Admin Sidebar Navigation - SSMPAS v2.0
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';
if (!isset($noti_count)) { $noti_count = is_logged_in() ? get_unread_notification_count($_SESSION['user_id'] ?? 0, $_SESSION['role'] ?? 'admin') : 0; }
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>SSMPAS</span>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-avatar">A</div>
        <span class="sidebar-user-name"><?= e($_SESSION['name'] ?? 'Admin') ?></span>
        <small class="sidebar-user-role">Administrator</small>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
        <div class="sidebar-heading">Management</div>
        <a href="students.php" class="sidebar-link <?= $current_page === 'students.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i><span>Students</span>
        </a>
        <a href="teachers.php" class="sidebar-link <?= $current_page === 'teachers.php' ? 'active' : '' ?>">
            <i class="bi bi-person-workspace"></i><span>Teachers</span>
        </a>
        <a href="departments.php" class="sidebar-link <?= $current_page === 'departments.php' ? 'active' : '' ?>">
            <i class="bi bi-building"></i><span>Departments</span>
        </a>
        <a href="classes.php" class="sidebar-link <?= $current_page === 'classes.php' ? 'active' : '' ?>">
            <i class="bi bi-door-open"></i><span>Classes</span>
        </a>
        <a href="subjects.php" class="sidebar-link <?= $current_page === 'subjects.php' ? 'active' : '' ?>">
            <i class="bi bi-book"></i><span>Subjects</span>
        </a>
        <div class="sidebar-heading">Academic</div>
        <a href="exams.php" class="sidebar-link <?= $current_page === 'exams.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i><span>Exams</span>
        </a>
        <a href="marks.php" class="sidebar-link <?= $current_page === 'marks.php' ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i><span>Marks</span>
        </a>
        <a href="attendance.php" class="sidebar-link <?= $current_page === 'attendance.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i><span>Attendance</span>
        </a>
        <a href="results.php" class="sidebar-link <?= $current_page === 'results.php' ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i><span>Results</span>
        </a>
        <div class="sidebar-heading">System</div>
        <a href="notifications.php" class="sidebar-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i><span>Notifications</span>
            <?php if ($noti_count > 0): ?>
                <span class="sidebar-badge"><?= $noti_count ?></span>
            <?php endif; ?>
        </a>
        <a href="reports.php" class="sidebar-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span>
        </a>
        <a href="activity_logs.php" class="sidebar-link <?= $current_page === 'activity_logs.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i><span>Activity Logs</span>
        </a>
        <a href="settings.php" class="sidebar-link <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>
        <a href="profile.php" class="sidebar-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i><span>Profile</span>
        </a>
        <div class="sidebar-heading"></div>
        <a href="../auth/logout.php" class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-left"></i><span>Logout</span>
        </a>
    </nav>
</aside>
