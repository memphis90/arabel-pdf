<?php

require_once __DIR__ . '/../src/Pdf.php';
require_once __DIR__ . '/../src/DocumentStyle.php';
require_once __DIR__ . '/../src/Layout/Row.php';
require_once __DIR__ . '/../src/Layout/Col.php';
require_once __DIR__ . '/../src/Layout/Table.php';
require_once __DIR__ . '/../src/Layout/Panel.php';
require_once __DIR__ . '/../src/Layout/Header.php';
require_once __DIR__ . '/../src/Layout/Footer.php';
require_once __DIR__ . '/../src/Document.php';

use Arabel\Pdf\Document;
use Arabel\Pdf\DocumentStyle;

// ── Style: palette professionale blu/grigio ──────────────────────────────────

$style = new DocumentStyle();
$style->h1(22, [15, 55, 120], 'B', 12)
      ->h2(12, [15, 55, 120], 'B', 8)
      ->p(9,  [60, 60, 60],   '',  5.5);

$style->tableHeadBg = [15, 55, 120];
$style->tableHeadFg = [255, 255, 255];
$style->tableHeadH  = 8.0;
$style->tableAltBg  = [235, 241, 255];
$style->tableRowH   = 7.0;
$style->tableRowFg  = [40, 40, 40];
$style->tableLineH  = 5.0;

$style->hrColor     = [180, 195, 230];
$style->hrSpacing   = 5.0;

$doc = new Document('Helvetica', $style);

// ── Header default (pagina 1) ─────────────────────────────────────────────

$doc->setHeader()
    ->bg([15, 55, 120])
    ->fg([255, 255, 255])
    ->left('ARABEL SRL', 'Software & Digital Products')
    ->right('FATTURA', '# INV-2026-0042')
    ->height(22);

// ── Header named per allegato (pagina 2) ─────────────────────────────────

$doc->setHeader('allegato')
    ->bg([15, 55, 120])
    ->fg([255, 255, 255])
    ->left('ALLEGATO A — Dettaglio attività e ore lavorate', 'Fattura INV-2026-0042 | Acme Technologies SpA')
    ->height(22);

// ── Footer globale ────────────────────────────────────────────────────────

$doc->setFooter()
    ->left('Arabel Srl — P.IVA IT09876543210')
    ->right('Pagina {page}');

// ════════════════════════════════════════════════════════════════════════════
// PAGINA 1 — Fattura principale
// ════════════════════════════════════════════════════════════════════════════

$doc->addPage();
$doc->spacer(6);

// ── Mittente / Destinatario ──────────────────────────────────────────────────

$doc->row()
        ->col(6)->h2('Emessa da')
        ->col(6)->h2('Fatturata a')
    ->endRow()
    ->row()
        ->col(6)->p('Arabel Srl')
        ->col(6)->p('Acme Technologies SpA')
    ->endRow()
    ->row()
        ->col(6)->p('Via della Innovazione, 12')
        ->col(6)->p('Corso Vittorio Emanuele, 88')
    ->endRow()
    ->row()
        ->col(6)->p('20121 Milano (MI)')
        ->col(6)->p('00186 Roma (RM)')
    ->endRow()
    ->row()
        ->col(6)->p('P.IVA: IT09876543210')
        ->col(6)->p('P.IVA: IT01234567890')
    ->endRow()
    ->row()
        ->col(6)->p('billing@arabel.dev')
        ->col(6)->p('amministrazione@acme.it')
    ->endRow();

$doc->spacer(6)->hr()->spacer(4);

// ── Dettagli fattura ─────────────────────────────────────────────────────────

$doc->row()
        ->col(3)->h2('Data emissione')
        ->col(3)->h2('Scadenza')
        ->col(3)->h2('Metodo pagamento')
        ->col(3)->h2('Valuta')
    ->endRow()
    ->row()
        ->col(3)->p('11 Aprile 2026')
        ->col(3)->p('11 Maggio 2026')
        ->col(3)->p('Bonifico bancario')
        ->col(3)->p('EUR (€)')
    ->endRow();

$doc->spacer(8)->hr()->spacer(4);

// ── Righe fattura — con align() ──────────────────────────────────────────────

$doc->h2('Dettaglio servizi');
$doc->spacer(3);

$doc->table(['Descrizione', 'Periodo', 'Qty', 'Prezzo unitario', 'IVA', 'Totale'])
        ->widths([5, 3, 1, 2, 1, 2])
        ->align(['L', 'L', 'C', 'R', 'C', 'R'])   // ← TEST align()
        ->tr(['Sviluppo modulo PDF — Document API',         'Gen–Feb 2026', '1',  '€ 3.200,00', '22%', '€ 3.904,00'])
        ->tr(['Sviluppo modulo PDF — Pdf API low-level',    'Feb–Mar 2026', '1',  '€ 2.400,00', '22%', '€ 2.928,00'])
        ->tr(['Integrazione Packagist e CI/CD pipeline',    'Mar 2026',     '1',  '€ 800,00',   '22%', '€ 976,00'])
        ->tr(['Licenza annuale arabel/pdf — Piano Pro',     'Apr 2026',     '12', '€ 49,00',    '22%', '€ 717,36'])
        ->tr(['Consulenza tecnica — ottimizzazione render', 'Mar–Apr 2026', '8h', '€ 120,00',   '22%', '€ 1.170,24'])
        ->tr(['Formazione team interno (4 sessioni)',        'Apr 2026',     '4',  '€ 350,00',   '22%', '€ 1.708,00'])
        ->tr(['Supporto prioritario 12 mesi',                'Apr 2026',     '1',  '€ 600,00',   '22%', '€ 732,00'])
        // ← TEST colspan: riga subtotale che rompe il flusso normale
        ->tr([
            ['text' => 'Subtotale servizi di sviluppo (prime 3 righe):', 'colspan' => 5, 'align' => 'R'],
            ['text' => '€ 7.808,00', 'align' => 'R'],
        ])
    ->endTable();

$doc->spacer(4);

// ── Totali — con panel() ──────────────────────────────────────────────────────

$doc->row()
        ->col(7)->p('')
        ->col(2)->p('Imponibile:')
        ->col(3)->p('€ 10.938,00')
    ->endRow()
    ->row()
        ->col(7)->p('')
        ->col(2)->p('IVA 22%:')
        ->col(3)->p('€ 2.406,36')
    ->endRow();

$doc->spacer(2);

// ← TEST panel(): sostituisce il raw() rect + text del totale
$doc->panel()
        ->bg([15, 55, 120])
        ->fg([255, 255, 255])
        ->padding(4)
        ->h2('TOTALE FATTURA:    € 13.344,36')
    ->endPanel();

$doc->hr()->spacer(4);

// ── Note di pagamento ────────────────────────────────────────────────────────
$doc->addPage();

$doc->h2('Coordinate bancarie');
$doc->spacer(2);

$doc->row()
        ->col(3)->p('Banca:')
        ->col(9)->p('Banca Sella SpA — Agenzia Milano Centro')
    ->endRow()
    ->row()
        ->col(3)->p('IBAN:')
        ->col(9)->p('IT60 X054 2811 1010 0000 0123 456')
    ->endRow()
    ->row()
        ->col(3)->p('BIC/SWIFT:')
        ->col(9)->p('SELBIT2BXXX')
    ->endRow()
    ->row()
        ->col(3)->p('Causale:')
        ->col(9)->p('Pagamento fattura INV-2026-0042 — Arabel Srl')
    ->endRow();

$doc->spacer(6);

$doc->row()
        ->col(12)->p('Il pagamento dovrà pervenire entro il 11 Maggio 2026. In caso di ritardo verranno applicati interessi di mora nella misura del tasso BCE + 8 punti percentuali ai sensi del D.Lgs. 231/2002. Per qualsiasi chiarimento contattare billing@arabel.dev.')
    ->endRow();

// ════════════════════════════════════════════════════════════════════════════
// PAGINA 2 — Allegato tecnico (dettaglio ore)
// ════════════════════════════════════════════════════════════════════════════

$doc->addPage('P', 'allegato');
$doc->spacer(6);

$doc->h2('Consulenza tecnica — dettaglio sessioni');
$doc->spacer(3);

$doc->table(['Data', 'Attività', 'Sviluppatore', 'Ore', 'Note'])
        ->widths([2, 5, 2, 1, 4])
        ->align(['C', 'L', 'L', 'C', 'L'])         // ← align() anche qui
        ->tr(['03/03/2026', 'Analisi architettura PDF renderer',         'M. Rossi',  '2h', 'Kickoff tecnico con CTO Acme'])
        ->tr(['05/03/2026', 'Ottimizzazione pipeline font encoding',     'M. Rossi',  '1h', 'Fix iconv Windows-1252'])
        ->tr(['10/03/2026', 'Refactoring layout engine Row/Col',         'M. Rossi',  '2h', 'Introdotto grid a 12 colonne'])
        ->tr(['18/03/2026', 'Review codice e code style',                'L. Bianchi','1h', 'PSR-12 compliance'])
        ->tr(['24/03/2026', 'Ottimizzazione compressione stream PDF',    'M. Rossi',  '1h', 'gzcompress level 6'])
        ->tr(['02/04/2026', 'Implementazione DocumentStyle API',         'M. Rossi',  '1h', 'Colori, font size, spacing'])
        // ← colspan: riga totale ore di consulenza
        ->tr([
            ['text' => 'Totale ore consulenza:', 'colspan' => 3, 'align' => 'R'],
            ['text' => '8h', 'align' => 'C'],
            '',
        ])
    ->endTable();

$doc->spacer(8);

$doc->h2('Formazione team interno — dettaglio sessioni');
$doc->spacer(3);

$doc->table(['Data', 'Argomento', 'Partecipanti', 'Durata', 'Materiale'])
        ->widths([2, 4, 2, 1, 5])
        ->align(['C', 'L', 'C', 'C', 'L'])
        ->tr(['07/04/2026', 'Introduzione a arabel/pdf — Document API',   '6 persone', '2h', 'Slides + esempi pratici fattura/report'])
        ->tr(['09/04/2026', 'Pdf API low-level e posizionamento preciso',  '4 persone', '2h', 'Esercizi live, watermark, immagini'])
        ->tr(['10/04/2026', 'Grid layout e tabelle avanzate',              '6 persone', '2h', 'Workshop dashboard KPI'])
        ->tr(['11/04/2026', 'Stili custom, CI/CD e deploy Packagist',      '3 persone', '2h', 'Setup ambiente produzione'])
        // ← colspan full-width nota
        ->tr([
            ['text' => 'Tutti i materiali sono disponibili su: docs.arabel.dev/training', 'colspan' => 5, 'align' => 'C'],
        ])
    ->endTable();

$doc->spacer(8)->hr()->spacer(4);

$doc->row()
        ->col(6)->h2('Riepilogo ore')
        ->col(6)->h2('Firma e timbro')
    ->endRow();

$firmaTopY = $doc->getCursorY();

$doc->row()
        ->col(3)->p('Sviluppo:')
        ->col(3)->p('40 ore')
        ->col(6)->p('')
    ->endRow()
    ->row()
        ->col(3)->p('Consulenza:')
        ->col(3)->p('8 ore')
        ->col(6)->p('')
    ->endRow()
    ->row()
        ->col(3)->p('Formazione:')
        ->col(3)->p('8 ore')
        ->col(6)->p('')
    ->endRow()
    ->row()
        ->col(3)->h2('Totale:')
        ->col(3)->h2('56 ore')
        ->col(6)->p('')
    ->endRow();

$firmaH = $doc->getCursorY() - $firmaTopY;
$doc->raw()
    ->setDrawColor(180, 195, 230)
    ->setLineWidth(0.3)
    ->rect($doc->colX(6), $firmaTopY, $doc->colW(6), $firmaH);

// ════════════════════════════════════════════════════════════════════════════
// PAGINA 3 — Crash test panel() con varianti
// ════════════════════════════════════════════════════════════════════════════

$doc->addPage();

$doc->h1('Crash test: panel()');
$doc->spacer(4);

// Panel scuro con fg bianco
$doc->panel()
        ->bg([15, 55, 120])
        ->fg([255, 255, 255])
        ->padding(5)
        ->h2('Panel scuro — h2 + p + spacer')
        ->spacer(2)
        ->p('Questo testo deve apparire bianco su sfondo blu. Il panel misura la propria altezza prima di disegnare il rect, quindi testo e sfondo sono sempre allineati.')
    ->endPanel();

// Panel chiaro senza fg (usa colori default DocumentStyle)
$doc->panel()
        ->bg([235, 241, 255])
        ->padding(4)
        ->h2('Panel chiaro — colori default')
        ->p('Nessun fg() impostato: ogni elemento usa il proprio colore da DocumentStyle.')
        ->spacer(2)
        ->p('Seconda riga di testo per verificare che la misurazione altezza sia corretta con multipli elementi.')
    ->endPanel();

// Panel con hr interno
$doc->panel()
        ->bg([245, 245, 245])
        ->padding(5)
        ->b('TOTALE FATTURA')
        ->hr()
        ->h2('€ 13.344,36')
        ->p('IVA inclusa al 22%')
    ->endPanel();

// Panel minimal — un solo elemento
$doc->panel()
        ->bg([220, 255, 220])
        ->padding(3)
        ->p('Panel minimal — singola riga p()')
    ->endPanel();

// Testo dopo i panel — il cursore deve essere corretto
$doc->spacer(4);
$doc->h2('Testo dopo tutti i panel — cursore ok?');
$doc->p('Se questo testo appare subito dopo l\'ultimo panel senza sovrapposizioni, il calcolo del cursore è corretto.');

// ── Output ───────────────────────────────────────────────────────────────────

$ts  = date('Ymd_His');
$out = __DIR__ . '/output/invoice_test_' . $ts . '.pdf';
$doc->output($out, 'F');

echo "Fattura generata: $out\n";
echo "Pagine: 3 (fattura + allegato + crash test panel)\n";
