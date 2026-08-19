<?php
$pageTitle = 'Customers'; $activeNav = 'customers';
require_once __DIR__ . '/../layout.php';

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM customers WHERE id=?")->execute([(int)$_GET['delete']]);
    flash('success', 'Customer deleted successfully.');
    header('Location: index.php'); exit;
}
// Handle toggle status
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE customers SET status=IF(status='Active','Inactive','Active') WHERE id=?")->execute([(int)$_GET['toggle']]);
    header('Location: index.php'); exit;
}

$search  = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? '';
$routeF  = (int)($_GET['route_id'] ?? 0);
$where   = 'WHERE 1=1';
$params  = [];
if ($search)  { $where .= ' AND (c.name LIKE ? OR c.phone LIKE ? OR c.id = ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = (int)$search; }
if ($status)  { $where .= ' AND c.status=?'; $params[] = $status; }
if ($routeF)  { $where .= ' AND c.route_id=?'; $params[] = $routeF; }

$st = $pdo->prepare("SELECT c.*, r.route_name FROM customers c LEFT JOIN routes r ON r.id=c.route_id $where ORDER BY c.id ASC");
$st->execute($params);
$customers = $st->fetchAll();
$routes    = $pdo->query("SELECT * FROM routes ORDER BY route_name")->fetchAll();

$totalActive   = count(array_filter($customers, fn($c) => $c['status'] === 'Active'));
$totalInactive = count($customers) - $totalActive;
?>

<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <span>👥</span>
      <span class="page-title-gold">Customers</span>
    </h1>
    <p class="page-sub"><?= count($customers) ?> total · <?= $totalActive ?> active · <?= $totalInactive ?> inactive</p>
  </div>
  <div class="header-actions">
    <a href="add.php" class="btn btn-primary">＋ Add Customer</a>
  </div>
</div>

<!-- Filters -->
<div class="card mb-20">
  <form method="GET" class="search-form">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
      placeholder="🔍  Search by name, phone or Customer ID…"
      class="form-control search-input">
    <select name="status" class="form-select" style="max-width:140px">
      <option value="">All Status</option>
      <option value="Active"   <?= $status==='Active'  ?'selected':'' ?>>Active</option>
      <option value="Inactive" <?= $status==='Inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <select name="route_id" class="form-select" style="max-width:160px">
      <option value="">All Routes</option>
      <?php foreach ($routes as $r): ?>
      <option value="<?= $r['id'] ?>" <?= $routeF==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['route_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Search</button>
    <a href="index.php" class="btn btn-secondary">Clear</a>
  </form>
</div>

<?php if (empty($customers)): ?>
<div class="empty-state card">
  <span class="empty-icon">👥</span>
  <h4>No customers found</h4>
  <p>Try adjusting your search or <a href="add.php">add your first customer</a></p>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Customer ID</th>
        <th>Name &amp; Contact</th>
        <th>Route</th>
        <th>Milk Type</th>
        <th>Default Qty</th>
        <th>Rate/L</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($customers as $c): ?>
    <tr class="<?= $c['status']==='Inactive'?'row-inactive':'' ?>">
      <td>
        <span class="cid-badge">#<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></span>
      </td>
      <td>
        <div class="customer-name-cell">
          <span class="customer-main-name"><?= htmlspecialchars($c['name']) ?></span>
          <?php if ($c['phone']): ?>
          <span class="customer-phone">📞 <?= htmlspecialchars($c['phone']) ?></span>
          <?php endif; ?>
          <?php if ($c['address']): ?>
          <span class="customer-address">📍 <?= htmlspecialchars(substr($c['address'],0,40)).(strlen($c['address'])>40?'…':'') ?></span>
          <?php endif; ?>
        </div>
      </td>
      <td><?= $c['route_name'] ? '<span class="badge badge-info">'.htmlspecialchars($c['route_name']).'</span>' : '<span class="text-muted">—</span>' ?></td>
      <td>
        <?php $mtColor = ['Cow'=>'badge-success','Buffalo'=>'badge-purple','Mixed'=>'badge-warning']; ?>
        <span class="badge <?= $mtColor[$c['milk_type']] ?? 'badge-info' ?>"><?= $c['milk_type'] ?></span>
      </td>
      <td class="fw-bold"><?= $c['default_qty'] ?> L</td>
      <td class="fw-bold text-gold"><?= $currency ?><?= number_format($c['default_rate'],2) ?></td>
      <td>
        <span class="badge <?= $c['status']==='Active'?'badge-success':'badge-danger' ?>">
          <?= $c['status']==='Active' ? '● Active' : '○ Inactive' ?>
        </span>
      </td>
      <td>
        <div class="actions-cell">
          <a href="ledger.php?id=<?= $c['id'] ?>"      class="btn btn-sm btn-outline-gold">Ledger</a>
          <a href="edit.php?id=<?= $c['id'] ?>"         class="btn btn-sm btn-secondary">Edit</a>
          <a href="?toggle=<?= $c['id'] ?>"             class="btn btn-sm <?= $c['status']==='Active'?'btn-warning':'btn-success' ?>">
            <?= $c['status']==='Active'?'Pause':'Activate' ?>
          </a>
          <button onclick="confirmDelete('?delete=<?= $c['id'] ?>','<?= addslashes($c['name']) ?>')" class="btn btn-sm btn-danger">Del</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout_footer.php'; ?>
