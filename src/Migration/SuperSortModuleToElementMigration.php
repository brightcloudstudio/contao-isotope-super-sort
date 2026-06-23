<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

namespace Bcs\IsotopeSuperSortBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

/**
 * Converts the legacy Isotope 2 "Super Sort List" front end module (tl_module.type =
 * iso_super_sort_list) into the Isotope 3 "Super Sort List" content element
 * (tl_content.type = iso_super_sort_list).
 *
 * Mirrors Isotope\CoreBundle\Migration\ModuleToElementMigration: any module columns that no
 * longer exist on tl_content are preserved in the tl_content.jsonData column. Both module
 * placements are rewired — `module` content elements (via cteAlias) and layout modules.
 */
class SuperSortModuleToElementMigration extends AbstractMigration
{
    private const MODULE_TYPE = 'iso_super_sort_list';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module', 'tl_content'])) {
            return false;
        }

        $content = $schemaManager->introspectTableByUnquotedName('tl_content');

        // Defer until the schema step has created the columns this migration writes to, so the
        // legacy values land in real columns instead of being shovelled into jsonData.
        if (
            !$content->hasColumn('jsonData')
            || !$content->hasColumn('iso_list_where')
            || !$content->hasColumn('iso_newFilter')
        ) {
            return false;
        }

        return $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tl_module WHERE type = ?',
            [self::MODULE_TYPE],
        ) > 0;
    }

    public function run(): MigrationResult
    {
        $table = $this->connection->createSchemaManager()->introspectTableByUnquotedName('tl_content');

        $modules = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_module WHERE type = ?',
            [self::MODULE_TYPE],
        );

        $mapping = [];

        foreach ($modules as $module) {
            $mapping[$module['id']] = $this->createContentElement($module, $table);
        }

        $this->updateLayouts($mapping);

        return $this->createResult(
            true,
            \sprintf('Migrated %d Super Sort List module(s) to content elements.', \count($modules)),
        );
    }

    private function createContentElement(array $data, Table $table): int
    {
        $tableData = [];
        $jsonData = [];

        foreach ($data as $key => $value) {
            if ($table->hasColumn($key)) {
                $tableData[$key] = $value;
            } else {
                $jsonData[$key] = $value;
            }
        }

        unset($tableData['id']);
        $tableData['ptable'] = 'tl_theme';
        $tableData['title'] = $data['name'];

        // Try to express the legacy raw SQL "Condition" as an Isotope 3 filter query. On success the
        // raw SQL is cleared; anything we cannot translate stays in iso_list_where as a fallback.
        if (!empty($tableData['iso_list_where']) && empty($tableData['iso_filterQuery'])) {
            $filterQuery = $this->translateCondition((string) $tableData['iso_list_where']);

            if (null !== $filterQuery) {
                $tableData['iso_filterQuery'] = $filterQuery;
                $tableData['iso_list_where'] = '';
            }
        }

        $this->connection->insert('tl_content', [
            ...$tableData,
            'jsonData' => json_encode($jsonData, JSON_THROW_ON_ERROR),
        ]);

        $contentId = (int) $this->connection->lastInsertId();

        // Re-point `module` content elements that referenced this module.
        $this->connection->executeStatement(
            "UPDATE tl_content SET type='alias', cteAlias=? WHERE type='module' AND module=?",
            [$contentId, $data['id']],
        );

        $this->connection->delete('tl_module', ['id' => $data['id']]);

        return $contentId;
    }

    /**
     * Best-effort translation of a legacy raw SQL "Condition" into the Isotope 3 filter query DSL
     * (attribute + operator + value, multiple clauses joined by ";"). Deliberately conservative:
     * only simple "column <op> value" clauses joined by AND are handled; anything else (functions
     * such as LOCATE(), OR, sub-expressions, comma/quote-bearing values) returns null so the caller
     * keeps the original raw SQL as a fallback.
     */
    private function translateCondition(string $sql): ?string
    {
        $sql = trim($sql);

        // Strip a single pair of wrapping parentheses, e.g. "(name='foo')".
        if (str_starts_with($sql, '(') && str_ends_with($sql, ')')) {
            $sql = trim(substr($sql, 1, -1));
        }

        if ('' === $sql || preg_match('/\bOR\b/i', $sql)) {
            return null;
        }

        // SQL operator => Isotope 3 filter-query operator.
        $operatorMap = [
            '!=' => '!',
            '<>' => '!',
            '>=' => '≥',
            '<=' => '≤',
            '=' => ':',
            '>' => '>',
            '<' => '<',
        ];

        $clauses = preg_split('/\s+AND\s+/i', $sql);
        $filters = [];

        foreach ($clauses as $clause) {
            if (!preg_match('/^\(?\s*([a-zA-Z_]\w*)\s*(!=|<>|>=|<=|=|>|<)\s*(.+?)\s*\)?$/', trim($clause), $m)) {
                return null;
            }

            $value = trim($m[3]);

            // Unwrap a single-/double-quoted value; reject if quotes are unbalanced.
            if (preg_match("/^'(.*)'$/", $value, $q) || preg_match('/^"(.*)"$/', $value, $q)) {
                $value = $q[1];
            }

            // Reject values that would break the DSL or hint at SQL functions/expressions.
            if ('' === $value || preg_match('/[;()\'",]/', $value)) {
                return null;
            }

            $filters[] = $m[1].$operatorMap[$m[2]].$value;
        }

        return [] === $filters ? null : implode(';', $filters);
    }

    private function updateLayouts(array $mapping): void
    {
        if ([] === $mapping || !$this->connection->createSchemaManager()->tableExists('tl_layout')) {
            return;
        }

        $layouts = $this->connection->fetchAllKeyValue('SELECT id, modules FROM tl_layout');

        foreach ($layouts as $id => $modules) {
            $changed = false;
            $modules = StringUtil::deserialize($modules, true);

            foreach ($modules as $k => $module) {
                if (!isset($mapping[$module['mod']])) {
                    continue;
                }

                $modules[$k]['mod'] = 'content-'.$mapping[$module['mod']];
                $changed = true;
            }

            if ($changed) {
                $this->connection->update('tl_layout', ['modules' => serialize($modules)], ['id' => $id]);
            }
        }
    }
}
