<?php
$pageTitle = 'Record Sale'; $activeNav = 'sales';
require_once __DIR__ . '/../layout.php';
$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
$products  = $pdo->query("SELECT * FROM products WHERE status='Active' ORDER BY product_name")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid   = (int)$_POST['customer_id'];
    $pid   = (int)$_POST['product_id'];
    $date  = $_POST['sale_date'];
    $qty   = (float)$_POST['quantity'];
    $price = (float)$_POST['price_per_unit'];
    if (!$cid || !$pid || $qty <= 0) { $error = 'Please fill all required fields.'; }
    else {
        $pdo->prepare("INSERT INTO product_sales (customer_id,product_id,sale_date,quantity,price_per_unit) VALUES (?,?,?,?,?)")
            ->execute([$cid,$pid,$date,$qty,$price]);
        flash('success','Sale recorded.'); header('Location: index.php'); exit;
    }
}
// product prices for JS
$prodPrices = [];
foreach ($products as $p) $prodPrices[$p['id']] = $p['default_price'];
?>
<div class="page-header"><h1 class="page-title">Record Product Sale</h1><a href="index.php" class="btn btn-secondary">← Back</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:500px">
  <form method="POST">
    <div class="form-group"><label class="form-label">Customer *</label>
      <select name="customer_id" class="form-select" required>
        <option value="">— Select Customer —</option>
        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= ($_POST['customer_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label class="form-label">Product *</label>
      <select name="product_id" class="form-select" required onchange="fillPrice(this.value)">
        <option value="">— Select Product —</option>
        <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>" <?= ($_POST['product_id']??'')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['product_name']) ?> (<?= $p['unit'] ?>)</option><?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Date *</label><input type="date" name="sale_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group"><label class="form-label">Quantity</label><input type="number" name="quantity" step="0.5" id="qty" class="form-control" value="<?= $_POST['quantity']??1 ?>" oninput="calcTotal()"></div>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Price/Unit (<?= $currency ?>)</label><input type="number" name="price_per_unit" step="0.5" id="price" class="form-control" value="<?= $_POST['price_per_unit']??0 ?>" oninput="calcTotal()"></div>
      <div class="form-group"><label class="form-label">Total Amount</label><input type="text" id="total" class="form-control" readonly placeholder="Auto-calculated"></div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">✔ Save Sale</button></div>
  </form>
</div>
<script>
const prices = <?= json_encode($prodPrices) ?>;
function fillPrice(pid) {
  document.getElementById('price').value = prices[pid] || 0;
  calcTotal();
}
function calcTotal() {
  const q = parseFloat(document.getElementById('qty').value)||0;
  const p = parseFloat(document.getElementById('price').value)||0;
  document.getElementById('total').value = '<?= $currency ?>' + (q*p).toFixed(2);
}
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
