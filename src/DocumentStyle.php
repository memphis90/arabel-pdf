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

    // ── Deserialization ──────────────────────────────────────────────────────

    /**
     * Reconstruct a DocumentStyle from a DocumentStyleConfig array
     * produced by arabel-pdf-js (DocumentStyle.toConfig()).
     *
     * @param array<string, mixed> $cfg
     */
    public static function fromArray(array $cfg): static
    {
        $s = new static();

        if (isset($cfg['h1'])) {
            if (isset($cfg['h1']['size']))    $s->h1Size    = (float)  $cfg['h1']['size'];
            if (isset($cfg['h1']['color']))   $s->h1Color   = (array)  $cfg['h1']['color'];
            if (isset($cfg['h1']['style']))   $s->h1Style   = (string) $cfg['h1']['style'];
            if (isset($cfg['h1']['spacing'])) $s->h1Spacing = (float)  $cfg['h1']['spacing'];
        }
        if (isset($cfg['h2'])) {
            if (isset($cfg['h2']['size']))    $s->h2Size    = (float)  $cfg['h2']['size'];
            if (isset($cfg['h2']['color']))   $s->h2Color   = (array)  $cfg['h2']['color'];
            if (isset($cfg['h2']['style']))   $s->h2Style   = (string) $cfg['h2']['style'];
            if (isset($cfg['h2']['spacing'])) $s->h2Spacing = (float)  $cfg['h2']['spacing'];
        }
        if (isset($cfg['p'])) {
            if (isset($cfg['p']['size']))    $s->pSize    = (float)  $cfg['p']['size'];
            if (isset($cfg['p']['color']))   $s->pColor   = (array)  $cfg['p']['color'];
            if (isset($cfg['p']['style']))   $s->pStyle   = (string) $cfg['p']['style'];
            if (isset($cfg['p']['spacing'])) $s->pSpacing = (float)  $cfg['p']['spacing'];
        }
        if (isset($cfg['hr'])) {
            if (isset($cfg['hr']['color']))   $s->hrColor   = (array) $cfg['hr']['color'];
            if (isset($cfg['hr']['spacing'])) $s->hrSpacing = (float) $cfg['hr']['spacing'];
        }
        if (isset($cfg['table'])) {
            $t = $cfg['table'];
            if (isset($t['headBg'])) $s->tableHeadBg = (array) $t['headBg'];
            if (isset($t['headFg'])) $s->tableHeadFg = (array) $t['headFg'];
            if (isset($t['headH']))  $s->tableHeadH  = (float) $t['headH'];
            if (isset($t['rowFg']))  $s->tableRowFg  = (array) $t['rowFg'];
            if (isset($t['altBg']))  $s->tableAltBg  = (array) $t['altBg'];
            if (isset($t['rowH']))   $s->tableRowH   = (float) $t['rowH'];
        }

        return $s;
    }
}
