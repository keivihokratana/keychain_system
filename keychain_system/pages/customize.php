<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$db      = getDB();
$uid     = $_SESSION['user_id'];
$user    = getCurrentUser();
$designs = $db->query("SELECT * FROM designs WHERE is_active=1 ORDER BY id")->fetchAll();
$preselected = intval($_GET['design_id'] ?? 0);

$errors = [];

// ---- Handle Order Submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $design_id  = intval($_POST['design_id'] ?? 0);
    $custom_text = sanitize($_POST['custom_text'] ?? '');
    $quantity   = max(1, intval($_POST['quantity'] ?? 1));
    $notes      = sanitize($_POST['special_notes'] ?? '');
    $address    = sanitize($_POST['shipping_address'] ?? '');

    if (!$design_id) $errors[] = 'Please select a keychain design.';
    if (!$address)   $errors[] = 'Please enter a shipping address.';

    // Validate design price
    $designRow = null;
    if ($design_id) {
        $ds = $db->prepare("SELECT * FROM designs WHERE id=? AND is_active=1");
        $ds->execute([$design_id]);
        $designRow = $ds->fetch();
        if (!$designRow) $errors[] = 'Invalid design selected.';
    }

    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = uploadFile($_FILES['photo'], 'photos');
        if (isset($uploaded['error'])) {
            $errors[] = $uploaded['error'];
        } else {
            $photo_path = $uploaded['path'];
        }
    } elseif (empty($_FILES['photo']['name'])) {
        // photo optional, just continue
    } else {
        $errors[] = 'Photo upload failed. Please try again.';
    }

    if (empty($errors) && $designRow) {
        $unit_price  = $designRow['base_price'];
        $total_price = $unit_price * $quantity;
        $ref         = generateOrderRef();

        $stmt = $db->prepare("
            INSERT INTO orders (reference_number, user_id, design_id, photo_path, custom_text, quantity, unit_price, total_price, special_notes, shipping_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$ref, $uid, $design_id, $photo_path, $custom_text, $quantity, $unit_price, $total_price, $notes, $address]);
        $order_id = $db->lastInsertId();

        // Log status
        $log = $db->prepare("INSERT INTO order_status_history (order_id, status, note, changed_by) VALUES (?,?,?,?)");
        $log->execute([$order_id, 'pending', 'Order placed', $uid]);

        setFlash('success', "Order placed! 🎉 Reference: <strong>{$ref}</strong>. Please upload your payment proof.");
        header('Location: ' . SITE_URL . '/pages/my-orders.php?view=' . $order_id);
        exit;
    }
}

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
$shapeBg    = ['circle'=>'shape-bg-circle','rectangle'=>'shape-bg-rectangle','heart'=>'shape-bg-heart','star'=>'shape-bg-star','oval'=>'shape-bg-oval','custom'=>'shape-bg-custom'];

$page_title   = 'Customize Order';
$current_page = 'customize';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Steps -->
<div class="steps mb-32">
  <div class="step active done"><div class="step-dot">1</div><div class="step-label">Choose Design</div></div>
  <div class="step active"><div class="step-dot">2</div><div class="step-label">Customize</div></div>
  <div class="step"><div class="step-dot">3</div><div class="step-label">Checkout</div></div>
</div>

<?php foreach ($errors as $e): ?>
  <div class="flash flash-error"><span class="flash-icon">✕</span><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data" id="orderForm">
<div style="display:grid;grid-template-columns:1fr 340px;gap:28px;" class="customize-layout">

  <!-- Left: Form -->
  <div style="display:flex;flex-direction:column;gap:24px;">

    <!-- Step 1: Pick Design -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">1. Select Design</h3></div>
      <div class="card-body">
        <input type="hidden" name="design_id" id="selectedDesignId" value="<?= $preselected ?: ($_POST['design_id'] ?? '') ?>">
        <input type="hidden" id="selectedDesignPrice" value="0">
        <div class="designs-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;">
          <?php foreach ($designs as $d):
            $isSelected = ($preselected == $d['id']) || (!$preselected && (($_POST['design_id'] ?? 0) == $d['id']));
            $bg = $shapeBg[$d['shape']] ?? 'shape-bg-circle';
            $emoji = $shapeEmoji[$d['shape']] ?? '🔑';
          ?>
          <div class="design-card <?= $isSelected ? 'selected' : '' ?>"
               data-design-id="<?= $d['id'] ?>"
               data-price="<?= $d['base_price'] ?>">
            <div class="design-card-check">✓</div>
            <div class="design-card-img <?= $bg ?>" style="height:110px;">
              <?php if ($d['image'] && file_exists(UPLOAD_PATH . 'designs/' . $d['image'])): ?>
                <img src="<?= SITE_URL ?>/uploads/designs/<?= htmlspecialchars($d['image']) ?>" alt="">
              <?php else: ?>
                <span style="font-size:44px;"><?= $emoji ?></span>
              <?php endif; ?>
            </div>
            <div class="design-card-body" style="padding:10px 12px;">
              <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($d['name']) ?></div>
              <div style="font-size:12px;color:var(--terracotta);font-weight:600;"><?= formatPrice($d['base_price']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Step 2: Upload Photo -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">2. Upload Your Photo</h3>
        <span style="font-size:13px;color:var(--light);">Optional but recommended</span>
      </div>
      <div class="card-body">
        <div class="upload-zone">
          <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/gif,image/webp">
          <div class="upload-icon">📸</div>
          <div class="upload-text">Drag & drop or click to upload</div>
          <div class="upload-sub">JPG, PNG, WEBP — Max 5MB</div>
        </div>
        <div class="upload-preview" id="uploadPreview">
          <img id="uploadPreviewImg" src="" alt="Preview">
        </div>
      </div>
    </div>

    <!-- Step 3: Personalize -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">3. Personalize</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Custom Text / Message</label>
          <input type="text" name="custom_text" class="form-control" maxlength="200"
                 placeholder="e.g. Always in my heart 💕"
                 value="<?= htmlspecialchars($_POST['custom_text'] ?? '') ?>">
          <small style="color:var(--light);font-size:12px;">Max 200 characters. Leave blank if none.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity</label>
          <div class="qty-stepper">
            <button type="button" class="qty-btn" data-dir="down" data-target="quantity">−</button>
            <input type="number" name="quantity" id="quantity" class="qty-input" value="<?= intval($_POST['quantity'] ?? 1) ?>" min="1" max="100">
            <button type="button" class="qty-btn" data-dir="up" data-target="quantity">+</button>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Special Notes</label>
          <textarea name="special_notes" class="form-control" rows="3"
                    placeholder="Any special instructions or requests..."><?= htmlspecialchars($_POST['special_notes'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Step 4: Shipping -->
    <div class="card">
      <div class="card-header"><h3 class="card-title">4. Shipping Details</h3></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Shipping Address *</label>
          <textarea name="shipping_address" class="form-control" rows="3" required
                    placeholder="Full address including street, barangay, city, province"><?= htmlspecialchars($_POST['shipping_address'] ?? $user['address'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

  </div>

  <!-- Right: Summary -->
  <div style="position:sticky;top:80px;align-self:start;">
    <div class="card">
      <div class="card-header"><h3 class="card-title">Order Summary</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">

        <div class="price-summary">
          <div class="price-row">
            <span style="color:var(--mid);">Design Price</span>
            <span id="calcUnit" style="font-weight:600;">₱0.00</span>
          </div>
          <div class="price-row">
            <span style="color:var(--mid);">Quantity</span>
            <span id="calcQty" style="font-weight:600;">1</span>
          </div>
          <div class="price-row">
            <span style="color:var(--mid);">Subtotal</span>
            <span class="price-total" id="calcTotal">₱0.00</span>
          </div>
        </div>

        <input type="hidden" name="total_price" id="totalPrice" value="0">

        <div class="alert alert-info" style="font-size:13px;padding:12px 14px;">
          ℹ️ Payment proof upload available after order is placed.
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-full" id="submitBtn">
          Place Order →
        </button>
        <a href="<?= SITE_URL ?>/pages/gallery.php" class="btn btn-outline w-full">← Back to Gallery</a>
      </div>
    </div>
  </div>

</div>
</form>

<style>
@media (max-width:900px) { .customize-layout { grid-template-columns:1fr !important; } }
</style>

<script>
// Set initial design if preselected
document.addEventListener('DOMContentLoaded', () => {
  const preId = <?= $preselected ?: 0 ?>;
  if (preId) {
    const card = document.querySelector(`.design-card[data-design-id="${preId}"]`);
    if (card) {
      document.getElementById('selectedDesignId').value = preId;
      document.getElementById('selectedDesignPrice').value = card.dataset.price;
      updatePriceCalc();
    }
  }
  // If POST had a design_id
  const postId = <?= intval($_POST['design_id'] ?? 0) ?>;
  if (postId && !preId) {
    const card = document.querySelector(`.design-card[data-design-id="${postId}"]`);
    if (card) {
      document.getElementById('selectedDesignPrice').value = card.dataset.price;
      updatePriceCalc();
    }
  }

  // Form submit guard
  document.getElementById('orderForm').addEventListener('submit', function(e) {
    const designId = document.getElementById('selectedDesignId').value;
    if (!designId) {
      e.preventDefault();
      alert('Please select a keychain design first!');
      return;
    }
    document.getElementById('submitBtn').innerHTML = '<span class="spinner"></span> Placing Order...';
    document.getElementById('submitBtn').disabled = true;
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
