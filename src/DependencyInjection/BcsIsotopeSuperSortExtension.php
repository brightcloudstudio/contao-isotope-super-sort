<?php

declare(strict_types=1);

/*
 * This file is part of the bright-cloud-studio/contao-isotope-super-sort bundle.
 *
 * (c) Bright Cloud Studio
 *
 * @license LGPL-3.0-or-later
 */

namespace Bcs\IsotopeSuperSortBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BcsIsotopeSuperSortExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        (new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')))
            ->load('services.yaml')
        ;
    }
}
