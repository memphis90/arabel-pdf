# arabel/pdf

A lightweight, zero-dependency PHP library for generating PDF files programmatically.

> **Work in progress.** The API is functional and tested, but breaking changes may occur before v1.0.

---

## Why arabel/pdf?

Most PHP PDF libraries are either too heavy (TCPDF, mPDF) or too low-level (FPDF).
`arabel/pdf` gives you two layers — a semantic **Document API** for building layouts
quickly, and a direct **Pdf API** for precise, mm-level control — both fluent, both
dependency-free.

---

## Requirements

- PHP 8.1+
- Extensions: `zlib`, `iconv` (both enabled by default in most environments)

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

$doc = new Document();         // default font: Helvetica
// $doc = new Document('Times-Roman');  // override document font

$doc->addPage()
    ->h1('Monthly Report')
    ->h2('Sales — April 2026')
    ->hr()
    ->spacer()
    ->p('Summary of current month sales compared to the previous period.')
    ->spacer()
    ->output('report.pdf', 'F');
```

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

Content methods on `col()` return the parent `Row` so you can keep chaining columns.
Call `endRow()` to return to the `Document`.

| `col()` method | Description |
|----------------|-------------|
| `h1(string)`   | Large heading |
| `h2(string)`   | Section heading |
| `p(string)`    | Paragraph text |
| `text(string)` | Plain text |

### Tables

```php
$doc->addPage()
    ->h1('Product List')
    ->spacer()

    ->table(['Product', 'Category', 'Qty', 'Revenue'])
        ->tr(['Arabel PDF',     'Library', '142', '€ 0'])
        ->tr(['Arabel Builder', 'Tool',     '38', '€ 1,862'])
        ->tr(['Arabel Suite',   'Bundle',   '12', '€ 2,388'])
    ->endTable()

    ->output('products.pdf', 'F');
```

Columns are equally distributed by default. Use `widths()` to set custom proportions:

```php
->table(['Region', 'Clients', 'Total'])
    ->widths([3, 1, 1])   // 60% / 20% / 20%
    ->tr(['North Italy', '89', '€ 12,400'])
    ->tr(['South Italy', '22', '€ 4,900'])
->endTable()
```

### Document methods reference

| Method | Description |
|--------|-------------|
| `addPage('P'\|'L')` | New page — portrait or landscape |
| `h1(string)` | Large heading (20pt) |
| `h2(string)` | Section heading (14pt) |
| `p(string)` | Body paragraph (10pt) |
| `hr()` | Horizontal rule |
| `spacer(float $mm = 6)` | Vertical blank space |
| `row()` | Open a 12-column grid row → `Row` |
| `table(array $headers)` | Open a table → `Table` |
| `output(string, string)` | Finalize and output the PDF |
| `raw()` | Access the underlying `Pdf` instance |

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
Getter methods (`getX()`, `getY()`, `getMargins()`, `getStringWidth()`) return their
value and naturally break the chain, which is intentional.

### Pdf methods reference

| Method | Description |
|--------|-------------|
| `addPage('P'\|'L')` | New page |
| `setFont(family, size)` | Set font family and size in pt |
| `setTextColor(r, g, b)` | Text colour (0–255 per channel) |
| `setFillColor(r, g, b)` | Fill colour for cells and rects |
| `setDrawColor(r, g, b)` | Stroke colour for borders and lines |
| `setLineWidth(float)` | Line thickness in mm |
| `setMargins(l, t, r, b)` | Page margins in mm |
| `setXY(x, y)` | Move cursor to absolute position (mm) |
| `getX() / getY()` | Current cursor position in mm |
| `text(x, y, string)` | Print text at absolute position |
| `cell(w, h, text, border, ln, align)` | Render a cell, advance cursor |
| `rect(x, y, w, h, style)` | Draw a rectangle |
| `line(x1, y1, x2, y2)` | Draw a line |
| `image(file, x, y, w, h)` | Embed a JPEG or PNG image |
| `getStringWidth(string)` | Measure string width in mm |
| `output(name, dest)` | Finalize and output the PDF |

### `output()` destinations

| Dest | Behaviour |
|------|-----------|
| `'D'` | Force browser download (default) |
| `'I'` | Open inline in browser |
| `'F'` | Save to file (`$name` = full path) |
| `'S'` | Return raw PDF bytes as string |

### Supported image formats

| Format | Notes |
|--------|-------|
| JPEG   | Any colour mode |
| PNG    | 8-bit RGB or Grayscale, no alpha channel |

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

## Roadmap

- [ ] `composer.json` and Packagist distribution
- [ ] Multi-font support (bold, italic, custom TTF)
- [ ] Text wrapping inside `col()` and `cell()`
- [ ] PNG with alpha channel support
- [ ] `Document` style customization (colors, font sizes)
- [ ] Builder pattern integration with Arabel app

---

## License

MIT © [Arabel](https://arabel.dev)
