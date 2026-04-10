<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Document;
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
    private Document $doc;
    private Pdf      $pdf;
    private float    $cursorY;
    private float    $contentWidth;
    private float    $marginLeft;
    private string   $documentFont;
    private int      $colCount;

    /** @var float[] Column widths in mm */
    private array $colWidths;

    private const ROW_H    = 7.0;
    private const HEAD_H   = 8.0;
    private const HEAD_BG  = [41, 98, 255];
    private const HEAD_FG  = [255, 255, 255];
    private const ROW_FG   = [60, 60, 60];
    private const ALT_BG   = [245, 247, 255];

    private int $rowIndex = 0;

    public function __construct(
        Document $doc,
        Pdf      $pdf,
        float    $cursorY,
        float    $contentWidth,
        float    $marginLeft,
        array    $headers,
        string   $documentFont
    ) {
        $this->doc          = $doc;
        $this->pdf          = $pdf;
        $this->cursorY      = $cursorY;
        $this->contentWidth = $contentWidth;
        $this->marginLeft   = $marginLeft;
        $this->documentFont = $documentFont;
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
        $isAlt = $this->rowIndex % 2 === 1;

        $this->pdf
            ->setFont($this->documentFont, 10)
            ->setFillColor(...($isAlt ? self::ALT_BG : [255, 255, 255]))
            ->setTextColor(...self::ROW_FG)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($cells as $i => $cell) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->cell($this->colWidths[$i], self::ROW_H, (string) $cell, 1, $ln, 'L');
        }

        $this->cursorY += self::ROW_H;
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
        $this->pdf
            ->setFont($this->documentFont, 10)
            ->setFillColor(...self::HEAD_BG)
            ->setTextColor(...self::HEAD_FG)
            ->setXY($this->marginLeft, $this->cursorY);

        foreach ($headers as $i => $header) {
            $ln = $i === $this->colCount - 1 ? 1 : 0;
            $this->pdf->cell($this->colWidths[$i], self::HEAD_H, $header, 1, $ln, 'L');
        }

        $this->cursorY += self::HEAD_H;
    }
}
