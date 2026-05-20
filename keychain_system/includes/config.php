<?php
// ============================================
// Database Configuration
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'keychain_db');
define('SITE_NAME', 'KeyChain Studio');

// Dynamic SITE_URL — works on localhost, shared hosting, or any domain
(function() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Detect the subfolder the project lives in (e.g. /keychain_system/keychain_build)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $docRoot   = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $subPath   = $docRoot ? rtrim(substr($scriptDir, strlen($docRoot)), '/') : '';
    // Walk up from the current script's folder to the project root (where index.php lives)
    // We find the common base by stripping the last path segment for files inside subfolders
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = rtrim(dirname($scriptName), '/');
    // If we're inside /admin or /pages, go up one more level
    if (preg_match('#/(admin|pages)$#', $base)) {
        $base = dirname($base);
    }
    define('SITE_URL', $protocol . '://' . $host . rtrim($base, '/'));
})();

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
?>
