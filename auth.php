<?php
/**
 * auth.php — Include at the top of any protected page.
 * Checks session for logged-in user; redirects to login.php if not.
 *
 * Credentials (hashed for security):
 *   Username: MomaiDairy
 *   Password: MomaiDairy
 */

// Pre-hashed credentials (password_hash output for 'MomaiDairy')
define('AUTH_USERNAME', 'MomaiDairy');
define('AUTH_PASS_HASH', password_hash('MomaiDairy', PASSWORD_DEFAULT));

if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool {
    return !empty($_SESSION['auth_logged_in']) && $_SESSION['auth_logged_in'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /milk-management/login.php');
        exit;
    }
}

