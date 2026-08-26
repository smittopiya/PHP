<?php
$pageTitle = 'Routes'; $activeNav = 'routes';
require_once __DIR__ . '/../layout.php';

if (isset($_GET['delete'])) { $pdo->prepare("DELETE FROM routes WHERE id=?")->execute([(int)$_GET['delete']]); flash('success','Route deleted.'); header('Location: index.php'); exit; }

$routes = $pdo->query("SELECT r.*, COUNT(c.id) AS cust_count FROM routes r LEFT JOIN customers c ON c.route_id=r.id GROUP BY r.id ORDER BY r.route_name")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['route_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $id   = (int)($_POST['id'] ?? 0);
    if (!$name) { $error = 'Route name is required.'; }
    elseif ($id) {
        $pdo->prepare("UPDATE routes SET route_name=?,description=? WHERE id=?")->execute([$name,$desc,$id]);
        flash('success','Route updated.'); header('Location: index.php'); exit;
    } else {
        $pdo->prepare("INSERT INTO routes (route_name,description) VALUES (?,?)")->execute([$name,$desc]);
        flash('success','Route added.'); header('Location: index.php'); exit;
    }
}
?>
<div class="page-header"><h1 class="page-title">Routes</h1></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2">
  <!-- Add route form -->
  <div class="card">
    <div class="card-header"><h3>Add New Route</h3></div>
    <form method="POST">
      <div class="form-group"><label class="form-label">Route Name *</label><input type="text" name="route_name" class="form-control" placeholder="e.g. Main Route" value="<?= htmlspecialchars($_POST['route_name']??'') ?>" required></div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Optional description"><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
      <div class="form-actions"><button type="submit" class="btn btn-primary">+ Add Route</button></div>
    </form>
  </div>

  <!-- Routes list -->
  <div class="card">
    <div class="card-header"><h3>All Routes <span class="badge badge-info"><?= count($routes) ?></span></h3></div>
    <?php if (empty($routes)): ?>
    <div class="empty-state"><span>🗺️</span><p>No routes yet.</p></div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Route Name</th><th>Customers</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($routes as $r): ?>
      <tr>
        <td><strong><?= htmlspecialchars($r['route_name']) ?></strong><br><small><?= htmlspecialchars($r['description'] ?: '') ?></small></td>
        <td><span class="badge badge-info"><?= $r['cust_count'] ?></span></td>
        <td class="actions-cell">
          <button onclick="confirmDelete('?delete=<?= $r['id'] ?>','<?= addslashes($r['route_name']) ?>')" class="btn btn-sm btn-danger">Delete</button>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
