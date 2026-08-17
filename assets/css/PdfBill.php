<?php
/**
 * PdfBill — Pure PHP PDF generator for Smart Dairy milk bills.
 * No Composer, no external libraries needed.
 * Uses PDF 1.4 specification with built-in Helvetica / Helvetica-Bold fonts.
 */
class PdfBill {

    private $W  = 595.28;   // A4 width  in points
    private $H  = 841.89;   // A4 height in points
    private $lm = 40;       // left  margin
    private $rm = 555.28;   // right margin (W - 40)
    private $bm = 40;       // bottom margin

    /* ── Escape a UTF-8 string for PDF text stream ─────────── */
    private function e(string $s): string {
        $s = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)$s);
        return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s);
    }

    /* ── Format number ──────────────────────────────────────── */
    private function n($v, int $d = 2): string {
        return number_format((float)$v, $d, '.', '');
    }

    /* ── PDF fill colour (r,g,b each 0-1) ──────────────────── */
    private function fc(float $r, float $g, float $b): string {
        return round($r,3).' '.round($g,3).' '.round($b,3)." rg\n";
    }

    /* ── PDF stroke colour ──────────────────────────────────── */
    private function sc(float $r, float $g, float $b): string {
        return round($r,3).' '.round($g,3).' '.round($b,3)." RG\n";
    }

    /* ── Filled rectangle [x, y-bottom, w, h] ──────────────── */
    private function rf(float $x, float $y, float $w, float $h): string {
        return "$x $y $w $h re f\n";
    }

    /* ── Text at (x, y) – baseline – with colour & font ─────── */
    private function txt(float $x, float $y, string $s,
                         string $font = 'F1', float $sz = 9,
                         float $r = 0.08, float $g = 0.10, float $b = 0.18): string {
        $c = $this->fc($r,$g,$b);
        return "BT\n/{$font} {$sz} Tf\n{$c}{$x} {$y} Td\n(".$this->e($s).") Tj\nET\n";
    }

    /* ── Horizontal rule ────────────────────────────────────── */
    private function hr(float $y, float $r=0.80, float $g=0.82, float $b=0.86): string {
        return $this->fc($r,$g,$b).$this->rf($this->lm, $y, $this->rm - $this->lm, 0.75);
    }

    /* ═══════════════════════════════════════════════════════════
       PUBLIC: generate()
       ═══════════════════════════════════════════════════════════ */
    public function generate(
        array  $bill,
        array  $entries,
        array  $sales,
        array  $payments,
        string $dairyName,
        string $ownerName,
        string $dairyPhone,
        string $dairyAddr,
        string $currency
    ): string {

        $cs  = '';                  // content stream
        $lm  = $this->lm;
        $rm  = $this->rm;
        $cw  = $rm - $lm;          // content width = 515.28
        $cy  = $this->H - 40;      // current Y (starts near top)

        // ── 1. HEADER BLOCK ────────────────────────────────────
        $hH = 72;
        $cs .= $this->fc(0.039, 0.086, 0.157);     // navy #0A1628
        $cs .= $this->rf($lm, $cy - $hH, $cw, $hH);
        $cs .= $this->fc(0.788, 0.635, 0.153);     // gold accent line
        $cs .= $this->rf($lm, $cy - $hH - 3, $cw, 3);

        // Dairy name
        $cs .= $this->txt($lm+12, $cy-26, $dairyName, 'F2', 18, 1,1,1);
        // Sub-line
        $sub = trim(($dairyAddr ? $dairyAddr : '') . ($dairyPhone ? '   Ph: '.$dairyPhone : ''));
        if ($sub) $cs .= $this->txt($lm+12, $cy-44, $sub, 'F1', 8, 0.65,0.78,0.90);

        // Right side: bill label + month
        $monthName = date('F Y', mktime(0,0,0,$bill['bill_month'],1,$bill['bill_year']));
        $cs .= $this->txt($rm-118, $cy-26, 'MONTHLY MILK BILL', 'F2', 9, 0.788,0.635,0.153);
        $cs .= $this->txt($rm-100, $cy-42, $monthName,           'F1', 8, 0.65,0.78,0.90);

        $cy -= $hH + 3 + 6;

        // ── 2. CUSTOMER META BAR ───────────────────────────────
        $mH = 50;
        $cs .= $this->fc(0.930, 0.940, 0.970);
        $cs .= $this->rf($lm, $cy - $mH, $cw, $mH);

        $cid = '#'.str_pad($bill['customer_id'] ?? $bill['id'], 4, '0', STR_PAD_LEFT);
        $cs .= $this->txt($lm+10, $cy-13, $cid,              'F2',  8, 0.788,0.635,0.153);
        $cs .= $this->txt($lm+10, $cy-26, $bill['name'],     'F2', 12, 0.039,0.086,0.157);
        if (!empty($bill['phone']))
            $cs .= $this->txt($lm+10, $cy-40, 'Ph: '.$bill['phone'], 'F1', 8, 0.40,0.45,0.55);

        $billNo  = 'Bill #'.str_pad($bill['id'], 5, '0', STR_PAD_LEFT);
        $genDate = 'Generated: '.date('d M Y');
        $cs .= $this->txt($rm-130, $cy-13, $billNo,              'F2', 9, 0.039,0.086,0.157);
        $cs .= $this->txt($rm-130, $cy-26, 'Milk: '.($bill['milk_type'] ?? ''), 'F1', 8, 0.40,0.45,0.55);
        $cs .= $this->txt($rm-130, $cy-40, $genDate,             'F1', 8, 0.55,0.60,0.65);

        $cy -= $mH + 12;

        // ── 3. TABLE HELPERS ────────────────────────────────────

        $sectionTitle = function(string $title) use (&$cs, &$cy, $lm, $cw) {
            $cs .= $this->fc(0.039, 0.086, 0.157);
            $cs .= $this->rf($lm, $cy-15, $cw, 15);
            $cs .= $this->txt($lm+8, $cy-10, $title, 'F2', 8, 1,1,1);
            $cy -= 15;
        };

        $tableHead = function(array $cols) use (&$cs, &$cy, $lm) {
            $totalW = array_sum(array_column($cols, 1));
            $cs .= $this->fc(0.870, 0.880, 0.910);
            $cs .= $this->rf($lm, $cy-14, $totalW, 14);
            $x = $lm;
            foreach ($cols as [$lbl, $w, $align]) {
                $tx = $align === 'R' ? ($x + $w - 5) : ($x + 5);
                $cs .= $this->txt($tx, $cy-10, $lbl, 'F2', 7.5, 0.25,0.30,0.40);
                $x += $w;
            }
            $cy -= 14;
        };

        $tableRow = function(array $cols, array $vals, int $idx) use (&$cs, &$cy, $lm) {
            $rH = 13;
            $totalW = array_sum(array_column($cols, 1));
            if ($idx % 2 === 1) {
                $cs .= $this->fc(0.955, 0.960, 0.975);
                $cs .= $this->rf($lm, $cy - $rH, $totalW, $rH);
            }
            $x = $lm;
            foreach ($cols as $i => [$lbl, $w, $align]) {
                $tx = $align === 'R' ? ($x + $w - 5) : ($x + 5);
                $cs .= $this->txt($tx, $cy-9.5, $vals[$i] ?? '', 'F1', 7.5);
                $x += $w;
            }
            $cy -= $rH;
        };

        $subtotalRow = function(string $label, string $val) use (&$cs, &$cy, $lm, $cw, $rm) {
            $cs .= $this->fc(0.950, 0.905, 0.760);
            $cs .= $this->rf($lm, $cy-13, $cw, 13);
            $cs .= $this->txt($lm+8, $cy-9, $label, 'F2', 8, 0.04,0.09,0.16);
            $cs .= $this->txt($rm-72, $cy-9, $val,  'F2', 8, 0.04,0.09,0.16);
            $cy -= 17;
        };

        // ── 4. MILK ENTRIES TABLE ──────────────────────────────
        if (!empty($entries)) {
            $sectionTitle('MILK ENTRIES');
            $cols = [
                ['Date',   68, 'L'],
                ['Shift',  58, 'L'],
                ['Qty (L)',74, 'R'],
                ['Rate/L', 74, 'R'],
                ['Amount', 90, 'R'],
            ];
            $tableHead($cols);
            $milkTotal = 0;
            foreach ($entries as $i => $e) {
                if ($cy < 140) break;
                $amt = $e['quantity'] * $e['rate_per_liter'];
                $milkTotal += $amt;
                $tableRow($cols, [
                    date('d M', strtotime($e['entry_date'])),
                    ucfirst($e['shift']),
                    $this->n($e['quantity'],1).' L',
                    'Rs.'.$this->n($e['rate_per_liter'],2),
                    'Rs.'.$this->n($amt,2),
                ], $i);
            }
            $subtotalRow('Milk Subtotal', 'Rs.'.$this->n($milkTotal,2));
        }

        // ── 5. PRODUCT SALES TABLE ─────────────────────────────
        if (!empty($sales)) {
            $cy -= 6;
            $sectionTitle('PRODUCT SALES');
            $cols = [
                ['Date',       62, 'L'],
                ['Product',   153, 'L'],
                ['Qty',        58, 'L'],
                ['Price/Unit', 78, 'R'],
                ['Total',      78, 'R'],
            ];
            $tableHead($cols);
            $salesTotal = 0;
            foreach ($sales as $i => $s) {
                if ($cy < 140) break;
                $salesTotal += $s['total_amount'];
                $tableRow($cols, [
                    date('d M', strtotime($s['sale_date'])),
                    $s['product_name'],
                    $s['quantity'].' '.($s['unit'] ?? ''),
                    'Rs.'.$this->n($s['price_per_unit'],2),
                    'Rs.'.$this->n($s['total_amount'],2),
                ], $i);
            }
            $subtotalRow('Products Subtotal', 'Rs.'.$this->n($salesTotal,2));
        }

        // ── 6. PAYMENTS TABLE ──────────────────────────────────
        if (!empty($payments)) {
            $cy -= 6;
            $sectionTitle('PAYMENTS RECEIVED');
            $cols = [
                ['Date',         88, 'L'],
                ['Payment Mode', 200,'L'],
                ['Amount',       141,'R'],
            ];
            $tableHead($cols);
            $paidTotal = 0;
            foreach ($payments as $i => $p) {
                if ($cy < 140) break;
                $paidTotal += $p['amount'];
                $tableRow($cols, [
                    date('d M Y', strtotime($p['payment_date'])),
                    $p['payment_mode'],
                    'Rs.'.$this->n($p['amount'],2),
                ], $i);
            }
            $subtotalRow('Total Paid', 'Rs.'.$this->n($paidTotal,2));
        }

        // ── 7. BILL SUMMARY BOX (bottom-right) ─────────────────
        $cy -= 18;
        $sbW = 215;
        $sbX = $rm - $sbW;
        $sbH = 88;

        // Navy box
        $cs .= $this->fc(0.039, 0.086, 0.157);
        $cs .= $this->rf($sbX, $cy - $sbH, $sbW, $sbH);

        // Summary rows
        $rows = [
            ['Current Bill',     'Rs.'.$this->n($bill['current_bill'],2)],
            ['Previous Balance', 'Rs.'.$this->n($bill['previous_balance'],2)],
            ['(-) Total Paid',   'Rs.'.$this->n($bill['total_paid'],2)],
        ];
        $sy = $cy - 13;
        foreach ($rows as [$lbl, $val]) {
            $cs .= $this->txt($sbX+10, $sy, $lbl, 'F1', 8,  0.62,0.75,0.90);
            $cs .= $this->txt($rm-72,  $sy, $val, 'F2', 8,  0.85,0.88,0.95);
            $sy -= 14;
        }

        // Gold divider
        $cs .= $this->fc(0.788, 0.635, 0.153);
        $cs .= $this->rf($sbX, $cy - 57, $sbW, 1);

        // Final due / settled
        $finalDue = (float)$bill['final_due'];
        $isOk     = $finalDue <= 0;
        $fLabel   = $isOk ? 'FULLY SETTLED' : 'BALANCE DUE';
        $fAmt     = 'Rs.'.$this->n(abs($finalDue), 2);

        if ($isOk)
            $cs .= $this->fc(0.08, 0.72, 0.30);
        else
            $cs .= $this->fc(0.92, 0.18, 0.14);
        $cs .= "BT\n/F2 8 Tf\n".($sbX+10)." ".($cy-70)." Td\n(".$this->e($fLabel).") Tj\nET\n";
        $cs .= $this->txt($rm-80, $cy-70, $fAmt, 'F2', 11, 1,1,1);

        // ── 8. FOOTER ──────────────────────────────────────────
        $ftH = 22;
        $cs .= $this->fc(0.039, 0.086, 0.157);
        $cs .= $this->rf($lm, $this->bm, $cw, $ftH);
        $footLine = 'Thank you for choosing '.$dairyName.'  —  Fresh milk, trusted daily';
        $cs .= $this->txt($lm+12, $this->bm+7, $footLine, 'F1', 7.5, 0.65,0.78,0.90);

        return $this->buildPdf($cs);
    }

    /* ═══════════════════════════════════════════════════════════
       PRIVATE: assemble valid PDF 1.4 binary
       ═══════════════════════════════════════════════════════════ */
    private function buildPdf(string $stream): string {
        $objects  = [];
        $offsets  = [];
        $buf      = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n"; // header + binary comment

        // Obj 1 — Catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";

        // Obj 2 — Pages
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";

        // Obj 3 — Page
        $objects[3] = "3 0 obj\n"
            ."<< /Type /Page /Parent 2 0 R\n"
            ."   /MediaBox [0 0 595.28 841.89]\n"
            ."   /Resources\n"
            ."   << /Font\n"
            ."      << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica      /Encoding /WinAnsiEncoding >>\n"
            ."         /F2 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\n"
            ."      >>\n"
            ."   >>\n"
            ."   /Contents 4 0 R\n"
            .">>\nendobj";

        // Obj 4 — Content stream
        $len = strlen($stream);
        $objects[4] = "4 0 obj\n<< /Length $len >>\nstream\n{$stream}\nendstream\nendobj";

        // Write objects and record offsets
        foreach ($objects as $n => $obj) {
            $offsets[$n] = strlen($buf);
            $buf .= $obj."\n\n";
        }

        // Cross-reference table
        $xrefOffset = strlen($buf);
        $count = count($objects) + 1;
        $buf .= "xref\n0 $count\n";
        $buf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $buf .= str_pad($offsets[$i], 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        // Trailer
        $buf .= "trailer\n<< /Size $count /Root 1 0 R >>\n";
        $buf .= "startxref\n$xrefOffset\n%%EOF";

        return $buf;
    }
}

