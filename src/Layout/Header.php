<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Pdf;

/**
 * A repeatable page header registered on a Document.
 *
 * Headers are registered by name via Document::setHeader(). The 'default'
 * header is applied automatically to every addPage() call. Named headers
 * are applied explicitly: $doc->addPage('P', 'allegato').
 *
 * Multiple headers can be registered on the same document:
 *
 *   $doc->setHeader()                          // name = 'default'
 *       ->bg([15, 55, 120])
 *       ->fg([255, 255, 255])
 *       ->left('ARABEL SRL', 'Software & Digital Products')
 *       ->right('FATTURA', '# INV-2026-0042')
 *       ->height(22);
 *
 *   $doc->setHeader('allegato')
 *       ->bg([15, 55, 120])
 *       ->fg([255, 255, 255])
 *       ->left('ALLEGATO A — Dettaglio attività', 'Fattura INV-2026-0042');
 *
 *   $doc->addPage();                // → 'default' header
 *   $doc->addPage('P', 'allegato'); // → 'allegato' header
 *   $doc->addPage('P', false);      // → no header
 */
class Header
{
    /** @var int[] Background fill color — empty = no background */
    private array $bg = [];

    /** @var int[] Text color */
    private array $fg = [255, 255, 255];

    private float  $height   = 20.0;
    private string $leftMain  = '';
    private string $leftSub   = '';
    private string $rightMain = '';
    private string $rightSub  = '';

    // ── Configuration ─────────────────────────────────────────────────────────

    /** @param int[] $color [r, g, b] */
    public function bg(array $color): static
    {
        $this->bg = $color;
        return $this;
    }

    /** @param int[] $color [r, g, b] */
    public function fg(array $color): static
    {
        $this->fg = $color;
        return $this;
    }

    /** Height of the header band in mm. */
    public function height(float $h): static
    {
        $this->height = $h;
        return $this;
    }

    /**
     * Left-side content: a main line (larger, bold) and an optional subtitle.
     */
    public function left(string $main, string $sub = ''): static
    {
        $this->leftMain = $main;
        $this->leftSub  = $sub;
        return $this;
    }

    /**
     * Right-side content: a main line (larger, bold) and an optional subtitle.
     * Both lines are right-aligned.
     */
    public function right(string $main, string $sub = ''): static
    {
        $this->rightMain = $main;
        $this->rightSub  = $sub;
        return $this;
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Render this header onto the current PDF page.
     * Returns the height consumed so the caller can advance the cursor.
     *
     * @param  string $font     Active document font family
     * @param  float  $pageW    Page width in mm
     * @param  float  $marginX  Left/right margin in mm
     */
    public function render(Pdf $pdf, string $font, float $pageW, float $marginX): float
    {
        // Background band
        if ($this->bg !== []) {
            $pdf->setFillColor(...$this->bg)->rect(0, 0, $pageW, $this->height, 'F');
        }

        $pdf->setTextColor(...$this->fg);

        $mainSize = 16.0;
        $subSize  = 9.0;
        $pad      = $marginX;

        // Left side
        if ($this->leftMain !== '') {
            $pdf->setFont($font, $mainSize, 'B')->text($pad, $this->height * 0.38, $this->leftMain);
        }
        if ($this->leftSub !== '') {
            $pdf->setFont($font, $subSize)->text($pad, $this->height * 0.72, $this->leftSub);
        }

        // Right side — measure text width to right-align
        if ($this->rightMain !== '') {
            $pdf->setFont($font, $mainSize, 'B');
            $w = $pdf->getStringWidth($this->rightMain);
            $pdf->text($pageW - $pad - $w, $this->height * 0.38, $this->rightMain);
        }
        if ($this->rightSub !== '') {
            $pdf->setFont($font, $subSize);
            $w = $pdf->getStringWidth($this->rightSub);
            $pdf->text($pageW - $pad - $w, $this->height * 0.72, $this->rightSub);
        }

        return $this->height;
    }
}
