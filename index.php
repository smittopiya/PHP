<?php
$pageTitle = 'Dashboard'; $activeNav = 'dashboard';
require_once __DIR__ . '/layout.php';

// ── Stats ──────────────────────────────────────────────────
$today = date('Y-m-d');
$month = date('m'); $year = date('Y');

// Today deliveries
$st = $pdo->prepare("SELECT COUNT(*) FROM milk_entries WHERE entry_date=? AND is_absent=0");
$st->execute([$today]); $todayDeliveries = $st->fetchColumn();

// Today absent
$st = $pdo->prepare("SELECT COUNT(*) FROM milk_entries WHERE entry_date=? AND is_absent=1");
$st->execute([$today]); $todayAbsent = $st->fetchColumn();

// Today collection
$st = $pdo->prepare("SELECT COALESCE(SUM(quantity*rate_per_liter),0) FROM milk_entries WHERE entry_date=? AND is_absent=0");
$st->execute([$today]); $todayCollection = $st->fetchColumn();

// Month total collection (milk)
$st = $pdo->prepare("SELECT COALESCE(SUM(quantity*rate_per_liter),0) FROM milk_entries WHERE MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0");
$st->execute([$month,$year]); $monthMilk = $st->fetchColumn();

// Month product sales
$st = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE MONTH(sale_date)=? AND YEAR(sale_date)=?");
$st->execute([$month,$year]); $monthSales = $st->fetchColumn();

// Month payments received
$st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=? AND YEAR(payment_date)=?");
$st->execute([$month,$year]); $monthPayments = $st->fetchColumn();

// Total pending balance (all customers)
$pendingQ = $pdo->query("
  SELECT COALESCE(SUM(e.quantity*e.rate_per_liter),0) + COALESCE(SUM(ps.total_amount),0) - COALESCE(SUM(py.amount),0)
  FROM customers c
  LEFT JOIN milk_entries e ON e.customer_id=c.id AND e.is_absent=0
  LEFT JOIN product_sales ps ON ps.customer_id=c.id
  LEFT JOIN payments py ON py.customer_id=c.id
  WHERE c.status='Active'
");
$totalPending = max(0, $pendingQ->fetchColumn());

// Active customers
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM customers WHERE status='Active'")->fetchColumn();

// Recent entries (last 10)
$recentEntries = $pdo->query("
  SELECT me.*, c.name AS cname, c.phone
  FROM milk_entries me
  JOIN customers c ON c.id=me.customer_id
  ORDER BY me.entry_date DESC, me.id DESC LIMIT 10
")->fetchAll();

// Recent payments (last 5)
$recentPayments = $pdo->query("
  SELECT p.*, c.name AS cname FROM payments p
  JOIN customers c ON c.id=p.customer_id
  ORDER BY p.payment_date DESC, p.id DESC LIMIT 5
")->fetchAll();
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><span>🏠</span> <span class="page-title-gold">Dashboard</span></h1>
    <p class="page-sub">Welcome back &mdash; <?= date('l, d F Y') ?></p>
  </div>
  <div class="header-actions">
    <a href="/milk-management/entries/index.php" class="btn btn-primary">+ Daily Entry</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card" style="--accent-color:var(--green)">
    <span class="stat-icon">🥛</span>
    <div class="stat-value"><?= $todayDeliveries ?></div>
    <div class="stat-label">Today's Deliveries</div>
    <div class="stat-sub"><?= date('d M Y') ?> &bull; <?= $todayAbsent ?> absent</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--gold)">
    <span class="stat-icon">💰</span>
    <div class="stat-value"><?= $currency ?><?= number_format($monthMilk + $monthSales, 0) ?></div>
    <div class="stat-label">Month Collection</div>
    <div class="stat-sub">Milk + Products</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--red)">
    <span class="stat-icon">⚠️</span>
    <div class="stat-value"><?= $currency ?><?= number_format($totalPending, 0) ?></div>
    <div class="stat-label">Pending Balance</div>
    <div class="stat-sub">All customers</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--blue)">
    <span class="stat-icon">👥</span>
    <div class="stat-value"><?= $totalCustomers ?></div>
    <div class="stat-label">Active Customers</div>
    <div class="stat-sub"><?= $currency ?><?= number_format($monthPayments,0) ?> received this month</div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card card-gold mt-20">
  <div class="card-header"><h3>⚡ Quick Actions</h3></div>
  <!-- Row 1 — Primary daily actions -->
  <div class="quick-actions" style="grid-template-columns:repeat(3,1fr);margin-bottom:12px">
    <a href="/milk-management/entries/index.php"  class="qa-btn qa-gold">  <i>🥛</i><span>Bulk Entry</span></a>
    <a href="/milk-management/payments/add.php"   class="qa-btn qa-green"> <i>💰</i><span>Add Payment</span></a>
    <a href="/milk-management/bills/generate.php" class="qa-btn qa-purple"><i>📄</i><span>Generate Bill</span></a>
  </div>
  <!-- Row 2 — Secondary actions -->
  <div class="quick-actions" style="grid-template-columns:repeat(3,1fr)">
    <a href="/milk-management/customers/add.php"  class="qa-btn qa-teal">  <i>👤</i><span>Add Customer</span></a>
    <a href="/milk-management/sales/add.php"      class="qa-btn qa-orange"><i>🛒</i><span>Record Sale</span></a>
    <a href="/milk-management/analytics/index.php" class="qa-btn qa-blue"> <i>📈</i><span>Analytics</span></a>
  </div>
</div>

<!-- Recent Entries + Recent Payments -->
<div class="grid-2 mt-20">
  <!-- Recent Entries -->
  <div class="card">
    <div class="card-header">
      <h3>Recent Entries</h3>
      <a href="/milk-management/entries/history.php" class="card-link">View All →</a>
    </div>
    <?php if (empty($recentEntries)): ?>
    <div class="empty-state"><span>🥛</span><p>No entries yet. <a href="/milk-management/entries/add.php">Add first entry</a></p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Customer</th><th>Date</th><th>Qty</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentEntries as $e): ?>
      <tr>
        <td><?= htmlspecialchars($e['cname']) ?></td>
        <td><?= date('d M', strtotime($e['entry_date'])) ?></td>
        <td><?= $e['is_absent'] ? '—' : $e['quantity'].'L' ?></td>
        <td><?= $e['is_absent'] ? '—' : $currency.number_format($e['quantity']*$e['rate_per_liter'],1) ?></td>
        <td><?= $e['is_absent'] ? '<span class="badge badge-danger">Absent</span>' : '<span class="badge badge-success">'.$e['shift'].'</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Payments -->
  <div class="card">
    <div class="card-header">
      <h3>Recent Payments</h3>
      <a href="/milk-management/payments/index.php" class="card-link">View All →</a>
    </div>
    <?php if (empty($recentPayments)): ?>
    <div class="empty-state"><span>💰</span><p>No payments yet. <a href="/milk-management/payments/add.php">Add payment</a></p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Customer</th><th>Date</th><th>Amount</th><th>Mode</th></tr></thead>
      <tbody>
      <?php foreach ($recentPayments as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['cname']) ?></td>
        <td><?= date('d M', strtotime($p['payment_date'])) ?></td>
        <td class="text-green fw-bold"><?= $currency.number_format($p['amount'],2) ?></td>
        <td><span class="badge badge-info"><?= $p['payment_mode'] ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
