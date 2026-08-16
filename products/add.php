<?php
$pageTitle = 'Add/Edit Product'; $activeNav = 'products';
require_once __DIR__ . '/../layout.php';
$id  = (int)($_GET['id'] ?? 0);
$prod = $id ? $pdo->prepare("SELECT * FROM products WHERE id=?") : null;
if ($prod) { $prod->execute([$id]); $prod = $prod->fetch(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['product_name'] ?? '');
    $unit  = trim($_POST['unit'] ?? 'kg');
    $price = (float)($_POST['default_price'] ?? 0);
    $status = $_POST['status'] ?? 'Active';
    if (!$name) { $error = 'Product name is required.'; }
    elseif ($id) {
        $pdo->prepare("UPDATE products SET product_name=?,unit=?,default_price=?,status=? WHERE id=?")->execute([$name,$unit,$price,$status,$id]);
        flash('success','Product updated.'); header('Location: index.php'); exit;
    } else {
        $pdo->prepare("INSERT INTO products (product_name,unit,default_price) VALUES (?,?,?)")->execute([$name,$unit,$price]);
        flash('success','Product added.'); header('Location: index.php'); exit;
    }
}
$f = $_SERVER['REQUEST_METHOD']==='POST' ? $_POST : ($prod ?? []);
?>
<div class="page-header"><h1 class="page-title"><?= $id?'Edit':'Add' ?> Product</h1><a href="index.php" class="btn btn-secondary">← Back</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:480px">
  <form method="POST">
    <div class="form-group"><label class="form-label">Product Name *</label><input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($f['product_name']??'') ?>" required></div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Unit</label>
        <select name="unit" class="form-select">
          <?php foreach (['kg','L','piece','packet','dozen'] as $u): ?>
          <option <?= ($f['unit']??'kg')===$u?'selected':'' ?>><?= $u ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Default Price (<?= $currency ?>)</label><input type="number" name="default_price" step="0.5" class="form-control" value="<?= $f['default_price']??0 ?>"></div>
    </div>
    <?php if ($id): ?>
    <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select"><option <?= ($f['status']??'')==='Active'?'selected':'' ?>>Active</option><option <?= ($f['status']??'')==='Inactive'?'selected':'' ?>>Inactive</option></select></div>
    <?php endif; ?>
    <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">✔ Save Product</button></div>
  </form>
</div>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
