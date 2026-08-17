<?php
/**
 * bills/pdf.php
 * Renders a clean, printable bill page (no sidebar/navigation).
 * User clicks "Save as PDF" from browser → attaches to WhatsApp.
 */
$id = (int)($_GET['id'] ?? 0);
require_once __DIR__ . '/../db.php';

$bill = $pdo->prepare("SELECT mb.*, c.name, c.phone, c.address, c.milk_type, c.id as customer_id FROM monthly_bills mb JOIN customers c ON c.id=mb.customer_id WHERE mb.id=?");
$bill->execute([$id]); $bill = $bill->fetch();
if (!$bill) die('<p>Bill not found.</p>');

$dairyName = setting('dairy_name', 'Smart Dairy');
$ownerName = setting('owner_name', 'Owner');
$phone     = setting('phone', '');
$address   = setting('address', '');
$currency  = setting('currency', '₹');

$month     = $bill['bill_month'];
$year      = $bill['bill_year'];
$monthName = date('F', mktime(0,0,0,$month,1));

$entries = $pdo->prepare("SELECT entry_date,shift,quantity,rate_per_liter FROM milk_entries WHERE customer_id=? AND MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0 ORDER BY entry_date,shift");
$entries->execute([$bill['customer_id'],$month,$year]); $entries = $entries->fetchAll();

$sales = $pdo->prepare("SELECT ps.sale_date, p.product_name, ps.quantity, p.unit, ps.price_per_unit, ps.total_amount FROM product_sales ps JOIN products p ON p.id=ps.product_id WHERE ps.customer_id=? AND MONTH(ps.sale_date)=? AND YEAR(ps.sale_date)=?");
$sales->execute([$bill['customer_id'],$month,$year]); $sales = $sales->fetchAll();

$payments = $pdo->prepare("SELECT payment_date,amount,payment_mode FROM payments WHERE customer_id=? AND MONTH(payment_date)=? AND YEAR(payment_date)=?");
$payments->execute([$bill['customer_id'],$month,$year]); $payments = $payments->fetchAll();

$milkTotal  = array_sum(array_map(fn($e) => $e['quantity'] * $e['rate_per_liter'], $entries));
$salesTotal = array_sum(array_column($sales,'total_amount'));
$paidTotal  = array_sum(array_column($payments,'amount'));

// Build WhatsApp URL entirely in PHP — no JS popup issues
$custPhone = preg_replace('/\D/', '', $bill['phone'] ?? '');
$waMsg = "Hi {$bill['name']},\n\n"
       . "Bill for $monthName $year:\n"
       . "- Bill Amount : {$currency}" . number_format($bill['current_bill'], 2) . "\n"
       . "- Prev Balance: {$currency}" . number_format($bill['previous_balance'], 2) . "\n"
       . "- Total Paid  : {$currency}" . number_format($bill['total_paid'], 2) . "\n"
       . ($bill['final_due'] <= 0
           ? "- Fully Settled ✓"
           : "- Balance Due : {$currency}" . number_format(abs($bill['final_due']), 2)
       ) . "\n\nThank you! - {$dairyName}";
$waUrl = strlen($custPhone) === 10
       ? 'https://wa.me/91' . $custPhone . '?text=' . rawurlencode($waMsg)
       : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bill – <?= htmlspecialchars($bill['name']) ?> – <?= $monthName ?> <?= $year ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Playfair+Display:wght@700&display=swap');

  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Inter', Arial, sans-serif; background: #f0f2f5; color: #111; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  /* Action bar – hidden on print */
  .action-bar {
    display: flex; gap: 12px; align-items: center; justify-content: center;
    padding: 14px 20px; background: #0A1628; flex-wrap: wrap;
    position: sticky; top: 0; z-index: 100;
  }
  .btn { display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:.9rem;font-weight:700;text-decoration:none;transition:all .2s; }
  .btn:hover { opacity:.85; transform:translateY(-1px); }
  .btn-gold { background:linear-gradient(135deg,#C9A227,#A07B10); color:#fff; box-shadow:0 4px 16px rgba(201,162,39,.35); }
  .btn-wa   { background:#25D366; color:#fff; box-shadow:0 4px 16px rgba(37,211,102,.35); }
  .btn-back { background:rgba(255,255,255,.1); color:#fff; border:1px solid rgba(255,255,255,.2); }

  /* Toast */
  #toast {
    position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(80px);
    background:#0A1628; color:#fff; padding:12px 24px; border-radius:40px;
    font-size:.875rem; font-weight:600; z-index:9999;
    border:1px solid rgba(201,162,39,.4); box-shadow:0 8px 32px rgba(0,0,0,.4);
    transition:transform .35s cubic-bezier(.4,0,.2,1), opacity .35s;
    opacity:0; white-space:nowrap; display:flex; align-items:center; gap:8px;
  }
  #toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
  #toast .dot { width:8px;height:8px;border-radius:50%;background:#25D366;animation:pulse 1s infinite; }
  @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }

  /* Bill paper */
  .bill-wrap { max-width: 780px; margin: 20px auto; padding: 0 16px 40px; }

  .bill-paper {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(10,22,40,.15);
  }

  /* Header */
  .bill-header {
    background: linear-gradient(135deg, #0A1628 0%, #1D3461 100%);
    padding: 28px 32px 22px;
    position: relative;
  }
  .bill-header::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, transparent, #C9A227, transparent);
  }
  .dairy-name { font-family:'Playfair Display',serif; font-size:1.8rem; color:#F0C040; }
  .dairy-sub  { color:rgba(200,215,240,.7); font-size:.85rem; margin-top:4px; }
  .bill-tag   {
    display:inline-block; margin-top:12px; padding:4px 16px; border-radius:20px;
    background:rgba(201,162,39,.15); border:1px solid rgba(201,162,39,.3);
    color:#F0C040; font-size:.78rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase;
  }

  /* Meta bar */
  .bill-meta {
    display:flex; justify-content:space-between; align-items:flex-start;
    padding:18px 32px; background:#F8F6F0; border-bottom:1px solid #e8e0cc;
    gap:20px; flex-wrap:wrap;
  }
  .meta-left .cid   { display:inline-block; background:linear-gradient(135deg,#A07B10,#C9A227); color:#fff; padding:2px 10px; border-radius:12px; font-size:.72rem; font-weight:800; margin-bottom:4px; }
  .meta-left strong { display:block; font-size:1rem; color:#0A1628; }
  .meta-left span   { font-size:.83rem; color:#555; }
  .meta-right       { text-align:right; font-size:.83rem; color:#555; }
  .meta-right strong{ display:block; font-size:1rem; color:#0A1628; }

  /* Body */
  .bill-body { padding:24px 32px; }

  .section-title {
    font-size:.78rem; font-weight:800; color:#0A1628; text-transform:uppercase;
    letter-spacing:.8px; margin:20px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title::after {
    content:''; flex:1; height:1px;
    background:linear-gradient(90deg,#C9A227,transparent); opacity:.4;
  }

  table.bt { width:100%; border-collapse:collapse; font-size:.85rem; }
  table.bt th { background:#0A1628; color:rgba(255,255,255,.9); padding:8px 12px; text-align:left; font-size:.73rem; text-transform:uppercase; letter-spacing:.5px; }
  table.bt td { padding:8px 12px; border-bottom:1px solid #f0ece0; }
  table.bt tr:last-child td { border-bottom:none; }
  table.bt tfoot td { background:#F0ECD6; font-weight:800; padding:9px 12px; border-top:2px solid #C9A227; }

  /* Summary */
  .summary-box {
    background:linear-gradient(135deg,#0A1628,#1D3461);
    border-radius:10px; padding:20px 24px; margin-top:20px;
    border:1px solid rgba(201,162,39,.3);
  }
  .sum-row { display:flex; justify-content:space-between; padding:5px 0; color:rgba(200,215,240,.8); font-size:.9rem; }
  .sum-divider { border:none; border-top:1px solid rgba(201,162,39,.25); margin:8px 0; }
  .sum-final { padding-top:12px; }
  .sum-final .lbl { font-size:1.1rem; font-weight:900; color:#F0C040; }
  .sum-final .amt { font-size:1.4rem; font-weight:900; color:#F0C040; }
  .sum-final.paid .lbl, .sum-final.paid .amt { color:#4ade80; }

  /* Footer */
  .bill-footer { text-align:center; padding:14px 32px; border-top:1px solid #e8e0cc; color:#888; font-size:.78rem; }

  /* ── Print styles ── */
  @media print {
    body { background:#fff; }
    .action-bar { display:none !important; }
    .bill-wrap { margin:0; padding:0; }
    .bill-paper { box-shadow:none; border-radius:0; }
    .bill-header, .summary-box, table.bt th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    @page { margin:10mm; size:A4; }
  }

  @media(max-width:600px) {
    .bill-header, .bill-meta, .bill-body, .bill-footer { padding-left:16px; padding-right:16px; }
  }
</style>
</head>
<body>

<!-- Action Bar -->
<div class="action-bar" id="actionBar">
  <button class="btn btn-gold" onclick="window.print()">🖨 Print / Save PDF</button>
  <?php if ($waUrl): ?>
  <button class="btn btn-wa" id="waBtn" onclick="shareWhatsApp()">📱 Share on WhatsApp</button>
  <?php elseif ($bill['phone']): ?>
  <span class="btn" style="background:#555;cursor:not-allowed;opacity:.6">📱 No Valid Phone</span>
  <?php endif; ?>
  <a href="index.php" class="btn btn-back">← Back</a>
</div>

<!-- Toast -->
<div id="toast"><span class="dot"></span><span id="toastMsg"></span></div>

<!-- Attach reminder overlay (shown after PDF downloads) -->
<div id="attachOverlay" style="display:none;position:fixed;inset:0;background:rgba(5,14,28,.82);z-index:9999;align-items:center;justify-content:center;flex-direction:column">
  <div style="background:#0D1E33;border:1.5px solid #C9A227;border-radius:16px;padding:32px 40px;max-width:420px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.5)">
    <div style="font-size:3rem;margin-bottom:12px">📱</div>
    <h3 style="color:#C9A227;margin-bottom:8px;font-size:1.1rem">WhatsApp is Opening!</h3>
    <p style="color:#8BA3C7;font-size:.88rem;line-height:1.6;margin-bottom:20px">
      Your PDF bill has been <strong style="color:#22C55E">saved & downloaded</strong> automatically.<br><br>
      In WhatsApp, click the <strong style="color:#fff">📎 Attach</strong> button → <strong style="color:#fff">Document</strong> → select the PDF from your <strong style="color:#fff">Downloads</strong> folder.
    </p>
    <div style="background:rgba(201,162,39,.12);border-radius:8px;padding:10px 14px;margin-bottom:20px;font-size:.8rem;color:#C9A227">
      📁 Saved as: <strong id="savedFilename"></strong>
    </div>
    <button onclick="document.getElementById('attachOverlay').style.display='none'" style="background:linear-gradient(135deg,#C9A227,#A07B10);color:#fff;border:none;padding:10px 28px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem">Got it ✓</button>
  </div>
</div>

<div class="bill-wrap">
  <div class="bill-paper">

    <!-- Header -->
    <div class="bill-header">
      <div class="dairy-name">🥛 <?= htmlspecialchars($dairyName) ?></div>
      <div class="dairy-sub">
        <?php if($address): ?><?= htmlspecialchars($address) ?><?php endif; ?>
        <?php if($phone): ?> &nbsp;·&nbsp; 📞 <?= htmlspecialchars($phone) ?><?php endif; ?>
      </div>
      <div class="bill-tag">Monthly Milk Bill &mdash; <?= strtoupper($monthName.' '.$year) ?></div>
    </div>

    <!-- Customer Meta -->
    <div class="bill-meta">
      <div class="meta-left">
        <div class="cid">CID #<?= str_pad($bill['customer_id'],4,'0',STR_PAD_LEFT) ?></div>
        <strong><?= htmlspecialchars($bill['name']) ?></strong>
        <?php if($bill['phone']): ?><span>📞 <?= htmlspecialchars($bill['phone']) ?></span><br><?php endif; ?>
        <?php if($bill['address']): ?><span>📍 <?= htmlspecialchars($bill['address']) ?></span><?php endif; ?>
      </div>
      <div class="meta-right">
        <strong>Bill #<?= str_pad($bill['id'],5,'0',STR_PAD_LEFT) ?></strong>
        <?= $monthName ?> <?= $year ?><br>
        Milk: <?= $bill['milk_type'] ?><br>
        <span style="color:#aaa;font-size:.75rem">Generated: <?= date('d M Y', strtotime($bill['generated_at'])) ?></span>
      </div>
    </div>

    <div class="bill-body">

      <!-- Milk Entries -->
      <?php if (!empty($entries)): ?>
      <div class="section-title">🥛 Milk Entries</div>
      <table class="bt">
        <thead><tr><th>Date</th><th>Shift</th><th>Qty (L)</th><th>Rate/L</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= date('d M', strtotime($e['entry_date'])) ?></td>
          <td><?= $e['shift'] ?></td>
          <td><?= $e['quantity'] ?> L</td>
          <td><?= $currency ?><?= $e['rate_per_liter'] ?></td>
          <td style="font-weight:700"><?= $currency ?><?= number_format($e['quantity']*$e['rate_per_liter'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" style="font-weight:800">🥛 Milk Subtotal</td><td><?= $currency ?><?= number_format($milkTotal,2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Product Sales -->
      <?php if (!empty($sales)): ?>
      <div class="section-title">🛒 Product Sales</div>
      <table class="bt">
        <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Price/Unit</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
        <tr>
          <td><?= date('d M', strtotime($s['sale_date'])) ?></td>
          <td><?= htmlspecialchars($s['product_name']) ?></td>
          <td><?= $s['quantity'] ?> <?= $s['unit'] ?></td>
          <td><?= $currency ?><?= $s['price_per_unit'] ?></td>
          <td style="font-weight:700"><?= $currency ?><?= number_format($s['total_amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" style="font-weight:800">🛒 Products Subtotal</td><td><?= $currency ?><?= number_format($salesTotal,2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Payments -->
      <?php if (!empty($payments)): ?>
      <div class="section-title">💰 Payments Received</div>
      <table class="bt">
        <thead><tr><th>Date</th><th>Mode</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= date('d M', strtotime($p['payment_date'])) ?></td>
          <td><?= $p['payment_mode'] ?></td>
          <td style="font-weight:700;color:#16a34a"><?= $currency ?><?= number_format($p['amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2" style="font-weight:800">💰 Total Paid</td><td><?= $currency ?><?= number_format($paidTotal,2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Summary -->
      <div class="summary-box">
        <div class="sum-row"><span>Current Bill</span><span><?= $currency ?><?= number_format($bill['current_bill'],2) ?></span></div>
        <div class="sum-row"><span>Previous Balance</span><span><?= $currency ?><?= number_format($bill['previous_balance'],2) ?></span></div>
        <hr class="sum-divider">
        <div class="sum-row"><span>Total Paid</span><span style="color:#4ade80">— <?= $currency ?><?= number_format($bill['total_paid'],2) ?></span></div>
        <div class="sum-row sum-final <?= $bill['final_due']<=0?'paid':'' ?>">
          <span class="lbl"><?= $bill['final_due']<=0 ? '✅ Advance / Paid' : '⚠️ Balance Due' ?></span>
          <span class="amt"><?= $currency ?><?= number_format(abs($bill['final_due']),2) ?></span>
        </div>
      </      Thank you for choosing <?= htmlspecialchars($dairyName) ?> &mdash; Fresh milk, trusted daily 🥛<br>
      <?php if($phone): ?>Contact: <?= htmlspecialchars($phone) ?><?php endif; ?>
    </div>

  </div><!-- /bill-paper -->
</div><!-- /bill-wrap -->

<script>
// ── Toast helper ──────────────────────────────────────────────
var _tt = null;
function showToast(msg, ms) {
  var el = document.getElementById('toast');
  var tx = document.getElementById('toastMsg');
  if (!el||!tx) return;
  tx.textContent = msg;
  el.classList.add('show');
  clearTimeout(_tt);
  if (ms > 0) _tt = setTimeout(function(){ el.classList.remove('show'); }, ms);
}

// ── PHP values passed to JS ───────────────────────────────────
var BILL_ID   = <?= (int)$id ?>;
var FILENAME  = <?= json_encode('Bill_'.$id.'_'.preg_replace('/[^a-zA-Z0-9_\-]/','_',$bill['name']).'_'.date('M_Y',mktime(0,0,0,$bill['bill_month'],1,$bill['bill_year'])).'.pdf') ?>;
var WA_URL    = <?= json_encode($waUrl) ?>;
var BILL_MSG  = <?= json_encode("Hi {$bill['name']},\n\nYour milk bill for {$monthName} is ready.\nBill Amount : {$currency}".number_format($bill['current_bill'],2)."\nTotal Paid  : {$currency}".number_format($bill['total_paid'],2)."\n".($bill['final_due']<=0?"Fully Settled ✓":"Balance Due : {$currency}".number_format(abs($bill['final_due']),2))."\n\nThank you! — {$dairyName}") ?>;

// ── Main share function ───────────────────────────────────────
async function shareWhatsApp() {
  var btn = document.getElementById('waBtn');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Preparing PDF…'; }
  showToast('📄 Generating PDF…', 0);

  try {
    // 1. Fetch PDF blob from server (also auto-saves to invoices/ folder)
    var resp = await fetch('download_pdf.php?id=' + BILL_ID);
    if (!resp.ok) throw new Error('Server error ' + resp.status);
    var blob = await resp.blob();
    var file = new File([blob], FILENAME, { type: 'application/pdf' });

    // 2a. Web Share API — works on Android, iOS, Windows 10+ Chrome
    //     Opens native share sheet → user taps WhatsApp → PDF goes directly into chat
    if (navigator.canShare && navigator.canShare({ files: [file] })) {
      showToast('📱 Opening share sheet — pick WhatsApp…', 0);
      await navigator.share({
        title:  '<?= addslashes($dairyName) ?> — Milk Bill',
        text:   BILL_MSG,
        files:  [file]
      });
      showToast('✅ PDF shared successfully!', 4000);

    // 2b. Fallback for browsers that don't support file sharing
    //     Auto-download PDF + open WhatsApp
    } else {
      var objUrl = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = objUrl; a.download = FILENAME;
      document.body.appendChild(a); a.click();
      document.body.removeChild(a);
      setTimeout(function(){ URL.revokeObjectURL(objUrl); }, 3000);

      document.getElementById('savedFilename').textContent = FILENAME;

      setTimeout(function(){
        window.open(WA_URL, '_blank');
        showToast('✅ PDF saved! Attach in WhatsApp → 📎 Document', 7000);
        var ov = document.getElementById('attachOverlay');
        if (ov) ov.style.display = 'flex';
      }, 1800);
    }

  } catch(err) {
    if (err.name === 'AbortError') {
      showToast('Sharing cancelled.', 3000);
    } else {
      showToast('❌ ' + err.message, 5000);
    }
  }

  if (btn) { btn.disabled = false; btn.textContent = '📱 Share on WhatsApp'; }
}
</script>
</body>
</html>

