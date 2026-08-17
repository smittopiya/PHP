<?php
$pageTitle = 'Generate Bills'; $activeNav = 'bills';
require_once __DIR__ . '/../layout.php';

$fMonth = (int)($_GET['month']  ?? date('m'));
$fYear  = (int)($_GET['year']   ?? date('Y'));
$cid    = (int)($_GET['cid']    ?? 0);
$generated = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = (int)$_POST['month'];
    $year  = (int)$_POST['year'];
    $cids  = $_POST['cids'] ?? [];

    foreach ($cids as $cid) {
        $cid = (int)$cid;
        // Milk total
        $st = $pdo->prepare("SELECT COALESCE(SUM(quantity*rate_per_liter),0) FROM milk_entries WHERE customer_id=? AND MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0");
        $st->execute([$cid,$month,$year]); $milk = $st->fetchColumn();

        // Product total
        $st = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM product_sales WHERE customer_id=? AND MONTH(sale_date)=? AND YEAR(sale_date)=?");
        $st->execute([$cid,$month,$year]); $prods = $st->fetchColumn();

        $current = $milk + $prods;

        // Previous balance (last month's final_due)
        $pm = $month==1?12:$month-1; $py = $month==1?$year-1:$year;
        $st = $pdo->prepare("SELECT COALESCE(final_due,0) FROM monthly_bills WHERE customer_id=? AND bill_month=? AND bill_year=?");
        $st->execute([$cid,$pm,$py]); $prevBal = max(0,$st->fetchColumn());

        // Total paid this month
        $st = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE customer_id=? AND MONTH(payment_date)=? AND YEAR(payment_date)=?");
        $st->execute([$cid,$month,$year]); $paid = $st->fetchColumn();

        $finalDue = $current + $prevBal - $paid;

        $pdo->prepare("INSERT INTO monthly_bills (customer_id,bill_month,bill_year,current_bill,previous_balance,total_paid,final_due)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE current_bill=VALUES(current_bill),previous_balance=VALUES(previous_balance),total_paid=VALUES(total_paid),final_due=VALUES(final_due)")
            ->execute([$cid,$month,$year,$current,$prevBal,$paid,$finalDue]);
        $generated[] = $cid;
    }
    flash('success', count($generated).' bill(s) generated for '.date('F',mktime(0,0,0,$month,1)).' '.$year.'.');
    header("Location: index.php?month=$month&year=$year"); exit;
}

$customers = $pdo->query("SELECT c.*, r.route_name FROM customers c LEFT JOIN routes r ON r.id=c.route_id WHERE c.status='Active' ORDER BY r.route_name, c.route_sequence, c.name")->fetchAll();
?>
<div class="page-header"><h1 class="page-title">Generate Bills</h1><a href="index.php" class="btn btn-secondary">← Bills List</a></div>
<div class="card mb-16">
  <form method="GET" class="search-form">
    <select name="month" class="form-select"><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?></select>
    <select name="year" class="form-select" style="max-width:100px"><?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select>
    <button class="btn btn-primary">Load Customers</button>
  </form>
</div>
<form method="POST">
  <input type="hidden" name="month" value="<?= $fMonth ?>">
  <input type="hidden" name="year"  value="<?= $fYear ?>">
  <div class="card">
    <div class="card-header">
      <h3>Customers — <?= date('F',mktime(0,0,0,$fMonth,1)) ?> <?= $fYear ?></h3>
      <div>
        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAll(true)">Select All</button>
        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleAll(false)">Deselect All</button>
      </div>
    </div>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th><input type="checkbox" onchange="toggleAll(this.checked)"></th><th>Customer</th><th>Route</th><th>Phone</th></tr></thead>
      <tbody>
      <?php foreach ($customers as $c): ?>
      <tr>
        <td><input type="checkbox" name="cids[]" class="cust-check" value="<?= $c['id'] ?>" checked></td>
        <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
        <td><?= htmlspecialchars($c['route_name'] ?: '—') ?></td>
        <td><?= htmlspecialchars($c['phone'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-lg">📄 Generate Selected Bills</button>
    </div>
  </div>
</form>
<script>
function toggleAll(val) {
  document.querySelectorAll('.cust-check').forEach(cb => cb.checked = val);
}
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
