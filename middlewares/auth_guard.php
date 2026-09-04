<?php
/**
 * Authentication Guard - SSMPAS v2.0
 * Role-based access control middleware
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

require_once __DIR__ . '/../includes/helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

function allowRole($role) {
    $roles = is_array($role) ? $role : [$role];
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        http_response_code(403);
        include __DIR__ . '/../includes/error_403.php';
        exit();
    }
}
