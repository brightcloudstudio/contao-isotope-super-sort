<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

namespace Bcs\IsotopeSuperSortBundle\ContaoManager;

use Bcs\IsotopeSuperSortBundle\BcsIsotopeSuperSortBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(BcsIsotopeSuperSortBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, 'isotope']),
        ];
    }
}
