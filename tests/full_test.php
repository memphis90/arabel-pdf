<?php

require_once __DIR__ . '/../src/Pdf.php';

use Arabel\Pdf\Pdf;

$pdf = new Pdf();

// ── Page 1: text, cells, colors, shapes ─────────────────────────────────────

$pdf->addPage();
$pdf->setFont('Helvetica', 14);

// Header bar
$pdf->setFillColor(41, 98, 255);
$pdf->setTextColor(255, 255, 255);
$pdf->cell(190, 12, 'Arabel PDF — Full Test', 0, 1, 'C');

// Reset colors
$pdf->setFillColor(255, 255, 255);
$pdf->setTextColor(0, 0, 0);

// Spacer
$pdf->setXY(10, 30);

// Table header
$pdf->setFont('Helvetica', 10);
$pdf->setFillColor(220, 220, 220);
$pdf->cell(60, 8, 'Prodotto', 1, 0, 'L');
$pdf->cell(60, 8, 'Categoria', 1, 0, 'C');
$pdf->cell(70, 8, 'Prezzo', 1, 1, 'R');

// Table rows
$pdf->setFillColor(255, 255, 255);

$rows = [
    ['Arabel PDF', 'Libreria', 'Gratuita'],
    ['Arabel Builder', 'Tool', 'Premium'],
    ['Arabel Suite', 'Bundle', 'Enterprise'],
];

foreach ($rows as $row) {
    $pdf->cell(60, 7, $row[0], 1, 0, 'L');
    $pdf->cell(60, 7, $row[1], 1, 0, 'C');
    $pdf->cell(70, 7, $row[2], 1, 1, 'R');
}

// Separator line
$pdf->setDrawColor(100, 100, 100);
$pdf->setLineWidth(0.3);
$pdf->line(10, 70, 200, 70);

// Colored rectangles
$pdf->setXY(10, 75);
$pdf->setFont('Helvetica', 9);
$pdf->text(10, 73, 'Forme:');

$colors = [[255, 80, 80], [80, 180, 80], [80, 120, 255], [255, 180, 0]];
$labels = ['Rosso', 'Verde', 'Blu', 'Giallo'];

foreach ($colors as $i => $rgb) {
    $rx = 10 + $i * 48;
    $pdf->setFillColor(...$rgb);
    $pdf->setDrawColor(50, 50, 50);
    $pdf->rect($rx, 76, 40, 15, 'DF');
    $pdf->setTextColor(255, 255, 255);
    $pdf->text($rx + 8, 81, $labels[$i]);
    $pdf->setTextColor(0, 0, 0);
}

// Text at absolute positions
$pdf->setFont('Helvetica', 11);
$pdf->text(10, 100, 'Testo a posizione assoluta — accenti: à è ì ò ù');
$pdf->text(10, 110, 'Simboli: © ® € £ ¥ § ¶');

// ── Page 2: landscape + more text ────────────────────────────────────────────

$pdf->addPage('L');
$pdf->setFont('Helvetica', 16);
$pdf->setFillColor(41, 98, 255);
$pdf->setTextColor(255, 255, 255);
$pdf->cell(277, 14, 'Pagina Landscape — A4 Orizzontale', 0, 1, 'C');

$pdf->setTextColor(0, 0, 0);
$pdf->setFillColor(255, 255, 255);
$pdf->setFont('Helvetica', 10);
$pdf->setXY(10, 30);
$pdf->cell(277, 8, 'Larghezza pagina: 297mm  |  Altezza: 210mm', 0, 1, 'C');

// Grid of cells
$pdf->setXY(10, 50);
for ($row = 0; $row < 4; $row++) {
    for ($col = 0; $col < 6; $col++) {
        $val = ($row * 6) + $col + 1;
        $pdf->setFillColor(200 + $col * 8, 220 - $row * 15, 240);
        $pdf->cell(44, 10, "Cella $val", 1, 0, 'C');
    }
    $pdf->cell(0, 10, '', 0, 1);
    $pdf->setXY(10, $pdf->getY());
}

// ── Save ─────────────────────────────────────────────────────────────────────

$out = __DIR__ . '/output/full_test.pdf';
$pdf->output($out, 'F');

echo "PDF generato: $out\n";
echo "Pagine: 2 (portrait + landscape)\n";
