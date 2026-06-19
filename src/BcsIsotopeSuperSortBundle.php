<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

namespace Bcs\IsotopeSuperSortBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class BcsIsotopeSuperSortBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
