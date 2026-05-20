<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db   = getDB();
$uid  = $_SESSION['user_id'];
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name    = sanitize($_POST['name'] ?? '');
        $phone   = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        if (!$name) {
            setFlash('error', 'Name is required.');
        } else {
            // Avatar upload
            $avatar = $user['avatar'];
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $up = uploadFile($_FILES['avatar'], 'photos');
                if (isset($up['success'])) $avatar = $up['path'];
                else setFlash('error', $up['error']);
            }
            $db->prepare("UPDATE users SET name=?,phone=?,address=?,avatar=? WHERE id=?")->execute([$name,$phone,$address,$avatar,$uid]);
            $_SESSION['name'] = $name;
            setFlash('success', 'Profile updated successfully! ✓');
            header('Location: ' . SITE_URL . '/pages/profile.php');
            exit;
        }
    }

    if ($action === 'change_password') {
        $old  = $_POST['old_password'] ?? '';
        $new  = $_POST['new_password'] ?? '';
        $conf = $_POST['confirm_password'] ?? '';
        if (!$old || !$new || !$conf) {
            setFlash('error', 'All password fields are required.');
        } elseif (!password_verify($old, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 6) {
            setFlash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $conf) {
            setFlash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            setFlash('success', 'Password changed successfully! 🔒');
            header('Location: ' . SITE_URL . '/pages/profile.php');
            exit;
        }
    }
}

$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));

$page_title   = 'My Profile';
$current_page = 'profile';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Profile Header -->
<div class="profile-header mb-24">
  <div class="profile-avatar-lg">
    <?php if ($user['avatar']): ?>
      <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="">
    <?php else: ?>
      <?= $initial ?>
    <?php endif; ?>
  </div>
  <div>
    <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
    <p class="profile-email"><?= htmlspecialchars($user['email']) ?></p>
    <span class="profile-badge"><?= ucfirst($user['role']) ?></span>
  </div>
  <div style="margin-left:auto;text-align:right;">
    <p class="text-sm text-muted">Member since</p>
    <p class="fw-600"><?= date('M Y', strtotime($user['created_at'])) ?></p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;" class="profile-grid">

  <!-- Update Profile -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Edit Profile</h3></div>
    <div class="card-body">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
          <label class="form-label">Profile Photo</label>
          <div class="upload-zone" style="padding:20px;">
            <input type="file" name="avatar" accept="image/*">
            <div class="upload-icon" style="font-size:28px;">🖼️</div>
            <div class="upload-text" style="font-size:13px;">Click to change photo</div>
            <div class="upload-sub">JPG or PNG, max 2MB</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:0.6;cursor:not-allowed;">
          <small class="text-muted text-sm">Email cannot be changed.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09xx xxx xxxx">
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea name="address" class="form-control" rows="3" placeholder="Your default shipping address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-full">Save Changes</button>
      </form>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
          <label class="form-label">Current Password *</label>
          <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
        </div>
        <div class="form-group">
          <label class="form-label">New Password *</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password *</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
        </div>
        <div class="alert alert-info" style="margin-bottom:16px;font-size:13px;">
          🔒 Use a strong password with letters and numbers.
        </div>
        <button type="submit" class="btn btn-primary w-full">Update Password</button>
      </form>
    </div>
  </div>

</div>

<style>@media(max-width:768px){.profile-grid{grid-template-columns:1fr !important;}.profile-header{flex-wrap:wrap;gap:16px;}}</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
