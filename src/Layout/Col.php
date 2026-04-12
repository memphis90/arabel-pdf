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
            ->setFont($this->documentFont, $s->h1Size, $s->h1Style)
            ->setTextColor(...$s->h1Color);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->h1Spacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render a section heading in this column — returns the parent Row. */
    public function h2(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->h2Size, $s->h2Style)
            ->setTextColor(...$s->h2Color);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->h2Spacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render body text in this column — returns the parent Row. */
    public function p(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, $s->pStyle)
            ->setTextColor(...$s->pColor);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->pSpacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render bold text in this column — returns the parent Row. */
    public function b(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'B')
            ->setTextColor(...$s->pColor);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->pSpacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render italic text in this column — returns the parent Row. */
    public function i(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'I')
            ->setTextColor(...$s->pColor);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->pSpacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render bold+italic text in this column — returns the parent Row. */
    public function bi(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize, 'BI')
            ->setTextColor(...$s->pColor);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->pSpacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /** Render plain text in this column — returns the parent Row. */
    public function text(string $text): Row
    {
        $s = $this->style;
        $this->pdf
            ->setFont($this->documentFont, $s->pSize)
            ->setTextColor(0, 0, 0);

        $h = $this->pdf->multiLine($this->x, $this->y, $this->width, $text, $s->pSpacing);
        $this->row->trackHeight($h);
        return $this->row;
    }

    /**
     * Render an image filling the column width — returns the parent Row.
     *
     * The height is calculated automatically to preserve the aspect ratio.
     * Pass an explicit $h (in mm) to override.
     *
     * Supports JPEG and PNG (including alpha — alpha is flattened against white).
     *
     * @param string $file Path to the image file (.jpg, .jpeg, .png)
     * @param float  $h    Display height in mm (0 = auto from aspect ratio)
     */
    public function image(string $file, float $h = 0.0): Row
    {
        if ($h === 0.0) {
            [$imgW, $imgH] = $this->pdf->getImagePixelSize($file);
            $h = $imgH > 0 ? $this->width * ($imgH / $imgW) : $this->width;
        }
        $this->pdf->image($file, $this->x, $this->y, $this->width, $h);
        $this->row->trackHeight($h);
        return $this->row;
    }
}
