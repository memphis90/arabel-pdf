<?php

require_once __DIR__ . '/../src/Pdf.php';

use Arabel\Pdf\Pdf;

$pdf = new Pdf();

$pdf->addPage();
$pdf->setFont('Helvetica', 14);
$pdf->text(20, 30, 'Hello World — Arabel PDF');

// Save to file instead of downloading (easier to test locally)
$pdf->output(__DIR__ . '/output/hello_world.pdf', 'F');

echo "PDF generated: " . __DIR__ . "/output/hello_world.pdf\n";
