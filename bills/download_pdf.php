<?php
/**
 * bills/download_pdf.php
 * Generates the bill PDF, saves it to /invoices/, and streams it as a download.
 * Called by the "Share on WhatsApp" button on pdf.php.
 */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../assets/PdfBill.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); die('Missing bill ID'); }

// ── Fetch bill + customer ────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT mb.*, c.name, c.phone, c.address, c.milk_type, c.id AS customer_id
    FROM monthly_bills mb
    JOIN customers c ON c.id = mb.customer_id
    WHERE mb.id = ?
");
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) { http_response_code(404); die('Bill not found'); }

// ── Fetch entries ────────────────────────────────────────────
$eStmt = $pdo->prepare("
    SELECT entry_date, shift, quantity, rate_per_liter
    FROM milk_entries
    WHERE customer_id = ? AND MONTH(entry_date) = ? AND YEAR(entry_date) = ?
      AND is_absent = 0
    ORDER BY entry_date, shift
");
$eStmt->execute([$bill['customer_id'], $bill['bill_month'], $bill['bill_year']]);
$entries = $eStmt->fetchAll();

// ── Fetch product sales ──────────────────────────────────────
$sStmt = $pdo->prepare("
    SELECT ps.sale_date, p.product_name, ps.quantity, p.unit,
           ps.price_per_unit, ps.total_amount
    FROM product_sales ps
    JOIN products p ON p.id = ps.product_id
    WHERE ps.customer_id = ? AND MONTH(ps.sale_date) = ? AND YEAR(ps.sale_date) = ?
");
$sStmt->execute([$bill['customer_id'], $bill['bill_month'], $bill['bill_year']]);
$sales = $sStmt->fetchAll();

// ── Fetch payments ───────────────────────────────────────────
$pStmt = $pdo->prepare("
    SELECT payment_date, amount, payment_mode
    FROM payments
    WHERE customer_id = ? AND MONTH(payment_date) = ? AND YEAR(payment_date) = ?
");
$pStmt->execute([$bill['customer_id'], $bill['bill_month'], $bill['bill_year']]);
$payments = $pStmt->fetchAll();

// ── Dairy settings ───────────────────────────────────────────
$dairyName  = setting('dairy_name', 'Smart Dairy');
$ownerName  = setting('owner_name', 'Owner');
$dairyPhone = setting('phone', '');
$dairyAddr  = setting('address', '');
$currency   = setting('currency', 'Rs.');

// ── Generate PDF binary ──────────────────────────────────────
$gen     = new PdfBill();
$pdfData = $gen->generate(
    $bill, $entries, $sales, $payments,
    $dairyName, $ownerName, $dairyPhone, $dairyAddr, $currency
);

// ── Save to invoices/ folder ─────────────────────────────────
$invoicesDir = __DIR__ . '/../invoices';
if (!is_dir($invoicesDir)) mkdir($invoicesDir, 0755, true);

$safeName  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bill['name']);
$monthName = date('M_Y', mktime(0,0,0,$bill['bill_month'],1,$bill['bill_year']));
$filename  = "Bill_{$id}_{$safeName}_{$monthName}.pdf";
$filepath  = $invoicesDir . '/' . $filename;
file_put_contents($filepath, $pdfData);

// ── Stream as download ───────────────────────────────────────
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfData));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $pdfData;
exit;

