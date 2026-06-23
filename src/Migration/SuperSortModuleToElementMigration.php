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

        // The legacy iso_list_where / iso_newFilter values are virtual fields: they live in
        // tl_content.jsonData (see createContentElement), so we only need the jsonData column.
        if (!$schemaManager->introspectTableByUnquotedName('tl_content')->hasColumn('jsonData')) {
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
