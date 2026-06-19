Contao Isotope Super Sort
=========================

Manual, per-page product ordering for Isotope eCommerce product lists.

This is a Contao 5 / Isotope 3 rewrite of [`asconsulting/isotope_super_sort`](https://github.com/asconsulting/isotope_super_sort).
The original was a legacy `system/modules` extension that subclassed the (now removed)
`Isotope\Module\ProductList`. Isotope 3 turned product lists into a content element
(`ProductListController`) whose product resolution is `private`, so this bundle ships a new
**“Super Sort List”** content element that mirrors the Isotope product list and additionally
applies a manual product order configured on the page.

## What it adds

* A **Product Sorting** field (`iso_product_order`) on every page (`tl_page`), where an editor can
  pick and order the products that should appear first.
* A **Super Sort List** content element (`iso_super_sort_list`) — identical configuration to the
  Isotope product list, but products listed in the page’s *Product Sorting* come first (in the saved
  order); all remaining products follow in their normal query order.

## Usage

Add a **Super Sort List** content element (Isotope group) instead of the normal product list and
configure it like any Isotope product list. Then choose where the manual order is defined via the
**Sort order source** option:

* **Use the order defined on the page** *(default)* — the list respects the **Product Sorting** field
  on the page the element is placed on. The element shows an explanation and a button that opens that
  page’s settings in a new window, so an editor can adjust the order without leaving the element.
* **Define the order on this element** — pick and order the products directly on the content element.
  Useful when several differently-ordered lists live on the same page.

In both cases, products you pick come first in the chosen order; everything else follows in the
list’s normal order.

## Migration from Contao 4.13 / Isotope 2

In Isotope 2 this was a *front end module* (`iso_super_sort_list`). In Isotope 3 product lists are
*content elements*. A **Contao migration** (run via the Contao Manager or `contao:migrate`) is
included that automatically converts every legacy `iso_super_sort_list` module into a
`iso_super_sort_list` content element — rewiring both layout module placements and `module` content
elements, exactly like Isotope’s own module-to-element migration. Settings that no longer have a
matching column are preserved in `tl_content.jsonData`. The `tl_page.iso_product_order` data is
carried over unchanged, so existing per-page ordering keeps working out of the box.
