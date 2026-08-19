<?php
$pageTitle = 'Add Payment'; $activeNav = 'payments';
require_once __DIR__ . '/../layout.php';

$customers = $pdo->query("SELECT id,name,phone FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
$preCid = (int)($_GET['cid'] ?? 0);
$error = '';
$balance = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid  = (int)$_POST['customer_id'];
    $date = $_POST['payment_date'];
    $amt  = (float)$_POST['amount'];
    $mode = $_POST['payment_mode'] ?? 'Cash';
    $rem  = trim($_POST['remarks'] ?? '');
    if ($amt <= 0) { $error = 'Amount must be greater than 0.'; }
    else {
        $pdo->prepare("INSERT INTO payments (customer_id,payment_date,amount,payment_mode,remarks) VALUES (?,?,?,?,?)")
            ->execute([$cid,$date,$amt,$mode,$rem]);
        flash('success', "Payment of {$currency}{$amt} recorded.");
        header('Location: index.php'); exit;
    }
}

// Load balance for pre-selected customer
if ($preCid) {
    $bq = $pdo->prepare("SELECT
        COALESCE(SUM(me.quantity*me.rate_per_liter),0) + COALESCE(SUM(ps.total_amount),0) - COALESCE(SUM(py.amount),0) AS balance
        FROM customers c
        LEFT JOIN milk_entries me ON me.customer_id=c.id AND me.is_absent=0
        LEFT JOIN product_sales ps ON ps.customer_id=c.id
        LEFT JOIN payments py ON py.customer_id=c.id
        WHERE c.id=?");
    $bq->execute([$preCid]); $balance = max(0, $bq->fetchColumn());
}
?>
<div class="page-header"><h1 class="page-title">Add Payment</h1><a href="index.php" class="btn btn-secondary">← Back</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:520px">
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Customer *</label>
      <select name="customer_id" class="form-select" required onchange="loadBalance(this.value)">
        <option value="">— Select Customer —</option>
        <?php foreach ($customers as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $preCid==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?> <?= $c['phone']?"({$c['phone']})":'' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="balanceBox" class="alert alert-warning" <?= $balance===null?'style="display:none"':'' ?>>
      Current Balance Due: <strong id="balVal"><?= $balance!==null?$currency.number_format($balance,2):'' ?></strong>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Date *</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group"><label class="form-label">Amount (<?= $currency ?>) *</label><input type="number" name="amount" step="0.01" class="form-control" placeholder="0.00" required id="amtInput" <?= $balance!==null?"value=\"".number_format($balance,2).'"':'' ?>></div>
    </div>
    <div class="form-group">
      <label class="form-label">Payment Mode</label>
      <div class="radio-group">
        <?php foreach (['Cash','UPI','Bank Transfer','Cheque'] as $m): ?>
        <label class="radio-label">
          <input type="radio" name="payment_mode" value="<?= $m ?>" <?= $m==='Cash'?'checked':'' ?>> <?= $m ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control" placeholder="Optional note…"></div>
    <div class="form-actions"><button type="submit" class="btn btn-success btn-lg">✔ Save Payment</button><a href="index.php" class="btn btn-secondary btn-lg">Cancel</a></div>
  </form>
</div>
<script>
function loadBalance(cid) {
  if (!cid) { document.getElementById('balanceBox').style.display='none'; return; }
  fetch('/milk-management/api/balance.php?cid='+cid)
    .then(r=>r.json()).then(d=>{
      document.getElementById('balanceBox').style.display='';
      document.getElementById('balVal').textContent = '₹'+parseFloat(d.balance||0).toFixed(2);
      document.getElementById('amtInput').value = parseFloat(d.balance||0).toFixed(2);
    });
}
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
