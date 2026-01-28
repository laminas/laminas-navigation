<?php

declare(strict_types=1);

namespace Laminas\Navigation\Service;

use Laminas\Config;
use Laminas\Http\Request;
use Laminas\Mvc\Application;
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

use function assert;
use function file_exists;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Abstract navigation factory
 */
abstract class AbstractNavigationFactory implements FactoryInterface
{
    /** @var array<array-key, array<string, mixed>>|null */
    protected $pages;

    /**
     * Create and return a new Navigation instance (v3).
     *
     * @param string     $requestedName
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
     * @throws InvalidArgumentException
     * @return array<array-key, array<string, mixed>>
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

            $configuration = $configuration['navigation'];
            $navigation    = $configuration[$this->getName()];
            $pages         = $this->getPagesFromConfig($navigation);
            $this->pages   = $this->preparePages($container, $pages);
        }

        return $this->pages;
    }

    /**
     * @param array<array-key, array<string, mixed>> $pages
     * @throws InvalidArgumentException
     * @return array<array-key, array<string, mixed>>
     */
    protected function preparePages(ContainerInterface $container, $pages)
    {
        $application = $container->get('Application');
        assert($application instanceof Application);

        $routeMatch = $application->getMvcEvent()->getRouteMatch();
        $router     = $application->getMvcEvent()->getRouter();
        $request    = $application->getMvcEvent()->getRequest();

        // HTTP request is the only one that may be injected
        if (! $request instanceof Request) {
            $request = null;
        }

        return $this->injectComponents($pages, $routeMatch, $router, $request);
    }

    /**
     * @param string|Config\Config|array<array-key, array<string, mixed>>|null $config
     * @throws InvalidArgumentException
     * @return array<array-key, array<string, mixed>>
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
        }

        if ($config instanceof Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        } elseif (! is_array($config)) {
            throw new Exception\InvalidArgumentException(
                'Invalid input, expected array, filename, or Traversable object'
            );
        }

        return $config;
    }

    /**
     * @param array<array-key, array<string, mixed>> $pages
     * @param RouteMatch|null                        $routeMatch
     * @param Router|null                            $router
     * @param Request|null                           $request
     * @return array<array-key, array<string, mixed>>
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
     * @psalm-assert RouteMatch|null $routeMatch
     * @throws Exception\InvalidArgumentException
     */
    private function validateRouteMatch(mixed $routeMatch): void
    {
        if (null === $routeMatch) {
            return;
        }

        if (! $routeMatch instanceof RouteMatch) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expected by %s::injectComponents; received %s',
                RouteMatch::class,
                self::class,
                get_debug_type($routeMatch)
            ));
        }
    }

    /**
     * Validate that a router argument provided to injectComponents is valid.
     *
     * @psalm-assert Router|null $router
     * @throws Exception\InvalidArgumentException
     */
    private function validateRouter(mixed $router): void
    {
        if (null === $router) {
            return;
        }

        if (! $router instanceof Router) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expected by %s::injectComponents; received %s',
                RouteMatch::class,
                self::class,
                get_debug_type($router),
            ));
        }
    }
}
