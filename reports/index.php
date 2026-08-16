<?php
/**
 * reports/index.php
 * CSV export MUST run before layout.php (which outputs HTML headers).
 */
require_once __DIR__ . '/../db.php';

$fMonth = (int)($_GET['month'] ?? date('m'));
$fYear  = (int)($_GET['year']  ?? date('Y'));

// ── CSV Export (runs BEFORE any HTML output) ──────────────────
if (isset($_GET['export'])) {
    $expType = $_GET['export'];

    // Clear any buffered output so NO HTML leaks into the file
    if (ob_get_level()) ob_end_clean();

    // Set CSV headers
    $filename = strtolower($expType) . '_' . date('M', mktime(0,0,0,$fMonth,1)) . '_' . $fYear . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // UTF-8 BOM so Excel opens it correctly with special chars
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    if ($expType === 'entries') {
        fputcsv($out, ['Date', 'Customer Name', 'CID', 'Shift', 'Quantity (L)', 'Rate (₹/L)', 'Amount (₹)', 'Status']);
        $rows = $pdo->prepare(
            "SELECT me.entry_date, c.name, c.id, me.shift,
                    me.quantity, me.rate_per_liter,
                    ROUND(me.quantity * me.rate_per_liter, 2),
                    me.is_absent
             FROM milk_entries me
             JOIN customers c ON c.id = me.customer_id
             WHERE MONTH(me.entry_date) = ? AND YEAR(me.entry_date) = ?
             ORDER BY me.entry_date, c.name"
        );
        $rows->execute([$fMonth, $fYear]);
        foreach ($rows->fetchAll(PDO::FETCH_NUM) as $r) {
            $r[7] = $r[7] ? 'Absent' : 'Present';
            $r[2] = '#' . str_pad($r[2], 4, '0', STR_PAD_LEFT); // Format CID
            fputcsv($out, $r);
        }

    } elseif ($expType === 'payments') {
        fputcsv($out, ['Date', 'Customer Name', 'CID', 'Amount (₹)', 'Payment Mode', 'Remarks']);
        $rows = $pdo->prepare(
            "SELECT p.payment_date, c.name, c.id, p.amount, p.payment_mode, p.remarks
             FROM payments p
             JOIN customers c ON c.id = p.customer_id
             WHERE MONTH(p.payment_date) = ? AND YEAR(p.payment_date) = ?
             ORDER BY p.payment_date, c.name"
        );
        $rows->execute([$fMonth, $fYear]);
        foreach ($rows->fetchAll(PDO::FETCH_NUM) as $r) {
            $r[2] = '#' . str_pad($r[2], 4, '0', STR_PAD_LEFT);
            fputcsv($out, $r);
        }

    } elseif ($expType === 'balance') {
        fputcsv($out, ['CID', 'Customer Name', 'Phone', 'Milk Bill (₹)', 'Product Bill (₹)', 'Total Bill (₹)', 'Total Paid (₹)', 'Balance Due (₹)']);
        $rows = $pdo->query(
            "SELECT c.id, c.name, c.phone,
                COALESCE((SELECT SUM(quantity*rate_per_liter) FROM milk_entries  WHERE customer_id=c.id AND is_absent=0),0) AS milk,
                COALESCE((SELECT SUM(total_amount)           FROM product_sales  WHERE customer_id=c.id),0)                AS prods,
                COALESCE((SELECT SUM(amount)                 FROM payments       WHERE customer_id=c.id),0)                AS paid
             FROM customers c
             WHERE c.status='Active'
             ORDER BY c.name"
        );
        foreach ($rows->fetchAll() as $r) {
            $total = round($r['milk'] + $r['prods'], 2);
            $bal   = round($total - $r['paid'], 2);
            fputcsv($out, [
                '#' . str_pad($r['id'], 4, '0', STR_PAD_LEFT),
                $r['name'],
                $r['phone'],
                round($r['milk'], 2),
                round($r['prods'], 2),
                $total,
                round($r['paid'], 2),
                $bal,
            ]);
        }
    }

    fclose($out);
    exit; // Stop — no HTML after this point
}

// ── Normal page load ──────────────────────────────────────────
$pageTitle = 'Reports'; $activeNav = 'reports';
require_once __DIR__ . '/../layout.php';

// Summary data
$milkTotal = $pdo->prepare("SELECT COALESCE(SUM(quantity*rate_per_liter),0) FROM milk_entries WHERE MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0");
$milkTotal->execute([$fMonth,$fYear]); $milkTotal = $milkTotal->fetchColumn();

$salesTotal = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE MONTH(sale_date)=? AND YEAR(sale_date)=?");
$salesTotal->execute([$fMonth,$fYear]); $salesTotal = $salesTotal->fetchColumn();

$paidTotal = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=? AND YEAR(payment_date)=?");
$paidTotal->execute([$fMonth,$fYear]); $paidTotal = $paidTotal->fetchColumn();

$absentDays = $pdo->prepare("SELECT COUNT(*) FROM milk_entries WHERE MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=1");
$absentDays->execute([$fMonth,$fYear]); $absentDays = $absentDays->fetchColumn();

$totalLiters = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM milk_entries WHERE MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0");
$totalLiters->execute([$fMonth,$fYear]); $totalLiters = $totalLiters->fetchColumn();

$balances = $pdo->query(
    "SELECT c.id, c.name, c.phone,
        COALESCE((SELECT SUM(quantity*rate_per_liter) FROM milk_entries  WHERE customer_id=c.id AND is_absent=0),0) AS milk_bill,
        COALESCE((SELECT SUM(total_amount)           FROM product_sales  WHERE customer_id=c.id),0)                AS prod_bill,
        COALESCE((SELECT SUM(amount)                 FROM payments       WHERE customer_id=c.id),0)                AS total_paid
     FROM customers c WHERE c.status='Active' ORDER BY c.name"
)->fetchAll();
?>

<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><span>📊</span> <span class="page-title-gold">Reports</span></h1>
    <p class="page-sub"><?= date('F Y', mktime(0,0,0,$fMonth,1,$fYear)) ?> — Monthly Summary</p>
  </div>
</div>

<!-- Month / Year Filter -->
<div class="card mb-16">
  <form method="GET" class="search-form">
    <select name="month" class="form-select" style="max-width:150px">
      <?php for ($m=1;$m<=12;$m++): ?>
      <option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select" style="max-width:100px">
      <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
      <option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn btn-primary">Load</button>
  </form>
</div>

<!-- Monthly Stats -->
<div class="stats-grid">
  <div class="stat-card" style="--accent-color:var(--blue)">
    <span class="stat-icon">🥛</span>
    <div class="stat-value"><?= number_format($totalLiters,1) ?>L</div>
    <div class="stat-label">Milk Collected</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--green)">
    <span class="stat-icon">💰</span>
    <div class="stat-value"><?= $currency ?><?= number_format($milkTotal,0) ?></div>
    <div class="stat-label">Milk Revenue</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--purple)">
    <span class="stat-icon">🛒</span>
    <div class="stat-value"><?= $currency ?><?= number_format($salesTotal,0) ?></div>
    <div class="stat-label">Product Revenue</div>
  </div>
  <div class="stat-card" style="--accent-color:var(--gold)">
    <span class="stat-icon">💳</span>
    <div class="stat-value"><?= $currency ?><?= number_format($paidTotal,0) ?></div>
    <div class="stat-label">Payments Received</div>
    <div class="stat-sub"><?= $absentDays ?> absent entries</div>
  </div>
</div>

<!-- CSV Export Buttons -->
<div class="card card-gold mt-20">
  <div class="card-header"><h3>📥 Export to CSV</h3></div>
  <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:14px">
    Files open correctly in Excel, Google Sheets &amp; any spreadsheet app.
  </p>
  <div class="quick-actions" style="grid-template-columns:repeat(3,1fr)">
    <a href="?month=<?= $fMonth ?>&year=<?= $fYear ?>&export=entries"
       class="qa-btn qa-blue">
      📋 <span>Milk Entries<br><small><?= date('M Y',mktime(0,0,0,$fMonth,1,$fYear)) ?></small></span>
    </a>
    <a href="?month=<?= $fMonth ?>&year=<?= $fYear ?>&export=payments"
       class="qa-btn qa-green">
      💸 <span>Payments<br><small><?= date('M Y',mktime(0,0,0,$fMonth,1,$fYear)) ?></small></span>
    </a>
    <a href="?month=<?= $fMonth ?>&year=<?= $fYear ?>&export=balance"
       class="qa-btn qa-red">
      📊 <span>Balance Sheet<br><small>All Time</small></span>
    </a>
  </div>
</div>

<!-- Customer Balance Table -->
<div class="card mt-20">
  <div class="card-header">
    <h3>📋 Customer Balance Sheet (All Time)</h3>
    <a href="?month=<?= $fMonth ?>&year=<?= $fYear ?>&export=balance" class="btn btn-sm btn-primary">⬇ CSV</a>
  </div>
  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>CID</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Milk Bill</th>
        <th>Product Bill</th>
        <th>Total Bill</th>
        <th>Total Paid</th>
        <th>Balance</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($balances as $i => $b):
      $total = $b['milk_bill'] + $b['prod_bill'];
      $bal   = $total - $b['total_paid'];
    ?>
    <tr>
      <td><span class="cid-badge">#<?= str_pad($b['id'],4,'0',STR_PAD_LEFT) ?></span></td>
      <td><a href="/milk-management/customers/ledger.php?id=<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></a></td>
      <td><?= $b['phone'] ?: '—' ?></td>
      <td><?= $currency ?><?= number_format($b['milk_bill'],2) ?></td>
      <td><?= $currency ?><?= number_format($b['prod_bill'],2) ?></td>
      <td class="fw-bold"><?= $currency ?><?= number_format($total,2) ?></td>
      <td class="text-green"><?= $currency ?><?= number_format($b['total_paid'],2) ?></td>
      <td class="fw-bold <?= $bal>0?'text-red':'text-green' ?>">
        <?= $currency ?><?= number_format(abs($bal),2) ?>
        <?= $bal<=0 ? '<span class="badge badge-success">Paid</span>' : '' ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
