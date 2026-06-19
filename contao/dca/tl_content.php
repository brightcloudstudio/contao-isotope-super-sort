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
$GLOBALS['TL_DCA']['tl_content']['palettes']['iso_super_sort_list'] = '{type_legend},title,headline,type;{config_legend},numberOfItems,perPage,iso_category_scope,iso_searchFields,iso_filterQuery,iso_filterKey,iso_listingSortField,iso_listingSortDirection,iso_super_sort_source;{redirect_legend},iso_link_primary,iso_jump_first,iso_addProductJumpTo,iso_checkout_jumpTo,iso_checkout_guest_jumpTo;{reference_legend:hide},defineRoot;{template_legend:hide},customTpl,iso_list_layout,iso_gallery,iso_use_quantity,iso_hide_list,iso_hide_empty,iso_disable_options,iso_emptyMessage,iso_emptyFilter,iso_buttons;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'iso_super_sort_source';

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
