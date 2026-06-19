<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

use Bcs\IsotopeSuperSortBundle\EventListener\DataContainer\ProductOrderListener;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Add the product sorting field to every tl_page palette, just before the protected legend.
foreach (array_keys($GLOBALS['TL_DCA']['tl_page']['palettes']) as $palette) {
    if ('__selector__' === $palette || !\is_string($GLOBALS['TL_DCA']['tl_page']['palettes'][$palette])) {
        continue;
    }

    PaletteManipulator::create()
        ->addLegend('product_sort_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE, true)
        ->addField('iso_product_order', 'product_sort_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($palette, 'tl_page')
    ;
}

$GLOBALS['TL_DCA']['tl_page']['fields']['iso_product_order'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['iso_product_order'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_iso_product.name',
    'options_callback' => [ProductOrderListener::class, 'getProducts'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];
