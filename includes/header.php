<?php
/**
 * Main Layout Header - SSMPAS v2.0
 * Include with: <?php include '../includes/header.php'; ?>
 * Expects $page_title variable to be set before including.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/helpers.php';
$_base = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '..' : ((strpos($_SERVER['PHP_SELF'], '/teacher/') !== false) ? '..' : ((strpos($_SERVER['PHP_SELF'], '/student/') !== false) ? '..' : '.'));
$noti_count = is_logged_in() ? get_unread_notification_count($_SESSION['user_id'], $_SESSION['role']) : 0;

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'SSMPAS') ?> | SSMPAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_base ?>/assets/css/dashboard.css">
</head>
<body>
<?php
