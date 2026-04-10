<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Pdf;

/**
 * A single column cell inside a Row.
 *
 * Returned by Row::col(). All content methods (h1, h2, p, text) render
 * into this column and return the parent Row so you can chain more columns.
 *
 * Example:
 *   ->row()
 *       ->col(8)->p('Left content')   // returns Row
 *       ->col(4)->h2('Right value')   // returns Row
 *   ->endRow()
 */
class Col
{
    private Row    $row;
    private Pdf    $pdf;
    private float  $x;
    private float  $y;
    private float  $width;
    private string $documentFont;

    public function __construct(Row $row, Pdf $pdf, float $x, float $y, float $width, string $documentFont)
    {
        $this->row          = $row;
        $this->pdf          = $pdf;
        $this->x            = $x;
        $this->y            = $y;
        $this->width        = $width;
        $this->documentFont = $documentFont;
    }

    /** Render a large heading in this column — returns the parent Row. */
    public function h1(string $text): Row
    {
        $this->pdf
            ->setFont($this->documentFont, 20)
            ->setTextColor(33, 33, 33)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight(14);
        return $this->row;
    }

    /** Render a section heading in this column — returns the parent Row. */
    public function h2(string $text): Row
    {
        $this->pdf
            ->setFont($this->documentFont, 14)
            ->setTextColor(80, 80, 80)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight(10);
        return $this->row;
    }

    /** Render body text in this column — returns the parent Row. */
    public function p(string $text): Row
    {
        $this->pdf
            ->setFont($this->documentFont, 10)
            ->setTextColor(100, 100, 100)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight(7);
        return $this->row;
    }

    /** Render plain text in this column — returns the parent Row. */
    public function text(string $text): Row
    {
        $this->pdf
            ->setFont($this->documentFont, 10)
            ->setTextColor(0, 0, 0)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight(7);
        return $this->row;
    }
}
