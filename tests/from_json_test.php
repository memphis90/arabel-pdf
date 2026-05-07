<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Pdf.php';
require_once __DIR__ . '/../src/DocumentStyle.php';
require_once __DIR__ . '/../src/Layout/Row.php';
require_once __DIR__ . '/../src/Layout/Col.php';
require_once __DIR__ . '/../src/Layout/Table.php';
require_once __DIR__ . '/../src/Layout/Panel.php';
require_once __DIR__ . '/../src/Layout/Header.php';
require_once __DIR__ . '/../src/Layout/Footer.php';
require_once __DIR__ . '/../src/Document.php';

use Arabel\Pdf\Document;

// ── Simulate the DocAst that arabel-pdf-js would produce ──────────────────────

$ast = [
    'style' => [
        'h1'    => ['size' => 22, 'color' => [15, 55, 120], 'style' => 'B', 'spacing' => 12],
        'h2'    => ['size' => 12, 'color' => [15, 55, 120], 'style' => 'B', 'spacing' => 8],
        'p'     => ['size' => 9,  'color' => [60, 60, 60],  'style' => '',  'spacing' => 5.5],
        'hr'    => ['color' => [180, 195, 230], 'spacing' => 5],
        'table' => [
            'headBg' => [15, 55, 120], 'headFg' => [255, 255, 255], 'headH' => 8,
            'rowFg'  => [40, 40, 40],  'altBg'  => [235, 241, 255], 'rowH'  => 7,
        ],
    ],
    'pages' => [
        [
            'elements' => [
                ['type' => 'h1', 'text' => 'INVOICE'],
                ['type' => 'p',  'text' => '# INV-2026-001'],
                ['type' => 'hr'],
                ['type' => 'spacer', 'height' => 6],

                // Two-column row — one element per col (common case)
                ['type' => 'row', 'cols' => [
                    ['span' => 6, 'elements' => [['type' => 'p', 'text' => 'Bill to: Acme Corp']]],
                    ['span' => 6, 'elements' => [['type' => 'p', 'text' => 'Due: 2026-02-28']]],
                ]],

                // Multi-element col (JS extension — tests Col::batch())
                ['type' => 'row', 'cols' => [
                    ['span' => 8, 'elements' => [
                        ['type' => 'h2', 'text' => 'Services'],
                        ['type' => 'p',  'text' => 'Software development and consulting.'],
                    ]],
                    ['span' => 4, 'elements' => [
                        ['type' => 'p', 'text' => 'April 2026'],
                    ]],
                ]],

                ['type' => 'spacer', 'height' => 4],

                // Table
                ['type' => 'table',
                    'headers' => ['Description', 'Qty', 'Unit price', 'Total'],
                    'widths'  => [5, 1, 2, 2],
                    'aligns'  => ['L', 'C', 'R', 'R'],
                    'rows'    => [
                        ['Arabel PDF License',      '1',  '€ 49,00',  '€ 49,00'],
                        ['Development — Module PDF', '1',  '€ 800,00', '€ 800,00'],
                        ['',                        'Total', '',       '€ 849,00'],
                    ],
                ],

                ['type' => 'spacer', 'height' => 4],

                // Panel
                ['type' => 'panel',
                    'bg'       => [15, 55, 120],
                    'fg'       => [255, 255, 255],
                    'padding'  => 4,
                    'elements' => [
                        ['type' => 'h2', 'text' => 'Total due: € 849,00'],
                        ['type' => 'p',  'text' => 'Please pay by 2026-02-28. Thank you.'],
                    ],
                ],

                ['type' => 'hr'],
                ['type' => 'p', 'text' => 'Thank you for your business.'],
            ],
        ],
    ],
];

// ── Render from AST ───────────────────────────────────────────────────────────

$doc = Document::fromJson($ast);

$ts  = date('Ymd_His');
$out = __DIR__ . '/output/from_json_test_' . $ts . '.pdf';

if (!is_dir(__DIR__ . '/output')) {
    mkdir(__DIR__ . '/output', 0755, true);
}

$doc->output($out, 'F');

echo "PDF generated: $out\n";

// ── Also test JSON string input ───────────────────────────────────────────────

$json = json_encode($ast);
$doc2 = Document::fromJson($json);
$out2 = __DIR__ . '/output/from_json_string_test_' . $ts . '.pdf';
$doc2->output($out2, 'F');

echo "PDF from JSON string: $out2\n";
echo "fromJson() OK\n";
