# arabel/pdf

**Lightweight · Zero-Dependency · Fast** — PDF generation for PHP 8.1+

![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)
![Downloads](https://img.shields.io/packagist/dt/arabel/pdf)
![License](https://img.shields.io/packagist/l/arabel/pdf)

Generate PDFs with a **fluent, semantic API** — no mPDF, no dompdf, no TCPDF bloat.  
Two layers: a high-level **Document API** for reports and invoices, and a low-level **Pdf API** for pixel-perfect control.

> **Work in progress.** The API is functional and tested, but breaking changes may occur before v1.0.

---

## Why arabel/pdf?

Most PHP PDF libraries are either **too heavy** (mPDF, TCPDF ship megabytes of dependencies) or **too low-level** (raw PDF forces you to think in millimetres for everything).

`arabel/pdf` gives you the best of both worlds:

| | arabel/pdf | mPDF | dompdf | TCPDF |
|---|:---:|:---:|:---:|:---:|
| Dependencies | **0** | Many | Many | Some |
| Speed | ⭐⭐⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| Fluent API | ✅ | Partial | ✗ | ✗ |
| PHP 8.1+ native | ✅ | Partial | Partial | ✗ |
| Package size | **~100 KB** | > 10 MB | Large | Large |

*Based on community benchmarks and package sizes. Formal benchmarks coming in v1.0.*

---

## Installation

```bash
composer require arabel/pdf
```

**Requirements:** PHP 8.1+ · Extensions `zlib` and `iconv` (both enabled by default in most environments) · `GD` for PNG images with alpha channel

---

## Two layers

```
┌──────────────────────────────────────────┐
│  Document  — high-level, recommended     │
│  row / col / h1 / h2 / p / table...      │
└──────────────────┬───────────────────────┘
                   │ delegates to
┌──────────────────▼───────────────────────┐
│  Pdf  — low-level, mm-precise            │
│  cell / text / rect / line / image...    │
└──────────────────────────────────────────┘
```

Start with **Document** — no millimetres, no cursor math.  
Drop down to **Pdf** via `$doc->raw()` when you need exact positioning.

---

## Document API

### Basic example

```php
use Arabel\Pdf\Document;
use Arabel\Pdf\DocumentStyle;

$style = new DocumentStyle();
$style->h1(22, [15, 55, 120], 'B', 12)
      ->h2(12, [15, 55, 120], 'B', 8)
      ->p(9,  [60, 60, 60],   '',  5.5);

$doc = new Document('Helvetica', $style);

$doc->addPage()
    ->h1('Monthly Report')
    ->h2('Sales — April 2026')
    ->hr()
    ->spacer()
    ->p('Summary of current month sales compared to the previous period.')
    ->output('report.pdf', 'F');
```

### Named headers and footers

Define repeatable headers and footers once — they are applied automatically on every `addPage()`.  
Multiple named variants let you use different headers for different sections.

```php
// Default header — applied to every addPage()
$doc->setHeader()
    ->bg([15, 55, 120])
    ->fg([255, 255, 255])
    ->left('ARABEL SRL', 'Software & Digital Products')
    ->right('FATTURA', '# INV-2026-0042')
    ->height(22);

// Named header — applied explicitly
$doc->setHeader('allegato')
    ->bg([15, 55, 120])
    ->fg([255, 255, 255])
    ->left('ALLEGATO A — Dettaglio attività', 'Fattura INV-2026-0042');

// Footer with page number
$doc->setFooter()
    ->left('Arabel Srl — P.IVA IT09876543210')
    ->right('Pagina {page}');

$doc->addPage();                // → 'default' header + footer
$doc->addPage('P', 'allegato'); // → 'allegato' header + footer
$doc->addPage('P', false);      // → no header, footer only
```

### Automatic page break

Content never overlaps the footer. Before rendering each element, the Document measures
its height and triggers a new page automatically if it would exceed the safe area.

### Grid layout — row / col

The page is divided into a **12-column grid**. Use `col($span)` to define how many
columns a block occupies (1–12).

```php
$doc->addPage()
    ->h1('Dashboard')
    ->spacer()

    ->row()
        ->col(8)->p('Left side — takes 8 of 12 columns.')
        ->col(4)->h2('€ 24,500')
    ->endRow()

    ->row()
        ->col(4)->h2('142 PDFs')
        ->col(4)->h2('38 upgrades')
        ->col(4)->h2('94% satisfaction')
    ->endRow()
    ->row()
        ->col(4)->p('generated this month')
        ->col(4)->p('Free → Pro conversions')
        ->col(4)->p('satisfaction index')
    ->endRow()

    ->output('dashboard.pdf', 'F');
```

### Images in columns

Use `col()->image()` to embed a JPEG or PNG directly inside the grid.  
Height is auto-calculated from the image's aspect ratio — pass an explicit `$h` (mm) to override.  
PNG files with alpha channel are supported: the alpha layer is composited against white.

```php
$doc->row()
    ->col(3)->image('logo.png')        // auto height from aspect ratio
    ->col(3)->image('badge.png', 20)   // forced 20 mm height
    ->col(6)->h1('Company Name')
->endRow();
```

### Tables

```php
$doc->addPage()
    ->h1('Product List')
    ->spacer()

    ->table(['Product', 'Category', 'Qty', 'Revenue'])
        ->widths([3, 2, 1, 1])
        ->align(['L', 'L', 'C', 'R'])
        ->tr(['Arabel PDF',     'Library', '142', '€ 0'])
        ->tr(['Arabel Builder', 'Tool',      '38', '€ 1,862'])
        ->tr(['Arabel Suite',   'Bundle',    '12', '€ 2,388'])
    ->endTable()

    ->output('products.pdf', 'F');
```

`widths()`, `align()`, and `tr()` can be called in **any order** — rendering is deferred to `endTable()`.

**Colspan** — merge cells across columns:

```php
->table(['Descrizione', 'Qty', 'Prezzo', 'IVA', 'Totale'])
    ->tr(['Arabel PDF', '1', '€ 49,00', '22%', '€ 59,78'])
    ->tr([
        ['text' => 'Subtotale:', 'colspan' => 4, 'align' => 'R'],
        ['text' => '€ 59,78',                    'align' => 'R'],
    ])
->endTable()
```

### Colored panels

```php
$doc->panel()
        ->bg([15, 55, 120])
        ->fg([255, 255, 255])
        ->padding(5)
        ->h2('TOTALE FATTURA:')
        ->b('€ 13.344,36')
    ->endPanel();
```

Background is measured and drawn before text — no coordinate math required.

### DocumentStyle

```php
$style = new DocumentStyle();

// Fluent configurators (recommended)
$style->h1(22, [15, 55, 120], 'B', 12)
      ->h2(12, [15, 55, 120], 'B', 8)
      ->p(9,  [60, 60, 60],   '',  5.5);

// Table style
$style->tableHeadBg = [15, 55, 120];
$style->tableHeadFg = [255, 255, 255];
$style->tableAltBg  = [235, 241, 255];
$style->tableRowH   = 7.0;
$style->tableLineH  = 5.0;
```

### Document methods reference

| Method | Description |
|--------|-------------|
| `setMargins(l, t, r, b)` | Override default 15 mm margins — call before `addPage()` |
| `addPage('P'\|'L', $header)` | New page — portrait or landscape, optional named header |
| `setHeader(string $name = 'default')` | Register a named header → `Header` |
| `setFooter(string $name = 'default')` | Register a named footer → `Footer` |
| `h1(string)` | Large heading |
| `h2(string)` | Section heading |
| `p(string)` | Body paragraph |
| `b(string)` | Bold paragraph |
| `i(string)` | Italic paragraph |
| `bi(string)` | Bold + italic paragraph |
| `hr()` | Horizontal rule |
| `spacer(float $mm = 6)` | Vertical blank space |
| `row()` | Open a 12-column grid row → `Row` |
| `col(int)->image(file, h)` | Embed image in a column — auto height from aspect ratio |
| `table(array $headers)` | Open a table → `Table` |
| `panel()` | Open a colored content block → `Panel` |
| `output(string, string)` | Finalize and output the PDF |
| `raw()` | Access the underlying `Pdf` instance |
| `getCursorY()` | Current Y position in mm |
| `colX(int $startSpan)` | X coordinate of a grid column — use with `raw()` |
| `colW(int $span)` | Width of N grid columns — use with `raw()` |

---

## Pdf API

Use `Pdf` directly when you need pixel-precise control.

```php
use Arabel\Pdf\Pdf;

$pdf = new Pdf();

$pdf->addPage()
    ->setFont('Helvetica', 14)
    ->setTextColor(41, 98, 255)
    ->text(20, 30, 'Hello from Pdf')
    ->setFillColor(240, 240, 240)
    ->cell(100, 10, 'Cell with border', 1, 1, 'C')
    ->setDrawColor(180, 0, 0)
    ->line(10, 60, 200, 60)
    ->image('logo.png', 10, 70, 40)
    ->output('output.pdf', 'F');
```

All mutating methods return `static` — the full API is fluent.

### Pdf methods reference

| Method | Description |
|--------|-------------|
| `addPage('P'\|'L')` | New page |
| `setFont(family, size, style)` | Set font family, size in pt, style ('B', 'I', 'BI') |
| `setTextColor(r, g, b)` | Text colour (0–255 per channel) |
| `setFillColor(r, g, b)` | Fill colour for cells and rects |
| `setDrawColor(r, g, b)` | Stroke colour for borders and lines |
| `setLineWidth(float)` | Line thickness in mm |
| `setMargins(l, t, r, b)` | Page margins in mm |
| `setXY(x, y)` | Move cursor to absolute position (mm) |
| `getX() / getY()` | Current cursor position in mm |
| `getStringWidth(string)` | Measure string width in mm |
| `text(x, y, string)` | Print text at absolute position |
| `cell(w, h, text, border, ln, align)` | Render a cell, advance cursor |
| `rect(x, y, w, h, style)` | Draw a rectangle |
| `line(x1, y1, x2, y2)` | Draw a line |
| `image(file, x, y, w, h)` | Embed a JPEG or PNG image |
| `output(name, dest)` | Finalize and output the PDF |

### `output()` destinations

| Dest | Behaviour |
|------|-----------|
| `'D'` | Force browser download (default) |
| `'I'` | Open inline in browser |
| `'F'` | Save to file (`$name` = full path) |
| `'S'` | Return raw PDF bytes as string |

---

## Mixing both layers

```php
$doc = new Document();

$doc->addPage()
    ->h1('Invoice #1042');

// Drop to Pdf for a precise watermark
$doc->raw()
    ->setFont('Helvetica', 60)
    ->setTextColor(230, 230, 230)
    ->text(25, 120, 'DRAFT');

// Back to Document
$doc->spacer()
    ->table(['Description', 'Amount'])
        ->tr(['Consulting', '€ 800'])
    ->endTable()
    ->output('invoice.pdf', 'F');
```

---

## Roadmap to v1.0

- [x] Fluent Document API (row/col grid, tables, headings)
- [x] Bold / italic font support with dynamic font registry
- [x] Document style customization (`DocumentStyle`)
- [x] Text wrapping in `col()`, tables, and Document methods
- [x] Table column alignment and colspan
- [x] Colored panels (`panel()`)
- [x] Named repeatable headers and footers
- [x] Automatic page break before footer safe area
- [x] Fluent `DocumentStyle` configurators (`h1/h2/p()`)
- [ ] Table helper for totals rows
- [ ] `money()` / `date()` formatting helpers
- [x] PNG with alpha channel / logo support (`col()->image()`, auto aspect ratio)
- [ ] Expanded test coverage and official benchmarks

---

## License

MIT © [Arabel](https://arabel.dev)

---

⭐ If you find this useful, a star goes a long way.  
Issues, feedback, and PRs are very welcome.
