<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$designs = $db->query("SELECT * FROM designs WHERE is_active=1 ORDER BY id LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KeyChain Studio — Personalized Photo Keychains</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔑</text></svg>">
<style>
.features { background: var(--white); padding: 80px 32px; }
.features-inner { max-width: 1100px; margin: 0 auto; }
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; margin-top: 48px; }
.feature-item { text-align: center; padding: 32px 24px; border-radius: var(--radius-lg); border: 1px solid var(--border); transition: all var(--transition); }
.feature-item:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.feature-icon { font-size: 40px; margin-bottom: 16px; }
.feature-title { font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 10px; }
.feature-desc { font-size: 14px; color: var(--light); }
.gallery-section { padding: 80px 32px; max-width: 1200px; margin: 0 auto; }
.gallery-header { text-align: center; margin-bottom: 48px; }
.gallery-header h2 { margin-bottom: 12px; }
.cta-section { background: var(--charcoal); color: #fff; padding: 80px 32px; text-align: center; }
.cta-section h2 { color: #fff; margin-bottom: 16px; }
.cta-section p { color: rgba(255,255,255,0.6); margin-bottom: 36px; font-size: 16px; }
.footer-bar { background: var(--white); border-top: 1px solid var(--border); padding: 24px 32px; display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: var(--light); }
@media (max-width: 768px) {
  .features-grid { grid-template-columns: 1fr; }
  .gallery-section { padding: 48px 20px; }
  .features { padding: 48px 20px; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="public-nav">
  <a href="<?= SITE_URL ?>" class="nav-logo">🔑 KeyChain Studio</a>
  <div class="nav-links" id="navLinks">
    <a href="<?= SITE_URL ?>" class="nav-link active">Home</a>
    <a href="<?= SITE_URL ?>/pages/gallery.php" class="nav-link">Designs</a>
    <?php if (isLoggedIn()): ?>
      <a href="<?= isAdmin() ? SITE_URL.'/admin/dashboard.php' : SITE_URL.'/dashboard.php' ?>" class="nav-link">Dashboard</a>
      <a href="<?= SITE_URL ?>/logout.php" class="btn btn-outline btn-sm">Logout</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/login.php" class="nav-link">Login</a>
      <a href="<?= SITE_URL ?>/login.php?tab=register" class="btn btn-primary btn-sm">Get Started</a>
    <?php endif; ?>
  </div>
  <div class="nav-mobile-toggle" id="mobileNavToggle">
    <span></span><span></span><span></span>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-eyebrow">Handcrafted with love</div>
    <h1>Your Memories,<br><em>Beautifully</em> Preserved</h1>
    <p class="hero-desc">Turn your favorite photos into stunning personalized keychains. Choose a design, upload your photo, add a message, and we'll create something magical.</p>
    <div class="hero-actions">
      <a href="<?= SITE_URL ?>/pages/gallery.php" class="btn btn-primary btn-lg">Browse Designs</a>
      <a href="<?= isLoggedIn() ? SITE_URL.'/pages/customize.php' : SITE_URL.'/login.php' ?>" class="btn btn-outline btn-lg">Order Now</a>
    </div>
  </div>
  <div class="hero-img">
    <div class="hero-blob">🔑</div>
  </div>
</section>

<!-- Features -->
<section class="features">
  <div class="features-inner">
    <div style="text-align:center">
      <h2>How It Works</h2>
      <p>Three simple steps to your perfect keychain</p>
    </div>
    <div class="features-grid">
      <div class="feature-item">
        <div class="feature-icon">📸</div>
        <div class="feature-title">Upload Your Photo</div>
        <p class="feature-desc">Share your cherished memories, portraits, or any image you love.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon">🎨</div>
        <div class="feature-title">Pick a Design</div>
        <p class="feature-desc">Choose from our collection of shapes, styles, and materials.</p>
      </div>
      <div class="feature-item">
        <div class="feature-icon">✨</div>
        <div class="feature-title">Receive Your Order</div>
        <p class="feature-desc">We craft and deliver your personalized keychain with care.</p>
      </div>
    </div>
  </div>
</section>

<!-- Design Gallery Preview -->
<section class="gallery-section">
  <div class="gallery-header">
    <h2>Popular Designs</h2>
    <p>Crafted to hold your most precious moments</p>
  </div>
  <div class="designs-grid">
    <?php
    $shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
    $shapeBg = ['circle'=>'shape-bg-circle','rectangle'=>'shape-bg-rectangle','heart'=>'shape-bg-heart','star'=>'shape-bg-star','oval'=>'shape-bg-oval','custom'=>'shape-bg-custom'];
    foreach ($designs as $d):
      $shape = $d['shape'];
      $bg = $shapeBg[$shape] ?? 'shape-bg-circle';
      $emoji = $shapeEmoji[$shape] ?? '🔑';
    ?>
    <div class="design-card">
      <div class="design-card-img <?= $bg ?>">
        <?php if ($d['image'] && file_exists(UPLOAD_PATH . 'designs/' . $d['image'])): ?>
          <img src="<?= SITE_URL ?>/uploads/designs/<?= htmlspecialchars($d['image']) ?>" alt="">
        <?php else: ?>
          <span class="shape-emoji"><?= $emoji ?></span>
        <?php endif; ?>
      </div>
      <div class="design-card-body">
        <div class="design-card-name"><?= htmlspecialchars($d['name']) ?></div>
        <div class="design-card-desc"><?= htmlspecialchars($d['description']) ?></div>
        <div class="flex" style="align-items:center;justify-content:space-between;margin-top:12px;">
          <div class="design-card-price"><?= formatPrice($d['base_price']) ?></div>
          <a href="<?= SITE_URL ?>/pages/customize.php?design_id=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Order</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center" style="margin-top:40px;">
    <a href="<?= SITE_URL ?>/pages/gallery.php" class="btn btn-outline btn-lg">View All Designs</a>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <h2>Ready to Create Yours?</h2>
  <p>Join hundreds of happy customers who've preserved their memories</p>
  <a href="<?= SITE_URL ?>/login.php?tab=register" class="btn btn-rose btn-lg">Start Now — It's Free</a>
</section>

<!-- Footer -->
<footer class="footer-bar">
  <span>© <?= date('Y') ?> KeyChain Studio. All rights reserved.</span>
  <span>Made with ❤️ for memories</span>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
