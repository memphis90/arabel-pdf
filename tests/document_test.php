<?php

require_once __DIR__ . '/../src/Pdf.php';
require_once __DIR__ . '/../src/Layout/Row.php';
require_once __DIR__ . '/../src/Layout/Col.php';
require_once __DIR__ . '/../src/Layout/Table.php';
require_once __DIR__ . '/../src/Document.php';

use Arabel\Pdf\Document;

$doc = new Document();

// ── Pagina 1: layout semantico ───────────────────────────────────────────────

$doc->addPage()
    ->h1('Report Mensile')
    ->h2('Vendite Aprile 2026')
    ->hr()
    ->spacer(4)

    // Row a due colonne: testo a sinistra, valore in evidenza a destra
    ->row()
        ->col(8)->p('Riepilogo delle vendite del mese corrente rispetto al periodo precedente.')
        ->col(4)->h2('€ 24.500')
    ->endRow()

    ->spacer(6)

    // Tabella principale
    ->table(['Prodotto', 'Categoria', 'Qtà', 'Ricavo'])
        ->tr(['Arabel PDF',     'Libreria', '142', '€ 0'])
        ->tr(['Arabel Builder', 'Tool',      '38', '€ 1.862'])
        ->tr(['Arabel Suite',   'Bundle',    '12', '€ 2.388'])
    ->endTable()

    ->spacer(8)
    ->h2('Dettaglio per regione')

    // Tabella con colonne proporzionali custom: regione più larga
    ->table(['Regione', 'Clienti', 'Totale'])
        ->widths([3, 1, 1])
        ->tr(['Italia — Nord',  '89', '€ 12.400'])
        ->tr(['Italia — Centro', '41', '€ 7.200'])
        ->tr(['Italia — Sud',   '22', '€ 4.900'])
    ->endTable()

    ->spacer(8)

    // Row a tre colonne uguali: metriche KPI
    ->row()
        ->col(4)->h2('142 PDF')
        ->col(4)->h2('38 upgrade')
        ->col(4)->h2('94% soddisf.')
    ->endRow()
    ->row()
        ->col(4)->p('generati questo mese')
        ->col(4)->p('da piano Free a Pro')
        ->col(4)->p('indice soddisfazione')
    ->endRow();

// ── Pagina 2: landscape con griglia ─────────────────────────────────────────

$doc->addPage('L')
    ->h1('Dashboard Landscape')
    ->h2('Panoramica prodotti — Q1 2026')
    ->hr()
    ->spacer(4)

    ->table(['Prodotto', 'Gen', 'Feb', 'Mar', 'Totale Q1'])
        ->tr(['Arabel PDF',     '41', '48', '53', '142'])
        ->tr(['Arabel Builder', '10', '14', '14', '38'])
        ->tr(['Arabel Suite',   '3',  '4',  '5',  '12'])
    ->endTable()

    ->spacer(8)

    ->row()
        ->col(6)->h2('Note operative')
        ->col(6)->h2('Prossimi obiettivi')
    ->endRow()
    ->row()
        ->col(6)->p('Builder in beta — accesso anticipato attivo.')
        ->col(6)->p('Lancio Suite enterprise entro fine Q2.')
    ->endRow()
    ->row()
        ->col(6)->p('Integrazione Arabel PDF in produzione da Feb.')
        ->col(6)->p('Documentazione API pubblica entro maggio.')
    ->endRow();

// ── Output ───────────────────────────────────────────────────────────────────

$out = __DIR__ . '/output/document_test.pdf';
$doc->output($out, 'F');

echo "PDF generato: $out\n";
echo "Pagine: 2 (portrait + landscape)\n";
