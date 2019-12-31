<?php

/**
 * @see       https://github.com/laminas/laminas-navigation for the canonical source repository
 * @copyright https://github.com/laminas/laminas-navigation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-navigation/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Navigation;

use Laminas\Config\Config;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Router\RouteMatch;
use Laminas\Mvc\Router\RouteStackInterface;
use Laminas\Navigation;
use Laminas\Navigation\Page\Mvc as MvcPage;
use Laminas\Navigation\Service\ConstructedNavigationFactory;
use Laminas\Navigation\Service\DefaultNavigationFactory;
use Laminas\Navigation\Service\NavigationAbstractServiceFactory;
use Laminas\ServiceManager\ServiceManager;

/**
 * Tests the class Laminas\Navigation\MvcNavigationFactory
 *
 * @group      Laminas_Navigation
 */
class ServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $serviceManager;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
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

        $this->router = $router = $this->prophesize(RouteStackInterface::class);
        $this->request = $request = $this->prophesize(HttpRequest::class);

        $routeMatch = new RouteMatch([
            'controller' => 'post',
            'action'     => 'view',
            'id'         => '1337',
        ]);

        $this->mvcEvent = $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);
        $mvcEvent->getRouter()->willReturn($router->reveal());
        $mvcEvent->getRequest()->willReturn($request->reveal());

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->willReturn($mvcEvent->reveal());

        $serviceManager->setService('Application', $application->reveal());
        $serviceManager->setAllowOverride(true);
    }

    /**
     * @covers \Laminas\Navigation\Service\AbstractNavigationFactory
     */
    public function testDefaultFactoryAcceptsFileString()
    {
        $this->serviceManager->setFactory('Navigation', TestAsset\FileNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');
    }

    /**
     * @covers \Laminas\Navigation\Service\DefaultNavigationFactory
     */
    public function testMvcPagesGetInjectedWithComponents()
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);
        $container = $this->serviceManager->get('Navigation');

        $recursive = function ($that, $pages) use (&$recursive) {
            foreach ($pages as $page) {
                if ($page instanceof MvcPage) {
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteStackInterface', $page->getRouter());
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testConstructedNavigationFactoryInjectRouterAndMatcher()
    {
        $builder = $this->getMockBuilder(ConstructedNavigationFactory::class);
        $builder->setConstructorArgs([__DIR__ . '/_files/navigation_mvc.xml'])
                ->setMethods(['injectComponents']);

        $factory = $builder->getMock();

        $factory->expects($this->once())
                ->method('injectComponents')
                ->with(
                    $this->isType('array'),
                    $this->isInstanceOf('Laminas\Mvc\Router\RouteMatch'),
                    $this->isInstanceOf('Laminas\Mvc\Router\RouteStackInterface')
                );

        $this->serviceManager->setFactory('Navigation', function ($services) use ($factory) {
            return $factory($services, 'Navigation');
        });

        $container = $this->serviceManager->get('Navigation');
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testMvcPagesGetInjectedWithComponentsInConstructedNavigationFactory()
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
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteStackInterface', $page->getRouter());
                    $that->assertInstanceOf('Laminas\Mvc\Router\RouteMatch', $page->getRouteMatch());
                }

                $recursive($that, $page->getPages());
            }
        };
        $recursive($this, $container->getPages());
    }

    /**
     * @covers \Laminas\Navigation\Service\DefaultNavigationFactory
     */
    public function testDefaultFactory()
    {
        $this->serviceManager->setFactory('Navigation', DefaultNavigationFactory::class);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testConstructedFromArray()
    {
        $argument = [
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html'
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html'
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html'
            ]
        ];

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\ConstructedNavigationFactory
     */
    public function testConstructedFromFileString()
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
    public function testConstructedFromConfig()
    {
        $argument = new Config([
            [
                'label' => 'Page 1',
                'uri'   => 'page1.html'
            ],
            [
                'label' => 'Page 2',
                'uri'   => 'page2.html'
            ],
            [
                'label' => 'Page 3',
                'uri'   => 'page3.html'
            ]
        ]);

        $factory = new ConstructedNavigationFactory($argument);
        $this->serviceManager->setFactory('Navigation', $factory);

        $container = $this->serviceManager->get('Navigation');
        $this->assertEquals(3, $container->count());
    }

    /**
     * @covers \Laminas\Navigation\Service\NavigationAbstractServiceFactory
     */
    public function testNavigationAbstractServiceFactory()
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

        $this->assertInstanceOf('Laminas\Navigation\Navigation', $container);
        $this->assertEquals(3, $container->count());
    }
}
