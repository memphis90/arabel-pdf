<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Pdf;

/**
 * A repeatable page footer registered on a Document.
 *
 * Footers are registered by name via Document::setFooter(). The 'default'
 * footer is applied automatically to every page before a new page starts
 * and on the final page at output() time.
 *
 * Use {page} in any text field to insert the current page number.
 *
 *   $doc->setFooter()
 *       ->left('Arabel Srl — P.IVA IT09876543210')
 *       ->right('Pagina {page}');
 *
 *   $doc->setFooter('allegato')
 *       ->center('ALLEGATO A  —  Pagina {page}');
 */
class Footer
{
    private string $leftText   = '';
    private string $centerText = '';
    private string $rightText  = '';

    /** @var int[] Text color */
    private array $fg = [150, 150, 150];

    private float $height = 8.0;

    // ── Configuration ─────────────────────────────────────────────────────────

    public function left(string $text): static
    {
        $this->leftText = $text;
        return $this;
    }

    public function center(string $text): static
    {
        $this->centerText = $text;
        return $this;
    }

    public function right(string $text): static
    {
        $this->rightText = $text;
        return $this;
    }

    /** @param int[] $color [r, g, b] */
    public function fg(array $color): static
    {
        $this->fg = $color;
        return $this;
    }

    /** Height reserved at page bottom in mm. */
    public function height(float $h): static
    {
        $this->height = $h;
        return $this;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Render this footer onto the current PDF page.
     *
     * @param  string $font    Active document font family
     * @param  float  $pageW   Page width in mm
     * @param  float  $pageH   Page height in mm
     * @param  float  $marginX Left/right margin in mm
     * @param  int    $page    Current page number (replaces {page})
     */
    public function render(Pdf $pdf, string $font, float $pageW, float $pageH, float $marginX, int $page): void
    {
        $y       = $pageH - $this->height;
        $size    = 8.0;
        $pad     = $marginX;
        $replace = fn(string $t) => str_replace('{page}', (string) $page, $t);

        $pdf->setFont($font, $size)->setTextColor(...$this->fg);

        if ($this->leftText !== '') {
            $pdf->text($pad, $y, $replace($this->leftText));
        }

        if ($this->centerText !== '') {
            $w = $pdf->getStringWidth($replace($this->centerText));
            $pdf->text(($pageW - $w) / 2, $y, $replace($this->centerText));
        }

        if ($this->rightText !== '') {
            $text = $replace($this->rightText);
            $w    = $pdf->getStringWidth($text);
            $pdf->text($pageW - $pad - $w, $y, $text);
        }
    }
}
