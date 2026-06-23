<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source'] = ['Quelle der Sortierung', 'Legen Sie fest, wo die manuelle Produktreihenfolge für diese Liste definiert wird.'];
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source_options'] = [
    'page' => 'Reihenfolge der Seite verwenden',
    'element' => 'Reihenfolge an diesem Element festlegen',
];

$GLOBALS['TL_LANG']['tl_content']['iso_product_order'] = ['Produktsortierung', 'Reihenfolge der Produkte in dieser Liste. Nicht ausgewählte Produkte folgen anschließend in ihrer normalen Reihenfolge.'];

$GLOBALS['TL_LANG']['tl_content']['iso_list_where'] = ['Bedingung', 'Hier können Sie eine SQL-Bedingung eingeben, um die Produkte zu filtern (wird an die WHERE-Klausel angehängt). Spaltennamen müssen dem Isotope-3-Produktschema entsprechen.'];
$GLOBALS['TL_LANG']['tl_content']['iso_newFilter'] = ['Filterung nach neuen Produkten', 'Liste auf kürzlich hinzugefügte („neue“) bzw. nicht neue („alte“) Produkte beschränken. Leer lassen, um alle Produkte anzuzeigen.'];
$GLOBALS['TL_LANG']['tl_content']['iso_newFilter_options'] = [
    'show_new' => 'Nur neue Produkte anzeigen',
    'show_old' => 'Nur alte Produkte anzeigen',
];
$GLOBALS['TL_LANG']['tl_content']['iso_newPeriod'] = ['Zeitraum „neu“ (Tage)', 'Ein Produkt gilt als „neu“, wenn es innerhalb dieser Anzahl von Tagen hinzugefügt wurde. Standard ist 30.'];

$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link'] = ['Produktreihenfolge', ''];
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_explanation'] = 'Diese Liste wird nach der „Produktsortierung“ der Seite sortiert, auf der sie platziert ist. Öffnen Sie die Seiteneinstellungen (in einem neuen Fenster), ändern und speichern Sie die Reihenfolge und laden Sie dieses Element anschließend neu.';
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_button'] = 'Produktreihenfolge auf der Seite bearbeiten';
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_nopage'] = 'Speichern Sie dieses Element zuerst – danach erscheint hier ein Link zum Bearbeiten der Produktreihenfolge auf der Seite.';
