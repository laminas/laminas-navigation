<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use ArrayAccess;
use Laminas\Navigation\Navigation;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function strlen;
use function strpos;
use function strtolower;
use function substr;

/**
 * Navigation abstract service factory
 *
 * Allows configuring several navigation instances. If you have a navigation config key named "special" then you can
 * use $container->get('Laminas\Navigation\Special') to retrieve a navigation instance with this configuration.
 */
final class NavigationAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * Top-level configuration key indicating navigation configuration
     *
     * @var string
     */
    public const CONFIG_KEY = 'navigation';

    /**
     * Service manager factory prefix
     *
     * @var string
     */
    public const SERVICE_PREFIX = 'Laminas\\Navigation\\';

    /**
     * Navigation configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Can we create a navigation by the requested name? (v3)
     *
     * @param string $requestedName Name by which service was requested, must
     *     start with Laminas\Navigation\
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        if (0 !== strpos($requestedName, self::SERVICE_PREFIX)) {
            return false;
        }
        $config = $this->getConfig($container);

        return $this->hasNamedConfig($requestedName, $config);
    }

    /**
     * Can we create a navigation by the requested name? (v2)
     *
     * @param string $name Normalized name by which service was requested;
     *     ignored.
     * @param string $requestedName Name by which service was requested, must
     *     start with Laminas\Navigation\
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * {@inheritDoc}
     *
     * @return Navigation
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config  = $this->getConfig($container);
        $factory = new ConstructedNavigationFactory($this->getNamedConfig($requestedName, $config));
        return $factory($container, $requestedName);
    }

    /**
     * Can we create a navigation by the requested name? (v2)
     *
     * @param string $name Normalized name by which service was requested;
     *     ignored.
     * @param string $requestedName Name by which service was requested, must
     *     start with Laminas\Navigation\
     * @return Navigation
     */
    public function createServiceWithName(ServiceLocatorInterface $container, $name, $requestedName)
    {
        return $this($container, $requestedName);
    }

    /**
     * Get navigation configuration, if any
     *
     * @return array
     */
    protected function getConfig(ContainerInterface $container)
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $container->get('config');
        if (
            ! isset($config[self::CONFIG_KEY])
            || ! is_array($config[self::CONFIG_KEY])
        ) {
            $this->config = [];
            return $this->config;
        }

        $this->config = $config[self::CONFIG_KEY];
        return $this->config;
    }

    /**
     * Extract config name from service name
     *
     * @param string $name
     * @return string
     */
    private function getConfigName($name)
    {
        return substr($name, strlen(self::SERVICE_PREFIX));
    }

    /**
     * Does the configuration have a matching named section?
     *
     * @param string $name
     * @param array|ArrayAccess $config
     * @return bool
     */
    private function hasNamedConfig($name, $config)
    {
        $withoutPrefix = $this->getConfigName($name);

        if (isset($config[$withoutPrefix])) {
            return true;
        }

        if (isset($config[strtolower($withoutPrefix)])) {
            return true;
        }

        return false;
    }

    /**
     * Get the matching named configuration section.
     *
     * @param string $name
     * @param array|ArrayAccess $config
     * @return array
     */
    private function getNamedConfig($name, $config)
    {
        $withoutPrefix = $this->getConfigName($name);

        if (isset($config[$withoutPrefix])) {
            return $config[$withoutPrefix];
        }

        if (isset($config[strtolower($withoutPrefix)])) {
            return $config[strtolower($withoutPrefix)];
        }

        return [];
    }
}
