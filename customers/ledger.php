<?php
$pageTitle = 'Customer Ledger'; $activeNav = 'customers';
require_once __DIR__ . '/../layout.php';

$id   = (int)($_GET['id'] ?? 0);
$st   = $pdo->prepare("SELECT c.*, r.route_name FROM customers c LEFT JOIN routes r ON r.id=c.route_id WHERE c.id=?");
$st->execute([$id]); $cust = $st->fetch();
if (!$cust) { flash('danger','Customer not found.'); header('Location: index.php'); exit; }

// Filter by month/year
$fMonth = (int)($_GET['month'] ?? date('m'));
$fYear  = (int)($_GET['year']  ?? date('Y'));

// Milk entries for the month
$entries = $pdo->prepare("SELECT * FROM milk_entries WHERE customer_id=? AND MONTH(entry_date)=? AND YEAR(entry_date)=? ORDER BY entry_date, shift");
$entries->execute([$id,$fMonth,$fYear]); $entries = $entries->fetchAll();

// Product sales for the month
$sales = $pdo->prepare("SELECT ps.*, p.product_name, p.unit FROM product_sales ps JOIN products p ON p.id=ps.product_id WHERE ps.customer_id=? AND MONTH(ps.sale_date)=? AND YEAR(ps.sale_date)=? ORDER BY ps.sale_date");
$sales->execute([$id,$fMonth,$fYear]); $sales = $sales->fetchAll();

// Payments for the month
$payments = $pdo->prepare("SELECT * FROM payments WHERE customer_id=? AND MONTH(payment_date)=? AND YEAR(payment_date)=? ORDER BY payment_date");
$payments->execute([$id,$fMonth,$fYear]); $payments = $payments->fetchAll();

// Totals
$milkTotal  = array_sum(array_map(fn($e) => $e['is_absent'] ? 0 : $e['quantity']*$e['rate_per_liter'], $entries));
$salesTotal = array_sum(array_column($sales,'total_amount'));
$paidTotal  = array_sum(array_column($payments,'amount'));
$balance    = $milkTotal + $salesTotal - $paidTotal;

$monthName = date('F', mktime(0,0,0,$fMonth,1,$fYear));
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <span class="page-title-gold"><?= htmlspecialchars($cust['name']) ?></span>
      <span class="cid-badge cid-badge-lg">#<?= str_pad($cust['id'],4,'0',STR_PAD_LEFT) ?></span>
    </h1>
    <p class="page-sub">
      <?php if ($cust['phone']): ?>📞 <?= htmlspecialchars($cust['phone']) ?> &nbsp;·&nbsp;<?php endif; ?>
      🥛 <?= $cust['milk_type'] ?> Milk &nbsp;·&nbsp;
      🗺️ <?= htmlspecialchars($cust['route_name'] ?? 'No Route') ?>
    </p>
  </div>
  <div class="header-actions">
    <a href="edit.php?id=<?= $cust['id'] ?>" class="btn btn-outline-gold">✏️ Edit</a>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
</div>

<!-- Month filter -->
<div class="card mb-16">
  <form method="GET" class="search-form">
    <input type="hidden" name="id" value="<?= $id ?>">
    <select name="month" class="form-select">
      <?php for ($m=1;$m<=12;$m++): ?>
      <option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select" style="max-width:110px">
      <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
      <option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
  </form>
</div>

<!-- Summary Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card" style="--accent:#2471A3"><div class="stat-icon">🥛</div><div class="stat-value"><?= $currency ?><?= number_format($milkTotal,2) ?></div><div class="stat-label">Milk Bill</div></div>
  <div class="stat-card" style="--accent:#7D3C98"><div class="stat-icon">🛒</div><div class="stat-value"><?= $currency ?><?= number_format($salesTotal,2) ?></div><div class="stat-label">Product Sales</div></div>
  <div class="stat-card" style="--accent:#1E8449"><div class="stat-icon">💰</div><div class="stat-value"><?= $currency ?><?= number_format($paidTotal,2) ?></div><div class="stat-label">Paid</div></div>
  <div class="stat-card" style="--accent:<?= $balance>0?'#C0392B':'#1E8449' ?>"><div class="stat-icon"><?= $balance>0?'⚠️':'✅' ?></div><div class="stat-value"><?= $currency ?><?= number_format(abs($balance),2) ?></div><div class="stat-label"><?= $balance>0?'Balance Due':'Advance Paid' ?></div></div>
</div>

<div class="grid-2 mt-20">
  <!-- Milk Entries -->
  <div class="card">
    <div class="card-header">
      <h3>Milk Entries — <?= $monthName ?> <?= $fYear ?></h3>
      <a href="/milk-management/entries/add.php?cid=<?= $id ?>" class="btn btn-sm btn-primary">+ Add</a>
    </div>
    <?php if (empty($entries)): ?>
    <div class="empty-state"><span>🥛</span><p>No entries this month.</p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Shift</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
      <tbody>
      <?php foreach ($entries as $e): ?>
      <tr <?= $e['is_absent']?'class="row-inactive"':'' ?>>
        <td><?= date('d M', strtotime($e['entry_date'])) ?></td>
        <td><?= $e['shift'] ?></td>
        <td><?= $e['is_absent']?'<span class="badge badge-danger">Absent</span>':$e['quantity'].'L' ?></td>
        <td><?= $e['is_absent']?'—':$currency.$e['rate_per_liter'] ?></td>
        <td class="fw-bold"><?= $e['is_absent']?'—':$currency.number_format($e['quantity']*$e['rate_per_liter'],2) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="4" class="text-right fw-bold">Total</td><td class="fw-bold text-green"><?= $currency.number_format($milkTotal,2) ?></td></tr></tfoot>
    </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Payments -->
  <div class="card">
    <div class="card-header">
      <h3>Payments — <?= $monthName ?> <?= $fYear ?></h3>
      <a href="/milk-management/payments/add.php?cid=<?= $id ?>" class="btn btn-sm btn-success">+ Add</a>
    </div>
    <?php if (empty($payments)): ?>
    <div class="empty-state"><span>💰</span><p>No payments this month.</p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Remarks</th></tr></thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= date('d M', strtotime($p['payment_date'])) ?></td>
        <td class="fw-bold text-green"><?= $currency.number_format($p['amount'],2) ?></td>
        <td><span class="badge badge-info"><?= $p['payment_mode'] ?></span></td>
        <td><?= htmlspecialchars($p['remarks'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td class="fw-bold">Total Paid</td><td class="fw-bold text-green" colspan="3"><?= $currency.number_format($paidTotal,2) ?></td></tr></tfoot>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Generate Bill button -->
<div class="card mt-20 text-center">
  <a href="/milk-management/bills/generate.php?cid=<?= $id ?>&month=<?= $fMonth ?>&year=<?= $fYear ?>" class="btn btn-primary btn-lg">📄 Generate Bill for <?= $monthName ?> <?= $fYear ?></a>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
