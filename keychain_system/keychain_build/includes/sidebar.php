<?php
// includes/sidebar.php
// Usage: include with $current_page set
if (!isset($current_page)) $current_page = '';
$user = getCurrentUser();
$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
$isAdminUser = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'KeyChain Studio' ?> — KeyChain Studio</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔑</text></svg>">
</head>
<body>

<div class="layout">
  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <a href="<?= SITE_URL ?>">
        <div class="sidebar-logo-text">🔑 KeyChain Studio</div>
        <div class="sidebar-logo-sub"><?= $isAdminUser ? 'Admin Panel' : 'Customer Portal' ?></div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <?php if ($isAdminUser): ?>
      <div class="nav-section">
        <div class="nav-section-label">Overview</div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-item <?= $current_page === 'admin_dash' ? 'active' : '' ?>">
          <span class="nav-icon">📊</span> Dashboard
        </a>
      </div>
      <div class="nav-section">
        <div class="nav-section-label">Management</div>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="nav-item <?= $current_page === 'admin_orders' ? 'active' : '' ?>">
          <span class="nav-icon">📦</span> Orders
        </a>
        <a href="<?= SITE_URL ?>/admin/designs.php" class="nav-item <?= $current_page === 'admin_designs' ? 'active' : '' ?>">
          <span class="nav-icon">🎨</span> Designs
        </a>
        <a href="<?= SITE_URL ?>/admin/customers.php" class="nav-item <?= $current_page === 'admin_customers' ? 'active' : '' ?>">
          <span class="nav-icon">👥</span> Customers
        </a>
      </div>
      <?php else: ?>
      <div class="nav-section">
        <div class="nav-section-label">Menu</div>
        <a href="<?= SITE_URL ?>/dashboard.php" class="nav-item <?= $current_page === 'dash' ? 'active' : '' ?>">
          <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="<?= SITE_URL ?>/pages/gallery.php" class="nav-item <?= $current_page === 'gallery' ? 'active' : '' ?>">
          <span class="nav-icon">🎨</span> Browse Designs
        </a>
        <a href="<?= SITE_URL ?>/pages/customize.php" class="nav-item <?= $current_page === 'customize' ? 'active' : '' ?>">
          <span class="nav-icon">✏️</span> Customize & Order
        </a>
        <a href="<?= SITE_URL ?>/pages/my-orders.php" class="nav-item <?= $current_page === 'my_orders' ? 'active' : '' ?>">
          <span class="nav-icon">📦</span> My Orders
        </a>
        <a href="<?= SITE_URL ?>/pages/track.php" class="nav-item <?= $current_page === 'track' ? 'active' : '' ?>">
          <span class="nav-icon">🔍</span> Track Order
        </a>
      </div>
      <?php endif; ?>

      <div class="nav-section">
        <div class="nav-section-label">Account</div>
        <a href="<?= SITE_URL ?>/pages/profile.php" class="nav-item <?= $current_page === 'profile' ? 'active' : '' ?>">
          <span class="nav-icon">👤</span> Profile
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="nav-item">
          <span class="nav-icon">🚪</span> Logout
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar">
          <?php if ($user['avatar'] ?? false): ?>
            <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="">
          <?php else: ?>
            <?= $initial ?>
          <?php endif; ?>
        </div>
        <div>
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
          <div class="sidebar-user-role"><?= htmlspecialchars($user['role'] ?? '') ?></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- Mobile Sidebar Toggle -->
  <button class="sidebar-toggle" id="sidebarToggle">☰</button>

  <!-- Main Content -->
  <div class="main-content">
    <div class="topbar">
      <h2 class="topbar-title"><?= $page_title ?? '' ?></h2>
      <div class="topbar-actions">
        <span style="font-size:13px;color:var(--light);">Hi, <?= htmlspecialchars($user['name'] ?? '') ?> 👋</span>
        <?php if (!$isAdminUser): ?>
        <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary btn-sm">+ New Order</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="page-content">
      <?php showFlash(); ?>
