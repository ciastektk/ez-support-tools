<?php

/**
 * File containing the EzSystemsEzSupportToolsExtension class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzSupportToolsBundle\DependencyInjection;

use EzSystems\EzSupportToolsBundle\SystemInfo\Collector\EzSystemInfoCollector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EzSystemsEzSupportToolsExtension extends Extension
{
    public function getAlias()
    {
        return 'ezplatform_support_tools';
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('default_settings.yml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (!isset($config['system_info']) || !$config['system_info']['powered_by']['enabled']) {
            return;
        }

        // Unless there is a custom name, we autodetect based on installed packages
        $vendor = $container->getParameter('kernel.root_dir') . '/../vendor/';
        if ($config['system_info']['powered_by']['custom_name'] !== null) {
            $name = $config['system_info']['powered_by']['custom_name'];
        } else if (is_dir($vendor . EzSystemInfoCollector::COMMERCE_PACKAGES[0])) {
            $name = 'eZ Commerce';
        } elseif (is_dir($vendor . EzSystemInfoCollector::ENTERPISE_PACKAGES[0])) {
            $name = 'eZ Platform Enterprise';
        } else {
            $name = 'eZ Platform';
        }

        // Unlike in 3.x there is no constant for version in 2.5, so while this looks hard coded it reflects composer
        // requirements for this package version
        If ($config['system_info']['powered_by']['release'] === 'major') {
            $name .= ' 2';
        } else if ($config['system_info']['powered_by']['release'] === 'minor') {
            $name .= ' 2.5';
        }

        $container->setParameter('ezplatform_support_tools.system_info.powered_by.name', trim($name));
    }
}
