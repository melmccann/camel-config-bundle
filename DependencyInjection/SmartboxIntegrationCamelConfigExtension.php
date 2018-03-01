<?php

namespace Smartbox\Integration\CamelConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SmartboxIntegrationCamelConfigExtension extends Extension
{
    protected $config;

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getFlowsDirectories()
    {
        return $this->config['flows_directories'];
    }

    public function getFrozenFlowsDirectory()
    {
        return $this->config['frozen_flows_directory'];
    }

    public function resolveFlowsDirectories(ContainerBuilder $container)
    {
        $this->config['flows_directories'] = $this->resolveLocations($this->config['flows_directories'], $container);
        return $this->getFlowsDirectories();
    }

    public function resolveFrozenFlowsDirectory(ContainerBuilder $container)
    {
        $this->config['frozen_flows_directory'] = $this->resolveLocation($this->config['frozen_flows_directory'], $container);
        return $this->getFrozenFlowsDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('smartesb.flows_directories', $this->resolveFlowsDirectories($container));
        $container->setParameter('smartesb.frozen_flows_directory', $this->resolveFrozenFlowsDirectory($container));
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    private function resolveLocations(array $directories, ContainerBuilder $container){
        $resolvedDirectories = array();
        foreach ($directories as $directory) {
            $dir = $this->resolveLocation($directory, $container);
            $resolvedDirectories[] = $dir;
        }
        return $resolvedDirectories;
    }

    private function resolveLocation(string $directory, ContainerBuilder $container){
        $bundles = $container->getParameter('kernel.bundles');
        $dir = rtrim(str_replace('\\', '/', $directory), '/');
            if ('@' === $dir[0]) {
                $bundleName = substr($directory, 1, strpos($dir, '/') - 1);

                if (!isset($bundles[$bundleName])) {
                    throw new RuntimeException(sprintf('The bundle "%s" has not been registered with AppKernel. Available bundles: %s', $bundleName, implode(', ', array_keys($bundles))));
                }

                $ref = new \ReflectionClass($bundles[$bundleName]);
                $dir = dirname($ref->getFileName()).substr($dir, strlen('@'.$bundleName));
            }
            return rtrim($dir, '\\/');
    }
}
