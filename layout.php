<?php
/**
 * layout.php — Shared header/sidebar/footer included by every page.
 * Usage: include __DIR__ . '/../layout.php';
 */
session_start();
require_once __DIR__ . '/db.php';

$pageTitle  = $pageTitle ?? 'Smart Dairy';
$activeNav  = $activeNav ?? 'dashboard';
$dairyName  = setting('dairy_name', 'Smart Dairy');
$darkMode   = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] == '1';
$currency   = setting('currency', '₹');
$flash      = getFlash();

$navItems = [
    'dashboard' => ['icon' => '🏠', 'label' => 'Dashboard',    'url' => '/milk-management/index.php'],
    'entries'   => ['icon' => '🥛', 'label' => 'Daily Entries', 'url' => '/milk-management/entries/index.php'],
    'customers' => ['icon' => '👥', 'label' => 'Customers',     'url' => '/milk-management/customers/index.php'],
    'payments'  => ['icon' => '💰', 'label' => 'Payments',      'url' => '/milk-management/payments/index.php'],
    'bills'     => ['icon' => '📄', 'label' => 'Bills',         'url' => '/milk-management/bills/index.php'],
    'products'  => ['icon' => '🧀', 'label' => 'Products',      'url' => '/milk-management/products/index.php'],
    'sales'     => ['icon' => '🛒', 'label' => 'Sales',         'url' => '/milk-management/sales/index.php'],
    'routes'    => ['icon' => '🗺️', 'label' => 'Routes',        'url' => '/milk-management/routes/index.php'],
    'reports'   => ['icon' => '📊', 'label' => 'Reports',       'url' => '/milk-management/reports/index.php'],
    'analytics' => ['icon' => '📈', 'label' => 'Analytics',     'url' => '/milk-management/analytics/index.php'],
    'settings'  => ['icon' => '⚙️', 'label' => 'Settings',      'url' => '/milk-management/settings/index.php'],
];
?>
<!DOCTYPE html>
<html lang="en" <?= $darkMode ? 'class="dark"' : '' ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($dairyName) ?></title>
<meta name="description" content="Smart Dairy — Luxury Milk Delivery Management System">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/milk-management/assets/css/style.css">
</head>
<body class="<?= $darkMode ? 'dark' : '' ?>">

<!-- ── Sidebar ───────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <span class="brand-icon">🥛</span>
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
      <?= $darkMode ? '☀️ Light Mode' : '🌙 Dark Mode' ?>
    </button>
  </div>
</nav>

<!-- ── Overlay (mobile) ──────────────────────────────────── -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── Main wrapper ──────────────────────────────────────── -->
<div class="main-wrapper">

  <!-- Top bar -->
  <header class="topbar">
    <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="Menu">☰</button>
    <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
    <div class="topbar-right">
      <span class="topbar-badge">🥛 <?= htmlspecialchars($dairyName) ?></span>
      <span class="topbar-date"><?= date('D, d M Y') ?></span>
    </div>
  </header>

  <!-- Flash message -->
  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?>" id="flashMsg">
    <?= htmlspecialchars($flash['msg']) ?>
    <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
  </div>
  <?php endif; ?>

  <!-- Page content -->
  <main class="page-content">
