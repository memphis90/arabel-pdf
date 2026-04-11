<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Document;
use Arabel\Pdf\DocumentStyle;
use Arabel\Pdf\Pdf;

/**
 * A table builder inside a Document.
 *
 * Returned by Document::table(). Renders a styled header row automatically,
 * then accepts data rows via tr(). Call endTable() to return to the Document.
 *
 * Column widths are distributed equally by default.
 * Use widths() to set custom proportions.
 *
 * Example:
 *   ->table(['Prodotto', 'Qtà', 'Prezzo'])
 *       ->tr(['Arabel PDF',     '142', '€ 0'])
 *       ->tr(['Arabel Builder', '38',  '€ 49'])
 *   ->endTable()
 */
class Table
{
    private Document      $doc;
    private Pdf           $pdf;
    private DocumentStyle $style;
    private float         $cursorY;
    private float         $contentWidth;
    private float         $marginLeft;
    private string        $documentFont;
    private int           $colCount;

    /** @var float[] Column widths in mm */
    private array $colWidths;

    /** @var string[] Stored until the first tr() so widths() can run first */
    private array $headers;

    private bool $headRendered = false;
    private int  $rowIndex     = 0;

    public function __construct(
        Document      $doc,
        Pdf           $pdf,
        float         $cursorY,
        float         $contentWidth,
        float         $marginLeft,
        array         $headers,
        string        $documentFont,
        DocumentStyle $style
    ) {
        $this->doc          = $doc;
        $this->pdf          = $pdf;
        $this->cursorY      = $cursorY;
        $this->contentWidth = $contentWidth;
        $this->marginLeft   = $marginLeft;
        $this->documentFont = $documentFont;
        $this->style        = $style;
        $this->colCount     = count($headers);
        $this->colWidths    = array_fill(0, $this->colCount, $contentWidth / $this->colCount);
        $this->headers      = $headers;
        // renderHead() is deferred to the first tr() so widths() can be called first
    }

    /**
     * Set custom column widths as proportional values.
     * Must be called before tr() rows are added.
     *
     * Example: ->widths([2, 1, 1]) distributes 50% / 25% / 25%
     *
     * @param int[] $proportions Relative weights, one per column
     */
    public function widths(array $proportions): static
    {
        $total = array_sum($proportions);
        foreach ($proportions as $i => $p) {
            $this->colWidths[$i] = ($p / $total) * $this->contentWidth;
        }
        return $this;
    }

    /**
     * Add a data row to the table.
     *
     * @param string[] $cells One value per column
     */
    public function tr(array $cells): static
    {
        if (!$this->headRendered) {
            $this->renderHead($this->headers);
        }

        $s = $this->style;

        // First pass: measure each cell and find the tallest
        $this->pdf->setFont($this->documentFont, $s->pSize);
        $rowH = $s->tableRowH;
        foreach ($cells as $i => $cell) {
            $h = $this->pdf->calcWrappedHeight((string) $cell, $this->colWidths[$i], $s->tableLineH);
            if ($h > $rowH) {
                $rowH = $h;
            }
        }

        // Second pass: render all cells at the same height
        $isAlt = $this->rowIndex % 2 === 1;
        $this->pdf
            ->setFillColor(...($isAlt ? $s->tableAltBg : [255, 255, 255]))
            ->setTextColor(...$s->tableRowFg)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($cells as $i => $cell) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->multiCell($this->colWidths[$i], $rowH, (string) $cell, 1, $s->tableLineH, 'L', $ln);
        }

        $this->cursorY += $rowH;
        $this->rowIndex++;
        return $this;
    }

    /**
     * Close the table and return to the Document, advancing the cursor
     * past the last rendered row.
     */
    public function endTable(): Document
    {
        // Edge case: table with headers only and no tr() calls
        if (!$this->headRendered) {
            $this->renderHead($this->headers);
        }

        $this->doc->advanceCursor($this->cursorY);
        return $this->doc;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function renderHead(array $headers): void
    {
        $s = $this->style;

        // First pass: measure and find the tallest header cell
        $this->pdf->setFont($this->documentFont, $s->pSize);
        $headH = $s->tableHeadH;
        foreach ($headers as $i => $header) {
            $h = $this->pdf->calcWrappedHeight($header, $this->colWidths[$i], $s->tableLineH);
            if ($h > $headH) {
                $headH = $h;
            }
        }

        // Second pass: render all headers at the same height
        $this->pdf
            ->setFillColor(...$s->tableHeadBg)
            ->setTextColor(...$s->tableHeadFg)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($headers as $i => $header) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->multiCell($this->colWidths[$i], $headH, $header, 1, $s->tableLineH, 'L', $ln);
        }

        $this->cursorY    += $headH;
        $this->headRendered = true;
    }
}
