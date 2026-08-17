<?php
$id  = (int)($_GET['id'] ?? 0);
require_once __DIR__ . '/../db.php';
$bill = $pdo->prepare("SELECT mb.*, c.name, c.phone, c.address, c.milk_type FROM monthly_bills mb JOIN customers c ON c.id=mb.customer_id WHERE mb.id=?");
$bill->execute([$id]); $bill = $bill->fetch();
if (!$bill) die('<p>Bill not found.</p>');

$dairyName = setting('dairy_name','Smart Dairy');
$ownerName = setting('owner_name','Owner');
$phone     = setting('phone','');
$address   = setting('address','');
$currency  = setting('currency','₹');

$month = $bill['bill_month']; $year = $bill['bill_year'];
$monthName = date('F', mktime(0,0,0,$month,1));

// Milk entries
$entries = $pdo->prepare("SELECT entry_date,shift,quantity,rate_per_liter,is_absent FROM milk_entries WHERE customer_id=? AND MONTH(entry_date)=? AND YEAR(entry_date)=? AND is_absent=0 ORDER BY entry_date, shift");
$entries->execute([$bill['customer_id'],$month,$year]); $entries = $entries->fetchAll();

// Product sales
$sales = $pdo->prepare("SELECT ps.sale_date, p.product_name, ps.quantity, p.unit, ps.price_per_unit, ps.total_amount FROM product_sales ps JOIN products p ON p.id=ps.product_id WHERE ps.customer_id=? AND MONTH(ps.sale_date)=? AND YEAR(ps.sale_date)=?");
$sales->execute([$bill['customer_id'],$month,$year]); $sales = $sales->fetchAll();

// Payments
$payments = $pdo->prepare("SELECT payment_date, amount, payment_mode FROM payments WHERE customer_id=? AND MONTH(payment_date)=? AND YEAR(payment_date)=?");
$payments->execute([$bill['customer_id'],$month,$year]); $payments = $payments->fetchAll();

$waMsg = urlencode("Hi {$bill['name']}, your milk bill for $monthName $year is {$currency}".number_format($bill['final_due'],2).". {$dairyName} — {$phone}");
$waUrl = "https://wa.me/91{$bill['phone']}?text=$waMsg";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bill — <?= htmlspecialchars($bill['name']) ?> — <?= $monthName ?> <?= $year ?></title>
<link rel="stylesheet" href="/milk-management/assets/css/style.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap');
.bill-wrap { max-width:760px; margin:20px auto; padding:0 16px; font-family:'Inter',sans-serif; }
.bill-actions { display:flex; gap:12px; padding:16px 0; flex-wrap:wrap; }

.bill-paper {
  background:#fff; border-radius:16px; overflow:hidden;
  box-shadow:0 8px 40px rgba(10,22,40,0.15);
  border:1px solid #e8e0cc;
}
.bill-header-top {
  background:linear-gradient(135deg,#0A1628 0%,#1D3461 100%);
  padding:28px 32px 20px;
  position:relative;
  overflow:hidden;
}
.bill-header-top::after {
  content:'';position:absolute;bottom:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,transparent,#C9A227,transparent);
}
.bill-header-top h1 {
  font-family:'Playfair Display',serif;
  font-size:1.9rem; color:#F0C040; margin:0 0 4px; letter-spacing:0.5px;
}
.bill-header-top .dairy-info { color:rgba(200,215,240,0.75); font-size:.875rem; }
.bill-header-top .bill-tag {
  display:inline-block; margin-top:12px;
  background:rgba(201,162,39,0.15); border:1px solid rgba(201,162,39,0.3);
  color:#F0C040; padding:4px 16px; border-radius:20px;
  font-size:.8rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase;
}

.bill-meta-bar {
  display:flex; justify-content:space-between; align-items:flex-start;
  padding:18px 32px; background:#F8F6F0; border-bottom:1px solid #e8e0cc;
  gap:20px; flex-wrap:wrap;
}
.bill-meta-bar .meta-block { font-size:.875rem; color:#333; }
.bill-meta-bar .meta-block strong { display:block; color:#0A1628; font-size:1rem; margin-bottom:2px; }
.bill-meta-bar .meta-block .cid-tag {
  display:inline-block; background:linear-gradient(135deg,#A07B10,#C9A227);
  color:#fff; padding:2px 10px; border-radius:12px; font-size:.72rem; font-weight:800;
  letter-spacing:0.5px; margin-bottom:4px;
}
.bill-body { padding:24px 32px; }
.bill-section-title {
  font-weight:800; color:#0A1628; margin:18px 0 10px;
  font-size:.85rem; text-transform:uppercase; letter-spacing:0.8px;
  display:flex; align-items:center; gap:8px;
}
.bill-section-title::after {
  content:''; flex:1; height:1px;
  background:linear-gradient(90deg,#C9A227,transparent); opacity:0.4;
}
.bill-table { width:100%; border-collapse:collapse; font-size:.875rem; margin-bottom:6px; }
.bill-table th {
  background:#0A1628; color:rgba(255,255,255,.9);
  padding:9px 14px; text-align:left; font-size:.75rem;
  font-weight:700; text-transform:uppercase; letter-spacing:0.5px;
}
.bill-table td { padding:8px 14px; border-bottom:1px solid #f0ece0; color:#222; }
.bill-table tr:last-child td { border-bottom:none; }
.bill-table tfoot td {
  font-weight:800; background:#F0ECD6; padding:10px 14px;
  border-top:2px solid #C9A227; color:#0A1628;
}

.bill-summary {
  background:linear-gradient(135deg,#0A1628 0%,#1D3461 100%);
  border-radius:12px; padding:20px 24px; margin-top:20px;
  border:1px solid rgba(201,162,39,0.3);
}
.bill-summary table { width:100%; }
.bill-summary td { padding:6px 0; color:rgba(200,215,240,0.85); font-size:.9rem; }
.bill-summary td:last-child { text-align:right; font-weight:700; color:#fff; }
.bill-summary .summary-divider { border:none; border-top:1px solid rgba(201,162,39,0.25); margin:8px 0; }
.bill-summary .final-row td { padding-top:14px; font-size:1.2rem; font-weight:900; }
.bill-summary .final-row .final-label { color:#F0C040; }
.bill-summary .final-row .final-amount { color:#F0C040; font-size:1.4rem; }
.bill-summary .final-row.paid .final-label,
.bill-summary .final-row.paid .final-amount { color:#4ade80; }

.bill-footer { text-align:center; padding:16px 32px; border-top:1px solid #e8e0cc; color:#888; font-size:.8rem; }

@media print {
  .bill-actions { display:none!important; }
  .bill-wrap { margin:0; padding:0; }
  .bill-paper { box-shadow:none; border:none; }
  .bill-header-top { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .bill-table th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .bill-summary { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  body { background:#fff!important; }
}
@media(max-width:600px){ .bill-meta-bar,.bill-body,.bill-header-top{padding:16px;} }
</style>

</head>
<body>
<div class="bill-wrap">
  <!-- Actions -->
  <div class="bill-actions">
    <button onclick="window.print()" class="btn btn-primary">🖨 Print Bill</button>
    <?php if ($bill['phone']): ?>
    <a href="<?= $waUrl ?>" target="_blank" class="btn btn-success">📱 WhatsApp</a>
    <?php endif; ?>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <div class="bill-paper">
    <!-- Luxury Header -->
    <div class="bill-header-top">
      <h1>🥛 <?= htmlspecialchars($dairyName) ?></h1>
      <div class="dairy-info">
        <?php if ($address): ?><?= htmlspecialchars($address) ?><?php endif; ?>
        <?php if ($phone): ?> &nbsp;·&nbsp; 📞 <?= htmlspecialchars($phone) ?><?php endif; ?>
      </div>
      <div class="bill-tag">Monthly Milk Bill — <?= strtoupper($monthName.' '.$year) ?></div>
    </div>

    <!-- Customer Meta Bar -->
    <div class="bill-meta-bar">
      <div class="meta-block">
        <div class="cid-tag">CID #<?= str_pad($bill['customer_id'],4,'0',STR_PAD_LEFT) ?></div>
        <strong><?= htmlspecialchars($bill['name']) ?></strong>
        <?php if ($bill['phone']): ?>📞 <?= htmlspecialchars($bill['phone']) ?><br><?php endif; ?>
        <?php if ($bill['address']): ?><?= htmlspecialchars($bill['address']) ?><?php endif; ?>
      </div>
      <div class="meta-block" style="text-align:right">
        <strong>Bill #<?= str_pad($bill['id'],5,'0',STR_PAD_LEFT) ?></strong>
        <?= $monthName ?> <?= $year ?><br>
        Milk Type: <?= $bill['milk_type'] ?><br>
        <small style="color:#888">Generated: <?= date('d M Y',strtotime($bill['generated_at'])) ?></small>
      </div>
    </div>

    <div class="bill-body">
      <!-- Milk Entries -->
      <?php if (!empty($entries)): ?>
      <div class="bill-section-title">🥛 Milk Entries</div>
      <table class="bill-table">
        <thead><tr><th>Date</th><th>Shift</th><th>Qty (L)</th><th>Rate</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= date('d M',strtotime($e['entry_date'])) ?></td>
          <td><?= $e['shift'] ?></td>
          <td><?= $e['quantity'] ?>L</td>
          <td><?= $currency ?><?= $e['rate_per_liter'] ?>/L</td>
          <td style="font-weight:700"><?= $currency ?><?= number_format($e['quantity']*$e['rate_per_liter'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4" style="font-weight:800">🥛 Milk Total</td><td><?= $currency ?><?= number_format($bill['current_bill'] - array_sum(array_column($sales,'total_amount')),2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Product Sales -->
      <?php if (!empty($sales)): ?>
      <div class="bill-section-title">🛒 Product Sales</div>
      <table class="bill-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Price/Unit</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['product_name']) ?></td>
          <td><?= $s['quantity'] ?> <?= $s['unit'] ?></td>
          <td><?= $currency ?><?= $s['price_per_unit'] ?></td>
          <td style="font-weight:700"><?= $currency ?><?= number_format($s['total_amount'],2) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3" style="font-weight:800">🛒 Products Total</td><td><?= $currency ?><?= number_format(array_sum(array_column($sales,'total_amount')),2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Payments -->
      <?php if (!empty($payments)): ?>
      <div class="bill-section-title">💰 Payments Received</div>
      <table class="bill-table">
        <thead><tr><th>Date</th><th>Mode</th><th>Amount</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
        <tr><td><?= date('d M',strtotime($p['payment_date'])) ?></td><td><?= $p['payment_mode'] ?></td><td style="font-weight:700;color:#16a34a"><?= $currency ?><?= number_format($p['amount'],2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2" style="font-weight:800">💰 Total Paid</td><td><?= $currency ?><?= number_format($bill['total_paid'],2) ?></td></tr></tfoot>
      </table>
      <?php endif; ?>

      <!-- Summary Block -->
      <div class="bill-summary">
        <table>
          <tr><td>Current Bill</td><td><?= $currency ?><?= number_format($bill['current_bill'],2) ?></td></tr>
          <tr><td>Previous Balance</td><td><?= $currency ?><?= number_format($bill['previous_balance'],2) ?></td></tr>
          <hr class="summary-divider">
          <tr><td>Total Paid</td><td style="color:#4ade80">— <?= $currency ?><?= number_format($bill['total_paid'],2) ?></td></tr>
          <tr class="final-row <?= $bill['final_due']<=0?'paid':'' ?>">
            <td class="final-label"><?= $bill['final_due']<=0 ? '✅ Advance Paid' : '⚠️ Balance Due' ?></td>
            <td class="final-amount"><?= $currency ?><?= number_format(abs($bill['final_due']),2) ?></td>
          </tr>
        </table>
      </div>
    </div><!-- /bill-body -->

    <div class="bill-footer">
      Thank you for choosing <?= htmlspecialchars($dairyName) ?> — Fresh milk, trusted daily 🥛
    </div>
  </div><!-- /bill-paper -->

</div><!-- /bill-wrap -->
</body>
</html>

