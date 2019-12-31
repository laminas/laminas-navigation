<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Navigation\Service;

use Laminas\Navigation\Navigation;
use Laminas\ServiceManager\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Navigation abstract service factory
 *
 * Allows configuring several navigation instances. If you have a navigation config key named "special" then you can
 * use $serviceLocator->get('Laminas\Navigation\Special') to retrieve a navigation instance with this configuration.
 */
final class NavigationAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * Top-level configuration key indicating navigation configuration
     *
     * @var string
     */
    const CONFIG_KEY = 'navigation';

    /**
     * Service manager factory prefix
     *
     * @var string
     */
    const SERVICE_PREFIX = 'Laminas\Navigation\\';

    /**
     * Normalized name prefix
     */
    const NAME_PREFIX = 'laminasnavigation';

    /**
     * Navigation configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Can we create a navigation by the requested name?
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Service name (as resolved by ServiceManager)
     * @param string $requestedName Name by which service was requested, must start with Laminas\Navigation\
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (0 !== strpos($name, self::NAME_PREFIX)) {
            return false;
        }
        $config = $this->getConfig($serviceLocator);

        return (!empty($config[$this->getConfigName($name)]));
    }

    /**
     * Create a navigation container
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param string $name Service name (as resolved by ServiceManager)
     * @param string $requestedName Name by which service was requested
     * @return Navigation
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->getConfig($serviceLocator);
        $factory = new ConstructedNavigationFactory($config[$this->getConfigName($name)]);
        return $factory->createService($serviceLocator);
    }

    /**
     * Get navigation configuration, if any
     *
     * @param  ServiceLocatorInterface $services
     * @return array
     */
    protected function getConfig(ServiceLocatorInterface $services)
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (!$services->has('Config')) {
            $this->config = array();
            return $this->config;
        }

        $config = $services->get('Config');
        if (!isset($config[self::CONFIG_KEY])
            || !is_array($config[self::CONFIG_KEY])
        ) {
            $this->config = array();
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
    protected function getConfigName($name)
    {
        return substr($name, strlen(self::NAME_PREFIX));
    }
}
