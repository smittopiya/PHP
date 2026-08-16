<?php
$pageTitle = 'Product Sales'; $activeNav = 'sales';
require_once __DIR__ . '/../layout.php';

if (isset($_GET['delete'])) { $pdo->prepare("DELETE FROM product_sales WHERE id=?")->execute([(int)$_GET['delete']]); flash('success','Sale deleted.'); header('Location: index.php'); exit; }

$fMonth = (int)($_GET['month'] ?? date('m'));
$fYear  = (int)($_GET['year']  ?? date('Y'));
$fCust  = (int)($_GET['cid']   ?? 0);
$where  = "WHERE MONTH(ps.sale_date)=? AND YEAR(ps.sale_date)=?";
$params = [$fMonth,$fYear];
if ($fCust) { $where .= " AND ps.customer_id=?"; $params[] = $fCust; }
$sales = $pdo->prepare("SELECT ps.*, c.name AS cname, p.product_name, p.unit FROM product_sales ps JOIN customers c ON c.id=ps.customer_id JOIN products p ON p.id=ps.product_id $where ORDER BY ps.sale_date DESC, ps.id DESC");
$sales->execute($params); $sales = $sales->fetchAll();
$totalSales = array_sum(array_column($sales,'total_amount'));
$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
?>
<div class="page-header"><h1 class="page-title">Product Sales</h1><a href="add.php" class="btn btn-primary">+ Record Sale</a></div>
<div class="card mb-16">
  <form method="GET" class="search-form flex-wrap">
    <select name="month" class="form-select"><?php for ($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$fMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?></select>
    <select name="year" class="form-select" style="max-width:100px"><?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option <?= $y==$fYear?'selected':'' ?>><?= $y ?></option><?php endfor; ?></select>
    <select name="cid" class="form-select"><option value="">All Customers</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $fCust==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-primary">Filter</button>
  </form>
</div>
<div class="stats-grid" style="grid-template-columns:repeat(2,1fr);margin-bottom:16px">
  <div class="stat-card" style="--accent:#7D3C98"><div class="stat-icon">🛒</div><div class="stat-value"><?= count($sales) ?></div><div class="stat-label">Total Sales</div></div>
  <div class="stat-card" style="--accent:#1E8449"><div class="stat-icon">💰</div><div class="stat-value"><?= $currency ?><?= number_format($totalSales,2) ?></div><div class="stat-label">Total Revenue</div></div>
</div>
<?php if (empty($sales)): ?>
<div class="empty-state card"><span>🛒</span><p>No sales for selected period. <a href="add.php">Record a sale</a></p></div>
<?php else: ?>
<div class="card"><div class="table-wrap">
<table class="table">
  <thead><tr><th>#</th><th>Customer</th><th>Product</th><th>Date</th><th>Qty</th><th>Price/Unit</th><th>Total</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($sales as $i => $s): ?>
  <tr>
    <td><?= $i+1 ?></td>
    <td><?= htmlspecialchars($s['cname']) ?></td>
    <td><?= htmlspecialchars($s['product_name']) ?> <small>(<?= $s['unit'] ?>)</small></td>
    <td><?= date('d M Y',strtotime($s['sale_date'])) ?></td>
    <td><?= $s['quantity'] ?> <?= $s['unit'] ?></td>
    <td><?= $currency ?><?= $s['price_per_unit'] ?></td>
    <td class="fw-bold"><?= $currency ?><?= number_format($s['total_amount'],2) ?></td>
    <td><button onclick="confirmDelete('?delete=<?= $s['id'] ?>','sale')" class="btn btn-sm btn-danger">Delete</button></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td colspan="6" class="text-right fw-bold">Total</td><td class="fw-bold text-green"><?= $currency ?><?= number_format($totalSales,2) ?></td><td></td></tr></tfoot>
</table>
</div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
