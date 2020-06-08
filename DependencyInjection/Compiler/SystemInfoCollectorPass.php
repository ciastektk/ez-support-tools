<?php

/**
 * File containing the SystemInfoCollectorPass class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzSupportToolsBundle\DependencyInjection\Compiler;

use EzSystems\EzSupportToolsBundle\SystemInfo\Collector\EzSystemInfoCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SystemInfoCollectorPass implements CompilerPassInterface
{
    /**
     * Registers the SystemInfoCollector into the system info collector registry.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->processRegistery($container);
        $this->processSystemInfo($container);
    }

    private function processRegistery(ContainerBuilder $container)
    {
        if (!$container->has('support_tools.system_info.collector_registry')) {
            return;
        }

        $infoCollectorsTagged = $container->findTaggedServiceIds('support_tools.system_info.collector');

        $infoCollectors = [];
        foreach ($infoCollectorsTagged as $id => $tags) {
            foreach ($tags as $attributes) {
                $infoCollectors[$attributes['identifier']] = new Reference($id);
            }
        }

        $infoCollectorRegistryDef = $container->findDefinition('support_tools.system_info.collector_registry');
        $infoCollectorRegistryDef->setArguments([$infoCollectors]);
    }

    private function processSystemInfo(ContainerBuilder $container)
    {
        if (!$container->hasParameter('ez_support_tools.powered_by_options.enabled') ||
            !$container->getParameter('ez_support_tools.powered_by_options.enabled')
        ) {
            return;
        }

        if ($name = $container->getParameter('ez_support_tools.powered_by_options.custom_name')) {
            $container->setParameter('ez_support_tools.promote_platform.name', $name);
            return;
        }

        $vendor = $container->getParameter('kernel.root_dir') . '/../vendor/';
        if (is_dir($vendor . EzSystemInfoCollector::COMMERCE_PACKAGES[0])) {
            $name = 'eZ Commerce';
        } elseif (is_dir($vendor . EzSystemInfoCollector::ENTERPISE_PACKAGES[0])) {
            $name = 'eZ Platform Enterprise';
        } else {
            $name = 'eZ Platform';
        }

        $releaseInfo = $container->getParameter('ez_support_tools.powered_by_options.release');
        // Unlike in 3.x there is no constant for version in 2.5, so while this looks hard coded it reflects composer
        // requirements for this package version
        If ($releaseInfo === 'major') {
            $name .= ' 2';
        } else if ($releaseInfo === 'minor') {
            $name .= ' 2.5';
        }

        $container->setParameter('ez_support_tools.powered_by.name', $name);
    }
}
