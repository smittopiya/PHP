<?php
$pageTitle = 'Products'; $activeNav = 'products';
require_once __DIR__ . '/../layout.php';

if (isset($_GET['delete'])) { $pdo->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['delete']]); flash('success','Product deleted.'); header('Location: index.php'); exit; }
if (isset($_GET['toggle'])) { $pdo->prepare("UPDATE products SET status=IF(status='Active','Inactive','Active') WHERE id=?")->execute([(int)$_GET['toggle']]); header('Location: index.php'); exit; }

$products = $pdo->query("SELECT * FROM products ORDER BY status, product_name")->fetchAll();
?>
<div class="page-header"><h1 class="page-title">Products</h1><a href="add.php" class="btn btn-primary">+ Add Product</a></div>
<?php if (empty($products)): ?>
<div class="empty-state card"><span>🧀</span><p>No products yet. <a href="add.php">Add first product</a></p></div>
<?php else: ?>
<div class="card">
<div class="table-wrap">
<table class="table">
  <thead><tr><th>#</th><th>Product Name</th><th>Unit</th><th>Default Price</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($products as $i => $p): ?>
  <tr class="<?= $p['status']==='Inactive'?'row-inactive':'' ?>">
    <td><?= $i+1 ?></td>
    <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
    <td><?= htmlspecialchars($p['unit']) ?></td>
    <td><?= $currency ?><?= number_format($p['default_price'],2) ?></td>
    <td><span class="badge <?= $p['status']==='Active'?'badge-success':'badge-danger' ?>"><?= $p['status'] ?></span></td>
    <td class="actions-cell">
      <a href="add.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
      <a href="?toggle=<?= $p['id'] ?>" class="btn btn-sm <?= $p['status']==='Active'?'btn-warning':'btn-success' ?>"><?= $p['status']==='Active'?'Deactivate':'Activate' ?></a>
      <button onclick="confirmDelete('?delete=<?= $p['id'] ?>','<?= addslashes($p['product_name']) ?>')" class="btn btn-sm btn-danger">Delete</button>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
