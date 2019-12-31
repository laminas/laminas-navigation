<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Navigation\Service;

use Laminas\Config;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Mvc\Router\RouteStackInterface as Router;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Navigation;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract navigation factory
 */
abstract class AbstractNavigationFactory implements FactoryInterface
{
    /**
     * @var array
     */
    protected $pages;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return \Laminas\Navigation\Navigation
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $pages = $this->getPages($serviceLocator);
        return new Navigation($pages);
    }

    /**
     * @abstract
     * @return string
     */
    abstract protected function getName();

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @return array
     * @throws \Laminas\Navigation\Exception\InvalidArgumentException
     */
    protected function getPages(ServiceLocatorInterface $serviceLocator)
    {
        if (null === $this->pages) {
            $configuration = $serviceLocator->get('Config');

            if (!isset($configuration['navigation'])) {
                throw new Exception\InvalidArgumentException('Could not find navigation configuration key');
            }
            if (!isset($configuration['navigation'][$this->getName()])) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Failed to find a navigation container by the name "%s"',
                    $this->getName()
                ));
            }

            $application = $serviceLocator->get('Application');
            $routeMatch  = $application->getMvcEvent()->getRouteMatch();
            $router      = $application->getMvcEvent()->getRouter();
            $pages       = $this->getPagesFromConfig($configuration['navigation'][$this->getName()]);

            $this->pages = $this->injectComponents($pages, $routeMatch, $router);
        }
        return $this->pages;
    }

    /**
     * @param string|\Laminas\Config\Config|array $config
     * @return array|null|\Laminas\Config\Config
     * @throws \Laminas\Navigation\Exception\InvalidArgumentException
     */
    protected function getPagesFromConfig($config = null)
    {
        if (is_string($config)) {
            if (file_exists($config)) {
                $config = Config\Factory::fromFile($config);
            } else {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Config was a string but file "%s" does not exist',
                    $config
                ));
            }
        } elseif ($config instanceof Config\Config) {
            $config = $config->toArray();
        } elseif (!is_array($config)) {
            throw new Exception\InvalidArgumentException('
                Invalid input, expected array, filename, or Laminas\Config object'
            );
        }

        return $config;
    }

    /**
     * @param array $pages
     * @param RouteMatch $routeMatch
     * @param Router $router
     * @return mixed
     */
    protected function injectComponents(array $pages, RouteMatch $routeMatch = null, Router $router = null)
    {
        foreach ($pages as &$page) {
            $hasMvc = isset($page['action']) || isset($page['controller']) || isset($page['route']);
            if ($hasMvc) {
                if (!isset($page['routeMatch']) && $routeMatch) {
                    $page['routeMatch'] = $routeMatch;
                }
                if (!isset($page['router'])) {
                    $page['router'] = $router;
                }
            }

            if (isset($page['pages'])) {
                $page['pages'] = $this->injectComponents($page['pages'], $routeMatch, $router);
            }
        }
        return $pages;
    }
}
