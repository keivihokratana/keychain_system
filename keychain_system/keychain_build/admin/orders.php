<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// ---- Handle Status Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $order_id = intval($_POST['order_id'] ?? 0);

    if ($action === 'update_status' && $order_id) {
        $status = $_POST['status'] ?? '';
        $note   = sanitize($_POST['note'] ?? '');
        $allowed = ['pending','processing','completed','cancelled'];
        if (in_array($status, $allowed)) {
            $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $order_id]);
            $db->prepare("INSERT INTO order_status_history (order_id,status,note,changed_by) VALUES (?,?,?,?)")
               ->execute([$order_id, $status, $note ?: 'Status updated by admin', $_SESSION['user_id']]);
            setFlash('success', 'Order status updated successfully!');
        }
        header('Location: ' . SITE_URL . '/admin/orders.php?view=' . $order_id);
        exit;
    }
}

// ---- View Single Order ----
$viewOrder = null;
$statusHistory = [];
if (isset($_GET['view'])) {
    $oid = intval($_GET['view']);
    $stmt = $db->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
               d.name as design_name, d.shape, d.material
        FROM orders o
        JOIN users u ON o.user_id=u.id
        JOIN designs d ON o.design_id=d.id
        WHERE o.id=?
    ");
    $stmt->execute([$oid]);
    $viewOrder = $stmt->fetch();
    if ($viewOrder) {
        $hist = $db->prepare("SELECT h.*, u.name as changed_by_name FROM order_status_history h LEFT JOIN users u ON h.changed_by=u.id WHERE h.order_id=? ORDER BY h.created_at ASC");
        $hist->execute([$oid]);
        $statusHistory = $hist->fetchAll();
    }
}

// ---- Filters ----
$filterStatus = $_GET['status'] ?? '';
$search       = sanitize($_GET['q'] ?? '');
$page         = max(1, intval($_GET['page'] ?? 1));
$perPage      = 12;
$offset       = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($filterStatus) { $where .= ' AND o.status=?'; $params[] = $filterStatus; }
if ($search) {
    $where .= ' AND (o.reference_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id=u.id WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);

$orders = $db->prepare("
    SELECT o.*, u.name as customer_name, d.name as design_name
    FROM orders o
    JOIN users u ON o.user_id=u.id
    JOIN designs d ON o.design_id=d.id
    WHERE $where
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$orders->execute($params);
$orders = $orders->fetchAll();

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];

$page_title   = $viewOrder ? 'Order Details' : 'Manage Orders';
$current_page = 'admin_orders';
include __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($viewOrder): ?>
<!-- ============ SINGLE ORDER VIEW ============ -->
<div style="margin-bottom:16px;display:flex;gap:10px;">
  <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline btn-sm">← All Orders</a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;" class="admin-order-layout">

  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Order Info -->
    <div class="card">
      <div class="card-header">
        <div>
          <div style="font-family:monospace;font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($viewOrder['reference_number']) ?></div>
          <p style="font-size:13px;color:var(--light);margin-top:2px;">Placed <?= formatDate($viewOrder['created_at']) ?></p>
        </div>
        <?php $st = getOrderStatusLabel($viewOrder['status']); ?>
        <span class="badge <?= $st['class'] ?>" style="font-size:13px;padding:6px 14px;"><?= $st['label'] ?></span>
      </div>
      <div class="card-body">
        <div class="three-col" style="gap:16px;">
          <div><p class="text-sm text-muted mb-8">Customer</p><p class="fw-600"><?= htmlspecialchars($viewOrder['customer_name']) ?></p></div>
          <div><p class="text-sm text-muted mb-8">Email</p><p class="fw-600" style="font-size:13px;"><?= htmlspecialchars($viewOrder['customer_email']) ?></p></div>
          <div><p class="text-sm text-muted mb-8">Phone</p><p class="fw-600"><?= htmlspecialchars($viewOrder['customer_phone'] ?? '—') ?></p></div>
          <div><p class="text-sm text-muted mb-8">Design</p><p class="fw-600"><?= htmlspecialchars($viewOrder['design_name']) ?> <?= $shapeEmoji[$viewOrder['shape']] ?? '' ?></p></div>
          <div><p class="text-sm text-muted mb-8">Material</p><p class="fw-600"><?= htmlspecialchars($viewOrder['material']) ?></p></div>
          <div><p class="text-sm text-muted mb-8">Quantity</p><p class="fw-600"><?= $viewOrder['quantity'] ?> pcs</p></div>
          <div><p class="text-sm text-muted mb-8">Custom Text</p><p class="fw-600"><?= $viewOrder['custom_text'] ? htmlspecialchars($viewOrder['custom_text']) : '<em style="color:var(--light)">None</em>' ?></p></div>
          <div><p class="text-sm text-muted mb-8">Unit Price</p><p class="fw-600"><?= formatPrice($viewOrder['unit_price']) ?></p></div>
          <div><p class="text-sm text-muted mb-8">Total</p><p class="fw-600" style="color:var(--terracotta);font-family:var(--font-display);font-size:1.1rem;"><?= formatPrice($viewOrder['total_price']) ?></p></div>
        </div>
        <?php if ($viewOrder['special_notes']): ?>
        <div style="margin-top:16px;padding:12px;background:var(--cream);border-radius:var(--radius-sm);">
          <p class="text-sm text-muted mb-8">Special Notes</p>
          <p style="font-size:14px;"><?= nl2br(htmlspecialchars($viewOrder['special_notes'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($viewOrder['shipping_address']): ?>
        <div style="margin-top:12px;padding:12px;background:var(--cream);border-radius:var(--radius-sm);">
          <p class="text-sm text-muted mb-8">Shipping Address</p>
          <p style="font-size:14px;"><?= nl2br(htmlspecialchars($viewOrder['shipping_address'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Customer Photo -->
    <?php if ($viewOrder['photo_path']): ?>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Customer Photo</h3></div>
      <div class="card-body">
        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['photo_path']) ?>" class="photo-thumb-lg" alt="">
        <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['photo_path']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top:12px;">🔍 View Full Size</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Payment Proof -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Payment Proof</h3>
        <?php $ps = getPaymentStatusLabel($viewOrder['payment_status']); ?>
        <span class="badge <?= $ps['class'] ?>"><?= $ps['label'] ?></span>
      </div>
      <div class="card-body">
        <?php if ($viewOrder['payment_proof']): ?>
          <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['payment_proof']) ?>" class="photo-thumb-lg" alt="">
          <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['payment_proof']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top:12px;">🔍 Full Size</a>
        <?php else: ?>
          <div class="empty-state" style="padding:28px;">
            <div class="empty-state-icon" style="font-size:32px;">💳</div>
            <p>No payment proof uploaded yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status History -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">Status History</h3></div>
      <div class="card-body">
        <div class="timeline">
          <?php foreach (array_reverse($statusHistory) as $h): ?>
          <div class="timeline-item">
            <div class="timeline-dot active"></div>
            <div class="timeline-content">
              <div class="timeline-title"><?= ucfirst(htmlspecialchars($h['status'])) ?>
                <?php if ($h['changed_by_name']): ?>
                  <span style="font-size:12px;font-weight:400;color:var(--light);">by <?= htmlspecialchars($h['changed_by_name']) ?></span>
                <?php endif; ?>
              </div>
              <div class="timeline-date"><?= formatDate($h['created_at']) ?></div>
              <?php if ($h['note']): ?><div class="timeline-note"><?= htmlspecialchars($h['note']) ?></div><?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Update Status Panel -->
  <div style="position:sticky;top:80px;align-self:start;">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Update Status</h3></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
          <div class="form-group">
            <label class="form-label">Order Status</label>
            <select name="status" class="form-control">
              <option value="pending"    <?= $viewOrder['status']==='pending'    ? 'selected' : '' ?>>⏳ Pending</option>
              <option value="processing" <?= $viewOrder['status']==='processing' ? 'selected' : '' ?>>⚙️ Processing</option>
              <option value="completed"  <?= $viewOrder['status']==='completed'  ? 'selected' : '' ?>>✅ Completed</option>
              <option value="cancelled"  <?= $viewOrder['status']==='cancelled'  ? 'selected' : '' ?>>❌ Cancelled</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Note (optional)</label>
            <textarea name="note" class="form-control" rows="3" placeholder="Add a note for this status update..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-full">Update Status</button>
        </form>
      </div>
    </div>
  </div>

</div>
<style>@media(max-width:900px){.admin-order-layout{grid-template-columns:1fr !important;}}</style>

<?php else: ?>
<!-- ============ ORDERS LIST ============ -->
<div class="section-header">
  <div>
    <h2 class="section-title">All Orders</h2>
    <p class="section-sub"><?= $total ?> order<?= $total != 1 ? 's' : '' ?> found</p>
  </div>
</div>

<!-- Filters -->
<div class="card mb-24">
  <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;width:100%;">
      <div style="flex:1;min-width:200px;">
        <label class="form-label">Search</label>
        <input type="text" name="q" class="form-control" placeholder="Reference, customer name, email…" value="<?= htmlspecialchars($search) ?>">
      </div>
      <div>
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Statuses</option>
          <option value="pending"    <?= $filterStatus==='pending'    ? 'selected' : '' ?>>Pending</option>
          <option value="processing" <?= $filterStatus==='processing' ? 'selected' : '' ?>>Processing</option>
          <option value="completed"  <?= $filterStatus==='completed'  ? 'selected' : '' ?>>Completed</option>
          <option value="cancelled"  <?= $filterStatus==='cancelled'  ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>
      <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline">Clear</a>
      </div>
    </form>
  </div>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state">
  <div class="empty-state-icon">📦</div>
  <h3>No orders found</h3>
  <p>Try adjusting your filters.</p>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Reference</th><th>Customer</th><th>Design</th><th>Qty</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
          $st = getOrderStatusLabel($o['status']);
          $ps = getPaymentStatusLabel($o['payment_status']);
        ?>
        <tr>
          <td><span style="font-family:monospace;font-size:13px;font-weight:600;"><?= htmlspecialchars($o['reference_number']) ?></span></td>
          <td><?= htmlspecialchars($o['customer_name']) ?></td>
          <td><?= htmlspecialchars($o['design_name']) ?></td>
          <td><?= $o['quantity'] ?></td>
          <td style="font-weight:700;color:var(--terracotta);"><?= formatPrice($o['total_price']) ?></td>
          <td><span class="badge <?= $ps['class'] ?>"><?= $ps['label'] ?></span></td>
          <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
          <td style="font-size:12px;color:var(--light);"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
          <td><a href="?view=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
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
