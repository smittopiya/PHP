<?php
$pageTitle = 'Edit Customer'; $activeNav = 'customers';
require_once __DIR__ . '/../layout.php';
require_once __DIR__ . '/../validate.php';

$id    = (int)($_GET['id'] ?? 0);
$custQ = $pdo->prepare("SELECT * FROM customers WHERE id=?");
$custQ->execute([$id]); $cust = $custQ->fetch();
if (!$cust) { flash('danger','Customer not found.'); header('Location: index.php'); exit; }

$routes = $pdo->query("SELECT * FROM routes ORDER BY route_name")->fetchAll();
$errors = [];
$old    = $cust; // prefill from DB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $v = new Validator($_POST);
    $v->required('name','Customer name')
      ->minLength('name',2,'Customer name')
      ->maxLength('name',100,'Customer name')
      ->name('name','Customer name')
      ->phone('phone','Phone number')
      ->maxLength('address',255,'Address')
      ->numeric('route_sequence','Route sequence')
      ->between('route_sequence',0,999,'Route sequence')
      ->inList('milk_type',['Cow','Buffalo','Mixed'],'Milk type')
      ->required('default_qty','Default quantity')
      ->numeric('default_qty','Default quantity')
      ->between('default_qty',0.5,100,'Default quantity')
      ->required('default_rate','Rate per liter')
      ->numeric('default_rate','Rate per liter')
      ->between('default_rate',1,9999,'Rate per liter')
      ->inList('status',['Active','Inactive'],'Status');

    if ($v->passes()) {
        $name    = $v->clean('name');
        $phone   = preg_replace('/\s+/','',$v->clean('phone'));
        $address = $v->clean('address');
        $route   = (int)($_POST['route_id']??0) ?: null;
        $seq     = (int)$v->clean('route_sequence');
        $mtype   = $v->clean('milk_type');
        $qty     = (float)$v->clean('default_qty');
        $rate    = (float)$v->clean('default_rate');
        $status  = $v->clean('status');

        $pdo->prepare("UPDATE customers SET name=?,phone=?,address=?,route_id=?,route_sequence=?,milk_type=?,default_qty=?,default_rate=?,status=? WHERE id=?")
            ->execute([$name,$phone,$address,$route,$seq,$mtype,$qty,$rate,$status,$id]);
        flash('success','Customer #'.str_pad($id,4,'0',STR_PAD_LEFT).' updated.');
        header('Location: index.php'); exit;
    }
    $errors = $v->errors();
}
?>
<div class="page-header">
  <div class="page-header-left">
    <h1 class="page-title">
      <span>✏️</span>
      <span class="page-title-gold">Edit Customer</span>
      <span class="cid-badge cid-badge-lg">#<?= str_pad($id,4,'0',STR_PAD_LEFT) ?></span>
    </h1>
    <p class="page-sub">Editing: <?= htmlspecialchars($cust['name']) ?></p>
  </div>
  <div class="header-actions">
    <a href="ledger.php?id=<?= $id ?>" class="btn btn-outline-gold">View Ledger</a>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
<div class="validation-summary">
  <h4>⚠ Please fix the following errors:</h4>
  <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card card-gold" style="max-width:680px">
  <!-- CID Display -->
  <div style="display:flex;align-items:center;gap:16px;padding:12px 18px;background:var(--gold-glow);border-radius:var(--radius-sm);border:1px solid rgba(201,162,39,0.2);margin-bottom:22px">
    <div>
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted)">Customer ID</div>
      <div style="font-size:1.6rem;font-weight:900;color:var(--gold);font-family:'Courier New',monospace">#<?= str_pad($id,4,'0',STR_PAD_LEFT) ?></div>
    </div>
    <div style="border-left:1px solid var(--border);padding-left:16px;font-size:.82rem;color:var(--text-muted)">
      <div>Member since: <strong><?= date('d M Y',strtotime($cust['created_at'])) ?></strong></div>
      <div>ID is permanent and cannot be changed</div>
    </div>
  </div>

  <form method="POST" novalidate>
    <div class="grid-2">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="name" id="name"
          class="<?= fieldClass($errors,'name') ?>"
          minlength="2" maxlength="100" required
          value="<?= htmlspecialchars($old['name']??'') ?>">
        <span class="char-counter" id="nameCtr">0/100</span>
        <?= fieldError($errors,'name') ?>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" id="phone"
          class="<?= fieldClass($errors,'phone') ?>"
          placeholder="10-digit mobile"
          pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric"
          value="<?= htmlspecialchars($old['phone']??'') ?>">
        <?= fieldError($errors,'phone') ?>
        <span class="field-hint">10 digits, starts with 6–9</span>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Address</label>
      <textarea name="address" id="address"
        class="<?= fieldClass($errors,'address') ?>"
        rows="2" maxlength="255"><?= htmlspecialchars($old['address']??'') ?></textarea>
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
          min="0" max="999" step="1"
          value="<?= htmlspecialchars($old['route_sequence']??0) ?>">
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
        <label class="form-label">Default Qty (L) *</label>
        <input type="number" name="default_qty" id="default_qty"
          class="<?= fieldClass($errors,'default_qty') ?>"
          step="0.5" min="0.5" max="100" required
          value="<?= htmlspecialchars($old['default_qty']??1) ?>">
        <?= fieldError($errors,'default_qty') ?>
        <span class="field-hint">0.5 – 100 L</span>
      </div>
      <div class="form-group">
        <label class="form-label">Rate (<?= $currency ?>/L) *</label>
        <input type="number" name="default_rate" id="default_rate"
          class="<?= fieldClass($errors,'default_rate') ?>"
          step="0.5" min="1" max="9999" required
          value="<?= htmlspecialchars($old['default_rate']??50) ?>">
        <?= fieldError($errors,'default_rate') ?>
        <span class="field-hint"><?= $currency ?>1 – <?= $currency ?>9,999</span>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Status</label>
      <div class="radio-group">
        <label class="radio-label"><input type="radio" name="status" value="Active" <?= ($old['status']??'')==='Active'?'checked':'' ?>> ● Active</label>
        <label class="radio-label"><input type="radio" name="status" value="Inactive" <?= ($old['status']??'')==='Inactive'?'checked':'' ?>> ○ Inactive</label>
      </div>
      <?= fieldError($errors,'status') ?>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary btn-lg">✔ Update Customer</button>
      <a href="index.php" class="btn btn-secondary btn-lg">Cancel</a>
    </div>
  </form>
</div>

<script>
document.getElementById('phone').addEventListener('input', function() {
  this.value = this.value.replace(/\D/g,'').slice(0,10);
  const ok = /^[6-9][0-9]{9}$/.test(this.value);
  this.classList.toggle('is-invalid', this.value.length > 0 && !ok);
  this.classList.toggle('is-valid',   this.value.length === 0 || ok);
});
function initCounter(id, ctrId, max) {
  const el=document.getElementById(id), ctr=document.getElementById(ctrId);
  if(!el||!ctr) return;
  const u=()=>{const l=el.value.length;ctr.textContent=l+'/'+max;ctr.className='char-counter'+(l>max*.9?(l>=max?' over':' warn'):'');};
  el.addEventListener('input',u);u();
}
initCounter('name','nameCtr',100);
initCounter('address','addrCtr',255);
</script>
<?php require_once __DIR__ . '/../layout_footer.php'; ?>
