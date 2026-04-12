<?php

declare(strict_types=1);

namespace Arabel\Pdf;

use Arabel\Pdf\Layout\Footer;
use Arabel\Pdf\Layout\Header;
use Arabel\Pdf\Layout\Panel;
use Arabel\Pdf\Layout\Row;
use Arabel\Pdf\Layout\Table;

/**
 * High-level document API built on top of Pdf.
 *
 * Manages a semantic cursor (no mm knowledge required) and provides
 * grid-based layout via row()/col() and semantic elements: h1, h2, p, table.
 *
 * For pixel-precise control, access the underlying Pdf instance via raw().
 */
class Document
{
    private Pdf           $pdf;
    private DocumentStyle $style;

    private string $documentFont = 'Helvetica';

    private float $marginLeft   = 15.0;
    private float $marginTop    = 15.0;
    private float $marginRight  = 15.0;
    private float $marginBottom = 15.0;
    private float $pageW        = 210.0;
    private float $pageH        = 297.0; // A4 portrait height in mm

    /** Current Y cursor in mm from page top. */
    private float $cursorY = 15.0;

    /** Current page number (1-based). */
    private int $pageNumber = 0;

    /** Orientation of the current page — reused on automatic page breaks. */
    private string $currentOrientation = 'P';

    /** Header name used on the current page — reused on automatic page breaks. */
    private string|false $currentHeaderName = false;

    /** @var Header[] Registered headers keyed by name */
    private array $headers = [];

    /** @var Footer[] Registered footers keyed by name */
    private array $footers = [];

    public function __construct(string $font = '', ?DocumentStyle $style = null)
    {
        $this->documentFont = !empty($font) ? $font : $this->documentFont;
        $this->style        = $style ?? new DocumentStyle();
        $this->pdf          = new Pdf();
        $this->pdf->setMargins($this->marginLeft, $this->marginTop, $this->marginRight);
    }

    // ── Configuration ────────────────────────────────────────────────────────

    /**
     * Override default margins (15 mm on all sides).
     * Must be called before addPage().
     *
     * @param float $left   Left margin in mm
     * @param float $top    Top margin in mm
     * @param float $right  Right margin in mm
     * @param float $bottom Bottom margin in mm (used for page-break threshold)
     */
    public function setMargins(float $left, float $top, float $right, float $bottom = 15.0): static
    {
        $this->marginLeft   = $left;
        $this->marginTop    = $top;
        $this->marginRight  = $right;
        $this->marginBottom = $bottom;
        $this->cursorY      = $top;
        $this->pdf->setMargins($left, $top, $right);
        return $this;
    }

    // ── Page ─────────────────────────────────────────────────────────────────

    /**
     * Add a new page and reset the cursor below the header (if any).
     *
     * @param string       $orientation 'P' portrait (default) | 'L' landscape
     * @param string|false $header      Header name to apply, false = none.
     *                                  Defaults to 'default' if registered.
     */
    public function addPage(string $orientation = 'P', string|false|null $header = null): static
    {
        // Render footer on the current page before opening a new one
        if ($this->pageNumber > 0) {
            $this->renderFooter('default');
        }

        $this->pdf->addPage($orientation);
        $this->pageNumber++;

        $this->currentOrientation = strtoupper($orientation);
        $this->pageW = $this->currentOrientation === 'L' ? 297.0 : 210.0;
        $this->pageH = $this->currentOrientation === 'L' ? 210.0 : 297.0;

        // Determine which header to apply
        $headerName = $header;
        if ($headerName === null) {
            $headerName = isset($this->headers['default']) ? 'default' : false;
        }

        if ($headerName !== false && isset($this->headers[$headerName])) {
            $consumed      = $this->headers[$headerName]->render($this->pdf, $this->documentFont, $this->pageW, $this->marginLeft);
            $this->cursorY = $consumed + $this->marginTop;
            $this->currentHeaderName = $headerName;
        } else {
            $this->cursorY           = $this->marginTop;
            $this->currentHeaderName = false;
        }

        return $this;
    }

    // ── Header / Footer registration ─────────────────────────────────────────

    /**
     * Register a page header by name.
     *
     * The 'default' header is applied automatically to every addPage() call.
     * Named headers are applied explicitly: $doc->addPage('P', 'allegato').
     *
     * Multiple headers can be registered:
     *
     *   $doc->setHeader()                           // name = 'default'
     *       ->bg([15, 55, 120])
     *       ->fg([255, 255, 255])
     *       ->left('ARABEL SRL', 'Software & Digital Products')
     *       ->right('FATTURA', '# INV-2026-0042')
     *       ->height(22);
     *
     *   $doc->setHeader('allegato')
     *       ->bg([15, 55, 120])
     *       ->fg([255, 255, 255])
     *       ->left('ALLEGATO A', 'Fattura INV-2026-0042');
     *
     *   $doc->addPage();                // → 'default' header
     *   $doc->addPage('P', 'allegato'); // → 'allegato' header
     *   $doc->addPage('P', false);      // → no header
     */
    public function setHeader(string $name = 'default'): Header
    {
        $header = new Header();
        $this->headers[$name] = $header;
        return $header;
    }

    /**
     * Register a page footer by name.
     *
     * The 'default' footer is rendered automatically on every page.
     * Use {page} in any text field to insert the current page number.
     *
     *   $doc->setFooter()
     *       ->left('Arabel Srl — P.IVA IT09876543210')
     *       ->right('Pagina {page}');
     *
     *   $doc->setFooter('allegato')
     *       ->center('ALLEGATO A  —  Pagina {page}');
     */
    public function setFooter(string $name = 'default'): Footer
    {
        $footer = new Footer();
        $this->footers[$name] = $footer;
        return $footer;
    }

    // ── Typography ───────────────────────────────────────────────────────────

    /** Large heading. */
    public function h1(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->h1Size, $s->h1Style);
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->h1Spacing));
        $this->pdf->setTextColor(...$s->h1Color);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->h1Spacing);
        return $this;
    }

    /** Section heading. */
    public function h2(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->h2Size, $s->h2Style);
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->h2Spacing));
        $this->pdf->setTextColor(...$s->h2Color);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->h2Spacing);
        return $this;
    }

    /** Body paragraph. */
    public function p(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->pSize, $s->pStyle);
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->pSpacing));
        $this->pdf->setTextColor(...$s->pColor);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Bold text — same size and color as p(). */
    public function b(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->pSize, 'B');
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->pSpacing));
        $this->pdf->setTextColor(...$s->pColor);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Italic text — same size and color as p(). */
    public function i(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->pSize, 'I');
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->pSpacing));
        $this->pdf->setTextColor(...$s->pColor);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Bold + italic text — same size and color as p(). */
    public function bi(string $text): static
    {
        $s = $this->style;
        $this->pdf->setFont($this->documentFont, $s->pSize, 'BI');
        $this->checkPageBreak($this->pdf->calcWrappedHeight($text, $this->contentWidth(), $s->pSpacing));
        $this->pdf->setTextColor(...$s->pColor);
        $this->cursorY += $this->pdf->multiLine($this->marginLeft, $this->cursorY, $this->contentWidth(), $text, $s->pSpacing);
        return $this;
    }

    /** Horizontal rule — thin line across the full content width. */
    public function hr(): static
    {
        $s = $this->style;
        $this->checkPageBreak($s->hrSpacing);
        $this->pdf
            ->setDrawColor(...$s->hrColor)
            ->setLineWidth(0.2)
            ->line($this->marginLeft, $this->cursorY, $this->marginLeft + $this->contentWidth(), $this->cursorY);
        $this->cursorY += $s->hrSpacing;
        return $this;
    }

    /** Vertical blank space. */
    public function spacer(float $height = 6.0): static
    {
        $this->checkPageBreak($height);
        $this->cursorY += $height;
        return $this;
    }

    // ── Grid layout ──────────────────────────────────────────────────────────

    /**
     * Start a 12-column grid row.
     * Call col() on the returned Row, then endRow() to return here.
     */
    public function row(): Row
    {
        return new Row($this, $this->pdf, $this->cursorY, $this->contentWidth(), $this->marginLeft, $this->documentFont, $this->style);
    }

    // ── Table ────────────────────────────────────────────────────────────────

    /**
     * Start a table with the given column headers.
     * Call tr() for each data row, then endTable() to return here.
     */
    public function table(array $headers): Table
    {
        return new Table($this, $this->pdf, $this->cursorY, $this->contentWidth(), $this->marginLeft, $headers, $this->documentFont, $this->style);
    }

    // ── Panel ────────────────────────────────────────────────────────────────

    /**
     * Start a colored, padded content block.
     * Configure with bg(), fg(), padding(), add content, then endPanel().
     */
    public function panel(): Panel
    {
        return new Panel($this, $this->pdf, $this->cursorY, $this->contentWidth(), $this->marginLeft, $this->documentFont, $this->style);
    }

    // ── Output ───────────────────────────────────────────────────────────────

    /**
     * Finalize and output the document.
     *
     * @param string $name Filename or path
     * @param string $dest 'D' download | 'I' inline | 'F' file | 'S' string
     * @return string Raw PDF bytes
     */
    public function output(string $name = 'document.pdf', string $dest = 'D'): string
    {
        // Render footer on the last page
        if ($this->pageNumber > 0) {
            $this->renderFooter('default');
        }

        return $this->pdf->output($name, $dest);
    }

    /**
     * Access the underlying Pdf instance for advanced/precise operations.
     * Use sparingly — prefer Document methods when possible.
     */
    public function raw(): Pdf
    {
        return $this->pdf;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /** Called by Row, Table, and Panel when they finish rendering. */
    public function advanceCursor(float $newY): void
    {
        $this->cursorY = $newY;
    }

    public function getCursorY(): float
    {
        return $this->cursorY;
    }

    public function getMarginLeft(): float
    {
        return $this->marginLeft;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    /**
     * X coordinate in mm where column $startSpan begins (0-based span offset).
     *
     * @param int $startSpan Number of column units from the left margin (0–12)
     */
    public function colX(int $startSpan): float
    {
        return $this->marginLeft + $startSpan * ($this->contentWidth() / 12);
    }

    /**
     * Width in mm of $span column units.
     *
     * @param int $span Number of column units (1–12)
     */
    public function colW(int $span): float
    {
        return $span * ($this->contentWidth() / 12);
    }

    private function contentWidth(): float
    {
        return $this->pageW - $this->marginLeft - $this->marginRight;
    }

    /**
     * Bottom Y limit of the safe content area.
     * Accounts for bottom margin and footer height if a default footer is set.
     */
    private function contentBottom(): float
    {
        $footerH = isset($this->footers['default'])
            ? $this->footers['default']->getHeight() + 4.0  // 4mm gap above footer
            : 0.0;

        return $this->pageH - $this->marginBottom - $footerH;
    }

    /**
     * Trigger an automatic page break if $neededHeight would overflow
     * the safe content area. Reuses the current orientation and header.
     */
    private function checkPageBreak(float $neededHeight): void
    {
        if ($this->pageNumber > 0 && $this->cursorY + $neededHeight > $this->contentBottom()) {
            $this->addPage($this->currentOrientation, $this->currentHeaderName);
        }
    }

    private function renderFooter(string $name): void
    {
        if (!isset($this->footers[$name])) {
            return;
        }
        $this->footers[$name]->render(
            $this->pdf,
            $this->documentFont,
            $this->pageW,
            $this->pageH,
            $this->marginLeft,
            $this->pageNumber
        );
    }
}
