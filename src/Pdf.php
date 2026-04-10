<?php

declare(strict_types=1);

namespace Arabel\Pdf;

class Pdf
{
    // ── Document state ──────────────────────────────────────────────────────
    private array  $pages      = [];
    private int    $pageCount  = 0;
    private int    $currentPage = 0;

    // ── PDF object tracking ──────────────────────────────────────────────────
    private array  $objects    = [];   // objectId => content string
    private int    $nextObjId  = 1;

    // ── Page defaults ────────────────────────────────────────────────────────
    private float  $pageWidth  = 595.28;  // A4 in points
    private float  $pageHeight = 841.89;

    // ── Current graphics state ───────────────────────────────────────────────
    private string $fontFamily = 'Helvetica';
    private float  $fontSize   = 12.0;
    private array  $textColor  = [0, 0, 0];      // RGB 0-255
    private array  $fillColor  = [255, 255, 255];
    private array  $drawColor  = [0, 0, 0];
    private float  $lineWidth  = 0.567;           // ~0.2mm

    // ── Margins ──────────────────────────────────────────────────────────────
    private float  $marginLeft   = 10.0;
    private float  $marginTop    = 10.0;
    private float  $marginRight  = 10.0;
    private float  $marginBottom = 10.0;

    // ── Cursor ───────────────────────────────────────────────────────────────
    private float  $x = 10.0;
    private float  $y = 10.0;

    // ── Built-in core font widths (Helvetica, units: 1/1000 of point) ────────
    private const HELVETICA_WIDTHS = [
        ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,"'"=>191,
        '('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
        '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,
        '8'=>556,'9'=>556,':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,
        '@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,
        'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,
        'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,
        'X'=>667,'Y'=>611,'Z'=>611,'['=>278,'\\'=>278,']'=>278,'^'=>469,'_'=>556,
        '`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,
        'h'=>556,'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,
        'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,
        'x'=>500,'y'=>500,'z'=>444,'{'=>334,'|'=>260,'}'=>334,'~'=>584,
    ];

    // ── Core fonts supported ─────────────────────────────────────────────────
    private const CORE_FONTS = ['Helvetica', 'Times-Roman', 'Courier'];

    // ────────────────────────────────────────────────────────────────────────
    // Public API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Add a new page and reset the cursor to the top-left margin.
     *
     * @param string $orientation 'P' portrait (default) | 'L' landscape
     */
    public function addPage(string $orientation = 'P'): static
    {
        $this->pageCount++;
        $this->currentPage = $this->pageCount;
        $this->pages[$this->currentPage] = '';

        // Reset cursor to top-left margin
        $this->x = $this->marginLeft;
        $this->y = $this->marginTop;

        return $this;
    }

    /**
     * Set the active font. Only core PDF fonts are supported at this stage.
     *
     * @param string $family 'Helvetica' | 'Times-Roman' | 'Courier'
     * @param float  $size   Font size in points (e.g. 12)
     * @param string $style  Reserved for future use ('B', 'I', 'BI')
     */
    public function setFont(string $family, float $size, string $style = ''): static
    {
        $this->fontFamily = $family;
        $this->fontSize   = $size;
        return $this;
    }

    /**
     * Set the text color using RGB values (0–255).
     *
     * @param int $r Red   (0–255)
     * @param int $g Green (0–255)
     * @param int $b Blue  (0–255)
     */
    public function setTextColor(int $r, int $g = 0, int $b = 0): static
    {
        $this->textColor = [$r, $g, $b];
        return $this;
    }

    /**
     * Set the fill color for cells and rectangles using RGB values (0–255).
     *
     * @param int $r Red   (0–255)
     * @param int $g Green (0–255)
     * @param int $b Blue  (0–255)
     */
    public function setFillColor(int $r, int $g = 0, int $b = 0): static
    {
        $this->fillColor = [$r, $g, $b];
        return $this;
    }

    /**
     * Set the stroke/border color for lines and rectangles using RGB values (0–255).
     *
     * @param int $r Red   (0–255)
     * @param int $g Green (0–255)
     * @param int $b Blue  (0–255)
     */
    public function setDrawColor(int $r, int $g = 0, int $b = 0): static
    {
        $this->drawColor = [$r, $g, $b];
        return $this;
    }

    /**
     * Set the line thickness for borders and drawn lines.
     *
     * @param float $width Line width in mm (default ~0.2mm)
     */
    public function setLineWidth(float $width): static
    {
        $this->lineWidth = $width;
        $this->writePage(sprintf("%.3f w\n", $width));
        return $this;
    }

    /**
     * Set the page margins. Right margin defaults to the same as left if omitted.
     *
     * @param float $left   Left margin in mm
     * @param float $top    Top margin in mm
     * @param float $right  Right margin in mm (defaults to $left)
     * @param float $bottom Bottom margin in mm
     */
    public function setMargins(float $left, float $top, float $right = -1, float $bottom = 10.0): static
    {
        $this->marginLeft   = $left;
        $this->marginTop    = $top;
        $this->marginRight  = $right < 0 ? $left : $right;
        $this->marginBottom = $bottom;
        return $this;
    }

    /**
     * Move the cursor to an absolute position on the page.
     *
     * @param float $x X coordinate in mm from the left edge
     * @param float $y Y coordinate in mm from the top edge
     */
    public function setXY(float $x, float $y): static
    {
        $this->x = $x;
        $this->y = $y;
        return $this;
    }

    /** Current cursor X position in mm. */
    public function getX(): float { return $this->x; }

    /** Current cursor Y position in mm. */
    public function getY(): float { return $this->y; }

    // ── Text at absolute position ─────────────────────────────────────────────

    /**
     * Print a string at an absolute position. Does not advance the cursor.
     * Use cell() for flow-based layout.
     *
     * @param float  $x    X coordinate in mm from the left edge
     * @param float  $y    Y coordinate in mm from the top edge
     * @param string $text UTF-8 string to render
     */
    public function text(float $x, float $y, string $text): static
    {
        $this->assertPageOpen();

        $pdfY  = $this->pageHeight - ($y * $this->scaleFactor()) - $this->fontSize;
        $pdfX  = $x * $this->scaleFactor();
        $color = $this->colorOp($this->textColor, 'rg');

        $escaped = $this->escapeText($text);

        $this->writePage(
            "BT\n" .
            "{$color}\n" .
            "/{$this->fontAlias()} {$this->fontSize} Tf\n" .
            "{$pdfX} {$pdfY} Td\n" .
            "({$escaped}) Tj\n" .
            "ET\n"
        );

        return $this;
    }

    // ── Cell ──────────────────────────────────────────────────────────────────

    /**
     * Print a rectangular cell, optionally with text, border, and fill.
     * Advances the cursor after rendering based on $ln.
     *
     * @param float      $w      Cell width in mm
     * @param float      $h      Cell height in mm
     * @param string     $text   UTF-8 text to print inside the cell
     * @param int|string $border 0 = no border, 1 = full border (partial borders coming in a future version)
     * @param int        $ln     Cursor advance: 0 = right, 1 = next line, 2 = below (same X)
     * @param string     $align  Text alignment: 'L' left | 'C' center | 'R' right
     */
    public function cell(
        float  $w,
        float  $h       = 0,
        string $text    = '',
        int|string $border = 0,
        int    $ln      = 0,
        string $align   = 'L'
    ): static {
        $this->assertPageOpen();

        $sf   = $this->scaleFactor();
        $x    = $this->x * $sf;
        $y    = $this->pageHeight - $this->y * $sf - $h * $sf;
        $w_pt = $w * $sf;
        $h_pt = $h * $sf;

        $stream = '';

        // Background fill
        if ($this->fillColor !== [255, 255, 255]) {
            $fc = $this->colorOp($this->fillColor, 'rg');
            $stream .= "{$fc}\n{$x} {$y} {$w_pt} {$h_pt} re f\n";
        }

        // Border
        if ($border) {
            $dc = $this->colorOp($this->drawColor, 'RG');
            $stream .= "{$dc}\n{$x} {$y} {$w_pt} {$h_pt} re S\n";
        }

        // Text
        if ($text !== '') {
            $color   = $this->colorOp($this->textColor, 'rg');
            $escaped = $this->escapeText($text);

            // Horizontal alignment
            $textW  = $this->getStringWidth($text);
            $textX  = match ($align) {
                'C'     => $this->x + ($w - $textW) / 2,
                'R'     => $this->x + $w - $textW - 1,
                default => $this->x + 1,
            };

            $pdfTX = $textX * $sf;
            $pdfTY = $y + ($h_pt - $this->fontSize) / 2;

            $stream .=
                "BT\n" .
                "{$color}\n" .
                "/{$this->fontAlias()} {$this->fontSize} Tf\n" .
                "{$pdfTX} {$pdfTY} Td\n" .
                "({$escaped}) Tj\n" .
                "ET\n";
        }

        $this->writePage($stream);

        // Advance cursor
        if ($ln === 1) {
            $this->x  = $this->marginLeft;
            $this->y += $h;
        } elseif ($ln === 2) {
            $this->x = $this->marginLeft;
        } else {
            $this->x += $w;
        }

        return $this;
    }

    // ── Shapes ────────────────────────────────────────────────────────────────

    /**
     * Draw a rectangle.
     *
     * @param float  $x     X coordinate of the top-left corner in mm
     * @param float  $y     Y coordinate of the top-left corner in mm
     * @param float  $w     Width in mm
     * @param float  $h     Height in mm
     * @param string $style '' = stroke only | 'F' = fill only | 'DF'/'FD' = fill + stroke
     */
    public function rect(float $x, float $y, float $w, float $h, string $style = ''): static
    {
        $this->assertPageOpen();

        $sf   = $this->scaleFactor();
        $pdfX = $x * $sf;
        $pdfY = $this->pageHeight - ($y * $sf) - ($h * $sf);
        $pdfW = $w * $sf;
        $pdfH = $h * $sf;

        $op = match (strtoupper($style)) {
            'F'  => 'f',
            'DF', 'FD' => 'B',
            default    => 'S',
        };

        $stream = '';
        if (str_contains('FDF', strtoupper($style))) {
            $fc = $this->colorOp($this->fillColor, 'rg');
            $stream .= "{$fc}\n";
        }
        $dc = $this->colorOp($this->drawColor, 'RG');
        $stream .= "{$dc}\n{$pdfX} {$pdfY} {$pdfW} {$pdfH} re {$op}\n";

        $this->writePage($stream);
        return $this;
    }

    /**
     * Draw a straight line between two points.
     *
     * @param float $x1 Start X in mm
     * @param float $y1 Start Y in mm
     * @param float $x2 End X in mm
     * @param float $y2 End Y in mm
     */
    public function line(float $x1, float $y1, float $x2, float $y2): static
    {
        $this->assertPageOpen();

        $sf = $this->scaleFactor();
        $dc = $this->colorOp($this->drawColor, 'RG');

        $this->writePage(sprintf(
            "%s\n%.3f %.3f m %.3f %.3f l S\n",
            $dc,
            $x1 * $sf, $this->pageHeight - $y1 * $sf,
            $x2 * $sf, $this->pageHeight - $y2 * $sf
        ));

        return $this;
    }

    // ── String width helper ───────────────────────────────────────────────────

    /**
     * Calculate the rendered width of a string with the current font and size.
     * Useful for manual alignment or before calling cell().
     *
     * @param  string $text UTF-8 string
     * @return float  Width in mm
     */
    public function getStringWidth(string $text): float
    {
        $w = 0;
        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $w += self::HELVETICA_WIDTHS[$text[$i]] ?? 556;
        }
        return $w * $this->fontSize / 1000;
    }

    // ── Output ────────────────────────────────────────────────────────────────

    /**
     * Finalize and output the PDF document.
     *
     * @param string $name Filename used for download or file save (e.g. 'invoice.pdf')
     * @param string $dest Output destination:
     *                     'D' = force download (default)
     *                     'I' = inline in browser
     *                     'F' = save to file (use $name as path)
     *                     'S' = return raw PDF string
     * @return string Raw PDF bytes (always returned regardless of $dest)
     */
    public function output(string $name = 'document.pdf', string $dest = 'D'): string
    {
        $pdf = $this->buildPdf();

        return match (strtoupper($dest)) {
            'S' => $pdf,
            'F' => (function () use ($pdf, $name) {
                file_put_contents($name, $pdf);
                return $pdf;
            })(),
            'I' => (function () use ($pdf, $name) {
                header('Content-Type: application/pdf');
                header("Content-Disposition: inline; filename=\"{$name}\"");
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                return $pdf;
            })(),
            default => (function () use ($pdf, $name) {  // 'D'
                header('Content-Type: application/pdf');
                header("Content-Disposition: attachment; filename=\"{$name}\"");
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                return $pdf;
            })(),
        };
    }

    // ────────────────────────────────────────────────────────────────────────
    // PDF generation internals
    // ────────────────────────────────────────────────────────────────────────

    private function buildPdf(): string
    {
        // Object 1 — Catalog
        $this->addObject(1, "<< /Type /Catalog /Pages 2 0 R >>");

        // Object 2 — Pages (placeholder, filled after we know page object IDs)
        $pageObjIds = [];
        $firstFreeId = 3;

        // Object 3 — Font
        $fontObjId = $firstFreeId++;
        $this->addObject($fontObjId,
            "<< /Type /Font /Subtype /Type1 /BaseFont /{$this->fontFamily} " .
            "/Encoding /WinAnsiEncoding >>"
        );

        // One object per page
        $contentObjIds = [];
        foreach ($this->pages as $pageNum => $stream) {
            $contentId = $firstFreeId++;
            $contentObjIds[$pageNum] = $contentId;

            $compressed = gzcompress($stream, 6);
            $len = strlen($compressed);
            $this->addObject($contentId,
                "<< /Filter /FlateDecode /Length {$len} >>\nstream\n" .
                $compressed .
                "\nendstream"
            );

            $pageId = $firstFreeId++;
            $pageObjIds[$pageNum] = $pageId;
            $this->addObject($pageId,
                "<< /Type /Page /Parent 2 0 R " .
                "/MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] " .
                "/Contents {$contentId} 0 R " .
                "/Resources << /Font << /F1 {$fontObjId} 0 R >> >> >>"
            );
        }

        // Object 2 — Pages dictionary (now we have IDs)
        $kids = implode(' ', array_map(fn($id) => "{$id} 0 R", $pageObjIds));
        $this->addObject(2,
            "<< /Type /Pages /Kids [{$kids}] /Count {$this->pageCount} >>"
        );

        // Serialize
        $out    = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        // Sort objects by ID
        ksort($this->objects);

        foreach ($this->objects as $id => $content) {
            $offsets[$id] = strlen($out);
            $out .= "{$id} 0 obj\n{$content}\nendobj\n";
        }

        // Cross-reference table
        $xrefOffset = strlen($out);
        $objCount   = count($this->objects) + 1;
        $out .= "xref\n0 {$objCount}\n";
        $out .= "0000000000 65535 f \n";

        ksort($offsets);
        foreach ($offsets as $offset) {
            $out .= str_pad((string)$offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $out .= "trailer\n<< /Size {$objCount} /Root 1 0 R >>\n";
        $out .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $out;
    }

    private function addObject(int $id, string $content): void
    {
        $this->objects[$id] = $content;
    }

    private function writePage(string $content): void
    {
        $this->pages[$this->currentPage] ??= '';
        $this->pages[$this->currentPage] .= $content;
    }

    private function assertPageOpen(): void
    {
        if ($this->currentPage === 0) {
            throw new \RuntimeException('No page open. Call addPage() first.');
        }
    }

    private function scaleFactor(): float
    {
        // User units = mm → points (1 mm = 2.8346 pt)
        return 2.8346;
    }

    private function fontAlias(): string
    {
        return 'F1';
    }

    private function colorOp(array $rgb, string $op): string
    {
        return sprintf('%.3f %.3f %.3f %s',
            $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $op
        );
    }

    private function escapeText(string $text): string
    {
        // PDF WinAnsiEncoding expects Windows-1252, not UTF-8
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
