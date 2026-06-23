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

use Isotope\CoreBundle\Migration\AbstractVirtualFieldsMigration;

/**
 * The legacy Super Sort "Condition" (iso_list_where) and "new products" (iso_newFilter /
 * iso_newPeriod) fields are Contao virtual fields stored in tl_content.jsonData, exactly like
 * Isotope 3's own list fields. An earlier version of this bundle wrongly declared them with an
 * 'sql' key, which created real columns on tl_content; the model then read those (empty) columns
 * instead of the jsonData values the module→element migration had already written.
 *
 * This migration relocates any such column values back into jsonData (without overwriting an
 * existing jsonData value) and drops the columns, restoring the values for the controller to read.
 * On installs where the columns were never created it is a no-op.
 */
class SuperSortVirtualFieldsMigration extends AbstractVirtualFieldsMigration
{
    protected function getMapping(): array
    {
        return [
            'tl_content' => ['iso_list_where', 'iso_newFilter', 'iso_newPeriod'],
        ];
    }
}
