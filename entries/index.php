<?php
$pageTitle = 'Daily Entries'; $activeNav = 'entries';
require_once __DIR__ . '/../layout.php';

$today  = $_GET['date'] ?? date('Y-m-d');
$shift  = $_GET['shift'] ?? 'Morning';
$routeId = (int)($_GET['route_id'] ?? 0);

// Handle bulk save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date  = $_POST['date'];
    $shift = $_POST['shift'];
    $entries = $_POST['entries'] ?? [];
    foreach ($entries as $cid => $row) {
        $absent = isset($row['absent']) ? 1 : 0;
        $qty    = $absent ? 0 : (float)($row['qty'] ?? 0);
        $rate   = (float)($row['rate'] ?? 0);
        $mtype  = $row['milk_type'] ?? 'Cow';
        $pdo->prepare("INSERT INTO milk_entries (customer_id,entry_date,shift,quantity,rate_per_liter,is_absent,milk_type)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),rate_per_liter=VALUES(rate_per_liter),is_absent=VALUES(is_absent),milk_type=VALUES(milk_type)")
            ->execute([(int)$cid,$date,$shift,$qty,$rate,$absent,$mtype]);
    }
    flash('success', count($entries).' entries saved for '.date('d M Y',strtotime($date)).' ('.$shift.').');
    header("Location: index.php?date=$date&shift=$shift&route_id=$routeId"); exit;
}

// Routes for filter
$routes = $pdo->query("SELECT * FROM routes ORDER BY route_name")->fetchAll();

// Customers
$where  = "WHERE c.status='Active'";
$params = [];
if ($routeId) { $where .= " AND c.route_id=?"; $params[] = $routeId; }

$custQ  = $pdo->prepare("SELECT c.*, r.route_name,
    COALESCE(me.quantity,c.default_qty) AS qty,
    COALESCE(me.rate_per_liter,c.default_rate) AS rate,
    COALESCE(me.is_absent,0) AS is_absent,
    COALESCE(me.milk_type,c.milk_type) AS mtype,
    me.id AS entry_id
  FROM customers c
  LEFT JOIN routes r ON r.id=c.route_id
  LEFT JOIN milk_entries me ON me.customer_id=c.id AND me.entry_date=? AND me.shift=?
  $where
  ORDER BY c.route_sequence, c.name");
$custQ->execute(array_merge([$today,$shift], $params));
$custs  = $custQ->fetchAll();
$totalLiters  = array_sum(array_map(fn($c) => $c['is_absent']?0:$c['qty'], $custs));
$totalAmount  = array_sum(array_map(fn($c) => $c['is_absent']?0:$c['qty']*$c['rate'], $custs));
$absentCount  = count(array_filter($custs, fn($c) => $c['is_absent']));
?>
<div class="page-header">
  <h1 class="page-title">Daily Entries</h1>
  <a href="history.php" class="btn btn-secondary">History →</a>
</div>

<!-- Filters bar -->
<div class="card mb-16">
  <form method="GET" class="search-form flex-wrap">
    <input type="date" name="date" value="<?= $today ?>" class="form-control" style="max-width:160px">
    <select name="shift" class="form-select" style="max-width:130px">
      <option <?= $shift==='Morning'?'selected':'' ?>>Morning</option>
      <option <?= $shift==='Evening'?'selected':'' ?>>Evening</option>
    </select>
    <select name="route_id" class="form-select" style="max-width:180px">
      <option value="">All Routes</option>
      <?php foreach ($routes as $r): ?>
      <option value="<?= $r['id'] ?>" <?= $routeId==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['route_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Load</button>
  </form>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
  <div class="stat-card" style="--accent:#2471A3"><div class="stat-icon">👥</div><div class="stat-value"><?= count($custs) ?></div><div class="stat-label">Customers</div></div>
  <div class="stat-card" style="--accent:#1E8449"><div class="stat-icon">🥛</div><div class="stat-value"><?= number_format($totalLiters,1) ?>L</div><div class="stat-label">Total Milk</div></div>
  <div class="stat-card" style="--accent:#E67E22"><div class="stat-icon">💰</div><div class="stat-value"><?= $currency ?><?= number_format($totalAmount,0) ?></div><div class="stat-label">Total Amount</div></div>
</div>

<!-- Bulk Entry Form -->
<?php if (empty($custs)): ?>
<div class="empty-state card"><span>👥</span><p>No active customers. <a href="/milk-management/customers/add.php">Add customers first.</a></p></div>
<?php else: ?>
<form method="POST" id="bulkForm">
  <input type="hidden" name="date"  value="<?= $today ?>">
  <input type="hidden" name="shift" value="<?= $shift ?>">
  <div class="card">
    <div class="card-header">
      <h3><?= date('d M Y',strtotime($today)) ?> — <?= $shift ?> Shift
        <span class="badge badge-danger"><?= $absentCount ?> absent</span>
      </h3>
      <div>
        <button type="button" class="btn btn-sm btn-warning" onclick="markAllPresent()">✔ All Present</button>
        <button type="button" class="btn btn-sm btn-danger"  onclick="markAllAbsent()">✖ All Absent</button>
      </div>
    </div>
    <div class="table-wrap">
    <table class="table entry-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Milk Type</th>
          <th>Qty (L)</th>
          <th>Rate (<?= $currency ?>/L)</th>
          <th>Amount</th>
          <th>Absent</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($custs as $i => $c): ?>
      <tr id="row-<?= $c['id'] ?>" class="<?= $c['is_absent']?'row-inactive entry-absent':'' ?>">
        <td><?= $i+1 ?></td>
        <td>
          <strong><?= htmlspecialchars($c['name']) ?></strong>
          <?php if ($c['phone']): ?><br><small><?= $c['phone'] ?></small><?php endif; ?>
        </td>
        <td>
          <select name="entries[<?= $c['id'] ?>][milk_type]" class="form-select form-select-sm">
            <?php foreach (['Cow','Buffalo','Mixed'] as $mt): ?>
            <option <?= $c['mtype']===$mt?'selected':'' ?>><?= $mt ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="number" name="entries[<?= $c['id'] ?>][qty]" step="0.5" min="0"
            value="<?= $c['qty'] ?>" class="form-control form-control-sm qty-input"
            data-id="<?= $c['id'] ?>" <?= $c['is_absent']?'disabled':'' ?>>
        </td>
        <td>
          <input type="number" name="entries[<?= $c['id'] ?>][rate]" step="0.5" min="0"
            value="<?= $c['rate'] ?>" class="form-control form-control-sm rate-input"
            data-id="<?= $c['id'] ?>" <?= $c['is_absent']?'disabled':'' ?>>
        </td>
        <td class="fw-bold" id="amt-<?= $c['id'] ?>">
          <?= $c['is_absent']?'—':$currency.number_format($c['qty']*$c['rate'],2) ?>
        </td>
        <td class="text-center">
          <input type="checkbox" name="entries[<?= $c['id'] ?>][absent]" value="1"
            class="absent-check" data-id="<?= $c['id'] ?>" <?= $c['is_absent']?'checked':'' ?>>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-lg">💾 Save All Entries</button>
    </div>
  </div>
</form>
<?php endif; ?>

<script>
const cur = '<?= $currency ?>';

function calcAmt(id) {
  const row   = document.getElementById('row-' + id);
  const absent = row.querySelector('.absent-check').checked;
  const qty   = parseFloat(row.querySelector('.qty-input')?.value || 0);
  const rate  = parseFloat(row.querySelector('.rate-input')?.value || 0);
  document.getElementById('amt-' + id).textContent = absent ? '—' : cur + (qty * rate).toFixed(2);
}

document.querySelectorAll('.absent-check').forEach(cb => {
  cb.addEventListener('change', function() {
    const id  = this.dataset.id;
    const row = document.getElementById('row-' + id);
    const dis = this.checked;
    row.classList.toggle('row-inactive', dis);
    row.classList.toggle('entry-absent', dis);
    row.querySelector('.qty-input').disabled  = dis;
    row.querySelector('.rate-input').disabled = dis;
    calcAmt(id);
  });
});

document.querySelectorAll('.qty-input, .rate-input').forEach(inp => {
  inp.addEventListener('input', () => calcAmt(inp.dataset.id));
});

function markAllPresent() {
  document.querySelectorAll('.absent-check').forEach(cb => { cb.checked = false; cb.dispatchEvent(new Event('change')); });
}
function markAllAbsent() {
  document.querySelectorAll('.absent-check').forEach(cb => { cb.checked = true; cb.dispatchEvent(new Event('change')); });
}
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
