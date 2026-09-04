<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once "../config/db.php";
require_once "../includes/helpers.php";

if (is_logged_in()) {
    redirect_by_role();
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    verify_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['name']     = $user['name'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['login_at'] = time();

                // Update last login
                $stmt2 = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt2->execute([$user['id']]);

                log_activity('login', 'User logged in successfully');

                redirect_by_role();
                exit;
            } else {
                $error = "Invalid email or password.";
                log_activity('login_failed', "Failed login attempt for: $email");
            }
        } catch (PDOException $ex) {
            error_log("Login error: " . $ex->getMessage());
            $error = "A system error occurred. Please try again.";
        }
    }
}

function redirect_by_role() {
    switch ($_SESSION['role']) {
        case 'admin':  header("Location: ../admin/dashboard.php"); break;
        case 'teacher': header("Location: ../teacher/dashboard.php"); break;
        case 'student': header("Location: ../student/dashboard.php"); break;
        default: header("Location: login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SSMPAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <h1>SSMPAS</h1>
            <p>Student Management & Performance Analytics</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= sanitize_input($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="email" id="email" class="form-control"
                       placeholder="Enter your email" required
                       value="<?= sanitize_input($email ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" name="login" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="login-footer">
            <p class="mb-1">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
