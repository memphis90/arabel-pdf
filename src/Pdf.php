<?php

declare(strict_types=1);

namespace Arabel\Pdf;

use RuntimeException;

class Pdf
{
    // ── Document state ───────────────────────────────────────────────────────

    /** @var array<int, array{stream: string, w: float, h: float}> */
    private array $pages       = [];
    private int   $pageCount   = 0;
    private int   $currentPage = 0;

    // ── PDF objects ──────────────────────────────────────────────────────────

    /** @var array<int, string> */
    private array $objects = [];

    // ── Default page size (A4 portrait, in points) ───────────────────────────

    private float $defaultW = 595.28;
    private float $defaultH = 841.89;

    // ── Font registry ────────────────────────────────────────────────────────

    /**
     * Maps 'Family:STYLE' => ['alias' => 'F1', 'name' => 'Helvetica-Bold']
     * Populated lazily as setFont() is called.
     * @var array<string, array{alias: string, name: string}>
     */
    private array  $fonts           = ['Helvetica:' => ['alias' => 'F1', 'name' => 'Helvetica']];
    private int    $fontCount       = 1;
    private string $currentFontKey  = 'Helvetica:';

    // ── Graphics state ───────────────────────────────────────────────────────

    private string $fontFamily = 'Helvetica';
    private string $fontStyle  = '';
    private float  $fontSize   = 12.0;
    /** @var int[] */
    private array $textColor = [0, 0, 0];
    /** @var int[] */
    private array $fillColor = [255, 255, 255];
    /** @var int[] */
    private array $drawColor = [0, 0, 0];

    // ── Margins (mm) ─────────────────────────────────────────────────────────

    private float $marginLeft   = 10.0;
    private float $marginTop    = 10.0;
    private float $marginRight  = 10.0;
    private float $marginBottom = 10.0;

    // ── Cursor (mm) ──────────────────────────────────────────────────────────

    private float $x = 10.0;
    private float $y = 10.0;

    // ── Image registry ───────────────────────────────────────────────────────

    /**
     * @var array<string, array{
     *   alias: string, w: int, h: int, type: string,
     *   colorSpace: string, data: string,
     *   colors?: int
     * }>
     */
    private array $images     = [];
    private int   $imageCount = 0;

    // ── Constants ────────────────────────────────────────────────────────────

    private const MM_TO_PT = 2.8346;

    /** Helvetica glyph widths in 1/1000 pt units. */
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
        [$w, $h] = strtoupper($orientation) === 'L'
            ? [$this->defaultH, $this->defaultW]
            : [$this->defaultW, $this->defaultH];

        $this->pageCount++;
        $this->currentPage = $this->pageCount;
        $this->pages[$this->currentPage] = ['stream' => '', 'w' => $w, 'h' => $h];

        $this->x = $this->marginLeft;
        $this->y = $this->marginTop;

        return $this;
    }

    /**
     * Set the active font.
     *
     * Supported families: 'Helvetica', 'Times-Roman', 'Courier'
     * Supported styles:   '' normal | 'B' bold | 'I' italic | 'BI' bold+italic
     *
     * @param string $family 'Helvetica' | 'Times-Roman' | 'Courier'
     * @param float  $size   Font size in points (e.g. 12)
     * @param string $style  '' | 'B' | 'I' | 'BI'
     */
    public function setFont(string $family, float $size, string $style = ''): static
    {
        $style = strtoupper($style);
        $key   = $family . ':' . $style;

        if (!isset($this->fonts[$key])) {
            $this->fontCount++;
            $this->fonts[$key] = [
                'alias' => 'F' . $this->fontCount,
                'name'  => $this->resolveFontName($family, $style),
            ];
        }

        $this->fontFamily     = $family;
        $this->fontStyle      = $style;
        $this->fontSize       = $size;
        $this->currentFontKey = $key;

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
     * Set the fill color for cells and rectangles (0–255 per channel).
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
     * Set the stroke/border color for lines and rectangles (0–255 per channel).
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
        $this->writePage(sprintf("%.3f w\n", $width));
        return $this;
    }

    /**
     * Set page margins. Right margin defaults to left if omitted.
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

    /**
     * Get the current margin values in mm.
     *
     * @return array{left: float, top: float, right: float, bottom: float}
     */
    public function getMargins(): array
    {
        return [
            'left'   => $this->marginLeft,
            'top'    => $this->marginTop,
            'right'  => $this->marginRight,
            'bottom' => $this->marginBottom,
        ];
    }

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

        $pageH = $this->currentPageHeight();
        $pdfX  = $x * self::MM_TO_PT;
        $pdfY  = $pageH - ($y * self::MM_TO_PT) - $this->fontSize;
        $col   = $this->colorOp($this->textColor, 'rg');
        $esc   = $this->escapeText($text);
        $alias = $this->fonts[$this->currentFontKey]['alias'];

        $this->writePage(
            "BT\n$col\n/$alias $this->fontSize Tf\n$pdfX $pdfY Td\n($esc) Tj\nET\n"
        );

        return $this;
    }

    /**
     * Print a rectangular cell, optionally with text, border, and fill.
     * Advances the cursor after rendering based on $ln.
     *
     * @param float      $w      Cell width in mm
     * @param float      $h      Cell height in mm
     * @param string     $text   UTF-8 text to print inside the cell
     * @param int|string $border 0 = no border | 1 = full border
     * @param int        $ln     0 = advance right | 1 = next line | 2 = below
     * @param string     $align  Text alignment: 'L' left | 'C' center | 'R' right
     */
    public function cell(
        float      $w,
        float      $h      = 0,
        string     $text   = '',
        int|string $border = 0,
        int        $ln     = 0,
        string     $align  = 'L'
    ): static {
        $this->assertPageOpen();

        $pageH = $this->currentPageHeight();
        $sf    = self::MM_TO_PT;
        $px    = $this->x * $sf;
        $py    = $pageH - $this->y * $sf - $h * $sf;
        $pw    = $w * $sf;
        $ph    = $h * $sf;
        $out   = '';

        // Background fill
        if ($this->fillColor !== [255, 255, 255]) {
            $out .= $this->colorOp($this->fillColor, 'rg') . "\n$px $py $pw $ph re f\n";
        }

        // Border
        if ($border) {
            $out .= $this->colorOp($this->drawColor, 'RG') . "\n$px $py $pw $ph re S\n";
        }

        // Text
        if ($text !== '') {
            $textW = $this->getStringWidth($text);
            $tx    = match ($align) {
                'C'     => ($this->x + ($w - $textW) / 2) * $sf,
                'R'     => ($this->x + $w - $textW - 1) * $sf,
                default => ($this->x + 1) * $sf,
            };
            $ty    = $py + ($ph - $this->fontSize) / 2;
            $col   = $this->colorOp($this->textColor, 'rg');
            $esc   = $this->escapeText($text);
            $alias = $this->fonts[$this->currentFontKey]['alias'];

            $out .= "BT\n$col\n/$alias $this->fontSize Tf\n$tx $ty Td\n($esc) Tj\nET\n";
        }

        $this->writePage($out);

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

    /**
     * Draw a rectangle.
     *
     * @param float  $x     X of top-left corner in mm
     * @param float  $y     Y of top-left corner in mm
     * @param float  $w     Width in mm
     * @param float  $h     Height in mm
     * @param string $style '' = stroke only | 'F' = fill only | 'DF'/'FD' = fill + stroke
     */
    public function rect(float $x, float $y, float $w, float $h, string $style = ''): static
    {
        $this->assertPageOpen();

        $pageH = $this->currentPageHeight();
        $sf    = self::MM_TO_PT;
        $px    = $x * $sf;
        $py    = $pageH - ($y * $sf) - ($h * $sf);
        $pw    = $w * $sf;
        $ph    = $h * $sf;
        $op    = match (strtoupper($style)) {
            'F'        => 'f',
            'DF', 'FD' => 'B',
            default    => 'S',
        };

        $out = '';
        if (str_contains('FDF', strtoupper($style))) {
            $out .= $this->colorOp($this->fillColor, 'rg') . "\n";
        }
        $out .= $this->colorOp($this->drawColor, 'RG') . "\n$px $py $pw $ph re $op\n";

        $this->writePage($out);
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

        $pageH = $this->currentPageHeight();
        $sf    = self::MM_TO_PT;
        $col   = $this->colorOp($this->drawColor, 'RG');

        $this->writePage(sprintf(
            "$col\n%.3f %.3f m %.3f %.3f l S\n",
            $x1 * $sf, $pageH - $y1 * $sf,
            $x2 * $sf, $pageH - $y2 * $sf
        ));

        return $this;
    }

    /**
     * Embed an image (JPEG or PNG) at the given position.
     *
     * If only $w or only $h is provided, the other dimension is calculated
     * to preserve the aspect ratio. If both are 0, the image is placed at
     * its natural size at 72 dpi.
     *
     * Supported formats:
     * - JPEG: any color mode
     * - PNG: 8-bit RGB or Grayscale without alpha channel
     *
     * @param string $file Path to the image file (.jpg, .jpeg, .png)
     * @param float  $x    X coordinate in mm from the left edge
     * @param float  $y    Y coordinate in mm from the top edge
     * @param float  $w    Display width in mm  (0 = auto)
     * @param float  $h    Display height in mm (0 = auto)
     */
    public function image(string $file, float $x, float $y, float $w = 0.0, float $h = 0.0): static
    {
        $this->assertPageOpen();

        $key = realpath($file) ?: $file;

        if (!isset($this->images[$key])) {
            $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $info = match ($ext) {
                'jpg', 'jpeg' => $this->parseJpeg($file),
                'png'         => $this->parsePng($file),
                default       => throw new RuntimeException("Unsupported image format: $ext (use jpg or png)"),
            };
            $this->imageCount++;
            $info['alias']   = 'Im' . $this->imageCount;
            $this->images[$key] = $info;
        }

        $img  = $this->images[$key];
        $imgW = $img['w'];
        $imgH = $img['h'];

        // Resolve display dimensions, preserving aspect ratio when needed
        if ($w === 0.0 && $h === 0.0) {
            $w = $imgW * 25.4 / 72;  // pixels @ 72 dpi → mm
            $h = $imgH * 25.4 / 72;
        } elseif ($w === 0.0) {
            $w = $h * $imgW / $imgH;
        } elseif ($h === 0.0) {
            $h = $w * $imgH / $imgW;
        }

        $pageH = $this->currentPageHeight();
        $sf    = self::MM_TO_PT;
        $pdfX  = $x * $sf;
        $pdfY  = $pageH - ($y * $sf) - ($h * $sf);
        $pdfW  = $w * $sf;
        $pdfH  = $h * $sf;
        $alias = $img['alias'];

        $this->writePage("q $pdfW 0 0 $pdfH $pdfX $pdfY cm /$alias Do Q\n");

        return $this;
    }

    /**
     * Calculate the rendered width of a string with the current font and size.
     * Returns width in points. For mm use getStringWidthMm().
     *
     * @param  string $text UTF-8 string
     * @return float  Width in points
     */
    public function getStringWidth(string $text): float
    {
        $w = 0;
        for ($i = 0, $len = strlen($text); $i < $len; $i++) {
            $w += self::HELVETICA_WIDTHS[$text[$i]] ?? 556;
        }
        return $w * $this->fontSize / 1000;
    }

    /**
     * Calculate the height in mm that $text will occupy when wrapped inside $maxWidthMm.
     * The active font must be set before calling this.
     *
     * @param  float  $maxWidthMm   Available width in mm (cell width minus padding)
     * @param  float  $lineHeightMm Vertical advance per line in mm
     * @return float  Total height in mm (lines × lineHeightMm)
     */
    public function calcWrappedHeight(string $text, float $maxWidthMm, float $lineHeightMm): float
    {
        $lines        = $this->wrapText($text, $maxWidthMm - 2.0);  // 2mm total horizontal padding
        $fontHeightMm = $this->fontSize / self::MM_TO_PT;
        // Add one extra (lineH − fontH) as bottom padding so the last line
        // has the same breathing room as the top margin above the first line.
        return count($lines) * $lineHeightMm + ($lineHeightMm - $fontHeightMm);
    }

    /**
     * Render a cell with text that wraps to fit within the cell width.
     * The background, border, and all text lines are drawn at height $h.
     * Use calcWrappedHeight() first to determine the correct $h.
     *
     * @param float  $w             Cell width in mm
     * @param float  $h             Cell height in mm (pre-calculated, covers all lines)
     * @param string $text          UTF-8 text to render
     * @param int    $border        0 = no border | 1 = full border
     * @param float  $lineHeightMm  Vertical advance per line in mm
     * @param string $align         'L' left | 'C' center | 'R' right
     * @param int    $ln            0 = advance right | 1 = next line
     */
    public function multiCell(
        float  $w,
        float  $h,
        string $text,
        int    $border,
        float  $lineHeightMm,
        string $align = 'L',
        int    $ln    = 0
    ): static {
        $this->assertPageOpen();

        $pageH  = $this->currentPageHeight();
        $sf     = self::MM_TO_PT;
        $px     = $this->x * $sf;
        $py     = $pageH - $this->y * $sf - $h * $sf;
        $pw     = $w * $sf;
        $ph     = $h * $sf;
        $out    = '';

        // Background fill
        if ($this->fillColor !== [255, 255, 255]) {
            $out .= $this->colorOp($this->fillColor, 'rg') . "\n$px $py $pw $ph re f\n";
        }

        // Border
        if ($border) {
            $out .= $this->colorOp($this->drawColor, 'RG') . "\n$px $py $pw $ph re S\n";
        }

        // Wrapped text lines — each line vertically centered in its line slot
        if ($text !== '') {
            $lines   = $this->wrapText($text, $w - 2.0);
            $alias   = $this->fonts[$this->currentFontKey]['alias'];
            $col     = $this->colorOp($this->textColor, 'rg');
            $lineHPt = $lineHeightMm * $sf;

            foreach ($lines as $i => $line) {
                // Baseline: center of slot i from the top of the cell
                $ty  = $py + $ph - ($i + 0.5) * $lineHPt - $this->fontSize / 2;
                $tx  = match ($align) {
                    'C'     => ($this->x + ($w - $this->getStringWidthMm($line)) / 2) * $sf,
                    'R'     => ($this->x + $w - $this->getStringWidthMm($line) - 1.0) * $sf,
                    default => ($this->x + 1.0) * $sf,
                };
                $esc = $this->escapeText($line);
                $out .= "BT\n$col\n/$alias $this->fontSize Tf\n$tx $ty Td\n($esc) Tj\nET\n";
            }
        }

        $this->writePage($out);

        // Advance cursor
        if ($ln === 1) {
            $this->x  = $this->marginLeft;
            $this->y += $h;
        } else {
            $this->x += $w;
        }

        return $this;
    }

    /**
     * Render text wrapped to fit within $maxWidthMm, advancing Y by $lineHeightMm
     * per line. The active font and text color must be set before calling this.
     *
     * Long single words that exceed the width are placed on their own line
     * without mid-word breaking.
     *
     * @param  float  $x             X coordinate in mm
     * @param  float  $y             Y coordinate in mm (top of first line)
     * @param  float  $maxWidthMm    Maximum line width in mm
     * @param  string $text          UTF-8 text to render
     * @param  float  $lineHeightMm  Vertical advance per line in mm
     * @return float  Total height consumed (lines × lineHeightMm)
     */
    public function multiLine(float $x, float $y, float $maxWidthMm, string $text, float $lineHeightMm): float
    {
        $lines = $this->wrapText($text, $maxWidthMm);
        foreach ($lines as $i => $line) {
            $this->text($x, $y + $i * $lineHeightMm, $line);
        }
        return count($lines) * $lineHeightMm;
    }

    /**
     * Finalize and output the PDF document.
     *
     * @param string $name Filename used for download or file path when dest = 'F'
     * @param string $dest Output destination:
     *                     'D' = force browser download (default)
     *                     'I' = open inline in browser
     *                     'F' = save to file ($name = full path)
     *                     'S' = return raw PDF bytes
     * @return string Raw PDF bytes (always returned, regardless of $dest)
     */
    public function output(string $name = 'document.pdf', string $dest = 'D'): string
    {
        $pdf = $this->buildPdf();

        $dest = strtoupper($dest);

        if ($dest === 'S') {
            return $pdf;
        }

        if ($dest === 'F') {
            file_put_contents($name, $pdf);
            return $pdf;
        }

        // 'D' (download) or 'I' (inline)
        $disposition = $dest === 'I' ? 'inline' : 'attachment';
        header('Content-Type: application/pdf');
        header("Content-Disposition: $disposition; filename=\"$name\"");
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;

        return $pdf;
    }

    // ────────────────────────────────────────────────────────────────────────
    // PDF generation internals
    // ────────────────────────────────────────────────────────────────────────

    private function buildPdf(): string
    {
        $nextId = 1;

        // Reserve obj 1 (Catalog) and obj 2 (Pages)
        $catalogId = $nextId++;  // 1
        $pagesId   = $nextId++;  // 2

        // Font objects — one per registered variant
        $fontObjMap = [];  // alias => PDF object ID
        foreach ($this->fonts as $font) {
            $fontId = $nextId++;
            $fontObjMap[$font['alias']] = $fontId;
            $this->addObject($fontId,
                "<< /Type /Font /Subtype /Type1 /BaseFont /{$font['name']} /Encoding /WinAnsiEncoding >>"
            );
        }

        // Image XObjects
        $imageObjMap = [];  // alias => PDF object ID
        foreach ($this->images as $img) {
            $imgId = $nextId++;
            $imageObjMap[$img['alias']] = $imgId;
            $len   = strlen($img['data']);

            if ($img['type'] === 'jpeg') {
                $this->addObject($imgId,
                    "<< /Type /XObject /Subtype /Image " .
                    "/Width $img[w] /Height $img[h] " .
                    "/ColorSpace /$img[colorSpace] /BitsPerComponent 8 " .
                    "/Filter /DCTDecode /Length $len >>\n" .
                    "stream\n" . $img['data'] . "\nendstream"
                );
            } else {
                // PNG — reuse the already-deflated IDAT data with PNG predictor
                $colors = $img['colors'];
                $imgW   = $img['w'];
                $this->addObject($imgId,
                    "<< /Type /XObject /Subtype /Image " .
                    "/Width $img[w] /Height $img[h] " .
                    "/ColorSpace /$img[colorSpace] /BitsPerComponent 8 " .
                    "/Filter /FlateDecode " .
                    "/DecodeParms << /Predictor 15 /Colors $colors /BitsPerComponent 8 /Columns $imgW >> " .
                    "/Length $len >>\n" .
                    "stream\n" . $img['data'] . "\nendstream"
                );
            }
        }

        // Page content + page objects
        $pageObjIds = [];
        foreach ($this->pages as $pageNum => $page) {
            $contentId = $nextId++;

            $compressed = gzcompress($page['stream'], 6);
            $len        = strlen($compressed);
            $this->addObject($contentId,
                "<< /Filter /FlateDecode /Length $len >>\nstream\n" .
                $compressed . "\nendstream"
            );

            // Resources: fonts + image XObjects
            $fontParts = [];
            foreach ($fontObjMap as $alias => $oid) {
                $fontParts[] = "/$alias $oid 0 R";
            }
            $fontRes = '/Font << ' . implode(' ', $fontParts) . ' >>';
            $xobjRes  = '';
            if (!empty($imageObjMap)) {
                $parts = [];
                foreach ($imageObjMap as $alias => $oid) {
                    $parts[] = "/$alias $oid 0 R";
                }
                $xobjRes = ' /XObject << ' . implode(' ', $parts) . ' >>';
            }

            $pageId            = $nextId++;
            $pageObjIds[$pageNum] = $pageId;
            $pw = $page['w'];
            $ph = $page['h'];
            $this->addObject($pageId,
                "<< /Type /Page /Parent $pagesId 0 R " .
                "/MediaBox [0 0 $pw $ph] " .
                "/Contents $contentId 0 R " .
                "/Resources << $fontRes$xobjRes >> >>"
            );
        }

        // Pages dictionary
        $kids = implode(' ', array_map(fn ($id) => "$id 0 R", $pageObjIds));
        $this->addObject($pagesId, "<< /Type /Pages /Kids [$kids] /Count $this->pageCount >>");

        // Catalog
        $this->addObject($catalogId, "<< /Type /Catalog /Pages $pagesId 0 R >>");

        // ── Serialize ────────────────────────────────────────────────────────
        $out     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        ksort($this->objects);
        foreach ($this->objects as $id => $content) {
            $offsets[$id] = strlen($out);
            $out .= "$id 0 obj\n$content\nendobj\n";
        }

        // Cross-reference table
        $xrefOffset = strlen($out);
        $objCount   = count($this->objects) + 1;
        $out .= "xref\n0 $objCount\n";
        $out .= "0000000000 65535 f \n";

        ksort($offsets);
        foreach ($offsets as $offset) {
            $out .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $out .= "trailer\n<< /Size $objCount /Root $catalogId 0 R >>\n";
        $out .= "startxref\n$xrefOffset\n%%EOF\n";

        return $out;
    }

    private function addObject(int $id, string $content): void
    {
        $this->objects[$id] = $content;
    }

    private function writePage(string $content): void
    {
        $this->pages[$this->currentPage]['stream'] .= $content;
    }

    private function currentPageHeight(): float
    {
        return $this->pages[$this->currentPage]['h'];
    }

    private function assertPageOpen(): void
    {
        if ($this->currentPage === 0) {
            throw new RuntimeException('No page open. Call addPage() first.');
        }
    }

    private function resolveFontName(string $family, string $style): string
    {
        return match ($style) {
            'B'        => match ($family) {
                'Times-Roman' => 'Times-Bold',
                'Courier'     => 'Courier-Bold',
                default       => $family . '-Bold',
            },
            'I'        => match ($family) {
                'Times-Roman' => 'Times-Italic',
                'Courier'     => 'Courier-Oblique',
                default       => $family . '-Oblique',
            },
            'BI', 'IB' => match ($family) {
                'Times-Roman' => 'Times-BoldItalic',
                'Courier'     => 'Courier-BoldOblique',
                default       => $family . '-BoldOblique',
            },
            default    => $family,
        };
    }

    /**
     * Break $text into lines that fit within $maxWidthMm using the current font.
     *
     * @return string[]
     */
    private function wrapText(string $text, float $maxWidthMm): array
    {
        $words   = explode(' ', $text);
        $lines   = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($this->getStringWidthMm($candidate) <= $maxWidthMm) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    private function getStringWidthMm(string $text): float
    {
        return $this->getStringWidth($text) / self::MM_TO_PT;
    }

    private function colorOp(array $rgb, string $op): string
    {
        return sprintf('%.3f %.3f %.3f %s', $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255, $op);
    }

    private function escapeText(string $text): string
    {
        // PDF WinAnsiEncoding expects Windows-1252, not raw UTF-8 bytes
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    // ── Image parsers ────────────────────────────────────────────────────────

    /**
     * @return array{w: int, h: int, colorSpace: string, data: string, type: string}
     * @throws RuntimeException
     */
    private function parseJpeg(string $file): array
    {
        $data = file_get_contents($file);
        if ($data === false) {
            throw new RuntimeException("Cannot read file: $file");
        }

        $len = strlen($data);
        $i   = 2;

        while ($i + 4 < $len) {
            if (ord($data[$i]) !== 0xFF) {
                throw new RuntimeException("Invalid JPEG structure at offset $i in: $file");
            }

            $marker = (ord($data[$i]) << 8) | ord($data[$i + 1]);

            // SOF markers carry image dimensions: C0–C3, C5–C7, C9–CB
            if (($marker >= 0xFFC0 && $marker <= 0xFFC3) || ($marker >= 0xFFC5 && $marker <= 0xFFC7)) {
                $h        = (ord($data[$i + 5]) << 8) | ord($data[$i + 6]);
                $w        = (ord($data[$i + 7]) << 8) | ord($data[$i + 8]);
                $channels = ord($data[$i + 9]);
                $cs       = $channels === 1 ? 'DeviceGray' : 'DeviceRGB';

                return ['w' => $w, 'h' => $h, 'colorSpace' => $cs, 'data' => $data, 'type' => 'jpeg'];
            }

            $segLen = (ord($data[$i + 2]) << 8) | ord($data[$i + 3]);
            $i     += 2 + $segLen;
        }

        throw new RuntimeException("Could not find SOF marker in JPEG: $file");
    }

    /**
     * Supports 8-bit RGB (color type 2) and Grayscale (color type 0) without alpha.
     *
     * @return array{w: int, h: int, colors: int, colorSpace: string, data: string, type: string}
     * @throws RuntimeException
     */
    private function parsePng(string $file): array
    {
        $data = file_get_contents($file);
        if ($data === false) {
            throw new RuntimeException("Cannot read file: $file");
        }

        if (!str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
            throw new RuntimeException("Not a valid PNG file: $file");
        }

        $w         = (int) unpack('N', substr($data, 16, 4))[1];
        $h         = (int) unpack('N', substr($data, 20, 4))[1];
        $bitDepth  = ord($data[24]);
        $colorType = ord($data[25]);

        if ($bitDepth !== 8) {
            throw new RuntimeException("Only 8-bit PNG is supported (got " . $bitDepth . "-bit): $file");
        }

        [$colors, $colorSpace] = match ($colorType) {
            0 => [1, 'DeviceGray'],
            2 => [3, 'DeviceRGB'],
            default => throw new RuntimeException(
                "Unsupported PNG color type $colorType in: $file\n" .
                "Supported: 0 (Grayscale), 2 (RGB). Convert to RGB PNG without transparency."
            ),
        };

        // Concatenate all IDAT chunks — this IS the zlib/FlateDecode stream
        $idat = '';
        $pos  = 8;
        while ($pos + 12 <= strlen($data)) {
            $chunkLen  = (int) unpack('N', substr($data, $pos, 4))[1];
            $chunkType = substr($data, $pos + 4, 4);

            if ($chunkType === 'IDAT') {
                $idat .= substr($data, $pos + 8, $chunkLen);
            } elseif ($chunkType === 'IEND') {
                break;
            }

            $pos += 12 + $chunkLen;
        }

        return [
            'w'          => $w,
            'h'          => $h,
            'colors'     => $colors,
            'colorSpace' => $colorSpace,
            'data'       => $idat,
            'type'       => 'png',
        ];
    }
}
