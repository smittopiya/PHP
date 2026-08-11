<?php
ob_start(); // Buffer all output so header() redirects always work

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_dairy');
define('DB_USER', 'root');
define('DB_PASS', '');
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
