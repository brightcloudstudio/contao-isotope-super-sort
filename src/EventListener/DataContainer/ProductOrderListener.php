<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

namespace Bcs\IsotopeSuperSortBundle\EventListener\DataContainer;

use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Backend helpers for the Super Sort product order fields.
 *
 * Replaces the legacy SuperSortHelper::getProducts() which relied on the removed
 * Isotope\Model\Product::findPublishedByCategories(). A page is an Isotope category root,
 * so products are resolved via tl_iso_product_category.pid = the page id.
 */
class ProductOrderListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Options for the page-level order field (tl_page.iso_product_order).
     *
     * @return array<int, string>
     */
    public function getProducts(DataContainer $dc): array
    {
        if (!$dc->id) {
            return [];
        }

        return $this->fetchProductOptions((int) $dc->id);
    }

    /**
     * Options for the element-level order field (tl_content.iso_product_order).
     *
     * Super Sort lists are usually filtered by a product attribute (e.g. the "category" condition)
     * rather than by the page's Isotope category, so scoping the picker to a category page would
     * leave it empty. When the element defines a root category we honour it; otherwise we offer all
     * products. Picks that are not actually in the rendered list are simply ignored when ordering.
     *
     * @return array<int, string>
     */
    public function getProductsForContent(DataContainer $dc): array
    {
        $row = $dc->id
            ? $this->connection->fetchAssociative('SELECT defineRoot, rootPage, jsonData FROM tl_content WHERE id = ?', [(int) $dc->id])
            : false;

        if (!$row) {
            return $this->fetchAllProductOptions();
        }

        // A defined root category takes precedence (matches the list's category scope).
        if ($row['defineRoot'] && $row['rootPage']) {
            return $this->fetchProductOptions((int) $row['rootPage']);
        }

        // Otherwise scope the picker to the element's own "Condition" (iso_list_where), so the
        // editor sees exactly the products this list shows. Falls back to all products if the
        // condition is empty or not a valid stand-alone WHERE clause.
        $condition = $this->getListWhere($row['jsonData']);

        if ('' !== $condition) {
            $products = $this->fetchProductOptionsByCondition($condition);

            if (null !== $products) {
                return $products;
            }
        }

        return $this->fetchAllProductOptions();
    }

    /**
     * Renders an explanation and a "open in new window" link to edit the page-level product order.
     */
    public function renderPageLink(DataContainer $dc): string
    {
        $label = $GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link'][0] ?? 'Product order';

        if (!$dc->id) {
            $message = $GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_nopage']
                ?? 'Save this element first, then a link to edit the product order on the page will appear here.';

            return '<div class="widget"><h3>'.$label.'</h3><p class="tl_info">'.$message.'</p></div>';
        }

        $pageId = $this->resolvePageId($dc);

        if (null === $pageId) {
            // The element is not tied to a single page (e.g. a theme/layout content element shown on
            // many pages), so there is no one page settings dialog to link to.
            $message = $GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_multipage']
                ?? 'This element can appear on several pages, so each page uses its own "Product Sorting" '
                    .'(edit it under Site Structure → the page → Product Sorting). Switch the sort order source '
                    .'to "this element" to define a single order here instead.';

            return '<div class="widget"><h3>'.$label.'</h3><p class="tl_info">'.$message.'</p></div>';
        }

        $explanation = $GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_explanation']
            ?? 'The products are ordered by the "Product Sorting" defined on the page this element is on. Open the page settings to change the order, then reload this element.';
        $linkText = $GLOBALS['TL_LANG']['tl_content']['iso_super_sort_page_link_button'] ?? 'Edit product order on the page';

        $request = $this->requestStack->getCurrentRequest();
        $href = $this->router->generate('contao_backend', array_filter([
            'do' => 'page',
            'act' => 'edit',
            'id' => $pageId,
            'ref' => $request?->attributes->get('_contao_referer_id'),
        ]), UrlGeneratorInterface::ABSOLUTE_PATH);

        $pageTitle = (string) $this->connection->fetchOne('SELECT title FROM tl_page WHERE id = ?', [$pageId]);

        return '<div class="widget">'
            .'<h3>'.$label.'</h3>'
            .'<p class="tl_help" style="margin:0 0 9px">'.$explanation.'</p>'
            .'<a href="'.htmlspecialchars($href, ENT_QUOTES).'" target="_blank" rel="noreferrer noopener" class="tl_submit">'
            .htmlspecialchars($linkText, ENT_QUOTES)
            .($pageTitle ? ' &rarr; '.htmlspecialchars($pageTitle, ENT_QUOTES) : '')
            .'</a>'
            .'</div>';
    }

    /**
     * @return array<int, string>
     */
    private function fetchProductOptions(int $pageId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT p.id, p.name, p.sku
             FROM tl_iso_product p
             INNER JOIN tl_iso_product_category c ON c.product_id = p.id
             WHERE c.pid = :pid AND p.pid = 0
             ORDER BY p.name',
            ['pid' => $pageId],
        );

        return $this->buildProductLabels($rows);
    }

    /**
     * @return array<int, string>
     */
    private function fetchAllProductOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, name, sku FROM tl_iso_product WHERE pid = 0 ORDER BY name',
        );

        return $this->buildProductLabels($rows);
    }

    /**
     * Scopes the picker to the products matching a legacy "Condition" (iso_list_where). The
     * condition runs against tl_iso_product alone (no self-join), so unqualified columns such as
     * "category" are unambiguous here. Returns null if the SQL is invalid so the caller can fall back.
     *
     * @return array<int, string>|null
     */
    private function fetchProductOptionsByCondition(string $condition): ?array
    {
        try {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id, name, sku FROM tl_iso_product WHERE pid = 0 AND ('.$condition.') ORDER BY name',
            );
        } catch (\Throwable) {
            return null;
        }

        return $this->buildProductLabels($rows);
    }

    /**
     * Reads the element's iso_list_where condition from its jsonData (it is a virtual field).
     */
    private function getListWhere(mixed $jsonData): string
    {
        if (!\is_string($jsonData) || '' === $jsonData) {
            return '';
        }

        $data = json_decode($jsonData, true);

        if (!\is_array($data) || !\is_string($data['iso_list_where'] ?? null)) {
            return '';
        }

        // Contao entity-encodes ( ) ' etc. on save; decode so the condition is valid SQL.
        return trim(StringUtil::decodeEntities($data['iso_list_where']));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, string>
     */
    private function buildProductLabels(array $rows): array
    {
        $products = [];

        foreach ($rows as $row) {
            $label = (string) $row['name'];

            if (!empty($row['sku'])) {
                $label .= ' (SKU: '.$row['sku'].')';
            }

            $products[(int) $row['id']] = $label;
        }

        return $products;
    }

    /**
     * Resolves the page a content element lives on (honouring its "define root" override).
     */
    private function resolvePageId(DataContainer $dc): ?int
    {
        if (!$dc->id) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT pid, ptable, defineRoot, rootPage FROM tl_content WHERE id = ?',
            [(int) $dc->id],
        );

        if (!$row) {
            return null;
        }

        if ($row['defineRoot'] && $row['rootPage']) {
            return (int) $row['rootPage'];
        }

        // Only the common case (element directly inside an article) is resolved automatically.
        if (($row['ptable'] ?: 'tl_article') !== 'tl_article') {
            return null;
        }

        $pageId = $this->connection->fetchOne('SELECT pid FROM tl_article WHERE id = ?', [(int) $row['pid']]);

        return $pageId ? (int) $pageId : null;
    }
}
