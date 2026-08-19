<?php
$pageTitle = 'Add Customer'; $activeNav = 'customers';
require_once __DIR__ . '/../layout.php';
require_once __DIR__ . '/../validate.php';

$routes = $pdo->query("SELECT * FROM routes ORDER BY route_name")->fetchAll();
$nextId = $pdo->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA='smart_dairy' AND TABLE_NAME='customers'")->fetchColumn();
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $v = new Validator($_POST);
    $v->required('name', 'Customer name')
      ->minLength('name', 2, 'Customer name')
      ->maxLength('name', 100, 'Customer name')
      ->name('name', 'Customer name')
      ->phone('phone', 'Phone number')
      ->maxLength('address', 255, 'Address')
      ->numeric('route_sequence', 'Route sequence')
      ->between('route_sequence', 0, 999, 'Route sequence')
      ->inList('milk_type', ['Cow','Buffalo','Mixed'], 'Milk type')
      ->required('default_qty', 'Default quantity')
      ->numeric('default_qty', 'Default quantity')
      ->between('default_qty', 0.5, 100, 'Default quantity')
      ->required('default_rate', 'Rate per liter')
      ->numeric('default_rate', 'Rate per liter')
      ->between('default_rate', 1, 9999, 'Rate per liter');

    if ($v->passes()) {
        $name    = $v->clean('name');
        $phone   = preg_replace('/\s+/', '', $v->clean('phone'));
        $address = $v->clean('address');
        $route   = (int)($old['route_id'] ?? 0) ?: null;
        $seq     = (int)$v->clean('route_sequence');
        $mtype   = $v->clean('milk_type');
        $qty     = (float)$v->clean('default_qty');
        $rate    = (float)$v->clean('default_rate');

        $pdo->prepare("INSERT INTO customers (name,phone,address,route_id,route_sequence,milk_type,default_qty,default_rate) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$name,$phone,$address,$route,$seq,$mtype,$qty,$rate]);
        $newId = $pdo->lastInsertId();
        flash('success', "Customer \"$name\" added — ID #".str_pad($newId,4,'0',STR_PAD_LEFT));
        header('Location: index.php'); exit;
    }
    $errors = $v->errors();
}
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title"><span>👤</span> <span class="page-title-gold">Add Customer</span></h1>
    <p class="page-sub">All fields marked * are required</p>
  </div>
  <a href="index.php" class="btn btn-secondary">← Back to List</a>
</div>

<?php if (!empty($errors)): ?>
<div class="validation-summary">
  <h4>⚠ Please fix the following errors:</h4>
  <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card card-gold" style="max-width:680px">
  <!-- CID Preview -->
  <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;background:var(--gold-glow);border-radius:var(--radius-sm);border:1px solid rgba(201,162,39,0.2);margin-bottom:22px">
    <div>
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted)">Assigned Customer ID</div>
      <div style="font-size:1.5rem;font-weight:900;color:var(--gold);font-family:'Courier New',monospace">#<?= str_pad($nextId,4,'0',STR_PAD_LEFT) ?></div>
    </div>
    <div style="font-size:.8rem;color:var(--text-muted);border-left:1px solid var(--border);padding-left:14px">
      Auto-assigned on save.<br>Unique &amp; permanent identity.
    </div>
  </div>

  <form method="POST" id="addCustForm" novalidate>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" id="name"
          class="<?= fieldClass($errors,'name') ?>"
          placeholder="e.g. Ramesh Patel"
          minlength="2" maxlength="100" required
          value="<?= htmlspecialchars($old['name']??'') ?>">
        <span class="char-counter" id="nameCtr">0/100</span>
        <?= fieldError($errors,'name') ?>
        <span class="field-hint">Letters, spaces, dots and hyphens only</span>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" id="phone"
          class="<?= fieldClass($errors,'phone') ?>"
          placeholder="10-digit mobile (e.g. 9876543210)"
          pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric"
          value="<?= htmlspecialchars($old['phone']??'') ?>">
        <?= fieldError($errors,'phone') ?>
        <span class="field-hint">10 digits, starts with 6–9. Leave blank if not available.</span>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Address</label>
      <textarea name="address" id="address"
        class="<?= fieldClass($errors,'address') ?>"
        rows="2" maxlength="255"
        placeholder="House / Street / Area (max 255 characters)"><?= htmlspecialchars($old['address']??'') ?></textarea>
      <span class="char-counter" id="addrCtr">0/255</span>
      <?= fieldError($errors,'address') ?>
    </div>

    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Route</label>
        <select name="route_id" class="form-select">
          <option value="">— Select Route —</option>
          <?php foreach ($routes as $r): ?>
          <option value="<?= $r['id'] ?>" <?= ($old['route_id']??'')==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['route_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Route Sequence #</label>
        <input type="number" name="route_sequence"
          class="<?= fieldClass($errors,'route_sequence') ?>"
          placeholder="Delivery order (0–999)"
          min="0" max="999" step="1"
          value="<?= htmlspecialchars($old['route_sequence']??'0') ?>">
        <?= fieldError($errors,'route_sequence') ?>
      </div>
    </div>

    <div class="divider-gold"></div>

    <div class="grid-3">
      <div class="form-group">
        <label class="form-label">Milk Type *</label>
        <select name="milk_type" class="<?= fieldClass($errors,'milk_type','form-select') ?>" required>
          <?php foreach (['Cow','Buffalo','Mixed'] as $mt): ?>
          <option <?= ($old['milk_type']??'Cow')===$mt?'selected':'' ?>><?= $mt ?></option>
          <?php endforeach; ?>
        </select>
        <?= fieldError($errors,'milk_type') ?>
      </div>
      <div class="form-group">
        <label class="form-label">Default Qty (Liters) *</label>
        <input type="number" name="default_qty" id="default_qty"
          class="<?= fieldClass($errors,'default_qty') ?>"
          step="0.5" min="0.5" max="100" required
          value="<?= htmlspecialchars($old['default_qty']??'1') ?>">
        <?= fieldError($errors,'default_qty') ?>
        <span class="field-hint">0.5 L – 100 L</span>
      </div>
      <div class="form-group">
        <label class="form-label">Rate per Liter (<?= $currency ?>) *</label>
        <input type="number" name="default_rate" id="default_rate"
          class="<?= fieldClass($errors,'default_rate') ?>"
          step="0.5" min="1" max="9999" required
          value="<?= htmlspecialchars($old['default_rate']??'50') ?>">
        <?= fieldError($errors,'default_rate') ?>
        <span class="field-hint"><?= $currency ?>1 – <?= $currency ?>9,999 per liter</span>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-lg">✔ Save Customer</button>
      <a href="index.php" class="btn btn-secondary btn-lg">Cancel</a>
    </div>
  </form>
</div>

<script>
// Real-time phone validation
document.getElementById('phone').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g,'').slice(0,10);
  const ok = /^[6-9][0-9]{9}$/.test(this.value);
  this.classList.toggle('is-invalid', this.value.length > 0 && !ok);
  this.classList.toggle('is-valid',   this.value.length === 0 || ok);
});

// Character counters
function initCounter(inputId, counterId, max) {
  const el = document.getElementById(inputId);
  const ctr = document.getElementById(counterId);
  if (!el || !ctr) return;
  const update = () => {
    const len = el.value.length;
    ctr.textContent = len + '/' + max;
    ctr.className = 'char-counter' + (len > max*0.9 ? (len >= max ? ' over' : ' warn') : '');
  };
  el.addEventListener('input', update); update();
}
initCounter('name', 'nameCtr', 100);
initCounter('address', 'addrCtr', 255);

// Range validation display
function validateRange(id, min, max) {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', function() {
    const v = parseFloat(this.value);
    const bad = isNaN(v) || v < min || v > max;
    this.classList.toggle('is-invalid', bad && this.value !== '');
    this.classList.toggle('is-valid',   !bad && this.value !== '');
  });
}
validateRange('default_qty', 0.5, 100);
validateRange('default_rate', 1, 9999);

// Name pattern
document.getElementById('name').addEventListener('blur', function() {
  const ok = /^[\p{L}\s\.\-\']{2,100}$/u.test(this.value);
  this.classList.toggle('is-invalid', !ok && this.value.length > 0);
  this.classList.toggle('is-valid', ok);
});
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
