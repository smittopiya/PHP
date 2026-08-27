<?php
/**
 * layout.php &mdash; Shared header/sidebar/footer included by every page.
 * Usage: include __DIR__ . '/../layout.php';
 */
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireLogin(); // Redirect to login.php if not authenticated

$pageTitle  = $pageTitle ?? 'Smart Dairy';
$activeNav  = $activeNav ?? 'dashboard';
$dairyName  = setting('dairy_name', 'Smart Dairy');
$darkMode   = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] == '1';
$currency   = setting('currency', '&#x20B9;');
$flash      = getFlash();

$navItems = [
    'dashboard' => ['icon' => '&#x1F3E0;', 'label' => 'Dashboard',    'url' => BASE_PATH.'index.php'],
    'entries'   => ['icon' => '&#x1F95B;', 'label' => 'Daily Entries', 'url' => BASE_PATH.'entries/index.php'],
    'customers' => ['icon' => '&#x1F465;', 'label' => 'Customers',     'url' => BASE_PATH.'customers/index.php'],
    'payments'  => ['icon' => '&#x1F4B0;', 'label' => 'Payments',      'url' => BASE_PATH.'payments/index.php'],
    'bills'     => ['icon' => '&#x1F4C4;', 'label' => 'Bills',         'url' => BASE_PATH.'bills/index.php'],
    'products'  => ['icon' => '&#x1F9C0;', 'label' => 'Products',      'url' => BASE_PATH.'products/index.php'],
    'sales'     => ['icon' => '&#x1F6D2;', 'label' => 'Sales',         'url' => BASE_PATH.'sales/index.php'],
    'routes'    => ['icon' => '&#x1F5FA;', 'label' => 'Routes',        'url' => BASE_PATH.'routes/index.php'],
    'reports'   => ['icon' => '&#x1F4CA;', 'label' => 'Reports',       'url' => BASE_PATH.'reports/index.php'],
    'analytics' => ['icon' => '&#x1F4C8;', 'label' => 'Analytics',     'url' => BASE_PATH.'analytics/index.php'],
    'settings'  => ['icon' => '&#x2699;', 'label' => 'Settings',      'url' => BASE_PATH.'settings/index.php'],
];
?>
<!DOCTYPE html>
<html lang="en" <?= $darkMode ? 'class="dark"' : '' ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> &mdash; <?= htmlspecialchars($dairyName) ?></title>
<meta name="description" content="Smart Dairy &mdash; Luxury Milk Delivery Management System">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style><?php include __DIR__ . '/assets/css/style.css'; ?></style>
</head>
<body class="<?= $darkMode ? 'dark' : '' ?>">

<!-- ── Sidebar ───────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">&#x1F95B;</span>
    <div>
      <span class="brand-name"><?= htmlspecialchars($dairyName) ?></span>
      <span class="brand-sub">Management Suite</span>
    </div>
  </div>
  <ul class="sidebar-nav">
    <?php foreach ($navItems as $key => $item): ?>
    <li>
      <a href="<?= $item['url'] ?>" class="nav-link <?= $activeNav === $key ? 'active' : '' ?>">
        <span class="nav-icon"><?= $item['icon'] ?></span>
        <span class="nav-label"><?= $item['label'] ?></span>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="sidebar-footer">
    <button class="btn-dark-toggle" onclick="toggleDark()" title="Toggle Dark Mode">
      <?= $darkMode ? '&#x2600; Light Mode' : '&#x1F319; Dark Mode' ?>
    </button>
    <a href="<?= BASE_PATH ?>logout.php" class="btn-dark-toggle" style="text-decoration:none;display:block;margin-top:6px;text-align:center;background:rgba(220,38,38,0.15);color:#F87171;border:1px solid rgba(220,38,38,0.25)" onclick="return confirm('Are you sure you want to logout?')">&#x1F513; Logout</a>
  </div>
</nav>

<!-- ── Overlay (mobile) ──────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── Main wrapper ──────────────────────────────────────── -->
<div class="main-wrapper">

  <!-- Top bar -->
  <header class="topbar">
    <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Menu">&#x2630;</button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
    <div class="topbar-right">
      <span class="topbar-badge">&#x1F95B; <?= htmlspecialchars($dairyName) ?></span>
      <span class="topbar-date"><?= date('D, d M Y') ?></span>
    </div>
  </header>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?>" id="flashMsg">
    <?= htmlspecialchars($flash['msg']) ?>
    <button class="alert-close" onclick="this.parentElement.remove()">&#x2715;</button>
  </div>
  <?php endif; ?>

  <!-- Page content -->
  <main class="page-content">
