<?php
ob_start(); // Buffer all output so header() redirects always work
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=utf-8');

/**
 * Auto-detect environment:
 * - localhost / 127.0.0.1 = Laragon (local)
 * - anything else         = InfinityFree (live)
 */
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);

if ($isLocal) {
    // ── LOCAL (Laragon) ──────────────────────────
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'smart_dairy');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // ── LIVE (InfinityFree) ──────────────────────
    define('DB_HOST', 'sql206.infinityfree.com');
    define('DB_NAME', 'if0_42651337_MomaiDairy');
    define('DB_USER', 'if0_42651337');
    define('DB_PASS', 'SmitPatel6513');
}
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Helper: get a setting value
function setting($key, $default = '') {
    global $pdo;
    $st = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $st->execute([$key]);
    $row = $st->fetch();
    return $row ? $row['setting_value'] : $default;
}

// Helper: flash messages via session
function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}
