<?php

declare(strict_types=1);

namespace LaminasTest\Navigation;

use Laminas\Config\Config;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Page\Mvc as MvcPage;
use Laminas\Navigation\Service\ConstructedNavigationFactory;
use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\Service\NavigationAbstractServiceFactory;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Tests the class Laminas\Navigation\MvcNavigationFactory
 *
 * @group      Laminas_Navigation
 */
class ServiceFactoryTest extends TestCase
{
    /** @var ServiceManager */
    protected $serviceManager;

    private MockObject $router;
    private MockObject $request;
    private MvcEvent $mvcEvent;

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

        $this->router  = $router = $this->createMock(RouteStackInterface::class);
        $this->request = $request = $this->createMock(HttpRequest::class);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $this->mvcEvent = $mvcEvent = $this->createMock(MvcEvent::class);
        $mvcEvent->expects(self::any())->method('getRouteMatch')->willReturn($routeMatch);
        $mvcEvent->expects(self::any())->method('getRouter')->willReturn($router);
        $mvcEvent->expects(self::any())->method('getRequest')->willReturn($request);

        $application = $this->createMock(Application::class);
        $application->expects(self::any())->method('getMvcEvent')->willReturn($mvcEvent);

        $serviceManager->setService('Application', $application);
        $serviceManager->setAllowOverride(true);
    }

    /**
     * @covers \Laminas\Navigation\Service\AbstractNavigationFactory
     */
    public function testDefaultFactoryAcceptsFileString(): void
    {
        $this->serviceManager->setFactory('Navigation', TestAsset\FileNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');

        $this->assertInstanceOf(Navigation::class, $container);
    }

    /**
     * @covers \Laminas\Navigation\Service\DefaultNavigationFactory
     */
    public function testMvcPagesGetInjectedWithComponents(): void
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');

        $recursive = function ($that, $pages) use (&$recursive) {
            foreach ($pages as $page) {
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf(RouteStackInterface::class, $page->getRouter());
                    $that->assertInstanceOf(RouteMatch::class, $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testConstructedNavigationFactoryInjectRouterAndMatcher(): void
    {
        $builder = $this->getMockBuilder(ConstructedNavigationFactory::class);
        $builder->setConstructorArgs([__DIR__ . '/_files/navigation_mvc.xml'])
                ->setMethods(['injectComponents']);

        $factory = $builder->getMock();

        $factory->expects($this->once())
                ->method('injectComponents')
                ->with(
                    $this->isType('array'),
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

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testMvcPagesGetInjectedWithComponentsInConstructedNavigationFactory(): void
    {
        $this->serviceManager->setFactory('Navigation', function ($services) {
            $argument = __DIR__ . '/_files/navigation_mvc.xml';
            $factory  = new ConstructedNavigationFactory($argument);
            return $factory($services, 'Navigation');
        });

        $container = $this->serviceManager->get('Navigation');
        $recursive = function ($that, $pages) use (&$recursive) {
            foreach ($pages as $page) {
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf(RouteStackInterface::class, $page->getRouter());
                    $that->assertInstanceOf(RouteMatch::class, $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    /**
     * @covers \Laminas\Navigation\Service\DefaultNavigationFactory
     */
    public function testDefaultFactory(): void
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
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
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testConstructedFromFileString(): void
    {
        $argument = __DIR__ . '/_files/navigation.xml';
        $factory  = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
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
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\NavigationAbstractServiceFactory
     */
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
}
