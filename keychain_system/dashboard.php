<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();
if (isAdmin()) { header('Location: '.SITE_URL.'/admin/dashboard.php'); exit; }

$db   = getDB();
$uid  = $_SESSION['user_id'];

// Stats
$totalOrders     = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $totalOrders->execute([$uid]); $totalOrders = $totalOrders->fetchColumn();
$pendingOrders   = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND status='pending'"); $pendingOrders->execute([$uid]); $pendingOrders = $pendingOrders->fetchColumn();
$completedOrders = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND status='completed'"); $completedOrders->execute([$uid]); $completedOrders = $completedOrders->fetchColumn();
$totalSpent      = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id=? AND payment_status='paid'"); $totalSpent->execute([$uid]); $totalSpent = $totalSpent->fetchColumn();

// Recent orders
$recentOrders = $db->prepare("
    SELECT o.*, d.name as design_name, d.shape
    FROM orders o
    JOIN designs d ON o.design_id = d.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$recentOrders->execute([$uid]);
$recentOrders = $recentOrders->fetchAll();

// Popular designs
$popularDesigns = $db->query("SELECT * FROM designs WHERE is_active=1 ORDER BY id LIMIT 3")->fetchAll();

$page_title = 'My Dashboard';
$current_page = 'dash';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value" data-target="<?= $totalOrders ?>"><?= $totalOrders ?></div>
    <div class="stat-label">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-value" data-target="<?= $pendingOrders ?>"><?= $pendingOrders ?></div>
    <div class="stat-label">Pending</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-value" data-target="<?= $completedOrders ?>"><?= $completedOrders ?></div>
    <div class="stat-label">Completed</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value" data-target="<?= $totalSpent ?>" data-float="true">₱<?= number_format($totalSpent,2) ?></div>
    <div class="stat-label">Total Spent</div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card mb-24">
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary">✏️ &nbsp;New Order</a>
    <a href="<?= SITE_URL ?>/pages/gallery.php"   class="btn btn-outline">🎨 &nbsp;Browse Designs</a>
    <a href="<?= SITE_URL ?>/pages/my-orders.php" class="btn btn-outline">📦 &nbsp;All Orders</a>
    <a href="<?= SITE_URL ?>/pages/track.php"     class="btn btn-outline">🔍 &nbsp;Track Order</a>
  </div>
</div>

<!-- Recent Orders & Popular Designs -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;" class="dashboard-grid">

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Orders</h3>
      <a href="<?= SITE_URL ?>/pages/my-orders.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if (empty($recentOrders)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">📦</div>
      <h3>No orders yet</h3>
      <p>Start by placing your first order!</p>
      <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary">Order Now</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Reference</th><th>Design</th><th>Total</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $order):
            $st = getOrderStatusLabel($order['status']);
          ?>
          <tr>
            <td><span style="font-family:monospace;font-size:13px;font-weight:600;"><?= htmlspecialchars($order['reference_number']) ?></span></td>
            <td><?= htmlspecialchars($order['design_name']) ?></td>
            <td style="font-weight:600;color:var(--terracotta);"><?= formatPrice($order['total_price']) ?></td>
            <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            <td><a href="<?= SITE_URL ?>/pages/my-orders.php?view=<?= $order['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Quick Order</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <?php
        $shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
        foreach ($popularDesigns as $d):
        ?>
        <a href="<?= SITE_URL ?>/pages/customize.php?design_id=<?= $d['id'] ?>" style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:var(--radius-sm);border:1px solid var(--border);transition:all var(--transition);" onmouseover="this.style.borderColor='var(--mauve)';this.style.background='var(--cream)'" onmouseout="this.style.borderColor='var(--border)';this.style.background=''">
          <div style="font-size:28px;width:44px;height:44px;background:var(--blush);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <?= $shapeEmoji[$d['shape']] ?? '🔑' ?>
          </div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($d['name']) ?></div>
            <div style="font-size:13px;color:var(--terracotta);font-weight:600;"><?= formatPrice($d['base_price']) ?></div>
          </div>
          <span style="color:var(--light);font-size:18px;">›</span>
        </a>
        <?php endforeach; ?>
        <a href="<?= SITE_URL ?>/pages/gallery.php" class="btn btn-outline btn-sm" style="margin-top:4px;">See all designs →</a>
      </div>
    </div>
  </div>
</div>

<style>
@media (max-width:768px) { .dashboard-grid { grid-template-columns:1fr !important; } }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
