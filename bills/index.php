<?php
$pageTitle = 'Bills'; $activeNav = 'bills';
require_once __DIR__ . '/../layout.php';
$fMonth = (int)($_GET['month'] ?? date('m'));
$fYear  = (int)($_GET['year']  ?? date('Y'));
$bills  = $pdo->prepare("SELECT mb.*, c.name AS cname, c.phone FROM monthly_bills mb JOIN customers c ON c.id=mb.customer_id WHERE mb.bill_month=? AND mb.bill_year=? ORDER BY c.name");
$bills->execute([$fMonth,$fYear]); $bills = $bills->fetchAll();
$totalDue = array_sum(array_column($bills,'final_due'));
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><span>📄</span> <span class="page-title-gold">Monthly Bills</span></h1>
    <p class="page-sub"><?= date('F Y',mktime(0,0,0,$fMonth,1,$fYear)) ?></p>
  </div>
  <a href="generate.php" class="btn btn-primary">Generate Bills</a>
</div>
<div class="card mb-16">
  <form method="GET" class="search-form">
    <select name="month" class="form-select"><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?></select>
    <select name="year" class="form-select" style="max-width:100px"><?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select>
    <button class="btn btn-primary">Load</button>
  </form>
</div>
<?php if (empty($bills)): ?>
<div class="empty-state card"><span>📄</span><p>No bills generated for <?= date('F',mktime(0,0,0,$fMonth,1)) ?> <?= $fYear ?>.<br><a href="generate.php?month=<?= $fMonth ?>&year=<?= $fYear ?>">Generate now →</a></p></div>
<?php else: ?>
<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:16px">
  <div class="stat-card" style="--accent:#2471A3"><div class="stat-icon">📄</div><div class="stat-value"><?= count($bills) ?></div><div class="stat-label">Bills Generated</div></div>
  <div class="stat-card" style="--accent:#C0392B"><div class="stat-icon">⚠️</div><div class="stat-value"><?= $currency ?><?= number_format($totalDue,0) ?></div><div class="stat-label">Total Due</div></div>
</div>
<div class="card"><div class="table-wrap">
<table class="table">
  <thead><tr><th>CID</th><th>Customer</th><th>Current Bill</th><th>Prev Balance</th><th>Total Paid</th><th>Final Due</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($bills as $i => $b): ?>
  <tr>
    <td><span class="cid-badge">#<?= str_pad($b['customer_id'],4,'0',STR_PAD_LEFT) ?></span></td>
    <td><strong><?= htmlspecialchars($b['cname']) ?></strong><br><small><?= $b['phone'] ?></small></td>
    <td><?= $currency ?><?= number_format($b['current_bill'],2) ?></td>
    <td><?= $currency ?><?= number_format($b['previous_balance'],2) ?></td>
    <td class="text-green"><?= $currency ?><?= number_format($b['total_paid'],2) ?></td>
    <td class="fw-bold <?= $b['final_due']>0?'text-red':'text-green' ?>"><?= $currency ?><?= number_format(abs($b['final_due']),2) ?> <?= $b['final_due']<=0?'<span class="badge badge-success">Paid</span>':'' ?></td>
    <td class="actions-cell">
      <a href="pdf.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-primary">🖨 PDF / Print</a>
      <?php if ($b['phone']): ?>
      <a href="pdf.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-success">📱 WhatsApp PDF</a>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
