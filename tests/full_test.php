<?php

require_once __DIR__ . '/../src/Pdf.php';

use Arabel\Pdf\Pdf;

$pdf = new Pdf();

// ── Page 1: text, cells, colors, shapes ─────────────────────────────────────

$pdf->addPage()
    ->setFont('Helvetica', 14)
    ->setFillColor(41, 98, 255)
    ->setTextColor(255, 255, 255)
    ->cell(190, 12, 'Arabel PDF — Full Test', 0, 1, 'C')
    ->setFillColor(255, 255, 255)
    ->setTextColor(0, 0, 0)
    ->setXY(10, 30)
    ->setFont('Helvetica', 10)
    ->setFillColor(220, 220, 220)
    ->cell(60, 8, 'Prodotto',  1, 0, 'L')
    ->cell(60, 8, 'Categoria', 1, 0, 'C')
    ->cell(70, 8, 'Prezzo',    1, 1, 'R')
    ->setFillColor(255, 255, 255);

// Table rows (loop — chain within each row)
$rows = [
    ['Arabel PDF',     'Libreria', 'Gratuita'],
    ['Arabel Builder', 'Tool',     'Premium'],
    ['Arabel Suite',   'Bundle',   'Enterprise'],
];

foreach ($rows as $row) {
    $pdf->cell(60, 7, $row[0], 1, 0, 'L')
        ->cell(60, 7, $row[1], 1, 0, 'C')
        ->cell(70, 7, $row[2], 1, 1, 'R');
}

// Separator + shapes header
$pdf->setDrawColor(100, 100, 100)
    ->setLineWidth(0.3)
    ->line(10, 70, 200, 70)
    ->setFont('Helvetica', 9)
    ->text(10, 73, 'Forme:');

// Colored rectangles (loop — chain within each iteration)
$colors = [[255, 80, 80], [80, 180, 80], [80, 120, 255], [255, 180, 0]];
$labels = ['Rosso', 'Verde', 'Blu', 'Giallo'];

foreach ($colors as $i => $rgb) {
    $rx = 10 + $i * 48;
    $pdf->setFillColor(...$rgb)
        ->setDrawColor(50, 50, 50)
        ->rect($rx, 76, 40, 15, 'DF')
        ->setTextColor(255, 255, 255)
        ->text($rx + 8, 81, $labels[$i])
        ->setTextColor(0, 0, 0);
}

// Text at absolute positions
$pdf->setFont('Helvetica', 11)
    ->text(10, 100, 'Testo a posizione assoluta — accenti: à è ì ò ù')
    ->text(10, 110, 'Simboli: © ® € £ ¥ § ¶');

// ── Page 2: landscape + grid ─────────────────────────────────────────────────

$pdf->addPage('L')
    ->setFont('Helvetica', 16)
    ->setFillColor(41, 98, 255)
    ->setTextColor(255, 255, 255)
    ->cell(277, 14, 'Pagina Landscape — A4 Orizzontale', 0, 1, 'C')
    ->setTextColor(0, 0, 0)
    ->setFillColor(255, 255, 255)
    ->setFont('Helvetica', 10)
    ->setXY(10, 30)
    ->cell(277, 8, 'Larghezza pagina: 297mm  |  Altezza: 210mm', 0, 1, 'C')
    ->setXY(10, 50);

// Colored grid (loop — getY() getter breaks chain by design, used between rows)
for ($row = 0; $row < 4; $row++) {
    for ($col = 0; $col < 6; $col++) {
        $pdf->setFillColor(200 + $col * 8, 220 - $row * 15, 240)
            ->cell(44, 10, 'Cella ' . ($row * 6 + $col + 1), 1, 0, 'C');
    }
    $pdf->cell(0, 10, '', 0, 1)
        ->setXY(10, $pdf->getY());  // getY() è un getter, rompe la chain intenzionalmente
}

// ── Save ─────────────────────────────────────────────────────────────────────

$out = __DIR__ . '/output/full_test.pdf';
$pdf->output($out, 'F');

echo "PDF generato: $out\n";
echo "Pagine: 2 (portrait + landscape)\n";
