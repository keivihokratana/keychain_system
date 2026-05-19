<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// View single customer
$viewCustomer = null;
$customerOrders = [];
if (isset($_GET['view'])) {
    $cid = intval($_GET['view']);
    $stmt = $db->prepare("SELECT * FROM users WHERE id=? AND role='customer'");
    $stmt->execute([$cid]);
    $viewCustomer = $stmt->fetch();
    if ($viewCustomer) {
        $co = $db->prepare("
            SELECT o.*, d.name as design_name
            FROM orders o JOIN designs d ON o.design_id=d.id
            WHERE o.user_id=? ORDER BY o.created_at DESC
        ");
        $co->execute([$cid]);
        $customerOrders = $co->fetchAll();
    }
}

// Search & paginate
$search  = sanitize($_GET['q'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;
$where   = "role='customer'";
$params  = [];
if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$total  = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
$total->execute($params); $total = $total->fetchColumn();
$pages = ceil($total / $perPage);

$customers = $db->prepare("
    SELECT u.*,
           COUNT(o.id) as order_count,
           COALESCE(SUM(o.total_price),0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id=o.user_id
    WHERE u.role='customer'
    " . ($search ? "AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)" : "") . "
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$customers->execute($params);
$customers = $customers->fetchAll();

$page_title   = $viewCustomer ? 'Customer Details' : 'Manage Customers';
$current_page = 'admin_customers';
include __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($viewCustomer): ?>
<!-- ============ SINGLE CUSTOMER VIEW ============ -->
<div style="margin-bottom:16px;">
  <a href="<?= SITE_URL ?>/admin/customers.php" class="btn btn-outline btn-sm">← All Customers</a>
</div>

<div class="profile-header mb-24">
  <div class="profile-avatar-lg" style="background:var(--blush);font-size:2rem;">
    <?php if ($viewCustomer['avatar']): ?>
      <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewCustomer['avatar']) ?>" alt="">
    <?php else: ?>
      <?= strtoupper(substr($viewCustomer['name'],0,1)) ?>
    <?php endif; ?>
  </div>
  <div>
    <h2 class="profile-name"><?= htmlspecialchars($viewCustomer['name']) ?></h2>
    <p class="profile-email"><?= htmlspecialchars($viewCustomer['email']) ?></p>
    <?php if ($viewCustomer['phone']): ?><p style="font-size:14px;color:var(--light);">📞 <?= htmlspecialchars($viewCustomer['phone']) ?></p><?php endif; ?>
    <span class="profile-badge">Customer</span>
  </div>
  <div style="margin-left:auto;text-align:right;">
    <p class="text-sm text-muted">Joined</p>
    <p class="fw-600"><?= date('M d, Y', strtotime($viewCustomer['created_at'])) ?></p>
  </div>
</div>

<?php if ($viewCustomer['address']): ?>
<div class="card mb-20">
  <div class="card-body">
    <p class="text-sm text-muted mb-8">Address</p>
    <p><?= nl2br(htmlspecialchars($viewCustomer['address'])) ?></p>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3 class="card-title">Order History (<?= count($customerOrders) ?>)</h3>
  </div>
  <?php if (empty($customerOrders)): ?>
  <div class="empty-state"><div class="empty-state-icon">📦</div><h3>No orders yet</h3></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Reference</th><th>Design</th><th>Qty</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($customerOrders as $o):
          $st = getOrderStatusLabel($o['status']);
          $ps = getPaymentStatusLabel($o['payment_status']);
        ?>
        <tr>
          <td><span style="font-family:monospace;font-size:13px;font-weight:600;"><?= htmlspecialchars($o['reference_number']) ?></span></td>
          <td><?= htmlspecialchars($o['design_name']) ?></td>
          <td><?= $o['quantity'] ?></td>
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

<?php else: ?>
<!-- ============ CUSTOMERS LIST ============ -->
<div class="section-header">
  <div>
    <h2 class="section-title">Customers</h2>
    <p class="section-sub"><?= $total ?> registered customer<?= $total != 1 ? 's' : '' ?></p>
  </div>
</div>

<!-- Search -->
<div class="card mb-20">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;">
      <div style="flex:1;">
        <input type="text" name="q" class="form-control" placeholder="Search by name, email, or phone…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-primary">Search</button>
      <?php if ($search): ?><a href="<?= SITE_URL ?>/admin/customers.php" class="btn btn-outline">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<?php if (empty($customers)): ?>
<div class="empty-state">
  <div class="empty-state-icon">👥</div>
  <h3>No customers found</h3>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $c):
          $initial = strtoupper(substr($c['name'],0,1));
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="sidebar-avatar" style="flex-shrink:0;">
                <?php if ($c['avatar']): ?>
                  <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($c['avatar']) ?>" alt="">
                <?php else: ?>
                  <?= $initial ?>
                <?php endif; ?>
              </div>
              <span style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></span>
            </div>
          </td>
          <td style="font-size:13px;"><?= htmlspecialchars($c['email']) ?></td>
          <td style="font-size:13px;"><?= htmlspecialchars($c['phone'] ?? '—') ?></td>
          <td><span class="badge" style="background:var(--blush);color:var(--terracotta);"><?= $c['order_count'] ?></span></td>
          <td style="font-weight:700;color:var(--terracotta);"><?= formatPrice($c['total_spent']) ?></td>
          <td style="font-size:12px;color:var(--light);"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
          <td><a href="?view=<?= $c['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
  <?php for ($i=1;$i<=$pages;$i++): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
