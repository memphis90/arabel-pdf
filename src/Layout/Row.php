<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Document;
use Arabel\Pdf\Pdf;

/**
 * A 12-column grid row inside a Document.
 *
 * Returned by Document::row(). Call col($span) to open a column,
 * then endRow() to close the row and return to the Document.
 */
class Row
{
    private Document $doc;
    private Pdf      $pdf;
    private float    $startY;
    private float    $contentWidth;
    private float    $marginLeft;
    private string   $documentFont;
    private float    $currentX;
    private float    $maxH = 0.0;

    public function __construct(
        Document $doc,
        Pdf      $pdf,
        float    $startY,
        float    $contentWidth,
        float    $marginLeft,
        string   $documentFont
    ) {
        $this->doc          = $doc;
        $this->pdf          = $pdf;
        $this->startY       = $startY;
        $this->contentWidth = $contentWidth;
        $this->marginLeft   = $marginLeft;
        $this->documentFont = $documentFont;
        $this->currentX     = $marginLeft;
    }

    /**
     * Open a column spanning $span units of a 12-column grid.
     * Returns a Col — call h1/h2/p/text on it, which returns this Row.
     *
     * @param int $span 1–12 (e.g. 6 = half width, 12 = full width)
     */
    public function col(int $span): Col
    {
        $colW           = ($this->contentWidth / 12) * $span;
        $col            = new Col($this, $this->pdf, $this->currentX, $this->startY, $colW, $this->documentFont);
        $this->currentX += $colW;
        return $col;
    }

    /**
     * Close the row and return to the Document, advancing the cursor
     * by the height of the tallest column rendered in this row.
     */
    public function endRow(): Document
    {
        $this->doc->advanceCursor($this->startY + $this->maxH);
        return $this->doc;
    }

    /** Called by Col after rendering to keep track of row height. */
    public function trackHeight(float $h): void
    {
        if ($h > $this->maxH) {
            $this->maxH = $h;
        }
    }
}
