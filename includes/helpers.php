<?php
/**
 * SSMPAS - Helper Functions & Security Utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ============================================================
// CSRF Protection
// ============================================================

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die("Invalid request. Please refresh and try again.");
    }
}

// ============================================================
// Input Sanitization
// ============================================================

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_required($fields, $data) {
    $errors = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }
    return $errors;
}

// ============================================================
// Flash Messages
// ============================================================

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function display_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = $flash['type'];
        $msg = htmlspecialchars($flash['message']);
        $class = $type === 'success' ? 'alert-success' : ($type === 'error' ? 'alert-danger' : 'alert-info');
        echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
            . $msg
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// ============================================================
// Grade & Percentage Calculation
// ============================================================

function calculate_grade($percentage) {
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B+';
    if ($percentage >= 60) return 'B';
    if ($percentage >= 50) return 'C';
    if ($percentage >= 40) return 'D';
    return 'F';
}

function grade_color($grade) {
    switch ($grade) {
        case 'A+': case 'A': return 'success';
        case 'B+': case 'B': return 'primary';
        case 'C': return 'info';
        case 'D': return 'warning';
        default: return 'danger';
    }
}

function is_passing($marks_obtained, $total_marks, $passing_percentage = 40) {
    if ($total_marks == 0) return false;
    return ($marks_obtained / $total_marks * 100) >= $passing_percentage;
}

// ============================================================
// Activity Logging
// ============================================================

function log_activity($action, $description = '') {
    global $conn;
    if (!isset($conn) || !$conn) return;
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip, $ua]);
    } catch (Exception $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }
}

// ============================================================
// Authorization Helpers
// ============================================================

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_role() {
    return $_SESSION['role'] ?? null;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function current_user_name() {
    return $_SESSION['name'] ?? 'User';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function require_role($role) {
    require_login();
    $roles = is_array($role) ? $role : [$role];
    if (!in_array(current_user_role(), $roles)) {
        http_response_code(403);
        include __DIR__ . '/../includes/error_403.php';
        exit;
    }
}

function require_role_ajax($role) {
    require_login();
    $roles = is_array($role) ? $role : [$role];
    if (!in_array(current_user_role(), $roles)) {
        http_response_code(403);
        exit(json_encode(['error' => 'Forbidden']));
    }
}

// ============================================================
// Notification Helpers
// ============================================================

function create_notification($title, $message, $type = 'info', $target_role = 'all', $target_user_id = null) {
    global $conn;
    try {
        $created_by = current_user_id();
        $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, target_role, target_user_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $message, $type, $target_role, $target_user_id, $created_by]);
    } catch (Exception $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }
}

function get_unread_notification_count($user_id, $role) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (target_role = 'all' OR target_role = ? OR target_user_id = ?)");
        $stmt->execute([$role, $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// ============================================================
// Pagination Helper
// ============================================================

function paginate($total_records, $per_page, $current_page) {
    $total_pages = max(1, ceil($total_records / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    return [
        'total_pages'  => $total_pages,
        'current_page' => $current_page,
        'offset'       => $offset,
        'per_page'     => $per_page,
    ];
}

function render_pagination($total_pages, $current_page, $query_params = []) {
    if ($total_pages <= 1) return;
    echo '<nav><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $query_params['page'] = $i;
        $url = '?' . http_build_query($query_params);
        $active = $i == $current_page ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

// ============================================================
// Upload Validation
// ============================================================

function validate_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/webp'], $max_size = 2097152) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Upload error: " . $file['error'];
    }
    if ($file['size'] > $max_size) {
        return "File too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB.";
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed_types)) {
        return "Invalid file type. Allowed: " . implode(', ', $allowed_types);
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $blocked = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat'];
    if (in_array($ext, $blocked)) {
        return "File type not allowed.";
    }
    return true;
}

// ============================================================
// Misc Helpers
// ============================================================

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
