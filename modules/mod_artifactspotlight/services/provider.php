<?php

/**
 * Artifact Spotlight — DI service provider.
 *
 * @copyright   (C) 2026 The Museum of Unbuilt Futures
 * @license     GNU General Public License version 2 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with the DI container.
     *
     * @param   Container  $container  The DI container
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\ArtifactSpotlight'));
        $container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\ArtifactSpotlight\\Site\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
