<?php
/**
 * auth.php &mdash; Include at the top of any protected page.
 * Checks session for logged-in user; redirects to login.php if not.
 *
 * Credentials:
 *   Username: MomaiDairy
 *   Password: MomaiDairy
 */

// Pre-hashed credentials
define('AUTH_USERNAME', 'MomaiDairy');
define('AUTH_PASS_HASH', password_hash('MomaiDairy', PASSWORD_DEFAULT));

// Auto-detect base path: /milk-management/ on localhost, / on live
$_isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']);
define('BASE_PATH', $_isLocal ? '/milk-management/' : '/');

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . 'login.php');
        exit;
    }
}

