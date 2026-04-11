<?php

declare(strict_types=1);

namespace Arabel\Pdf;

/**
 * Visual style configuration for the Document API.
 *
 * All color values are [r, g, b] arrays (0–255 per channel).
 * All size values are in points (pt), spacing values in millimetres (mm).
 *
 * Usage — fluent helpers (recommended):
 *   $style = new DocumentStyle();
 *   $style->h1(22, [15, 55, 120], 'B', 12)
 *         ->h2(12, [15, 55, 120], 'B', 8)
 *         ->p(9,  [60, 60, 60],   '',  5.5);
 *
 * Usage — direct properties (also supported):
 *   $style->h1Color = [200, 0, 50];
 *   $style->tableHeadBg = [0, 120, 90];
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

    // ── Fluent heading/paragraph configurators ────────────────────────────────

    /**
     * Configure h1 style in one call.
     *
     * @param int[]  $color   [r, g, b]
     * @param string $style   '' | 'B' | 'I' | 'BI'
     * @param float  $spacing mm advanced after the element
     */
    public function h1(float $size, array $color = [], string $style = 'B', float $spacing = 14.0): static
    {
        $this->h1Size    = $size;
        $this->h1Spacing = $spacing;
        $this->h1Style   = $style;
        if ($color !== []) {
            $this->h1Color = $color;
        }
        return $this;
    }

    /**
     * Configure h2 style in one call.
     *
     * @param int[]  $color   [r, g, b]
     * @param string $style   '' | 'B' | 'I' | 'BI'
     * @param float  $spacing mm advanced after the element
     */
    public function h2(float $size, array $color = [], string $style = '', float $spacing = 10.0): static
    {
        $this->h2Size    = $size;
        $this->h2Spacing = $spacing;
        $this->h2Style   = $style;
        if ($color !== []) {
            $this->h2Color = $color;
        }
        return $this;
    }

    /**
     * Configure paragraph style in one call.
     *
     * @param int[]  $color   [r, g, b]
     * @param string $style   '' | 'B' | 'I' | 'BI'
     * @param float  $spacing mm advanced after the element
     */
    public function p(float $size, array $color = [], string $style = '', float $spacing = 7.0): static
    {
        $this->pSize    = $size;
        $this->pSpacing = $spacing;
        $this->pStyle   = $style;
        if ($color !== []) {
            $this->pColor = $color;
        }
        return $this;
    }

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
