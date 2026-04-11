<?php

declare(strict_types=1);

namespace Arabel\Pdf;

use Arabel\Pdf\Layout\Row;
use Arabel\Pdf\Layout\Table;

/**
 * High-level document API built on top of Pdf.
 *
 * Manages a semantic cursor (no mm knowledge required) and provides
 * grid-based layout via row()/col() and semantic elements: h1, h2, p, table.
 *
 * For pixel-precise control, access the underlying Pdf instance via raw().
 */
class Document
{
    private Pdf           $pdf;
    private DocumentStyle $style;

    private string $documentFont = 'Helvetica';

    private float $marginLeft   = 15.0;
    private float $marginTop    = 15.0;
    private float $marginRight  = 15.0;
    private float $pageW        = 210.0; // A4 portrait width in mm

    /** Current Y cursor in mm from page top. */
    private float $cursorY = 15.0;

    public function __construct(string $font = '', ?DocumentStyle $style = null)
    {
        $this->documentFont = !empty($font) ? $font : $this->documentFont;
        $this->style        = $style ?? new DocumentStyle();
        $this->pdf          = new Pdf();
        $this->pdf->setMargins($this->marginLeft, $this->marginTop, $this->marginRight);
    }

    // ── Page ─────────────────────────────────────────────────────────────────

    /**
     * Add a new page and reset the cursor to the top margin.
     *
     * @param string $orientation 'P' portrait (default) | 'L' landscape
     */
    public function addPage(string $orientation = 'P'): static
    {
        $this->pdf->addPage($orientation);

        $this->pageW   = strtoupper($orientation) === 'L' ? 297.0 : 210.0;
        $this->cursorY = $this->marginTop;

        return $this;
    }

    // ── Typography ───────────────────────────────────────────────────────────

    /** Large heading. */
    public function h1(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->h1Size, $s->h1Style)
            ->setTextColor(...$s->h1Color);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->h1Spacing);
        return $this;
    }

    /** Section heading. */
    public function h2(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->h2Size, $s->h2Style)
            ->setTextColor(...$s->h2Color);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->h2Spacing);
        return $this;
    }

    /** Body paragraph. */
    public function p(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, $s->pStyle)
            ->setTextColor(...$s->pColor);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Bold text — same size and color as p(). */
    public function b(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'B')
            ->setTextColor(...$s->pColor);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Italic text — same size and color as p(). */
    public function i(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'I')
            ->setTextColor(...$s->pColor);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Bold + italic text — same size and color as p(). */
    public function bi(string $text): static
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'BI')
            ->setTextColor(...$s->pColor);

        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Horizontal rule — thin line across the full content width. */
    public function hr(): static
    {
        $s = $this->style;
        $this->pdf
            ->setDrawColor(...$s->hrColor)
            ->setLineWidth(0.2)
            ->line($this->marginLeft, $this->cursorY, $this->marginLeft + $this->contentWidth(), $this->cursorY);

        $this->cursorY += $s->hrSpacing;
        return $this;
    }

    /** Vertical blank space. */
    public function spacer(float $height = 6.0): static
    {
        $this->cursorY += $height;
        return $this;
    }

    // ── Grid layout ──────────────────────────────────────────────────────────

    /**
     * Start a 12-column grid row.
     * Call col() on the returned Row, then endRow() to return here.
     *
     * Example:
     *   ->row()
     *       ->col(8)->p('Left side content')
     *       ->col(4)->h2('Right value')
     *   ->endRow()
     */
    public function row(): Row
    {
        return new Row($this, $this->pdf, $this->cursorY, $this->contentWidth(), $this->marginLeft, $this->documentFont, $this->style);
    }

    // ── Table ────────────────────────────────────────────────────────────────

    /**
     * Start a table with the given column headers.
     * Columns are equally distributed across the content width.
     * Call tr() for each data row, then endTable() to return here.
     *
     * Example:
     *   ->table(['Prodotto', 'Qtà', 'Prezzo'])
     *       ->tr(['Arabel PDF', '142', '€ 0'])
     *       ->tr(['Arabel Builder', '38', '€ 49'])
     *   ->endTable()
     */
    public function table(array $headers): Table
    {
        return new Table($this, $this->pdf, $this->cursorY, $this->contentWidth(), $this->marginLeft, $headers, $this->documentFont, $this->style);
    }

    // ── Output ───────────────────────────────────────────────────────────────

    /**
     * Finalize and output the document.
     *
     * @param string $name Filename or path
     * @param string $dest 'D' download | 'I' inline | 'F' file | 'S' string
     * @return string Raw PDF bytes
     */
    public function output(string $name = 'document.pdf', string $dest = 'D'): string
    {
        return $this->pdf->output($name, $dest);
    }

    /**
     * Access the underlying Pdf instance for advanced/precise operations.
     * Use sparingly — prefer Document methods when possible.
     */
    public function raw(): Pdf
    {
        return $this->pdf;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /** Called by Row and Table when they finish rendering. */
    public function advanceCursor(float $newY): void
    {
        $this->cursorY = $newY;
    }

    public function getCursorY(): float
    {
        return $this->cursorY;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    /**
     * X coordinate in mm where column $startSpan begins (0-based span offset).
     *
     * Example: colX(7) → X where the 8th column unit starts (after 7 units)
     *
     * @param int $startSpan Number of column units from the left margin (0–12)
     */
    public function colX(int $startSpan): float
    {
        return $this->marginLeft + $startSpan * ($this->contentWidth() / 12);
    }

    /**
     * Width in mm of $span column units.
     *
     * Example: colW(5) → width of 5 out of 12 columns
     *
     * @param int $span Number of column units (1–12)
     */
    public function colW(int $span): float
    {
        return $span * ($this->contentWidth() / 12);
    }

    private function contentWidth(): float
    {
        return $this->pageW - $this->marginLeft - $this->marginRight;
    }
}
