<?php
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>SSMPAS | Smart Academic Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{font-family:'Inter',sans-serif}
        body{margin:0;min-height:100vh;background:#0f172a;color:#fff;overflow-x:hidden}
        .hero{position:relative;min-height:100vh;display:flex;align-items:center;padding:60px 0 40px;overflow:hidden}
        .hero-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 20% 50%,#1e3a5f 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,#334155 0%,transparent 50%),#0f172a;z-index:0}
        .hero-content{position:relative;z-index:1;text-align:center;max-width:800px;margin:0 auto}
        .hero-badge{display:inline-block;background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.3);padding:6px 16px;border-radius:50px;font-size:.82rem;letter-spacing:.04em;margin-bottom:18px}
        .hero h1{font-size:3.6rem;font-weight:800;line-height:1.15;margin-bottom:10px}
        .hero h1 span{background:linear-gradient(135deg,#3b82f6,#06b6d4);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .hero .lead{color:#94a3b8;font-size:1.08rem;max-width:600px;margin:0 auto 28px}
        .btn-cta{padding:13px 30px;border-radius:10px;font-weight:600;font-size:.95rem;text-decoration:none;display:inline-block;transition:.2s;border:none}
        .btn-primary-cta{background:#3b82f6;color:#fff}
        .btn-primary-cta:hover{background:#2563eb;color:#fff;transform:translateY(-1px)}
        .btn-outline-cta{background:transparent;color:#e2e8f0;border:1.5px solid #334155}
        .btn-outline-cta:hover{border-color:#475569;color:#fff}
        .features{padding:60px 0 40px;background:#f8fafc;color:#1e293b}
        .feature-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;height:100%;text-align:center;transition:.2s}
        .feature-card:hover{border-color:#cbd5e1;box-shadow:0 4px 20px rgba(15,23,42,.08);transform:translateY(-2px)}
        .feature-icon{width:50px;height:50px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;margin-bottom:14px}
        .feature-card h4{font-size:1rem;font-weight:600;margin-bottom:6px}
        .feature-card p{font-size:.85rem;color:#64748b;margin:0}
        .roles{padding:40px 0 60px;background:#f1f5f9;color:#1e293b}
        .role-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;text-align:center}
        .role-head{padding:16px;color:#fff;font-weight:600;font-size:.95rem}
        .role-head i{font-size:1.1rem;margin-right:6px}
        .role-body{padding:16px}
        .role-body ul{list-style:none;padding:0;margin:0;text-align:left;font-size:.85rem;color:#475569}
        .role-body li{padding:4px 0}
        .role-body li i{color:#22c55e;margin-right:6px}
        .landing-footer{background:#0f172a;color:#64748b;text-align:center;padding:20px 0;font-size:.82rem;border-top:1px solid #1e293b}
        @media(max-width:576px){.hero h1{font-size:2.2rem}}
    </style>
</head>
<body>
<section class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-content">
        <span class="hero-badge"><i class="bi bi-mortarboard me-1"></i> Final-Year Project &middot; SSMPAS v2.0</span>
        <h1><span>SSMPAS</span></h1>
        <p class="lead">A comprehensive academic management platform for students, faculty, and administration - unifying attendance, examinations, and performance analytics into one secure system.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="../auth/login.php" class="btn-cta btn-primary-cta">Sign In &nbsp;<i class="bi bi-arrow-right"></i></a>
            <a href="../auth/register.php" class="btn-cta btn-outline-cta">Create Account</a>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="text-center mb-4">
            <p class="text-uppercase small fw-bold text-primary mb-1">Core Capabilities</p>
            <h2 class="fw-bold">Everything your institution needs</h2>
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#3b82f6"><i class="bi bi-people-fill"></i></div>
                    <h4>Student & Teacher Management</h4>
                    <p>Centralized records with departments, classes, and profiles.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#10b981"><i class="bi bi-calendar-check"></i></div>
                    <h4>Attendance Tracking</h4>
                    <p>Daily attendance with percentages, trends, and warnings.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#f59e0b"><i class="bi bi-bar-chart-line"></i></div>
                    <h4>Performance Analytics</h4>
                    <p>Charts and insights across subjects and examinations.</p>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:#8b5cf6"><i class="bi bi-shield-check"></i></div>
                    <h4>Secure & Role-Based</h4>
                    <p>Prepared statements, CSRF, and strict authorization.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="roles">
    <div class="container">
        <div class="text-center mb-4">
            <p class="text-uppercase small fw-bold text-primary mb-1">Access tailored to your role</p>
            <h2 class="fw-bold">Designed for every user</h2>
        </div>
        <div class="row g-3 justify-content-center">
            <div class="col-md-4">
                <div class="role-card">
                    <div class="role-head" style="background:#2563eb"><i class="bi bi-shield-lock"></i> Administrator</div>
                    <div class="role-body">
                        <ul>
                            <li><i class="bi bi-check2"></i> Manage all users & departments</li>
                            <li><i class="bi bi-check2"></i> System-wide reports & analytics</li>
                            <li><i class="bi bi-check2"></i> Activity logs & settings</li>
                            <li><i class="bi bi-check2"></i> Notifications to all roles</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-card">
                    <div class="role-head" style="background:#059669"><i class="bi bi-person-workspace"></i> Teacher</div>
                    <div class="role-body">
                        <ul>
                            <li><i class="bi bi-check2"></i> Manage marks & attendance</li>
                            <li><i class="bi bi-check2"></i> Class performance insights</li>
                            <li><i class="bi bi-check2"></i> Generate filtered reports</li>
                            <li><i class="bi bi-check2"></i> Student profiles & history</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-card">
                    <div class="role-head" style="background:#7c3aed"><i class="bi bi-mortarboard"></i> Student</div>
                    <div class="role-body">
                        <ul>
                            <li><i class="bi bi-check2"></i> Personal marks & attendance</li>
                            <li><i class="bi bi-check2"></i> Results & performance trends</li>
                            <li><i class="bi bi-check2"></i> Printable reports</li>
                            <li><i class="bi bi-check2"></i> Notifications & profile update</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="landing-footer">
    <div class="container">
        &copy; <?= date('Y') ?> SSMPAS &middot; Student/Staff Management &amp; Performance Analysis System
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
