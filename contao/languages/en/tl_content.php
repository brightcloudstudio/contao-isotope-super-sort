<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source'] = ['Sort order source', 'Choose where the manual product order for this list is defined.'];
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_source_options'] = [
    'page' => 'Use the order defined on the page',
    'element' => 'Define the order on this element',
];

$GLOBALS['TL_LANG']['tl_content']['iso_product_order'] = ['Product Sorting', 'Order of products as they should appear in this list. Products you do not pick follow afterwards in their normal order.'];

$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link'] = ['Product order', ''];
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_explanation'] = 'This list is ordered by the "Product Sorting" defined on the page it is placed on. Open the page settings (in a new window), change the order and save, then reload this element.';
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_button'] = 'Edit product order on the page';
$GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_nopage'] = 'Save this element first — then a link to edit the product order on the page will appear here.';
