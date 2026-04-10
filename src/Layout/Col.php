<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\DocumentStyle;
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
    private Row           $row;
    private Pdf           $pdf;
    private DocumentStyle $style;
    private float         $x;
    private float         $y;
    private float         $width;
    private string        $documentFont;

    public function __construct(Row $row, Pdf $pdf, float $x, float $y, float $width, string $documentFont, DocumentStyle $style)
    {
        $this->row          = $row;
        $this->pdf          = $pdf;
        $this->x            = $x;
        $this->y            = $y;
        $this->width        = $width;
        $this->documentFont = $documentFont;
        $this->style        = $style;
    }

    /** Render a large heading in this column — returns the parent Row. */
    public function h1(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->h1Size)
            ->setTextColor(...$s->h1Color)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight($s->h1Spacing);
        return $this->row;
    }

    /** Render a section heading in this column — returns the parent Row. */
    public function h2(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->h2Size)
            ->setTextColor(...$s->h2Color)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight($s->h2Spacing);
        return $this->row;
    }

    /** Render body text in this column — returns the parent Row. */
    public function p(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize)
            ->setTextColor(...$s->pColor)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight($s->pSpacing);
        return $this->row;
    }

    /** Render plain text in this column — returns the parent Row. */
    public function text(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize)
            ->setTextColor(0, 0, 0)
            ->text($this->x, $this->y, $text);

        $this->row->trackHeight($s->pSpacing);
        return $this->row;
    }
}
