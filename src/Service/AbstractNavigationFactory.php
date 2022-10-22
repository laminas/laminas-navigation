<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use Laminas\Config;
use Laminas\Http\Request;
use Laminas\Navigation\Exception;
use Laminas\Navigation\Exception\InvalidArgumentException;
use Laminas\Navigation\Navigation;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface as Router;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Traversable;

use function file_exists;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Abstract navigation factory
 */
abstract class AbstractNavigationFactory implements FactoryInterface
{
    /** @var array */
    protected $pages;

    /**
     * Create and return a new Navigation instance (v3).
     *
     * @param string $requestedName
     * @param null|array $options
     * @return Navigation
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new Navigation($this->getPages($container));
    }

    /**
     * Create and return a new Navigation instance (v2).
     *
     * @return Navigation
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, Navigation::class);
    }

    /**
     * @abstract
     * @return string
     */
    abstract protected function getName();

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getPages(ContainerInterface $container)
    {
        if (null === $this->pages) {
            $configuration = $container->get('config');

            if (! isset($configuration['navigation'])) {
                throw new Exception\InvalidArgumentException('Could not find navigation configuration key');
            }
            if (! isset($configuration['navigation'][$this->getName()])) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Failed to find a navigation container by the name "%s"',
                    $this->getName()
                ));
            }

            $pages       = $this->getPagesFromConfig($configuration['navigation'][$this->getName()]);
            $this->pages = $this->preparePages($container, $pages);
        }
        return $this->pages;
    }

    /**
     * @param array|\Laminas\Config\Config $pages
     * @return array
     * @throws InvalidArgumentException
     */
    protected function preparePages(ContainerInterface $container, $pages)
    {
        $application = $container->get('Application');
        $routeMatch  = $application->getMvcEvent()->getRouteMatch();
        $router      = $application->getMvcEvent()->getRouter();
        $request     = $application->getMvcEvent()->getRequest();

        // HTTP request is the only one that may be injected
        if (! $request instanceof Request) {
            $request = null;
        }

        return $this->injectComponents($pages, $routeMatch, $router, $request);
    }

    /**
     * @param string|\Laminas\Config\Config|array $config
     * @return array|null|\Laminas\Config\Config
     * @throws InvalidArgumentException
     */
    protected function getPagesFromConfig($config = null)
    {
        if (is_string($config)) {
            if (! file_exists($config)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Config was a string but file "%s" does not exist',
                    $config
                ));
            }
            $config = Config\Factory::fromFile($config);
        } elseif ($config instanceof Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        } elseif (! is_array($config)) {
            throw new Exception\InvalidArgumentException(
                'Invalid input, expected array, filename, or Traversable object'
            );
        }

        return $config;
    }

    /**
     * @param array $pages
     * @param RouteMatch $routeMatch
     * @param Router $router
     * @param null|Request $request
     * @return array
     */
    protected function injectComponents(
        array $pages,
        $routeMatch = null,
        $router = null,
        $request = null
    ) {
        $this->validateRouteMatch($routeMatch);
        $this->validateRouter($router);

        foreach ($pages as &$page) {
            $hasUri = isset($page['uri']);
            $hasMvc = isset($page['action']) || isset($page['controller']) || isset($page['route']);
            if ($hasMvc) {
                if (! isset($page['routeMatch']) && $routeMatch) {
                    $page['routeMatch'] = $routeMatch;
                }
                if (! isset($page['router'])) {
                    $page['router'] = $router;
                }
            } elseif ($hasUri) {
                if (! isset($page['request'])) {
                    $page['request'] = $request;
                }
            }

            if (isset($page['pages'])) {
                $page['pages'] = $this->injectComponents($page['pages'], $routeMatch, $router, $request);
            }
        }
        return $pages;
    }

    /**
     * Validate that a route match argument provided to injectComponents is valid.
     *
     * @param null|RouteMatch $routeMatch
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function validateRouteMatch($routeMatch)
    {
        if (null === $routeMatch) {
            return;
        }

        if (! $routeMatch instanceof RouteMatch) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expected by %s::injectComponents; received %s',
                RouteMatch::class,
                self::class,
                is_object($routeMatch) ? $routeMatch::class : gettype($routeMatch)
            ));
        }
    }

    /**
     * Validate that a router argument provided to injectComponents is valid.
     *
     * @param null|Router $router
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    private function validateRouter($router)
    {
        if (null === $router) {
            return;
        }

        if (! $router instanceof Router) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expected by %s::injectComponents; received %s',
                RouteMatch::class,
                self::class,
                is_object($router) ? $router::class : gettype($router)
            ));
        }
    }
}
