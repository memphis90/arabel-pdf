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

    private int $rowIndex = 0;

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

        $this->renderHead($headers);
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
        $s     = $this->style;
        $isAlt = $this->rowIndex % 2 === 1;

        $this->pdf
            ->setFont($this->documentFont, $s->pSize)
            ->setFillColor(...($isAlt ? $s->tableAltBg : [255, 255, 255]))
            ->setTextColor(...$s->tableRowFg)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($cells as $i => $cell) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->cell($this->colWidths[$i], $s->tableRowH, (string) $cell, 1, $ln, 'L');
        }

        $this->cursorY += $s->tableRowH;
        $this->rowIndex++;
        return $this;
    }

    /**
     * Close the table and return to the Document, advancing the cursor
     * past the last rendered row.
     */
    public function endTable(): Document
    {
        $this->doc->advanceCursor($this->cursorY);
        return $this->doc;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function renderHead(array $headers): void
    {
        $s = $this->style;

        $this->pdf
            ->setFont($this->documentFont, $s->pSize)
            ->setFillColor(...$s->tableHeadBg)
            ->setTextColor(...$s->tableHeadFg)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($headers as $i => $header) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->cell($this->colWidths[$i], $s->tableHeadH, $header, 1, $ln, 'L');
        }

        $this->cursorY += $s->tableHeadH;
    }
}
