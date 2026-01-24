<?php

declare(strict_types=1);

namespace Laminas\Navigation\View;

use Laminas\ServiceManager\DelegatorFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * Inject the laminas-view HelperManager with laminas-navigation view helper configuration.
 *
 * This approach is used for backwards compatibility. The HelperConfig class performs
 * work to ensure that the navigation helper and all its sub-helpers are injected
 * with the view helper manager and application container.
 *
 * @final
 */
class ViewHelperManagerDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return HelperPluginManager
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, ?array $options = null)
    {
        $viewHelpers = $callback();
        assert($viewHelpers instanceof HelperPluginManager);

        (new HelperConfig())->configureServiceManager($viewHelpers);
        return $viewHelpers;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @param string $requestedName
     * @param callable $callback
     * @return HelperPluginManager
     */
    public function createDelegatorWithName(ServiceLocatorInterface $container, $name, $requestedName, $callback)
    {
        return $this($container, $requestedName, $callback);
    }
}
