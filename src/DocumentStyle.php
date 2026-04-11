<?php

declare(strict_types=1);

namespace Arabel\Pdf;

/**
 * Visual style configuration for the Document API.
 *
 * All color values are [r, g, b] arrays (0–255 per channel).
 * All size values are in points (pt), spacing values in millimetres (mm).
 *
 * Usage:
 *   $style = new DocumentStyle();
 *   $style->h1Color     = [200, 0, 50];
 *   $style->tableHeadBg = [0, 120, 90];
 *   $doc = new Document(style: $style);
 */
class DocumentStyle
{
    // ── Headings ─────────────────────────────────────────────────────────────

    public float  $h1Size    = 20.0;
    /** @var int[] */
    public array  $h1Color   = [33, 33, 33];
    public float  $h1Spacing = 14.0;  // mm advanced after h1
    public string $h1Style   = 'B';   // '' | 'B' | 'I' | 'BI'

    public float  $h2Size    = 14.0;
    /** @var int[] */
    public array  $h2Color   = [80, 80, 80];
    public float  $h2Spacing = 10.0;
    public string $h2Style   = '';

    // ── Paragraph ────────────────────────────────────────────────────────────

    public float  $pSize    = 10.0;
    /** @var int[] */
    public array  $pColor   = [100, 100, 100];
    public float  $pSpacing = 7.0;
    public string $pStyle   = '';

    // ── Horizontal rule ──────────────────────────────────────────────────────

    /** @var int[] */
    public array $hrColor   = [200, 200, 200];
    public float $hrSpacing = 4.0;  // mm advanced after hr

    // ── Table header row ─────────────────────────────────────────────────────

    /** @var int[] */
    public array $tableHeadBg = [41, 98, 255];
    /** @var int[] */
    public array $tableHeadFg = [255, 255, 255];
    public float $tableHeadH  = 8.0;

    // ── Table data rows ──────────────────────────────────────────────────────

    /** @var int[] */
    public array $tableRowFg  = [60, 60, 60];
    /** @var int[] */
    public array $tableAltBg  = [245, 247, 255];  // alternating row background
    public float $tableRowH   = 7.0;   // minimum row height in mm
    public float $tableLineH  = 5.0;   // line height when text wraps (mm per line)
}
