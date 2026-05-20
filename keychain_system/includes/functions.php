<?php
// ============================================
// Auth & Helper Functions
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// ---- Auth Functions ----

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/dashboard.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function login($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        return $user;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// ---- Order Helpers ----

function generateOrderRef() {
    return 'KC-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
}

function getOrderStatusLabel($status) {
    $labels = [
        'pending'    => ['label' => 'Pending',    'class' => 'badge-pending'],
        'processing' => ['label' => 'Processing', 'class' => 'badge-processing'],
        'completed'  => ['label' => 'Completed',  'class' => 'badge-completed'],
        'cancelled'  => ['label' => 'Cancelled',  'class' => 'badge-cancelled'],
    ];
    return $labels[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-default'];
}

function getPaymentStatusLabel($status) {
    $labels = [
        'unpaid'   => ['label' => 'Unpaid',   'class' => 'badge-pending'],
        'paid'     => ['label' => 'Paid',     'class' => 'badge-completed'],
        'refunded' => ['label' => 'Refunded', 'class' => 'badge-cancelled'],
    ];
    return $labels[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-default'];
}

// ---- File Upload ----

function uploadFile($file, $folder = 'photos') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
    if ($file['size'] > 5 * 1024 * 1024) return ['error' => 'File too large. Max 5MB.'];
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'path' => $folder . '/' . $filename];
    }
    return ['error' => 'Upload failed.'];
}

// ---- Sanitize ----

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatPrice($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}

// ---- Flash Messages ----

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $icon = $flash['type'] === 'success' ? '✓' : ($flash['type'] === 'error' ? '✕' : 'ℹ');
        echo "<div class='flash flash-{$flash['type']}'><span class='flash-icon'>{$icon}</span>{$flash['message']}</div>";
    }
}

// ---- Stats for Admin ----

function getAdminStats() {
    $db = getDB();
    $stats = [];
    $stats['total_orders'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['pending_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
    $stats['completed_orders'] = $db->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
    $stats['total_customers'] = $db->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $stats['total_revenue'] = $db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE payment_status='paid'")->fetchColumn();
    $stats['total_designs'] = $db->query("SELECT COUNT(*) FROM designs WHERE is_active=1")->fetchColumn();
    return $stats;
}
?>
