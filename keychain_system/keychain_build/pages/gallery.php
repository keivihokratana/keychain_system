<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db = getDB();
$designs = $db->query("SELECT * FROM designs WHERE is_active=1 ORDER BY id")->fetchAll();

$page_title   = 'Browse Designs';
$current_page = 'gallery';
include __DIR__ . '/../includes/sidebar.php';

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
$shapeBg    = ['circle'=>'shape-bg-circle','rectangle'=>'shape-bg-rectangle','heart'=>'shape-bg-heart','star'=>'shape-bg-star','oval'=>'shape-bg-oval','custom'=>'shape-bg-custom'];
?>

<div class="section-header">
  <div>
    <h2 class="section-title">Keychain Designs</h2>
    <p class="section-sub">Choose from <?= count($designs) ?> beautiful styles</p>
  </div>
  <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary">+ Customize Order</a>
</div>

<div class="designs-grid">
  <?php foreach ($designs as $d):
    $shape = $d['shape'];
    $bg    = $shapeBg[$shape] ?? 'shape-bg-circle';
    $emoji = $shapeEmoji[$shape] ?? '🔑';
  ?>
  <div class="card" style="overflow:hidden;cursor:pointer;transition:all var(--transition);"
       onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow-lg)'"
       onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div class="design-card-img <?= $bg ?>" style="height:180px;">
      <?php if ($d['image'] && file_exists(UPLOAD_PATH . 'designs/' . $d['image'])): ?>
        <img src="<?= SITE_URL ?>/uploads/designs/<?= htmlspecialchars($d['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
      <?php else: ?>
        <span class="shape-emoji"><?= $emoji ?></span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
        <h4 style="font-family:var(--font-display);"><?= htmlspecialchars($d['name']) ?></h4>
        <span class="badge" style="background:var(--blush);color:var(--terracotta);text-transform:capitalize;"><?= htmlspecialchars($d['shape']) ?></span>
      </div>
      <p style="font-size:13px;color:var(--light);margin-bottom:4px;"><?= htmlspecialchars($d['description']) ?></p>
      <p style="font-size:13px;color:var(--mid);margin-bottom:16px;">Material: <strong><?= htmlspecialchars($d['material']) ?></strong></p>
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="font-family:var(--font-display);font-size:1.3rem;color:var(--terracotta);"><?= formatPrice($d['base_price']) ?></span>
        <a href="<?= SITE_URL ?>/pages/customize.php?design_id=<?= $d['id'] ?>" class="btn btn-primary btn-sm">Order This</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
