<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// ---- Handle Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_design') {
        $id          = intval($_POST['design_id'] ?? 0);
        $name        = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $base_price  = floatval($_POST['base_price'] ?? 0);
        $shape       = sanitize($_POST['shape'] ?? 'circle');
        $material    = sanitize($_POST['material'] ?? 'Acrylic');
        $is_active   = isset($_POST['is_active']) ? 1 : 0;

        if (!$name || $base_price <= 0) {
            setFlash('error', 'Name and price are required.');
        } else {
            // Handle image upload
            $image = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $up = uploadFile($_FILES['image'], 'designs');
                if (isset($up['success'])) {
                    $image = basename($up['path']);
                } else {
                    setFlash('error', $up['error']);
                }
            }

            if ($id) {
                // Update
                if ($image) {
                    $db->prepare("UPDATE designs SET name=?,description=?,base_price=?,shape=?,material=?,is_active=?,image=? WHERE id=?")
                       ->execute([$name,$description,$base_price,$shape,$material,$is_active,$image,$id]);
                } else {
                    $db->prepare("UPDATE designs SET name=?,description=?,base_price=?,shape=?,material=?,is_active=? WHERE id=?")
                       ->execute([$name,$description,$base_price,$shape,$material,$is_active,$id]);
                }
                setFlash('success', 'Design updated!');
            } else {
                // Insert
                $db->prepare("INSERT INTO designs (name,description,base_price,shape,material,is_active,image) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$name,$description,$base_price,$shape,$material,$is_active,$image]);
                setFlash('success', 'Design added!');
            }
        }
        header('Location: ' . SITE_URL . '/admin/designs.php'); exit;
    }

    if ($action === 'toggle_active') {
        $id = intval($_POST['design_id'] ?? 0);
        $db->prepare("UPDATE designs SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        setFlash('success', 'Design status toggled.');
        header('Location: ' . SITE_URL . '/admin/designs.php'); exit;
    }

    if ($action === 'delete_design') {
        $id = intval($_POST['design_id'] ?? 0);
        // Check if used in orders
        $used = $db->prepare("SELECT COUNT(*) FROM orders WHERE design_id=?"); $used->execute([$id]); $used = $used->fetchColumn();
        if ($used > 0) {
            setFlash('error', 'Cannot delete: design is used in existing orders. Deactivate it instead.');
        } else {
            $db->prepare("DELETE FROM designs WHERE id=?")->execute([$id]);
            setFlash('success', 'Design deleted.');
        }
        header('Location: ' . SITE_URL . '/admin/designs.php'); exit;
    }
}

// Edit mode
$editDesign = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM designs WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $editDesign = $stmt->fetch();
}

$designs = $db->query("SELECT d.*, (SELECT COUNT(*) FROM orders o WHERE o.design_id=d.id) as order_count FROM designs d ORDER BY d.id")->fetchAll();

$shapeEmoji = ['circle'=>'⭕','rectangle'=>'▬','heart'=>'❤️','star'=>'⭐','oval'=>'🥚','custom'=>'✨'];
$shapeBg    = ['circle'=>'shape-bg-circle','rectangle'=>'shape-bg-rectangle','heart'=>'shape-bg-heart','star'=>'shape-bg-star','oval'=>'shape-bg-oval','custom'=>'shape-bg-custom'];

$page_title   = 'Manage Designs';
$current_page = 'admin_designs';
include __DIR__ . '/../includes/sidebar.php';
?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;" class="designs-admin-layout">

  <!-- Designs List -->
  <div>
    <div class="section-header">
      <div>
        <h2 class="section-title">Keychain Designs</h2>
        <p class="section-sub"><?= count($designs) ?> designs total</p>
      </div>
      <a href="#design-form" class="btn btn-primary" onclick="document.getElementById('design-form').scrollIntoView({behavior:'smooth'});return false;">+ Add Design</a>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px;">
      <?php foreach ($designs as $d):
        $bg    = $shapeBg[$d['shape']] ?? 'shape-bg-circle';
        $emoji = $shapeEmoji[$d['shape']] ?? '🔑';
      ?>
      <div class="card" style="<?= $d['is_active'] ? '' : 'opacity:0.6;' ?>">
        <div style="display:flex;align-items:center;gap:16px;padding:16px 20px;">
          <div class="<?= $bg ?>" style="width:60px;height:60px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;">
            <?php if ($d['image'] && file_exists(UPLOAD_PATH . 'designs/' . $d['image'])): ?>
              <img src="<?= SITE_URL ?>/uploads/designs/<?= htmlspecialchars($d['image']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
            <?php else: ?>
              <?= $emoji ?>
            <?php endif; ?>
          </div>
          <div style="flex:1;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
              <h4 style="font-size:15px;"><?= htmlspecialchars($d['name']) ?></h4>
              <span class="badge <?= $d['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>" style="font-size:11px;"><?= $d['is_active'] ? 'Active' : 'Inactive' ?></span>
            </div>
            <p style="font-size:13px;color:var(--light);margin-bottom:2px;"><?= htmlspecialchars($d['description']) ?></p>
            <div style="display:flex;gap:12px;font-size:13px;">
              <span style="color:var(--terracotta);font-weight:700;"><?= formatPrice($d['base_price']) ?></span>
              <span style="color:var(--light);">Shape: <?= ucfirst($d['shape']) ?></span>
              <span style="color:var(--light);"><?= $d['order_count'] ?> orders</span>
            </div>
          </div>
          <div style="display:flex;gap:8px;flex-shrink:0;">
            <a href="?edit=<?= $d['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="design_id" value="<?= $d['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm"><?= $d['is_active'] ? 'Deactivate' : 'Activate' ?></button>
            </form>
            <?php if ($d['order_count'] == 0): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this design permanently?')">
              <input type="hidden" name="action" value="delete_design">
              <input type="hidden" name="design_id" value="<?= $d['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add/Edit Form -->
  <div id="design-form" style="position:sticky;top:80px;">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><?= $editDesign ? 'Edit Design' : 'Add New Design' ?></h3>
        <?php if ($editDesign): ?><a href="<?= SITE_URL ?>/admin/designs.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_design">
          <input type="hidden" name="design_id" value="<?= $editDesign['id'] ?? 0 ?>">

          <div class="form-group">
            <label class="form-label">Design Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editDesign['name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($editDesign['description'] ?? '') ?></textarea>
          </div>
          <div class="two-col">
            <div class="form-group">
              <label class="form-label">Base Price (₱) *</label>
              <input type="number" name="base_price" class="form-control" step="0.01" min="1" required value="<?= $editDesign['base_price'] ?? '' ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Shape</label>
              <select name="shape" class="form-control">
                <?php foreach (['circle','rectangle','heart','star','oval','custom'] as $s): ?>
                <option value="<?= $s ?>" <?= ($editDesign['shape'] ?? 'circle') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Material</label>
            <input type="text" name="material" class="form-control" value="<?= htmlspecialchars($editDesign['material'] ?? 'Acrylic') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Design Image</label>
            <div class="upload-zone" style="padding:20px;">
              <input type="file" name="image" accept="image/*">
              <div class="upload-icon" style="font-size:24px;">🖼️</div>
              <div class="upload-text" style="font-size:13px;">Upload design preview</div>
              <div class="upload-sub">Optional — PNG or JPG</div>
            </div>
            <?php if ($editDesign && $editDesign['image'] && file_exists(UPLOAD_PATH . 'designs/' . $editDesign['image'])): ?>
            <img src="<?= SITE_URL ?>/uploads/designs/<?= htmlspecialchars($editDesign['image']) ?>" style="margin-top:8px;height:80px;border-radius:8px;object-fit:cover;">
            <?php endif; ?>
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:10px;">
            <input type="checkbox" name="is_active" id="is_active" <?= ($editDesign['is_active'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--terracotta);">
            <label for="is_active" style="font-weight:600;font-size:14px;cursor:pointer;text-transform:none;letter-spacing:0;">Active (visible to customers)</label>
          </div>
          <button type="submit" class="btn btn-primary w-full"><?= $editDesign ? '💾 Save Changes' : '+ Add Design' ?></button>
        </form>
      </div>
    </div>
  </div>

</div>

<style>@media(max-width:900px){.designs-admin-layout{grid-template-columns:1fr !important;}}</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
