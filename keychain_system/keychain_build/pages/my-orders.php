<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db  = getDB();
$uid = $_SESSION['user_id'];

// Upload payment proof
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_payment') {
    $order_id = intval($_POST['order_id'] ?? 0);
    // Verify ownership
    $chk = $db->prepare("SELECT id FROM orders WHERE id=? AND user_id=?");
    $chk->execute([$order_id, $uid]);
    if ($chk->fetch() && isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $uploaded = uploadFile($_FILES['payment_proof'], 'payments');
        if (isset($uploaded['success'])) {
            $db->prepare("UPDATE orders SET payment_proof=?, payment_status='paid' WHERE id=?")->execute([$uploaded['path'], $order_id]);
            $db->prepare("INSERT INTO order_status_history (order_id,status,note,changed_by) VALUES (?,'processing','Payment proof uploaded',?)")->execute([$order_id, $uid]);
            $db->prepare("UPDATE orders SET status='processing' WHERE id=? AND status='pending'")->execute([$order_id]);
            setFlash('success', 'Payment proof uploaded! Your order is now being processed. ✅');
        } else {
            setFlash('error', $uploaded['error'] ?? 'Upload failed.');
        }
    } else {
        setFlash('error', 'Invalid request or upload failed.');
    }
    header('Location: ' . SITE_URL . '/pages/my-orders.php?view=' . $order_id);
    exit;
}

// View single order
$viewOrder = null;
$statusHistory = [];
if (isset($_GET['view'])) {
    $oid = intval($_GET['view']);
    $stmt = $db->prepare("
        SELECT o.*, d.name as design_name, d.shape, d.material, d.base_price
        FROM orders o
        JOIN designs d ON o.design_id = d.id
        WHERE o.id=? AND o.user_id=?
    ");
    $stmt->execute([$oid, $uid]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $hist = $db->prepare("SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC");
        $hist->execute([$oid]);
        $statusHistory = $hist->fetchAll();
    }
}

// All orders (paginated)
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$total = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $total->execute([$uid]); $total = $total->fetchColumn();
$pages = ceil($total / $perPage);

$orders = $db->prepare("
    SELECT o.*, d.name as design_name
    FROM orders o
    JOIN designs d ON o.design_id=d.id
    WHERE o.user_id=?
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$orders->execute([$uid, $perPage, $offset]);
$orders = $orders->fetchAll();

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];

$page_title   = $viewOrder ? 'Order #' . $viewOrder['reference_number'] : 'My Orders';
$current_page = 'my_orders';
include __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($viewOrder): ?>
<!-- ==================== SINGLE ORDER VIEW ==================== -->
<div style="margin-bottom:16px;">
  <a href="<?= SITE_URL ?>/pages/my-orders.php" class="btn btn-outline btn-sm">← Back to Orders</a>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;" class="order-detail-layout">

  <div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Order Info -->
    <div class="card">
      <div class="card-header">
        <div>
          <h3 class="card-title"><?= htmlspecialchars($viewOrder['reference_number']) ?></h3>
          <p style="font-size:13px;color:var(--light);margin-top:2px;">Placed on <?= formatDate($viewOrder['created_at']) ?></p>
        </div>
        <?php $st = getOrderStatusLabel($viewOrder['status']); ?>
        <span class="badge <?= $st['class'] ?>" style="font-size:13px;padding:6px 14px;"><?= $st['label'] ?></span>
      </div>
      <div class="card-body">
        <div class="two-col">
          <div>
            <p class="text-sm text-muted mb-8">Design</p>
            <p class="fw-600"><?= htmlspecialchars($viewOrder['design_name']) ?> <?= $shapeEmoji[$viewOrder['shape']] ?? '' ?></p>
          </div>
          <div>
            <p class="text-sm text-muted mb-8">Material</p>
            <p class="fw-600"><?= htmlspecialchars($viewOrder['material']) ?></p>
          </div>
          <div>
            <p class="text-sm text-muted mb-8">Custom Text</p>
            <p class="fw-600"><?= $viewOrder['custom_text'] ? htmlspecialchars($viewOrder['custom_text']) : '<em style="color:var(--light)">None</em>' ?></p>
          </div>
          <div>
            <p class="text-sm text-muted mb-8">Quantity</p>
            <p class="fw-600"><?= $viewOrder['quantity'] ?> pcs</p>
          </div>
          <div>
            <p class="text-sm text-muted mb-8">Unit Price</p>
            <p class="fw-600"><?= formatPrice($viewOrder['unit_price']) ?></p>
          </div>
          <div>
            <p class="text-sm text-muted mb-8">Total</p>
            <p class="fw-600" style="color:var(--terracotta);font-family:var(--font-display);font-size:1.2rem;"><?= formatPrice($viewOrder['total_price']) ?></p>
          </div>
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

    <!-- Uploaded Photo -->
    <?php if ($viewOrder['photo_path']): ?>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Your Photo</h3></div>
      <div class="card-body">
        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['photo_path']) ?>" class="photo-thumb-lg" alt="Order Photo">
      </div>
    </div>
    <?php endif; ?>

    <!-- Payment -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Payment</h3>
        <?php $ps = getPaymentStatusLabel($viewOrder['payment_status']); ?>
        <span class="badge <?= $ps['class'] ?>"><?= $ps['label'] ?></span>
      </div>
      <div class="card-body">
        <?php if ($viewOrder['payment_proof']): ?>
          <p style="font-size:14px;color:var(--mid);margin-bottom:12px;">Payment proof submitted:</p>
          <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($viewOrder['payment_proof']) ?>" class="photo-thumb-lg" alt="Payment Proof">
        <?php else: ?>
          <div class="alert alert-warn" style="margin-bottom:16px;">⚠️ Please upload your payment proof to process your order.</div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_payment">
            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
            <div class="upload-zone" style="padding:24px;">
              <input type="file" name="payment_proof" accept="image/*" required>
              <div class="upload-icon">💳</div>
              <div class="upload-text">Upload Payment Screenshot</div>
              <div class="upload-sub">GCash, bank transfer, etc.</div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:12px;width:100%;">Upload Proof</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Status Timeline -->
  <div>
    <div class="card">
      <div class="card-header"><h3 class="card-title">Order Timeline</h3></div>
      <div class="card-body">
        <?php if (empty($statusHistory)): ?>
          <p class="text-muted text-sm">No status updates yet.</p>
        <?php else: ?>
        <div class="timeline">
          <?php foreach (array_reverse($statusHistory) as $h): ?>
          <div class="timeline-item">
            <div class="timeline-dot active"></div>
            <div class="timeline-content">
              <div class="timeline-title"><?= ucfirst($h['status']) ?></div>
              <div class="timeline-date"><?= formatDate($h['created_at']) ?></div>
              <?php if ($h['note']): ?>
              <div class="timeline-note"><?= htmlspecialchars($h['note']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Status Steps Visual -->
    <div class="card" style="margin-top:16px;">
      <div class="card-body">
        <?php
        $statusSteps = ['pending'=>0,'processing'=>1,'completed'=>2,'cancelled'=>3];
        $currentStep = $statusSteps[$viewOrder['status']] ?? 0;
        $stepNames = ['Pending','Processing','Completed'];
        $stepIcons = ['⏳','⚙️','✅'];
        if ($viewOrder['status'] === 'cancelled') {
            echo '<div class="badge badge-cancelled" style="width:100%;justify-content:center;padding:10px;font-size:14px;">❌ Order Cancelled</div>';
        } else {
            foreach ($stepNames as $i => $sname):
                $done = $i < $currentStep;
                $active = $i === $currentStep;
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;<?= $i < count($stepNames)-1 ? 'border-bottom:1px solid var(--border);' : '' ?>">
          <div style="width:36px;height:36px;border-radius:50%;background:<?= $done||$active ? 'var(--terracotta)' : 'var(--border)' ?>;color:<?= $done||$active ? '#fff' : 'var(--light)' ?>;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;"><?= $stepIcons[$i] ?></div>
          <div>
            <div style="font-weight:<?= $active ? '700' : '500' ?>;color:<?= $active ? 'var(--terracotta)' : ($done ? 'var(--charcoal)' : 'var(--light)') ?>;font-size:14px;"><?= $sname ?></div>
          </div>
        </div>
        <?php endforeach; } ?>
      </div>
    </div>
  </div>

</div>
<style>@media(max-width:768px){.order-detail-layout{grid-template-columns:1fr !important;}}</style>

<?php else: ?>
<!-- ==================== ORDERS LIST ==================== -->
<div class="section-header">
  <div>
    <h2 class="section-title">My Orders</h2>
    <p class="section-sub"><?= $total ?> order<?= $total != 1 ? 's' : '' ?> total</p>
  </div>
  <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary">+ New Order</a>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state">
  <div class="empty-state-icon">📦</div>
  <h3>No orders yet</h3>
  <p>Place your first personalized keychain order!</p>
  <a href="<?= SITE_URL ?>/pages/customize.php" class="btn btn-primary">Start Ordering</a>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <table id="dataTable">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Design</th>
          <th>Qty</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o):
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
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?page=<?= $i ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
