<?php
$page_title = "Profile";
include "../config/db.php";
include "../includes/helpers.php";
require_role('admin');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['name']??''); $email = trim($_POST['email']??'');
        if (empty($name)||empty($email)) { set_flash('error','Name and email required.'); }
        elseif (!validate_email($email)) { set_flash('error','Invalid email.'); }
        else {
            try {
                $conn->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email,$_SESSION['user_id']]);
                $_SESSION['name'] = $name; $_SESSION['email'] = $email;
                log_activity('profile_update','Updated profile'); set_flash('success','Profile updated!');
                header("Location: profile.php"); exit;
            } catch(PDOException $ex){ set_flash('error','Update failed.'); }
        }
    } elseif ($action === 'change_password') {
        $old = $_POST['old_password']??''; $new = $_POST['new_password']??''; $confirm = $_POST['confirm_password']??'';
        if (empty($old)||empty($new)) { set_flash('error','All password fields required.'); }
        elseif (!password_verify($old,$user['password'])) { set_flash('error','Current password is incorrect.'); }
        elseif (strlen($new) < 6) { set_flash('error','New password must be at least 6 characters.'); }
        elseif ($new !== $confirm) { set_flash('error','Passwords do not match.'); }
        else {
            $hashed = password_hash($new,PASSWORD_DEFAULT);
            $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed,$_SESSION['user_id']]);
            log_activity('password_change','Changed password'); set_flash('success','Password changed!');
            header("Location: profile.php"); exit;
        }
    } elseif ($action === 'upload_photo' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error']===UPLOAD_ERR_OK) {
        $result = validate_upload($_FILES['profile_photo']);
        if ($result === true) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'],PATHINFO_EXTENSION));
            $filename = 'user_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
            $target = '../uploads/'.$filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'],$target)){
                $conn->prepare("UPDATE users SET profile_photo=? WHERE id=?")->execute([$filename,$_SESSION['user_id']]);
                log_activity('photo_upload','Uploaded profile photo'); set_flash('success','Photo updated!');
            } else { set_flash('error','Upload failed.'); }
        } else { set_flash('error',$result); }
        header("Location: profile.php"); exit;
    }
}

// Refresh user
$user = $conn->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();
$initials = strtoupper(substr($user['name'],0,1));
$recent_logs = $conn->prepare("SELECT * FROM activity_logs WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$recent_logs->execute([$_SESSION['user_id']]);
$recent_logs = $recent_logs->fetchAll();

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_admin.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Profile</h2>
<div class="row g-3">
    <div class="col-md-4">
        <div class="panel">
            <div class="panel-body profile-card">
                <?php if($user['profile_photo']): ?>
                <img src="../uploads/<?= e($user['profile_photo']) ?>" alt="Profile" class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover">
                <?php else: ?>
                <div class="profile-avatar"><?= $initials ?></div>
                <?php endif; ?>
                <div class="profile-name"><?= e($user['name']) ?></div>
                <div class="profile-role"><?= e(ucfirst($user['role'])) ?></div>
                <p class="mt-2" style="font-size:.82rem;color:#64748b"><?= e($user['email']) ?></p>
                <p style="font-size:.78rem;color:#94a3b8">Joined: <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                <?php if($user['last_login']): ?><p style="font-size:.78rem;color:#94a3b8">Last login: <?= date('M d, Y h:i A', strtotime($user['last_login'])) ?></p><?php endif; ?>

                <hr>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_photo"><?= csrf_field() ?>
                    <label class="form-label" style="font-size:.82rem">Change Photo</label>
                    <input type="file" name="profile_photo" class="form-control mb-2" accept="image/jpeg,image/png,image/webp">
                    <button type="submit" class="btn btn-sm btn-primary-blue">Upload</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="panel mb-3">
            <div class="panel-header"><h3>Edit Profile</h3></div>
            <div class="panel-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile"><?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required></div>
                <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
                <button type="submit" class="btn btn-primary-blue">Save Changes</button>
            </form>
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-header"><h3>Change Password</h3></div>
            <div class="panel-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password"><?= csrf_field() ?>
                <div class="mb-2"><label class="form-label">Current Password</label><input type="password" name="old_password" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
            </div>
        </div>
        <div class="panel">
            <div class="panel-header"><h3>Recent Activity</h3></div>
            <div class="panel-body p-0">
                <table class="data-table"><thead><tr><th>Action</th><th>Description</th><th>Time</th></tr></thead><tbody>
                <?php foreach($recent_logs as $log): ?>
                <tr><td><?= e($log['action']) ?></td><td><?= e($log['description']) ?></td><td><?= time_ago($log['created_at']) ?></td></tr>
                <?php endforeach; if(!$recent_logs): ?><tr><td colspan="3" class="text-center text-muted">No activity yet</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </div>
    </div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
