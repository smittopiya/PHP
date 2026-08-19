<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');
$cid = (int)($_GET['cid'] ?? 0);
if (!$cid) { echo json_encode(['balance'=>0]); exit; }
$st = $pdo->prepare("SELECT
    COALESCE(SUM(me.quantity*me.rate_per_liter),0) + COALESCE(SUM(ps.total_amount),0) - COALESCE(SUM(py.amount),0) AS balance
    FROM customers c
    LEFT JOIN milk_entries me ON me.customer_id=c.id AND me.is_absent=0
    LEFT JOIN product_sales ps ON ps.customer_id=c.id
    LEFT JOIN payments py ON py.customer_id=c.id
    WHERE c.id=?");
$st->execute([$cid]);
$row = $st->fetch();
echo json_encode(['balance' => max(0, $row['balance'])]);
