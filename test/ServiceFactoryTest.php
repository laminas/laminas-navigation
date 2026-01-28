<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Config\Config;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Page\AbstractPage;
use Laminas\Navigation\Page\Mvc as MvcPage;
use Laminas\Navigation\Service\AbstractNavigationFactory;
use Laminas\Navigation\Service\ConstructedNavigationFactory;
use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\Service\NavigationAbstractServiceFactory;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionMethod;

#[CoversClass(AbstractNavigationFactory::class)]
#[CoversClass(DefaultNavigationFactory::class)]
#[CoversClass(ConstructedNavigationFactory::class)]
#[CoversClass(NavigationAbstractServiceFactory::class)]
final class ServiceFactoryTest extends TestCase
{
    private ServiceManager $serviceManager;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        $config = [
            'navigation' => [
                'file'    => __DIR__ . '/_files/navigation.xml',
                'default' => [
                    [
                        'label' => 'Page 1',
                        'uri'   => 'page1.html',
                    ],
                    [
                        'label' => 'MVC Page',
                        'route' => 'foo',
                        'pages' => [
                            [
                                'label' => 'Sub MVC Page',
                                'route' => 'foo',
                            ],
                        ],
                    ],
                    [
                        'label' => 'Page 3',
                        'uri'   => 'page3.html',
                    ],
                ],
            ],
        ];

        $this->serviceManager = $serviceManager = new ServiceManager();
        $serviceManager->setService('config', $config);

        $router  = $this->createMock(RouteStackInterface::class);
        $request = $this->createMock(HttpRequest::class);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $mvcEvent = $this->createMock(MvcEvent::class);
        $mvcEvent->expects(self::any())->method('getRouteMatch')->willReturn($routeMatch);
        $mvcEvent->expects(self::any())->method('getRouter')->willReturn($router);
        $mvcEvent->expects(self::any())->method('getRequest')->willReturn($request);

        $application = $this->createMock(Application::class);
        $application->expects(self::any())->method('getMvcEvent')->willReturn($mvcEvent);

        $serviceManager->setService('Application', $application);
        $serviceManager->setAllowOverride(true);
    }

    public function testDefaultFactoryAcceptsFileString(): void
    {
        $this->serviceManager->setFactory('Navigation', TestAsset\FileNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');

        $this->assertInstanceOf(Navigation::class, $container);
    }

    public function testMvcPagesGetInjectedWithComponents(): void
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);

        $recursive = function (self $that, iterable $pages) use (&$recursive): void {
            foreach ($pages as $page) {
                self::assertInstanceOf(AbstractPage::class, $page);
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf(RouteStackInterface::class, $page->getRouter());
                    $that->assertInstanceOf(RouteMatch::class, $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    public function testConstructedNavigationFactoryInjectRouterAndMatcher(): void
    {
        $builder = $this->getMockBuilder(ConstructedNavigationFactory::class);
        $builder->setConstructorArgs([__DIR__ . '/_files/navigation_mvc.xml'])
                ->onlyMethods(['injectComponents']);

        $factory = $builder->getMock();

        $factory->expects($this->once())
                ->method('injectComponents')
                ->with(
                    new IsType('array'),
                    $this->isInstanceOf(RouteMatch::class),
                    $this->isInstanceOf(RouteStackInterface::class)
                );

        $this->serviceManager->setFactory(
            'Navigation',
            static function (ContainerInterface $services) use ($factory): Navigation {
                $navigation = $factory($services, 'Navigation');
                self::assertInstanceOf(Navigation::class, $navigation);

                return $navigation;
            }
        );

        $this->serviceManager->get('Navigation');
    }

    public function testMvcPagesGetInjectedWithComponentsInConstructedNavigationFactory(): void
    {
        $this->serviceManager->setFactory('Navigation', function (ContainerInterface $services) {
            $argument = __DIR__ . '/_files/navigation_mvc.xml';
            $factory  = new ConstructedNavigationFactory($argument);
            return $factory($services, 'Navigation');
        });

        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);

        $recursive = function (self $that, iterable $pages) use (&$recursive): void {
            foreach ($pages as $page) {
                self::assertInstanceOf(AbstractPage::class, $page);
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf(RouteStackInterface::class, $page->getRouter());
                    $that->assertInstanceOf(RouteMatch::class, $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    public function testDefaultFactory(): void
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);

        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testConstructedFromArray(): void
    {
        $argument = [
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html',
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html',
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html',
            ],
        ];

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testConstructedFromFileString(): void
    {
        $argument = __DIR__ . '/_files/navigation.xml';
        $factory  = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testConstructedFromConfig(): void
    {
        $argument = new Config([
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html',
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html',
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html',
            ],
        ]);

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        self::assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testNavigationAbstractServiceFactory(): void
    {
        $factory = new NavigationAbstractServiceFactory();

        $this->assertTrue(
            $factory->canCreate($this->serviceManager, 'Laminas\Navigation\File')
        );
        $this->assertFalse(
            $factory->canCreate($this->serviceManager, 'Laminas\Navigation\Unknown')
        );

        $container = $factory(
            $this->serviceManager,
            'Laminas\Navigation\File'
        );

        $this->assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testNavigationAbstractServiceFactoryV2CanCreateServiceWithName(): void
    {
        $factory = new NavigationAbstractServiceFactory();

        $this->assertTrue(
            $factory->canCreateServiceWithName($this->serviceManager, 'name', 'Laminas\Navigation\File')
        );
        $this->assertFalse(
            $factory->canCreateServiceWithName($this->serviceManager, 'name', 'Laminas\Navigation\Unknown')
        );
    }

    public function testNavigationAbstractServiceFactoryV2CreateServiceWithName(): void
    {
        $factory = new NavigationAbstractServiceFactory();

        $container = $factory->createServiceWithName(
            $this->serviceManager,
            'name',
            'Laminas\Navigation\File'
        );

        $this->assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(3, $container->count());
    }

    public function testNavigationAbstractServiceFactoryReturnsFalseWhenNoConfigService(): void
    {
        $services = new ServiceManager();
        $factory  = new NavigationAbstractServiceFactory();

        $this->assertFalse($factory->canCreate($services, 'Laminas\Navigation\Test'));
    }

    public function testNavigationAbstractServiceFactoryReturnsFalseWhenNoNavigationConfig(): void
    {
        $services = new ServiceManager([
            'services' => [
                'config' => [],
            ],
        ]);
        $factory  = new NavigationAbstractServiceFactory();

        $this->assertFalse($factory->canCreate($services, 'Laminas\Navigation\Test'));
    }

    public function testNavigationAbstractServiceFactorySupportsLowercaseConfigName(): void
    {
        $services = new ServiceManager([
            'services' => [
                'config' => [
                    'navigation' => [
                        'lowercase' => [
                            ['label' => 'Test', 'uri' => '#'],
                        ],
                    ],
                ],
            ],
        ]);
        $factory  = new NavigationAbstractServiceFactory();

        $this->assertTrue($factory->canCreate($services, 'Laminas\Navigation\Lowercase'));
    }

    public function testConstructedNavigationFactoryGetName(): void
    {
        $factory = new ConstructedNavigationFactory([]);

        $this->assertSame('constructed', $factory->getName());
    }

    public function testNavigationAbstractServiceFactoryReturnsFalseForNonNavigationPrefix(): void
    {
        $services = new ServiceManager([
            'services' => [
                'config' => [
                    'navigation' => [
                        'test' => [],
                    ],
                ],
            ],
        ]);
        $factory  = new NavigationAbstractServiceFactory();

        $this->assertFalse($factory->canCreate($services, 'SomeOther\Service'));
    }

    public function testNavigationAbstractServiceFactoryWithExactCaseConfigName(): void
    {
        $services = new ServiceManager([
            'services' => [
                'config' => [
                    'navigation' => [
                        'ExactCase' => [
                            ['label' => 'Test', 'uri' => '#'],
                        ],
                    ],
                ],
            ],
        ]);
        $factory  = new NavigationAbstractServiceFactory();

        $this->assertTrue($factory->canCreate($services, 'Laminas\Navigation\ExactCase'));
    }

    public function testNavigationAbstractServiceFactoryInvokesWithExactCaseConfigName(): void
    {
        $router  = $this->createMock(RouteStackInterface::class);
        $request = $this->createMock(HttpRequest::class);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $mvcEvent = $this->createMock(MvcEvent::class);
        $mvcEvent->expects(self::any())->method('getRouteMatch')->willReturn($routeMatch);
        $mvcEvent->expects(self::any())->method('getRouter')->willReturn($router);
        $mvcEvent->expects(self::any())->method('getRequest')->willReturn($request);

        $application = $this->createMock(Application::class);
        $application->expects(self::any())->method('getMvcEvent')->willReturn($mvcEvent);

        $services = new ServiceManager([
            'services' => [
                'config'      => [
                    'navigation' => [
                        'ExactCase' => [
                            ['label' => 'Test', 'uri' => '#'],
                        ],
                    ],
                ],
                'Application' => $application,
            ],
        ]);
        $factory  = new NavigationAbstractServiceFactory();

        $container = $factory($services, 'Laminas\Navigation\ExactCase');

        $this->assertInstanceOf(Navigation::class, $container);
        $this->assertEquals(1, $container->count());
    }

    public function testGetNamedConfigReturnsEmptyArrayWhenNoMatchingConfig(): void
    {
        $factory = new NavigationAbstractServiceFactory();

        $r = new ReflectionMethod($factory, 'getNamedConfig');

        $config = [
            'other' => [['label' => 'Test', 'uri' => '#']],
        ];

        $result = $r->invoke($factory, 'Laminas\Navigation\NonExistent', $config);

        $this->assertSame([], $result);
    }
}
