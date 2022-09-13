<?php

declare(strict_types=1);

namespace Laminas\Navigation\View;

use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

/**
 * Inject the laminas-view HelperManager with laminas-navigation view helper configuration.
 *
 * This approach is used for backwards compatibility. The HelperConfig class performs
 * work to ensure that the navigation helper and all its sub-helpers are injected
 * with the view helper manager and application container.
 */
class ViewHelperManagerDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return HelperPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        $viewHelpers = $callback();
        (new HelperConfig())->configureServiceManager($viewHelpers);
        return $viewHelpers;
    }

    /**
     * {@inheritDoc}
     *
     * @return HelperPluginManager
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
