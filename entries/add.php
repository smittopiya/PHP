<?php
$pageTitle = 'Add Entry'; $activeNav = 'entries';
require_once __DIR__ . '/../layout.php';

$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
$preCid = (int)($_GET['cid'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid    = (int)$_POST['customer_id'];
    $date   = $_POST['entry_date'];
    $shift  = $_POST['shift'];
    $qty    = (float)$_POST['quantity'];
    $rate   = (float)$_POST['rate_per_liter'];
    $absent = isset($_POST['is_absent']) ? 1 : 0;
    $mtype  = $_POST['milk_type'] ?? 'Cow';
    if ($absent) { $qty = 0; $rate = 0; }
    try {
        $pdo->prepare("INSERT INTO milk_entries (customer_id,entry_date,shift,quantity,rate_per_liter,is_absent,milk_type)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),rate_per_liter=VALUES(rate_per_liter),is_absent=VALUES(is_absent)")
            ->execute([$cid,$date,$shift,$qty,$rate,$absent,$mtype]);
        flash('success','Entry saved.');
        header('Location: index.php'); exit;
    } catch (Exception $e) { $error = 'Error saving entry.'; }
}
// Pre-fill customer default
$def = $preCid ? $pdo->prepare("SELECT * FROM customers WHERE id=?") : null;
if ($def) { $def->execute([$preCid]); $def = $def->fetch(); }
?>
<div class="page-header"><h1 class="page-title">Add Single Entry</h1><a href="index.php" class="btn btn-secondary">← Bulk Entry</a></div>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card" style="max-width:500px">
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Customer *</label>
      <select name="customer_id" class="form-select" required onchange="fillDefault(this)">
        <option value="">— Select Customer —</option>
        <?php foreach ($customers as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $preCid==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2">
      <div class="form-group"><label class="form-label">Date *</label><input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="form-group"><label class="form-label">Shift</label>
        <select name="shift" class="form-select"><option>Morning</option><option>Evening</option></select>
      </div>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_absent" id="absentCheck" onchange="toggleAbsent(this)"> Mark as Absent</label>
    </div>
    <div id="qtyBlock">
      <div class="grid-3">
        <div class="form-group"><label class="form-label">Qty (L)</label><input type="number" name="quantity" id="qty" step="0.5" value="<?= $def?$def['default_qty']:1 ?>" class="form-control"></div>
        <div class="form-group"><label class="form-label">Rate (<?= $currency ?>/L)</label><input type="number" name="rate_per_liter" id="rate" step="0.5" value="<?= $def?$def['default_rate']:50 ?>" class="form-control"></div>
        <div class="form-group"><label class="form-label">Milk Type</label>
          <select name="milk_type" class="form-select"><option>Cow</option><option>Buffalo</option><option>Mixed</option></select>
        </div>
      </div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">✔ Save Entry</button></div>
  </form>
</div>
<script>
function toggleAbsent(cb) {
  document.getElementById('qtyBlock').style.display = cb.checked ? 'none' : '';
}
function fillDefault(sel) {
  // Could fetch via AJAX; for now it's pre-filled on load
}
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
