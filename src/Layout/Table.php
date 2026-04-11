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
 * then accepts data rows via tr(). Call endTable() to flush everything.
 *
 * Column widths and alignment can be declared in any order relative to tr() —
 * all rendering is deferred until endTable().
 *
 * Example:
 *   ->table(['Prodotto', 'Qtà', 'Prezzo', 'Totale'])
 *       ->widths([4, 1, 2, 2])
 *       ->tr(['Arabel PDF', '3', '€ 49,00', '€ 147,00'])
 *       ->tr(['Arabel Builder', '1', '€ 99,00', '€ 99,00'])
 *       ->align(['L', 'C', 'R', 'R'])
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

    /** @var string[] Per-column alignment: 'L', 'C', or 'R' */
    private array $colAligns;

    /** @var string[] Header labels */
    private array $headers;

    /** @var string[][] Buffered data rows */
    private array $rows = [];

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
        $this->colAligns    = array_fill(0, $this->colCount, 'L');
        $this->headers      = $headers;
    }

    /**
     * Set custom column widths as proportional values.
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
     * Set per-column alignment.
     *
     * Example: ->align(['L', 'L', 'R', 'R']) for text-left, numbers-right
     *
     * @param string[] $aligns One of 'L', 'C', 'R' per column
     */
    public function align(array $aligns): static
    {
        foreach ($aligns as $i => $a) {
            $this->colAligns[$i] = strtoupper($a);
        }
        return $this;
    }

    /**
     * Buffer a data row. Rendering is deferred to endTable().
     *
     * @param string[] $cells One value per column
     */
    public function tr(array $cells): static
    {
        $this->rows[] = $cells;
        return $this;
    }

    /**
     * Flush all buffered rows to the PDF and return to the Document.
     */
    public function endTable(): Document
    {
        $this->renderHead($this->headers);

        foreach ($this->rows as $index => $cells) {
            $this->renderRow($cells, $index);
        }

        $this->doc->advanceCursor($this->cursorY);
        return $this->doc;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function renderHead(array $headers): void
    {
        $s = $this->style;

        $this->pdf->setFont($this->documentFont, $s->pSize);
        $headH    = $s->tableHeadH;
        $colIndex = 0;
        foreach ($headers as $header) {
            [$text, $colspan] = $this->normalizeCell($header, $colIndex);
            $w = $this->spanWidth($colIndex, $colspan);
            $h = $this->pdf->calcWrappedHeight($text, $w, $s->tableLineH);
            if ($h > $headH) {
                $headH = $h;
            }
            $colIndex += $colspan;
        }

        $this->pdf
            ->setFillColor(...$s->tableHeadBg)
            ->setTextColor(...$s->tableHeadFg)
            ->setXY($this->marginLeft, $this->cursorY);

        $colIndex = 0;
        foreach ($headers as $header) {
            [$text, $colspan, $align] = $this->normalizeCell($header, $colIndex);
            $w  = $this->spanWidth($colIndex, $colspan);
            $ln = ($colIndex + $colspan >= $this->colCount) ? 1 : 0;
            $this->pdf->multiCell($w, $headH, $text, 1, $s->tableLineH, $align, $ln);
            $colIndex += $colspan;
        }

        $this->cursorY += $headH;
    }

    private function renderRow(array $cells, int $index): void
    {
        $s = $this->style;

        // First pass: measure tallest cell (colspan cells get their full merged width)
        $this->pdf->setFont($this->documentFont, $s->pSize);
        $rowH     = $s->tableRowH;
        $colIndex = 0;
        foreach ($cells as $cellDef) {
            [$text, $colspan] = $this->normalizeCell($cellDef, $colIndex);
            $w = $this->spanWidth($colIndex, $colspan);
            $h = $this->pdf->calcWrappedHeight($text, $w, $s->tableLineH);
            if ($h > $rowH) {
                $rowH = $h;
            }
            $colIndex += $colspan;
        }

        // Second pass: render
        $isAlt = $index % 2 === 1;
        $this->pdf
            ->setFillColor(...($isAlt ? $s->tableAltBg : [255, 255, 255]))
            ->setTextColor(...$s->tableRowFg)
            ->setXY($this->marginLeft, $this->cursorY);

        $colIndex = 0;
        foreach ($cells as $cellDef) {
            [$text, $colspan, $align] = $this->normalizeCell($cellDef, $colIndex);
            $w  = $this->spanWidth($colIndex, $colspan);
            $ln = ($colIndex + $colspan >= $this->colCount) ? 1 : 0;
            $this->pdf->multiCell($w, $rowH, $text, 1, $s->tableLineH, $align, $ln);
            $colIndex += $colspan;
        }

        $this->cursorY += $rowH;
    }

    /**
     * Normalize a cell definition into [text, colspan, align].
     * Accepts a plain string or an array with 'text', 'colspan', 'align' keys.
     *
     * @return array{0: string, 1: int, 2: string}
     */
    private function normalizeCell(mixed $cell, int $colIndex): array
    {
        if (is_string($cell) || is_numeric($cell)) {
            return [(string) $cell, 1, $this->colAligns[$colIndex] ?? 'L'];
        }

        $text    = (string) ($cell['text']    ?? '');
        $colspan = max(1, (int) ($cell['colspan'] ?? 1));
        $align   = (string) ($cell['align']   ?? $this->colAligns[$colIndex] ?? 'L');
        return [$text, $colspan, $align];
    }

    /** Sum of column widths from $startCol for $colspan columns. */
    private function spanWidth(int $startCol, int $colspan): float
    {
        $w = 0.0;
        for ($i = 0; $i < $colspan && $startCol + $i < $this->colCount; $i++) {
            $w += $this->colWidths[$startCol + $i];
        }
        return $w;
    }
}
