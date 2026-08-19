<?php
$pageTitle = 'Payments'; $activeNav = 'payments';
require_once __DIR__ . '/../layout.php';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM payments WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success','Payment deleted.'); header('Location: index.php'); exit;
}

$fMonth = (int)($_GET['month'] ?? date('m'));
$fYear  = (int)($_GET['year']  ?? date('Y'));
$fCust  = (int)($_GET['cid']   ?? 0);

$where = "WHERE MONTH(p.payment_date)=? AND YEAR(p.payment_date)=?";
$params = [$fMonth,$fYear];
if ($fCust) { $where .= " AND p.customer_id=?"; $params[] = $fCust; }

$payments = $pdo->prepare("SELECT p.*, c.name AS cname FROM payments p JOIN customers c ON c.id=p.customer_id $where ORDER BY p.payment_date DESC, p.id DESC");
$payments->execute($params); $payments = $payments->fetchAll();
$totalPaid = array_sum(array_column($payments,'amount'));

$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();

// Mode breakdown
$modes = [];
foreach ($payments as $p) $modes[$p['payment_mode']] = ($modes[$p['payment_mode']] ?? 0) + $p['amount'];
?>
<div class="page-header">
  <h1 class="page-title">Payments</h1>
  <a href="add.php" class="btn btn-success">+ Add Payment</a>
</div>

<div class="card mb-16">
  <form method="GET" class="search-form flex-wrap">
    <select name="month" class="form-select">
      <?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?>
    </select>
    <select name="year" class="form-select" style="max-width:100px">
      <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
    </select>
    <select name="cid" class="form-select">
      <option value="">All Customers</option>
      <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $fCust==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
  </form>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:16px">
  <div class="stat-card" style="--accent:#1E8449"><div class="stat-icon">💰</div><div class="stat-value"><?= $currency ?><?= number_format($totalPaid,2) ?></div><div class="stat-label">Total Collected</div></div>
  <?php foreach ($modes as $mode => $amt): ?>
  <div class="stat-card" style="--accent:#2471A3"><div class="stat-icon">💳</div><div class="stat-value"><?= $currency ?><?= number_format($amt,0) ?></div><div class="stat-label"><?= $mode ?></div></div>
  <?php endforeach; ?>
</div>

<?php if (empty($payments)): ?>
<div class="empty-state card"><span>💰</span><p>No payments for selected period. <a href="add.php">Add payment</a></p></div>
<?php else: ?>
<div class="card">
<div class="table-wrap">
<table class="table">
  <thead><tr><th>#</th><th>Customer</th><th>Date</th><th>Amount</th><th>Mode</th><th>Remarks</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($payments as $i => $p): ?>
  <tr>
    <td><?= $i+1 ?></td>
    <td><a href="/milk-management/customers/ledger.php?id=<?= $p['customer_id'] ?>"><?= htmlspecialchars($p['cname']) ?></a></td>
    <td><?= date('d M Y',strtotime($p['payment_date'])) ?></td>
    <td class="fw-bold text-green"><?= $currency ?><?= number_format($p['amount'],2) ?></td>
    <td><span class="badge badge-info"><?= $p['payment_mode'] ?></span></td>
    <td><?= htmlspecialchars($p['remarks'] ?: '—') ?></td>
    <td><button onclick="confirmDelete('?delete=<?= $p['id'] ?>','payment of <?= $currency.$p['amount'] ?>')" class="btn btn-sm btn-danger">Delete</button></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td colspan="3" class="fw-bold text-right">Total</td><td class="fw-bold text-green"><?= $currency ?><?= number_format($totalPaid,2) ?></td><td colspan="3"></td></tr></tfoot>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
