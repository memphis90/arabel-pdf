<?php

require_once __DIR__ . '/../src/Pdf.php';

use Arabel\Pdf\Pdf;

(new Pdf())
    ->addPage()
    ->setFont('Helvetica', 14)
    ->text(20, 30, 'Hello World — Arabel PDF')
    ->output(__DIR__ . '/output/hello_world.pdf', 'F');

echo "PDF generated: " . __DIR__ . "/output/hello_world.pdf\n";
