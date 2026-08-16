<?php
$pageTitle = 'Settings'; $activeNav = 'settings';
require_once __DIR__ . '/../layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'general') {
        $keys = ['dairy_name','owner_name','phone','address','currency'];
        foreach ($keys as $k) {
            $v = trim($_POST[$k] ?? '');
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")->execute([$k,$v]);
        }
        flash('success','Settings saved.'); header('Location: index.php'); exit;
    }

    if ($action === 'bulk_rate') {
        $newRate = (float)($_POST['new_rate'] ?? 0);
        $mtype   = $_POST['milk_type'] ?? '';
        if ($newRate > 0) {
            $q = $mtype ? "UPDATE customers SET default_rate=? WHERE milk_type=?" : "UPDATE customers SET default_rate=?";
            $p = $mtype ? [$newRate,$mtype] : [$newRate];
            $pdo->prepare($q)->execute($p);
            flash('success','Rate updated for all '.($mtype?:'').' customers.');
        }
        header('Location: index.php'); exit;
    }

    if ($action === 'bulk_absent') {
        $cids  = $_POST['bulk_cids'] ?? [];
        $days  = (int)($_POST['days'] ?? 1);
        $start = $_POST['start_date'] ?? date('Y-m-d');
        $shift = $_POST['shift'] ?? 'Morning';
        foreach ($cids as $cid) {
            $cid = (int)$cid;
            $st  = $pdo->prepare("SELECT default_rate,milk_type FROM customers WHERE id=?");
            $st->execute([$cid]); $c = $st->fetch();
            for ($d=0; $d<$days; $d++) {
                $dt = date('Y-m-d', strtotime($start . "+$d days"));
                $pdo->prepare("INSERT INTO milk_entries (customer_id,entry_date,shift,quantity,rate_per_liter,is_absent,milk_type) VALUES (?,?,?,0,?,1,?) ON DUPLICATE KEY UPDATE is_absent=1,quantity=0")
                    ->execute([$cid,$dt,$shift,$c['default_rate'],$c['milk_type']]);
            }
        }
        flash('success',count($cids).' customers marked absent for '.$days.' day(s).');
        header('Location: index.php'); exit;
    }
}

$s = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $r) $s[$r['setting_key']] = $r['setting_value'];

$customers = $pdo->query("SELECT id,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
?>
<div class="page-header"><h1 class="page-title">Settings</h1></div>

<div class="grid-2">
  <!-- General Settings -->
  <div class="card">
    <div class="card-header"><h3>⚙️ General Settings</h3></div>
    <form method="POST">
      <input type="hidden" name="action" value="general">
      <div class="form-group"><label class="form-label">Dairy Name</label><input type="text" name="dairy_name" class="form-control" value="<?= htmlspecialchars($s['dairy_name']??'Smart Dairy') ?>"></div>
      <div class="form-group"><label class="form-label">Owner Name</label><input type="text" name="owner_name" class="form-control" value="<?= htmlspecialchars($s['owner_name']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($s['phone']??'') ?>"></div>
      <div class="form-group"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($s['address']??'') ?></textarea></div>
      <div class="form-group"><label class="form-label">Currency Symbol</label><input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($s['currency']??'₹') ?>" style="max-width:80px"></div>
      <div class="form-group">
        <label class="form-label">Dark Mode</label>
        <button type="button" class="btn btn-secondary" onclick="toggleDark()"><?= isset($_COOKIE['dark_mode'])&&$_COOKIE['dark_mode']=='1'?'☀️ Switch to Light':'🌙 Switch to Dark' ?></button>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-primary">✔ Save Settings</button></div>
    </form>
  </div>

  <!-- Bulk Rate Update -->
  <div class="card">
    <div class="card-header"><h3>💸 Bulk Rate Update</h3></div>
    <form method="POST">
      <input type="hidden" name="action" value="bulk_rate">
      <div class="form-group"><label class="form-label">New Rate (<?= $currency ?>/Liter)</label><input type="number" name="new_rate" step="0.5" class="form-control" placeholder="e.g. 55" required></div>
      <div class="form-group"><label class="form-label">Apply to Milk Type (leave blank for all)</label>
        <select name="milk_type" class="form-select"><option value="">— All Customers —</option><option>Cow</option><option>Buffalo</option><option>Mixed</option></select>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-warning">🔄 Update Rate</button></div>
    </form>

    <!-- Advance Absence Marking -->
    <div class="card-header mt-20"><h3>📅 Advance Absence Marking</h3></div>
    <form method="POST">
      <input type="hidden" name="action" value="bulk_absent">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label class="form-label">Number of Days</label><input type="number" name="days" class="form-control" value="1" min="1" max="30"></div>
      </div>
      <div class="form-group"><label class="form-label">Shift</label><select name="shift" class="form-select"><option>Morning</option><option>Evening</option></select></div>
      <div class="form-group">
        <label class="form-label">Select Customers</label>
        <div style="max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px">
          <label style="display:block;margin-bottom:6px"><input type="checkbox" onchange="document.querySelectorAll('.acb').forEach(c=>c.checked=this.checked)"> <strong>Select All</strong></label>
          <?php foreach ($customers as $c): ?>
          <label style="display:block;padding:3px 0"><input type="checkbox" name="bulk_cids[]" class="acb" value="<?= $c['id'] ?>"> <?= htmlspecialchars($c['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-danger">✖ Mark Absent</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
