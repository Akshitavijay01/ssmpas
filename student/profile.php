<?php
$page_title = "My Profile";
include "../config/db.php";
include "../includes/helpers.php";
require_role('student');

$user = $conn->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$_SESSION['user_id']]); $user=$user->fetch();
$student = $conn->prepare("SELECT s.*, c.name AS class_name, c.section, d.name AS dept_name FROM students s LEFT JOIN classes c ON s.class_id=c.id LEFT JOIN departments d ON s.department_id=d.id WHERE s.user_id=?");
$student->execute([$_SESSION['user_id']]); $student=$student->fetch();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf(); $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['name']??''); $email = trim($_POST['email']??''); $phone = trim($_POST['phone']??''); $address = trim($_POST['address']??'');
        if (empty($name)||empty($email)) { set_flash('error','Name and email required.'); }
        elseif (!validate_email($email)) { set_flash('error','Invalid email.'); }
        else {
            $conn->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name,$email,$_SESSION['user_id']]);
            $_SESSION['name']=$name; $_SESSION['email']=$email;
            if ($student) { $conn->prepare("UPDATE students SET name=?,email=?,phone=?,address=? WHERE user_id=?")->execute([$name,$email,$phone?:null,$address?:null,$_SESSION['user_id']]); }
            set_flash('success','Profile updated!'); log_activity('profile_update','Updated profile');
            header("Location: profile.php"); exit;
        }
    } elseif ($action === 'change_password') {
        $old=$_POST['old_password']??''; $new=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??'';
        if (empty($old)||empty($new)) { set_flash('error','All password fields required.'); }
        elseif (!password_verify($old,$user['password'])) { set_flash('error','Current password is incorrect.'); }
        elseif (strlen($new)<6) { set_flash('error','Password must be at least 6 characters.'); }
        elseif ($new!==$confirm) { set_flash('error','Passwords do not match.'); }
        else { $conn->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['user_id']]); set_flash('success','Password changed!'); log_activity('password_change','Changed password'); header("Location: profile.php"); exit; }
    } elseif ($action === 'upload_photo' && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error']===UPLOAD_ERR_OK) {
        $result = validate_upload($_FILES['profile_photo']);
        if ($result === true) {
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'],PATHINFO_EXTENSION));
            $filename = 'user_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
            $target = '../uploads/'.$filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'],$target)){
                $conn->prepare("UPDATE users SET profile_photo=? WHERE id=?")->execute([$filename,$_SESSION['user_id']]);
                set_flash('success','Photo updated!');
            } else { set_flash('error','Upload failed.'); }
        } else { set_flash('error',$result); }
        header("Location: profile.php"); exit;
    }
}
$user = $conn->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$_SESSION['user_id']]); $user=$user->fetch();
$initials = strtoupper(substr($user['name'],0,1));

// NOW output HTML
include "../includes/header.php";
include "../includes/sidebar_student.php";
include "../includes/topbar.php";
?>
<div class="layout"><div class="main-content"><div class="content">
<h2 class="mb-3">My Profile</h2>
<div class="row g-3">
    <div class="col-md-4"><div class="panel"><div class="panel-body profile-card">
        <?php if($user['profile_photo']): ?><img src="../uploads/<?= e($user['profile_photo']) ?>" class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover">
        <?php else: ?><div class="profile-avatar" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)"><?= $initials ?></div><?php endif; ?>
        <div class="profile-name"><?= e($user['name']) ?></div><div class="profile-role">Student</div>
        <p class="mt-2" style="font-size:.82rem;color:#64748b"><?= e($user['email']) ?></p>
        <?php if($student): ?>
        <p style="font-size:.82rem;color:#64748b"><strong>Roll No:</strong> <?= e($student['roll_no']??'-') ?></p>
        <p style="font-size:.82rem;color:#64748b"><strong>Class:</strong> <?= e(($student['class_name']??'-').($student['section']?' - '.$student['section']:'')) ?></p>
        <p style="font-size:.82rem;color:#64748b"><strong>Dept:</strong> <?= e($student['dept_name']??'-') ?></p>
        <?php endif; ?>
        <hr>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_photo"><?= csrf_field() ?>
            <label class="form-label" style="font-size:.82rem">Change Photo</label>
            <input type="file" name="profile_photo" class="form-control mb-2" accept="image/jpeg,image/png,image/webp">
            <button type="submit" class="btn btn-sm btn-primary-blue">Upload</button>
        </form>
    </div></div></div>
    <div class="col-md-8">
        <div class="panel mb-3"><div class="panel-header"><h3>Edit Profile</h3></div><div class="panel-body">
        <form method="POST"><input type="hidden" name="action" value="update_profile"><?= csrf_field() ?>
            <div class="mb-2"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required></div>
            <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
            <div class="mb-2"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= e($student['phone']??'') ?>" placeholder="Phone number"></div>
            <div class="mb-2"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= e($student['address']??'') ?></textarea></div>
            <button type="submit" class="btn btn-primary-blue">Save Changes</button>
        </form></div></div>
        <div class="panel"><div class="panel-header"><h3>Change Password</h3></div><div class="panel-body">
        <form method="POST"><input type="hidden" name="action" value="change_password"><?= csrf_field() ?>
            <div class="mb-2"><label class="form-label">Current Password</label><input type="password" name="old_password" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required></div>
            <div class="mb-2"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn btn-warning">Change Password</button>
        </form></div></div>
    </div>
</div>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
