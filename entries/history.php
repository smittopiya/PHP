<?php
$pageTitle = 'Entry History'; $activeNav = 'entries';
require_once __DIR__ . '/../layout.php';

$fMonth  = (int)($_GET['month']  ?? date('m'));
$fYear   = (int)($_GET['year']   ?? date('Y'));
$fCust   = (int)($_GET['cid']    ?? 0);
$fShift  = $_GET['shift'] ?? '';

$where = "WHERE MONTH(me.entry_date)=? AND YEAR(me.entry_date)=?";
$params = [$fMonth, $fYear];
if ($fCust)  { $where .= " AND me.customer_id=?"; $params[] = $fCust; }
if ($fShift) { $where .= " AND me.shift=?";        $params[] = $fShift; }

$entries = $pdo->prepare("SELECT me.*, c.name AS cname, c.phone FROM milk_entries me
    JOIN customers c ON c.id=me.customer_id $where ORDER BY me.entry_date DESC, c.name");
$entries->execute($params); $entries = $entries->fetchAll();

$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
$totalLiters = array_sum(array_map(fn($e)=>$e['is_absent']?0:$e['quantity'],$entries));
$totalAmt    = array_sum(array_map(fn($e)=>$e['is_absent']?0:$e['quantity']*$e['rate_per_liter'],$entries));
?>
<div class="page-header">
  <h1 class="page-title">Entry History</h1>
  <a href="index.php" class="btn btn-primary">← Bulk Entry</a>
</div>

<div class="card mb-16">
  <form method="GET" class="search-form flex-wrap">
    <select name="month" class="form-select">
      <?php for ($m=1;$m<=12;$m++): ?>
      <option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-select" style="max-width:100px">
      <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
    </select>
    <select name="cid" class="form-select">
      <option value="">All Customers</option>
      <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $fCust==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="shift" class="form-select" style="max-width:130px">
      <option value="">All Shifts</option>
      <option <?= $fShift==='Morning'?'selected':'' ?>>Morning</option>
      <option <?= $fShift==='Evening'?'selected':'' ?>>Evening</option>
    </select>
    <button class="btn btn-primary">Filter</button>
  </form>
</div>

<div class="grid-3 mb-16">
  <div class="stat-card" style="--accent:#2471A3"><div class="stat-icon">📅</div><div class="stat-value"><?= count($entries) ?></div><div class="stat-label">Total Entries</div></div>
  <div class="stat-card" style="--accent:#1E8449"><div class="stat-icon">🥛</div><div class="stat-value"><?= number_format($totalLiters,1) ?>L</div><div class="stat-label">Total Milk</div></div>
  <div class="stat-card" style="--accent:#E67E22"><div class="stat-icon">💰</div><div class="stat-value"><?= $currency ?><?= number_format($totalAmt,0) ?></div><div class="stat-label">Total Amount</div></div>
</div>

<?php if (empty($entries)): ?>
<div class="empty-state card"><span>🥛</span><p>No entries found for the selected filter.</p></div>
<?php else: ?>
<div class="card">
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Date</th><th>Customer</th><th>Shift</th><th>Qty</th><th>Rate</th><th>Amount</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($entries as $e): ?>
  <tr class="<?= $e['is_absent']?'row-inactive':'' ?>">
    <td><?= date('d M Y',strtotime($e['entry_date'])) ?></td>
    <td><strong><?= htmlspecialchars($e['cname']) ?></strong><br><small><?= $e['phone'] ?></small></td>
    <td><?= $e['shift'] ?></td>
    <td><?= $e['is_absent']?'—':$e['quantity'].'L' ?></td>
    <td><?= $e['is_absent']?'—':$currency.$e['rate_per_liter'] ?></td>
    <td class="fw-bold"><?= $e['is_absent']?'—':$currency.number_format($e['quantity']*$e['rate_per_liter'],2) ?></td>
    <td><?= $e['is_absent']?'<span class="badge badge-danger">Absent</span>':'<span class="badge badge-success">'.$e['shift'].'</span>' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
