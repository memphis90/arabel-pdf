<?php

declare(strict_types=1);

namespace Arabel\Pdf\Layout;

use Arabel\Pdf\Document;
use Arabel\Pdf\DocumentStyle;
use Arabel\Pdf\Pdf;

/**
 * A colored, padded content block inside a Document.
 *
 * Returned by Document::panel(). All content methods are buffered and
 * flushed at endPanel(), so bg(), fg(), and padding() can be called
 * in any order relative to h1/h2/p/etc.
 *
 * Example:
 *   ->panel()
 *       ->bg([15, 55, 120])
 *       ->fg([255, 255, 255])
 *       ->padding(5)
 *       ->h2('TOTALE FATTURA:')
 *       ->b('€ 13.344,36')
 *   ->endPanel()
 */
class Panel
{
    private Document      $doc;
    private Pdf           $pdf;
    private DocumentStyle $style;
    private string        $font;
    private float         $startY;
    private float         $marginLeft;
    private float         $contentWidth;

    /** @var int[] Background fill color */
    private array $bg = [240, 242, 255];

    /** @var int[]|null Text color override — null = use each element's own default */
    private ?array $fg = null;

    private float $padding = 4.0;

    /** @var array{0: string, 1: mixed}[] Buffered operations */
    private array $buffer = [];

    public function __construct(
        Document      $doc,
        Pdf           $pdf,
        float         $startY,
        float         $contentWidth,
        float         $marginLeft,
        string        $font,
        DocumentStyle $style
    ) {
        $this->doc          = $doc;
        $this->pdf          = $pdf;
        $this->startY       = $startY;
        $this->contentWidth = $contentWidth;
        $this->marginLeft   = $marginLeft;
        $this->font         = $font;
        $this->style        = $style;
    }

    // ── Configuration ─────────────────────────────────────────────────────────

    /** @param int[] $color [r, g, b] */
    public function bg(array $color): static
    {
        $this->bg = $color;
        return $this;
    }

    /**
     * Override text color for every element inside this panel.
     * Useful for dark backgrounds where the default colors are unreadable.
     *
     * @param int[] $color [r, g, b]
     */
    public function fg(array $color): static
    {
        $this->fg = $color;
        return $this;
    }

    /** Inner padding in mm (applied on all four sides). */
    public function padding(float $p): static
    {
        $this->padding = $p;
        return $this;
    }

    // ── Content ───────────────────────────────────────────────────────────────

    public function h1(string $text): static  { $this->buffer[] = ['h1', $text];    return $this; }
    public function h2(string $text): static  { $this->buffer[] = ['h2', $text];    return $this; }
    public function p(string $text): static   { $this->buffer[] = ['p',  $text];    return $this; }
    public function b(string $text): static   { $this->buffer[] = ['b',  $text];    return $this; }
    public function i(string $text): static   { $this->buffer[] = ['i',  $text];    return $this; }
    public function bi(string $text): static  { $this->buffer[] = ['bi', $text];    return $this; }
    public function spacer(float $h = 6.0): static { $this->buffer[] = ['spacer', $h]; return $this; }
    public function hr(): static              { $this->buffer[] = ['hr', null];     return $this; }

    // ── Flush ─────────────────────────────────────────────────────────────────

    /**
     * Flush all buffered content to the PDF and return to the Document.
     *
     * Rendering order:
     *   1. Measure total content height
     *   2. Draw filled background rect
     *   3. Render text on top of the rect
     */
    public function endPanel(): Document
    {
        $s      = $this->style;
        $innerW = $this->contentWidth - $this->padding * 2;
        $innerX = $this->marginLeft + $this->padding;

        // 1. Measure total height (font must be set per item for accurate wrap)
        $contentH = 0.0;
        foreach ($this->buffer as [$type, $arg]) {
            $contentH += $this->measureItem($type, $arg, $innerW);
        }
        $totalH = $contentH + $this->padding * 2;

        // 2. Draw background rect first — text will render on top in PDF stream
        $this->pdf
            ->setFillColor(...$this->bg)
            ->rect($this->marginLeft, $this->startY, $this->contentWidth, $totalH, 'F');

        // 3. Render buffered items on top of the rect
        $curY = $this->startY + $this->padding;
        foreach ($this->buffer as [$type, $arg]) {
            $curY += $this->renderItem($type, $arg, $innerX, $curY, $innerW);
        }

        // 4. Advance Document cursor past the panel (+ standard paragraph gap)
        $this->doc->advanceCursor($this->startY + $totalH + $s->pSpacing);
        return $this->doc;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function measureItem(string $type, mixed $arg, float $innerW): float
    {
        $s = $this->style;

        return match ($type) {
            'h1'    => $this->measureText($s->h1Size, $s->h1Style,  (string) $arg, $innerW, $s->h1Spacing),
            'h2'    => $this->measureText($s->h2Size, $s->h2Style,  (string) $arg, $innerW, $s->h2Spacing),
            'p'     => $this->measureText($s->pSize,  $s->pStyle,   (string) $arg, $innerW, $s->pSpacing),
            'b'     => $this->measureText($s->pSize,  'B',          (string) $arg, $innerW, $s->pSpacing),
            'i'     => $this->measureText($s->pSize,  'I',          (string) $arg, $innerW, $s->pSpacing),
            'bi'    => $this->measureText($s->pSize,  'BI',         (string) $arg, $innerW, $s->pSpacing),
            'spacer' => (float) $arg,
            'hr'    => $s->hrSpacing,
            default => 0.0,
        };
    }

    private function measureText(float $size, string $style, string $text, float $w, float $spacing): float
    {
        $this->pdf->setFont($this->font, $size, $style);
        return $this->pdf->calcWrappedHeight($text, $w, $spacing);
    }

    private function renderItem(string $type, mixed $arg, float $x, float $y, float $innerW): float
    {
        $s   = $this->style;
        $pdf = $this->pdf;

        // Set font + color for this element
        match ($type) {
            'h1' => $pdf->setFont($this->font, $s->h1Size, $s->h1Style)->setTextColor(...($this->fg ?? $s->h1Color)),
            'h2' => $pdf->setFont($this->font, $s->h2Size, $s->h2Style)->setTextColor(...($this->fg ?? $s->h2Color)),
            'p'  => $pdf->setFont($this->font, $s->pSize,  $s->pStyle) ->setTextColor(...($this->fg ?? $s->pColor)),
            'b'  => $pdf->setFont($this->font, $s->pSize,  'B')        ->setTextColor(...($this->fg ?? $s->pColor)),
            'i'  => $pdf->setFont($this->font, $s->pSize,  'I')        ->setTextColor(...($this->fg ?? $s->pColor)),
            'bi' => $pdf->setFont($this->font, $s->pSize,  'BI')       ->setTextColor(...($this->fg ?? $s->pColor)),
            default => null,
        };

        // Render and return height consumed
        return match ($type) {
            'h1'    => $pdf->multiLine($x, $y, $innerW, (string) $arg, $s->h1Spacing),
            'h2'    => $pdf->multiLine($x, $y, $innerW, (string) $arg, $s->h2Spacing),
            'p', 'b', 'i', 'bi' => $pdf->multiLine($x, $y, $innerW, (string) $arg, $s->pSpacing),
            'spacer' => (float) $arg,
            'hr' => (function () use ($pdf, $s, $x, $y, $innerW): float {
                $pdf->setDrawColor(...$s->hrColor)->setLineWidth(0.2)->line($x, $y, $x + $innerW, $y);
                return $s->hrSpacing;
            })(),
            default => 0.0,
        };
    }
}
