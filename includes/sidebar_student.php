<?php
/**
 * Student Sidebar Navigation - SSMPAS v2.0
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';
if (!isset($noti_count)) { $noti_count = is_logged_in() ? get_unread_notification_count($_SESSION['user_id'] ?? 0, $_SESSION['role'] ?? 'student') : 0; }
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar student" id="studentSidebar">
    <div class="sidebar-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>SSMPAS</span>
    </div>
    <div class="sidebar-user">
        <div class="sidebar-avatar student">S</div>
        <span class="sidebar-user-name"><?= e($_SESSION['name'] ?? 'Student') ?></span>
        <small class="sidebar-user-role">Student</small>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>
        <a href="marks.php" class="sidebar-link <?= $current_page === 'marks.php' ? 'active' : '' ?>">
            <i class="bi bi-pencil-square"></i><span>My Marks</span>
        </a>
        <a href="attendance.php" class="sidebar-link <?= $current_page === 'attendance.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i><span>My Attendance</span>
        </a>
        <a href="results.php" class="sidebar-link <?= $current_page === 'results.php' ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i><span>Results</span>
        </a>
        <a href="performance.php" class="sidebar-link <?= $current_page === 'performance.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i><span>Performance</span>
        </a>
        <a href="reports.php" class="sidebar-link <?= $current_page === 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i><span>Reports</span>
        </a>
        <a href="notifications.php" class="sidebar-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i><span>Notifications</span>
            <?php if ($noti_count > 0): ?>
                <span class="sidebar-badge"><?= $noti_count ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="sidebar-link <?= $current_page === 'profile.php' ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i><span>My Profile</span>
        </a>
        <div class="sidebar-heading"></div>
        <a href="../auth/logout.php" class="sidebar-link text-danger">
            <i class="bi bi-box-arrow-left"></i><span>Logout</span>
        </a>
    </nav>
</aside>
