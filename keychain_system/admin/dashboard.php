<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db    = getDB();
$stats = getAdminStats();

// Monthly revenue for chart (last 6 months)
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $label = date('M', strtotime("-{$i} months"));
    $rev = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE payment_status='paid' AND DATE_FORMAT(created_at,'%Y-%m')=?");
    $rev->execute([$month]);
    $chartData[] = ['label' => $label, 'value' => (float)$rev->fetchColumn()];
}
$maxVal = max(array_column($chartData, 'value')) ?: 1;

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, u.name as customer_name, d.name as design_name
    FROM orders o
    JOIN users u ON o.user_id=u.id
    JOIN designs d ON o.design_id=d.id
    ORDER BY o.created_at DESC
    LIMIT 8
")->fetchAll();

$page_title   = 'Admin Dashboard';
$current_page = 'admin_dash';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value" data-target="<?= $stats['total_orders'] ?>"><?= $stats['total_orders'] ?></div>
    <div class="stat-label">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-value" data-target="<?= $stats['pending_orders'] ?>"><?= $stats['pending_orders'] ?></div>
    <div class="stat-label">Pending Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-value" data-target="<?= $stats['completed_orders'] ?>"><?= $stats['completed_orders'] ?></div>
    <div class="stat-label">Completed</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-value" data-target="<?= $stats['total_customers'] ?>"><?= $stats['total_customers'] ?></div>
    <div class="stat-label">Customers</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value" data-target="<?= $stats['total_revenue'] ?>" data-float="true">₱<?= number_format($stats['total_revenue'],2) ?></div>
    <div class="stat-label">Total Revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🎨</div>
    <div class="stat-value" data-target="<?= $stats['total_designs'] ?>"><?= $stats['total_designs'] ?></div>
    <div class="stat-label">Active Designs</div>
  </div>
</div>

<!-- Revenue Chart + Quick Actions -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:28px;" class="admin-grid">

  <div class="card">
    <div class="card-header"><h3 class="card-title">Revenue (Last 6 Months)</h3></div>
    <div class="card-body">
      <div style="display:flex;align-items:flex-end;gap:12px;height:160px;padding-top:16px;">
        <?php foreach ($chartData as $bar):
          $height = $maxVal > 0 ? round(($bar['value'] / $maxVal) * 140) : 4;
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:8px;">
          <span style="font-size:11px;color:var(--light);font-weight:600;"><?= $height > 20 ? '₱'.number_format($bar['value']) : '' ?></span>
          <div style="width:100%;height:<?= max(4,$height) ?>px;background:<?= $bar['value'] > 0 ? 'var(--terracotta)' : 'var(--border)' ?>;border-radius:6px 6px 0 0;transition:height 0.5s ease;position:relative;"
               title="<?= $bar['label'] ?>: ₱<?= number_format($bar['value'],2) ?>"></div>
          <span style="font-size:12px;color:var(--mid);font-weight:600;"><?= $bar['label'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3 class="card-title">Quick Actions</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
      <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-primary">📦 Manage Orders</a>
      <a href="<?= SITE_URL ?>/admin/designs.php" class="btn btn-outline">🎨 Manage Designs</a>
      <a href="<?= SITE_URL ?>/admin/customers.php" class="btn btn-outline">👥 View Customers</a>
      <a href="<?= SITE_URL ?>/admin/orders.php?status=pending" class="btn btn-outline" style="color:var(--terracotta);border-color:var(--rose);">
        ⏳ <?= $stats['pending_orders'] ?> Pending Order<?= $stats['pending_orders'] != 1 ? 's' : '' ?>
      </a>
    </div>
  </div>

</div>

<!-- Recent Orders -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Recent Orders</h3>
    <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <?php if (empty($recentOrders)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">📦</div>
    <h3>No orders yet</h3>
    <p>Orders will appear here once customers start placing them.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Reference</th><th>Customer</th><th>Design</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($recentOrders as $o):
          $st = getOrderStatusLabel($o['status']);
          $ps = getPaymentStatusLabel($o['payment_status']);
        ?>
        <tr>
          <td><span style="font-family:monospace;font-size:13px;font-weight:600;"><?= htmlspecialchars($o['reference_number']) ?></span></td>
          <td><?= htmlspecialchars($o['customer_name']) ?></td>
          <td><?= htmlspecialchars($o['design_name']) ?></td>
          <td style="font-weight:700;color:var(--terracotta);"><?= formatPrice($o['total_price']) ?></td>
          <td><span class="badge <?= $ps['class'] ?>"><?= $ps['label'] ?></span></td>
          <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
          <td style="font-size:12px;color:var(--light);"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
          <td><a href="<?= SITE_URL ?>/admin/orders.php?view=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<style>@media(max-width:900px){.admin-grid{grid-template-columns:1fr !important;}}</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
