<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db  = getDB();
$uid = $_SESSION['user_id'];
$order = null;
$statusHistory = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['ref'])) {
    $ref = sanitize(strtoupper(trim($_POST['ref'] ?? $_GET['ref'] ?? '')));
    if ($ref) {
        $stmt = $db->prepare("
            SELECT o.*, d.name as design_name, d.shape
            FROM orders o
            JOIN designs d ON o.design_id=d.id
            WHERE o.reference_number=? AND o.user_id=?
        ");
        $stmt->execute([$ref, $uid]);
        $order = $stmt->fetch();
        if ($order) {
            $hist = $db->prepare("SELECT * FROM order_status_history WHERE order_id=? ORDER BY created_at ASC");
            $hist->execute([$order['id']]);
            $statusHistory = $hist->fetchAll();
        } else {
            $error = 'Order not found. Please check your reference number.';
        }
    }
}

$page_title   = 'Track Order';
$current_page = 'track';
include __DIR__ . '/../includes/sidebar.php';

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
?>

<div style="max-width:680px;">

  <div class="card mb-24">
    <div class="card-header"><h3 class="card-title">🔍 Track Your Order</h3></div>
    <div class="card-body">
      <form method="POST" style="display:flex;gap:12px;align-items:flex-end;">
        <div class="form-group" style="flex:1;margin-bottom:0;">
          <label class="form-label">Order Reference Number</label>
          <input type="text" name="ref" class="form-control"
                 placeholder="e.g. KC-ABC123-20240519"
                 value="<?= htmlspecialchars($_POST['ref'] ?? $_GET['ref'] ?? '') ?>"
                 style="font-family:monospace;font-size:16px;letter-spacing:0.05em;">
        </div>
        <button type="submit" class="btn btn-primary">Track</button>
      </form>
      <?php if ($error): ?>
        <div class="flash flash-error" style="margin-top:16px;margin-bottom:0;"><span class="flash-icon">✕</span><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($order): ?>
  <!-- Order Found -->
  <div class="card mb-20">
    <div class="card-header">
      <div>
        <div style="font-family:monospace;font-size:1rem;font-weight:700;"><?= htmlspecialchars($order['reference_number']) ?></div>
        <div style="font-size:13px;color:var(--light);margin-top:2px;"><?= htmlspecialchars($order['design_name']) ?> <?= $shapeEmoji[$order['shape']] ?? '' ?> · <?= formatDate($order['created_at']) ?></div>
      </div>
      <?php $st = getOrderStatusLabel($order['status']); ?>
      <span class="badge <?= $st['class'] ?>" style="font-size:14px;padding:8px 16px;"><?= $st['label'] ?></span>
    </div>
    <div class="card-body">
      <!-- Progress Bar -->
      <?php
      $steps = ['pending'=>0,'processing'=>1,'completed'=>2];
      $cur = $order['status'] === 'cancelled' ? -1 : ($steps[$order['status']] ?? 0);
      ?>
      <?php if ($order['status'] !== 'cancelled'): ?>
      <div style="margin-bottom:28px;">
        <div style="display:flex;justify-content:space-between;position:relative;margin-bottom:8px;">
          <div style="position:absolute;top:14px;left:14px;right:14px;height:3px;background:var(--border);border-radius:2px;z-index:0;"></div>
          <div style="position:absolute;top:14px;left:14px;height:3px;background:var(--terracotta);border-radius:2px;z-index:1;transition:width 0.5s ease;width:<?= $cur === 0 ? '0%' : ($cur === 1 ? '50%' : '100%') ?>"></div>
          <?php
          $trackSteps = [
            ['label'=>'Order Placed','icon'=>'📝'],
            ['label'=>'Processing','icon'=>'⚙️'],
            ['label'=>'Completed','icon'=>'✅'],
          ];
          foreach ($trackSteps as $i => $ts): $done = $i <= $cur; ?>
          <div style="display:flex;flex-direction:column;align-items:center;gap:8px;position:relative;z-index:2;flex:1;">
            <div style="width:28px;height:28px;border-radius:50%;background:<?= $done ? 'var(--terracotta)' : 'var(--white)' ?>;border:2px solid <?= $done ? 'var(--terracotta)' : 'var(--border)' ?>;display:flex;align-items:center;justify-content:center;font-size:13px;">
              <?= $done ? '✓' : ($i+1) ?>
            </div>
            <span style="font-size:12px;font-weight:<?= $done ? '600' : '400' ?>;color:<?= $done ? 'var(--terracotta)' : 'var(--light)' ?>;text-align:center;"><?= $ts['label'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="badge badge-cancelled" style="width:100%;justify-content:center;padding:12px;font-size:14px;margin-bottom:20px;">❌ This order has been cancelled</div>
      <?php endif; ?>

      <!-- Order Details Grid -->
      <div class="two-col">
        <div>
          <p class="text-sm text-muted mb-8">Total Amount</p>
          <p style="font-family:var(--font-display);font-size:1.3rem;color:var(--terracotta);"><?= formatPrice($order['total_price']) ?></p>
        </div>
        <div>
          <p class="text-sm text-muted mb-8">Payment Status</p>
          <?php $ps = getPaymentStatusLabel($order['payment_status']); ?>
          <span class="badge <?= $ps['class'] ?>"><?= $ps['label'] ?></span>
        </div>
        <div>
          <p class="text-sm text-muted mb-8">Quantity</p>
          <p class="fw-600"><?= $order['quantity'] ?> pcs</p>
        </div>
        <div>
          <p class="text-sm text-muted mb-8">Custom Text</p>
          <p class="fw-600"><?= $order['custom_text'] ? htmlspecialchars($order['custom_text']) : '<em style="color:var(--light)">None</em>' ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Timeline -->
  <div class="card">
    <div class="card-header"><h3 class="card-title">Status History</h3></div>
    <div class="card-body">
      <?php if (empty($statusHistory)): ?>
        <p class="text-muted text-sm">No status updates yet.</p>
      <?php else: ?>
      <div class="timeline">
        <?php foreach (array_reverse($statusHistory) as $h): ?>
        <div class="timeline-item">
          <div class="timeline-dot active"></div>
          <div class="timeline-content">
            <div class="timeline-title"><?= ucfirst(htmlspecialchars($h['status'])) ?></div>
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

  <div style="margin-top:16px;display:flex;gap:12px;">
    <a href="<?= SITE_URL ?>/pages/my-orders.php?view=<?= $order['id'] ?>" class="btn btn-primary">View Full Order Details</a>
    <a href="<?= SITE_URL ?>/pages/track.php" class="btn btn-outline">Track Another</a>
  </div>

  <?php endif; ?>

  <!-- Quick Links -->
  <?php if (!$order): ?>
  <div class="card">
    <div class="card-body">
      <p style="font-size:14px;color:var(--mid);margin-bottom:16px;">You can also view all your orders and find reference numbers there:</p>
      <a href="<?= SITE_URL ?>/pages/my-orders.php" class="btn btn-outline">View All My Orders</a>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
