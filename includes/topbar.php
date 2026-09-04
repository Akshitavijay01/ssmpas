<?php
/**
 * Top Navbar Component - SSMPAS v2.0
 * Shows breadcrumbs, user info, and notification bell.
 * Include inside .main-content: <?php include '../includes/topbar.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/helpers.php';
if (!isset($noti_count)) { $noti_count = is_logged_in() ? get_unread_notification_count($_SESSION['user_id'] ?? 0, $_SESSION['role'] ?? 'admin') : 0; }
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="topbar-menu-btn" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h1 class="topbar-title"><?= e($page_title ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar-right">
        <a href="../<?= in_array(current_user_role(), ['admin','teacher','student']) ? current_user_role() : 'config' ?>/notifications.php" class="topbar-bell" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($noti_count > 0): ?>
                <span class="badge bg-danger"><?= $noti_count ?></span>
            <?php endif; ?>
        </a>
        <div class="topbar-user">
            <span class="topbar-user-name"><?= e($_SESSION['name'] ?? 'User') ?></span>
            <span class="topbar-user-role"><?= e($_SESSION['role'] ?? '') ?></span>
        </div>
        <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>
<?php include __DIR__ . '/alerts.php'; ?>
