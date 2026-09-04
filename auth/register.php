<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once "../config/db.php";
require_once "../includes/helpers.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!validate_email($email)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists. Please try another.";
                $conn->rollBack();
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
                $stmt->execute([$name, $email, $hashed]);
                $user_id = (int)$conn->lastInsertId();

                $stmt_profile = $conn->prepare("INSERT INTO students (user_id, name, email) VALUES (?, ?, ?)");
                $stmt_profile->execute([$user_id, $name, $email]);

                $conn->commit();
                log_activity('register', "New student registered: $email");
                $success = "Account created successfully! You can now log in.";
            }
        } catch (PDOException $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Register error: " . $ex->getMessage());
            $error = "An error occurred during registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SSMPAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h1>Create Account</h1>
            <p>Join SSMPAS Academic System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= sanitize_input($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= sanitize_input($success) ?> <a href="login.php">Login now</a></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter full name" required
                       value="<?= sanitize_input($name ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Enter email" required
                       value="<?= sanitize_input($email ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                <small class="text-muted">At least 6 characters</small>
            </div>
            <div class="mb-3">
                <button type="submit" name="register" class="btn btn-login w-100">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
            </div>
        </form>

        <div class="login-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
