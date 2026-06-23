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

// Mirror Isotope's iso_productlist palette for the Super Sort list content element, with an
// added "sort order source" selector in the config legend.
$GLOBALS['TL_DCA']['tl_content']['palettes']['iso_super_sort_list'] = '{type_legend},title,headline,type;{config_legend},numberOfItems,perPage,iso_category_scope,iso_searchFields,iso_filterQuery,iso_filterKey,iso_list_where,iso_newFilter,iso_newPeriod,iso_listingSortField,iso_listingSortDirection,iso_super_sort_source;{redirect_legend},iso_link_primary,iso_jump_first,iso_addProductJumpTo,iso_checkout_jumpTo,iso_checkout_guest_jumpTo;{reference_legend:hide},defineRoot;{template_legend:hide},customTpl,iso_list_layout,iso_gallery,iso_use_quantity,iso_hide_list,iso_hide_empty,iso_disable_options,iso_emptyMessage,iso_emptyFilter,iso_buttons;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'iso_super_sort_source';

// Isotope declares the "defineRoot"/"rootPage" fields without an SQL key, so the install tool
// never creates the columns. This element reads them explicitly (see ProductOrderListener and
// SuperSortListController), so make sure the columns exist.
$GLOBALS['TL_DCA']['tl_content']['fields']['defineRoot']['sql'] = ['type' => 'boolean', 'default' => false];
$GLOBALS['TL_DCA']['tl_content']['fields']['rootPage']['sql'] = ['type' => 'integer', 'unsigned' => true, 'default' => 0];

// Legacy Isotope 2 ProductList fields that Isotope 3 dropped. Re-added here so the migrated
// Super Sort elements can keep using them (see SuperSortListController::findProducts).
//
// These are declared WITHOUT an 'sql' key on purpose: like Isotope 3's own list fields
// (iso_category_scope, iso_filterQuery, …) they are Contao virtual fields stored in
// tl_content.jsonData. That is also where Isotope's module→element migration left the legacy
// values, so they are read back automatically with no data migration. SuperSortVirtualFieldsMigration
// drops the real columns if an earlier (incorrect) version of this bundle created them.
$GLOBALS['TL_DCA']['tl_content']['fields']['iso_list_where'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_list_where'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'long clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['iso_newFilter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_newFilter'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['show_new', 'show_old'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['iso_newFilter_options'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['iso_newPeriod'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_newPeriod'],
    'exclude' => true,
    'inputType' => 'text',
    'default' => 30,
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['iso_super_sort_source_element'] = 'iso_product_order';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['iso_super_sort_source_page'] = 'iso_super_sort_page_link';

$GLOBALS['TL_DCA']['tl_content']['fields']['iso_super_sort_source'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['page', 'element'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source_options'],
    'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
    'sql' => ['type' => 'string', 'length' => 16, 'default' => 'page'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['iso_product_order'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_product_order'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'foreignKey' => 'tl_iso_product.name',
    'options_callback' => [ProductOrderListener::class, 'getProductsForContent'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['iso_super_sort_page_link'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link'],
    'exclude' => true,
    'input_field_callback' => [ProductOrderListener::class, 'renderPageLink'],
    'eval' => ['doNotShow' => true, 'doNotCopy' => true],
];
